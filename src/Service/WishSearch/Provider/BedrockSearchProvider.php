<?php

declare(strict_types=1);

namespace App\Service\WishSearch\Provider;

use App\Service\WishSearch\Provider\Support\AwsSignatureV4;
use App\Service\WishSearch\Provider\Support\JsonResultExtractor;
use App\Service\WishSearch\WishSearchRequest;
use Psr\Log\LoggerInterface;
final class BedrockSearchProvider implements AiSearchProviderInterface
{
    public function __construct(
        private readonly string $region,
        private readonly string $accessKeyId,
        private readonly string $secretAccessKey,
        private readonly string $sessionToken,
        private readonly string $model,
        private readonly JsonResultExtractor $extractor,
        private readonly AwsSignatureV4 $signer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function name(): string
    {
        return 'bedrock';
    }

    public function isConfigured(): bool
    {
        return trim($this->accessKeyId) !== '' && trim($this->secretAccessKey) !== '';
    }

    public function search(WishSearchRequest $request): array
    {
        $model = $request->model !== '' ? $request->model : $this->model;
        $region = trim($this->region) !== '' ? trim($this->region) : 'us-east-1';
        $host = sprintf('bedrock-runtime.%s.amazonaws.com', $region);
        $canonicalUri = '/model/' . rawurlencode($model) . '/invoke';

        $payload = [
            'anthropic_version' => 'bedrock-2023-05-31',
            'max_tokens' => 2048,
            'system' => 'You are a precise search assistant. Respond ONLY with a JSON object '
                . 'of the form {"items": [ ... ]} and nothing else.',
            'messages' => [[
                'role' => 'user',
                'content' => $request->instruction
                    . "\n\nReturn at most " . $request->limit . ' items as JSON {"items":[...]} using exactly these keys per item: '
                    . implode(', ', $request->fieldNames()) . '.',
            ]],
        ];

        try {
            $body = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        $headers = $this->signer->signedHeaders(
            $region,
            'bedrock',
            trim($this->accessKeyId),
            trim($this->secretAccessKey),
            $host,
            $canonicalUri,
            $body,
            trim($this->sessionToken) !== '' ? trim($this->sessionToken) : null,
        );

        $raw = $this->post('https://' . $host . $canonicalUri, $body, $headers);
        if ($raw === null) {
            return [];
        }

        return $this->extractor->extractItems($this->collectText($raw));
    }

    /**
     * @param list<string> $headers
     *
     * @return array<string, mixed>|null
     */
    private function post(string $url, string $body, array $headers): ?array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
        ]);

        $response = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($response) || $statusCode < 200 || $statusCode >= 300) {
            $this->logger->warning('Bedrock wish-search request failed', ['status' => $statusCode]);

            return null;
        }

        $decoded = json_decode($response, true);

        return is_array($decoded) ? $decoded : null;
    }

    /** @param array<string, mixed> $response */
    private function collectText(array $response): string
    {
        $content = $response['content'] ?? null;
        if (!is_array($content)) {
            return '';
        }

        $text = '';
        foreach ($content as $block) {
            if (is_array($block) && is_string($block['text'] ?? null)) {
                $text .= $block['text'];
            }
        }

        return $text;
    }
}
