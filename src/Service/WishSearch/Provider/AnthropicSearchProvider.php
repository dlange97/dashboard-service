<?php

declare(strict_types=1);

namespace App\Service\WishSearch\Provider;

use App\Service\WishSearch\Provider\Support\JsonResultExtractor;
use App\Service\WishSearch\WishSearchRequest;
use Psr\Log\LoggerInterface;

/**
 * Anthropic Claude provider (AI_PROVIDER=anthropic).
 *
 * Uses the Messages API with the built-in web_search tool so the model can
 * actually browse the web before returning a strict JSON result list.
 */
final class AnthropicSearchProvider implements AiSearchProviderInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $apiUrl,
        private readonly string $model,
        private readonly JsonResultExtractor $extractor,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function name(): string
    {
        return 'anthropic';
    }

    public function isConfigured(): bool
    {
        return trim($this->apiKey) !== '';
    }

    public function search(WishSearchRequest $request): array
    {
        $model = $request->model !== '' ? $request->model : $this->model;
        $url = rtrim(trim($this->apiUrl), '/');
        if ($url === '') {
            $url = 'https://api.anthropic.com/v1/messages';
        }

        $payload = [
            'model' => $model,
            'max_tokens' => 2048,
            'system' => 'You are a precise search assistant. Use web search to find real, '
                . 'currently available results. Respond ONLY with a JSON object of the form '
                . '{"items": [ ... ]} and nothing else.',
            'messages' => [[
                'role' => 'user',
                'content' => $request->instruction
                    . "\n\nReturn at most " . $request->limit . ' items as JSON {"items":[...]} using exactly these keys per item: '
                    . implode(', ', $request->fieldNames()) . '.',
            ]],
            'tools' => [[
                'type' => 'web_search_20250305',
                'name' => 'web_search',
                'max_uses' => 5,
            ]],
        ];

        $raw = $this->post($url, $payload);
        if ($raw === null) {
            return [];
        }

        return $this->extractor->extractItems($this->collectText($raw));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|null
     */
    private function post(string $url, array $payload): ?array
    {
        try {
            $body = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'content-type: application/json',
                'x-api-key: ' . trim($this->apiKey),
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
        ]);

        $response = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($response) || $statusCode < 200 || $statusCode >= 300) {
            $this->logger->warning('Anthropic wish-search request failed', ['status' => $statusCode]);

            return null;
        }

        $decoded = json_decode($response, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Concatenate all text blocks from an Anthropic messages response.
     *
     * @param array<string, mixed> $response
     */
    private function collectText(array $response): string
    {
        $content = $response['content'] ?? null;
        if (!is_array($content)) {
            return '';
        }

        $text = '';
        foreach ($content as $block) {
            if (is_array($block) && ($block['type'] ?? null) === 'text' && is_string($block['text'] ?? null)) {
                $text .= $block['text'];
            }
        }

        return $text;
    }
}
