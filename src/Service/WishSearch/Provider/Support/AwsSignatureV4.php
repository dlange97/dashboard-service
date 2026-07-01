<?php

declare(strict_types=1);

namespace App\Service\WishSearch\Provider\Support;

/**
 * Minimal AWS Signature Version 4 signer for JSON POST requests
 * (enough to call the Bedrock runtime InvokeModel endpoint).
 */
final class AwsSignatureV4
{
    private const ALGORITHM = 'AWS4-HMAC-SHA256';

    /**
     * Build the signed HTTP headers for a request.
     *
     * @return list<string> headers in "Name: value" form, ready for CURLOPT_HTTPHEADER
     */
    public function signedHeaders(
        string $region,
        string $service,
        string $accessKey,
        string $secretKey,
        string $host,
        string $canonicalUri,
        string $payload,
        ?string $sessionToken = null,
    ): array {
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        $payloadHash = hash('sha256', $payload);

        $headers = [
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'host' => $host,
            'x-amz-date' => $amzDate,
        ];
        if ($sessionToken !== null && $sessionToken !== '') {
            $headers['x-amz-security-token'] = $sessionToken;
        }

        ksort($headers);
        $canonicalHeaders = '';
        foreach ($headers as $name => $value) {
            $canonicalHeaders .= $name . ':' . $value . "\n";
        }
        $signedHeaderNames = implode(';', array_keys($headers));

        $canonicalRequest = implode("\n", [
            'POST',
            $canonicalUri,
            '',
            $canonicalHeaders,
            $signedHeaderNames,
            $payloadHash,
        ]);

        $credentialScope = $dateStamp . '/' . $region . '/' . $service . '/aws4_request';
        $stringToSign = implode("\n", [
            self::ALGORITHM,
            $amzDate,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        $signingKey = $this->signingKey($secretKey, $dateStamp, $region, $service);
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        $authorization = sprintf(
            '%s Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            self::ALGORITHM,
            $accessKey,
            $credentialScope,
            $signedHeaderNames,
            $signature,
        );

        $result = [];
        foreach ($headers as $name => $value) {
            $result[] = $name . ': ' . $value;
        }
        $result[] = 'Authorization: ' . $authorization;

        return $result;
    }

    private function signingKey(string $secretKey, string $dateStamp, string $region, string $service): string
    {
        $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $secretKey, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);

        return hash_hmac('sha256', 'aws4_request', $kService, true);
    }
}
