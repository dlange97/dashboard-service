<?php

declare(strict_types=1);

namespace App\Service\WishSearch;

/**
 * Immutable value object describing a single wish-search execution.
 *
 * It carries everything a provider needs to fulfil the search without
 * knowing anything about the concrete topic — this is what keeps the
 * provider implementations polymorphic and topic-agnostic.
 */
final class WishSearchRequest
{
    /**
     * @param array<string, mixed>                     $criteria the raw user criteria (keyed by field name)
     * @param list<array<string, mixed>>               $fields   the topic field definitions (schema hints)
     */
    public function __construct(
        public readonly string $topicKey,
        public readonly string $topicLabel,
        public readonly string $instruction,
        public readonly array $criteria,
        public readonly array $fields,
        public readonly int $limit,
        public readonly string $model,
    ) {
    }

    /**
     * @return list<string>
     */
    public function fieldNames(): array
    {
        $names = [];
        foreach ($this->fields as $field) {
            $name = $field['name'] ?? null;
            if (is_string($name) && $name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }
}
