<?php

declare(strict_types=1);

namespace App\Service\WishSearch\Provider;

use App\Service\WishSearch\WishSearchRequest;
final class MockSearchProvider implements AiSearchProviderInterface
{
    public function name(): string
    {
        return 'mock';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function search(WishSearchRequest $request): array
    {
        $hint = $this->primaryHint($request->criteria);
        $items = [];

        for ($i = 1; $i <= $request->limit; ++$i) {
            $row = [];
            foreach ($request->fieldNames() as $field) {
                $row[$field] = $this->sampleValue($field, $request->criteria, $i);
            }

            $row['title'] = sprintf('%s — sample result #%d', $hint, $i);
            $row['url'] = sprintf('https://example.com/%s/%d', $request->topicKey, $i);
            $row['source'] = 'mock-provider';
            $row['description'] = sprintf(
                'Demo result generated locally (set AI_PROVIDER to anthropic or bedrock for live results). Match for: %s.',
                $hint,
            );

            $items[] = $row;
        }

        return $items;
    }

    /** @param array<string, mixed> $criteria */
    private function primaryHint(array $criteria): string
    {
        foreach (['role', 'topic', 'location', 'category', 'propertyType'] as $key) {
            $value = trim((string) ($criteria[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return 'Your search';
    }

    /** @param array<string, mixed> $criteria */
    private function sampleValue(string $field, array $criteria, int $index): string
    {
        if (isset($criteria[$field]) && is_scalar($criteria[$field]) && trim((string) $criteria[$field]) !== '') {
            return trim((string) $criteria[$field]);
        }

        return match ($field) {
            'price', 'salaryMin', 'priceMin', 'priceMax', 'salary' => (string) (1000 * $index),
            'currency' => 'EUR',
            'area', 'areaMin' => (string) (40 + 5 * $index),
            'rooms' => (string) (1 + ($index % 4)),
            'publishedAt' => date('Y-m-d'),
            default => '',
        };
    }
}
