<?php

declare(strict_types=1);

namespace App\Tests\Service\WishSearch;

use App\Service\WishSearch\Provider\MockSearchProvider;
use App\Service\WishSearch\Topic\JobOffersTopic;
use App\Service\WishSearch\Topic\RealEstateTopic;
use App\Service\WishSearch\Topic\WorldNewsTopic;
use App\Service\WishSearch\WishSearchService;
use PHPUnit\Framework\TestCase;

final class WishSearchServiceTest extends TestCase
{
    private function service(): WishSearchService
    {
        return new WishSearchService(
            [new RealEstateTopic(), new JobOffersTopic(), new WorldNewsTopic()],
            [new MockSearchProvider()],
            'mock',
            '',
            5,
            100,
        );
    }

    public function testTopicsExposeThreeStartingTopics(): void
    {
        $topics = $this->service()->topics();

        $keys = array_map(static fn (array $t): mixed => $t['key'], $topics);
        self::assertContains('real-estate', $keys);
        self::assertContains('job-offers', $keys);
        self::assertContains('world-news', $keys);
    }

    public function testConfigFallsBackToMockWhenProviderNotConfigured(): void
    {
        $service = new WishSearchService(
            [new RealEstateTopic()],
            [new MockSearchProvider()],
            'anthropic',
            '',
            5,
            100,
        );

        self::assertSame('mock', $service->config()['provider']);
        self::assertSame('anthropic', $service->config()['configuredProvider']);
    }

    public function testSearchClampsLimitToMax(): void
    {
        $result = $this->service()->search('real-estate', ['location' => 'Berlin'], 999);

        self::assertSame(100, $result['limit']);
        self::assertLessThanOrEqual(100, $result['count']);
    }

    public function testSearchClampsLimitToAtLeastOne(): void
    {
        $result = $this->service()->search('job-offers', ['role' => 'PHP'], 0);

        self::assertSame(1, $result['limit']);
    }

    public function testSearchReturnsNormalizedItemsWithTitle(): void
    {
        $result = $this->service()->search('real-estate', ['location' => 'Berlin'], 3);

        self::assertCount(3, $result['items']);
        foreach ($result['items'] as $item) {
            self::assertArrayHasKey('title', $item);
            self::assertNotSame('', $item['title']);
        }
    }

    public function testUnknownTopicThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service()->search('does-not-exist', [], 5);
    }
}
