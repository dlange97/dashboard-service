<?php

declare(strict_types=1);

namespace App\Service\WishSearch\Topic;

final class RealEstateTopic extends AbstractWishTopic
{
    public function key(): string
    {
        return 'real-estate';
    }

    public function label(): string
    {
        return 'Real estate';
    }

    public function icon(): string
    {
        return '🏠';
    }

    public function fields(): array
    {
        return [
            ['name' => 'location', 'label' => 'Location', 'type' => 'text', 'placeholder' => 'e.g. Berlin, Mitte'],
            ['name' => 'propertyType', 'label' => 'Property type', 'type' => 'select', 'options' => ['apartment', 'house', 'plot', 'commercial', 'room']],
            ['name' => 'transaction', 'label' => 'Transaction', 'type' => 'select', 'options' => ['buy', 'rent']],
            ['name' => 'priceMin', 'label' => 'Min price', 'type' => 'number', 'unit' => 'EUR'],
            ['name' => 'priceMax', 'label' => 'Max price', 'type' => 'number', 'unit' => 'EUR'],
            ['name' => 'areaMin', 'label' => 'Min area', 'type' => 'number', 'unit' => 'm²'],
            ['name' => 'rooms', 'label' => 'Rooms', 'type' => 'number'],
        ];
    }

    public function buildInstruction(array $criteria, int $limit): string
    {
        return sprintf(
            'Search the web for up to %d currently available real-estate listings matching these criteria: %s. '
            . 'For every listing return: title, price (number), currency, propertyType, area (in m²), '
            . 'rooms, location, url (direct link to the offer), description (one short sentence) and source (website name).',
            $limit,
            $this->describeCriteria($criteria),
        );
    }

    protected function resultKeys(): array
    {
        return ['title', 'price', 'currency', 'propertyType', 'area', 'rooms', 'location', 'url', 'description', 'source'];
    }
}
