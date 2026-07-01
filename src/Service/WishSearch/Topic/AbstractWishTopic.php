<?php

declare(strict_types=1);

namespace App\Service\WishSearch\Topic;
abstract class AbstractWishTopic implements WishTopicInterface
{
    /** @return list<string> */
    abstract protected function resultKeys(): array;

    protected function requiredKey(): string
    {
        return 'title';
    }

    public function normalizeResults(array $rawItems): array
    {
        $allowed = $this->resultKeys();
        $required = $this->requiredKey();
        $normalized = [];

        foreach ($rawItems as $raw) {
            if (!is_array($raw)) {
                continue;
            }

            $row = [];
            foreach ($allowed as $key) {
                if (!array_key_exists($key, $raw)) {
                    continue;
                }

                $value = $raw[$key];
                if (is_scalar($value)) {
                    $row[$key] = trim((string) $value);
                }
            }

            if (($row[$required] ?? '') === '') {
                continue;
            }

            $normalized[] = $row;
        }

        return $normalized;
    }

    /** @param array<string, mixed> $criteria */
    protected function describeCriteria(array $criteria): string
    {
        $parts = [];
        foreach ($criteria as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', array_map(static fn ($v): string => (string) $v, $value));
            }

            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            $parts[] = sprintf('%s: %s', $key, $value);
        }

        return $parts === [] ? '(no specific criteria provided)' : implode('; ', $parts);
    }
}
