<?php

declare(strict_types=1);

namespace App\Service\WishSearch\Topic;

/**
 * A wish-search topic (e.g. real estate, job offers, world news).
 *
 * Each topic is polymorphic: it declares its own dynamic criteria fields,
 * builds its own AI instruction and normalizes provider results into a
 * stable shape the frontend can render.
 */
interface WishTopicInterface
{
    /**
     * Stable machine key, e.g. "real-estate".
     */
    public function key(): string;

    /**
     * Human label, e.g. "Real estate".
     */
    public function label(): string;

    /**
     * Emoji / icon hint for the UI.
     */
    public function icon(): string;

    /**
     * Dynamic criteria fields shown to the user once this topic is selected.
     *
     * @return list<array<string, mixed>> each: name, label, type, optional options/placeholder/unit
     */
    public function fields(): array;

    /**
     * Build the natural-language instruction sent to the AI provider.
     *
     * @param array<string, mixed> $criteria
     */
    public function buildInstruction(array $criteria, int $limit): string;

    /**
     * Normalize raw provider items into a stable, render-ready shape.
     *
     * @param list<array<string, mixed>> $rawItems
     *
     * @return list<array<string, mixed>>
     */
    public function normalizeResults(array $rawItems): array;
}
