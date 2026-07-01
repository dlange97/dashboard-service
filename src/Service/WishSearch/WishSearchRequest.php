<?php

declare(strict_types=1);

namespace App\Service\WishSearch;
final class WishSearchRequest
{
    /**
     * @param array<string, mixed>       $criteria
     * @param list<array<string, mixed>> $fields
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

    /** @return list<string> */
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
