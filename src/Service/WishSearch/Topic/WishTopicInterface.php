<?php

declare(strict_types=1);

namespace App\Service\WishSearch\Topic;
interface WishTopicInterface
{
    public function key(): string;

    public function label(): string;

    public function icon(): string;

    /** @return list<array<string, mixed>> */
    public function fields(): array;

    /** @param array<string, mixed> $criteria */
    public function buildInstruction(array $criteria, int $limit): string;

    /**
     * @param list<array<string, mixed>> $rawItems
     *
     * @return list<array<string, mixed>>
     */
    public function normalizeResults(array $rawItems): array;
}
