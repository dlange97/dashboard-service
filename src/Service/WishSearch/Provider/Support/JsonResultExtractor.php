<?php

declare(strict_types=1);

namespace App\Service\WishSearch\Provider\Support;

/**
 * Best-effort extraction of a {"items":[...]} list out of an LLM text answer.
 *
 * Models sometimes wrap JSON in prose or markdown fences; this helper tries a
 * few strategies before giving up.
 */
final class JsonResultExtractor
{
    /**
     * @return list<array<string, mixed>>
     */
    public function extractItems(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        // Strip markdown code fences if present.
        $text = (string) preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text);

        $candidates = [];

        $direct = json_decode($text, true);
        if (is_array($direct)) {
            $candidates[] = $direct;
        }

        // Fallback: grab the first {...} or [...] block.
        if ($candidates === []) {
            $start = strcspn($text, '{[');
            if ($start < strlen($text)) {
                $sub = substr($text, $start);
                $decoded = json_decode($sub, true);
                if (is_array($decoded)) {
                    $candidates[] = $decoded;
                }
            }
        }

        foreach ($candidates as $candidate) {
            $items = $this->pickItems($candidate);
            if ($items !== []) {
                return $items;
            }
        }

        return [];
    }

    /**
     * @param array<int|string, mixed> $decoded
     *
     * @return list<array<string, mixed>>
     */
    private function pickItems(array $decoded): array
    {
        $list = $decoded;
        if (isset($decoded['items']) && is_array($decoded['items'])) {
            $list = $decoded['items'];
        } elseif (isset($decoded['results']) && is_array($decoded['results'])) {
            $list = $decoded['results'];
        }

        $items = [];
        foreach ($list as $entry) {
            if (is_array($entry)) {
                /** @var array<string, mixed> $entry */
                $items[] = $entry;
            }
        }

        return $items;
    }
}
