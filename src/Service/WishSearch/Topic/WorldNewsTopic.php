<?php

declare(strict_types=1);

namespace App\Service\WishSearch\Topic;

final class WorldNewsTopic extends AbstractWishTopic
{
    public function key(): string
    {
        return 'world-news';
    }

    public function label(): string
    {
        return 'World news';
    }

    public function icon(): string
    {
        return '🌍';
    }

    public function fields(): array
    {
        return [
            ['name' => 'topic', 'label' => 'Topic / keywords', 'type' => 'text', 'placeholder' => 'e.g. AI regulation'],
            ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => ['general', 'business', 'technology', 'science', 'sports', 'politics', 'health']],
            ['name' => 'region', 'label' => 'Region', 'type' => 'text', 'placeholder' => 'e.g. Europe'],
            ['name' => 'language', 'label' => 'Language', 'type' => 'select', 'options' => ['en', 'pl', 'de', 'fr', 'es']],
            ['name' => 'period', 'label' => 'Period', 'type' => 'select', 'options' => ['today', 'this-week', 'this-month']],
        ];
    }

    public function buildInstruction(array $criteria, int $limit): string
    {
        return sprintf(
            'Search the web for up to %d recent and relevant news items matching these criteria: %s. '
            . 'For every item return: title, summary (one or two sentences), category, source (publisher name), '
            . 'url (direct link to the article) and publishedAt (ISO 8601 date if known).',
            $limit,
            $this->describeCriteria($criteria),
        );
    }

    protected function resultKeys(): array
    {
        return ['title', 'summary', 'category', 'source', 'url', 'publishedAt'];
    }
}
