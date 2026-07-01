<?php

declare(strict_types=1);

namespace App\Service\WishSearch;

use App\Service\WishSearch\Provider\AiSearchProviderInterface;
use App\Service\WishSearch\Topic\WishTopicInterface;
final class WishSearchService
{
    /** @var array<string, WishTopicInterface> */
    private array $topics = [];

    /** @var array<string, AiSearchProviderInterface> */
    private array $providers = [];

    /**
     * @param iterable<WishTopicInterface>          $topics
     * @param iterable<AiSearchProviderInterface>   $providers
     */
    public function __construct(
        iterable $topics,
        iterable $providers,
        private readonly string $defaultProvider,
        private readonly string $defaultModel,
        private readonly int $defaultLimit,
        private readonly int $maxLimit,
    ) {
        foreach ($topics as $topic) {
            $this->topics[$topic->key()] = $topic;
        }
        foreach ($providers as $provider) {
            $this->providers[$provider->name()] = $provider;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function topics(): array
    {
        $out = [];
        foreach ($this->topics as $topic) {
            $out[] = [
                'key' => $topic->key(),
                'label' => $topic->label(),
                'icon' => $topic->icon(),
                'fields' => $topic->fields(),
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function config(): array
    {
        $active = $this->resolveProvider();

        return [
            'provider' => $active->name(),
            'configuredProvider' => $this->defaultProvider,
            'model' => $this->defaultModel,
            'defaultLimit' => $this->defaultLimit,
            'maxLimit' => $this->maxLimit,
            'availableProviders' => array_keys($this->providers),
        ];
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException when the topic is unknown
     */
    public function search(string $topicKey, array $criteria, ?int $limit): array
    {
        $topic = $this->topics[$topicKey] ?? null;
        if (!$topic instanceof WishTopicInterface) {
            throw new \InvalidArgumentException(sprintf('Unknown topic "%s".', $topicKey));
        }

        $resolvedLimit = $this->clampLimit($limit);
        $provider = $this->resolveProvider();

        $request = new WishSearchRequest(
            $topic->key(),
            $topic->label(),
            $topic->buildInstruction($criteria, $resolvedLimit),
            $criteria,
            $topic->fields(),
            $resolvedLimit,
            $this->defaultModel,
        );

        $rawItems = $provider->search($request);
        $items = $topic->normalizeResults($rawItems);
        if (count($items) > $resolvedLimit) {
            $items = array_slice($items, 0, $resolvedLimit);
        }

        return [
            'topic' => $topic->key(),
            'provider' => $provider->name(),
            'model' => $this->defaultModel,
            'limit' => $resolvedLimit,
            'count' => count($items),
            'items' => $items,
        ];
    }

    private function clampLimit(?int $limit): int
    {
        $value = $limit ?? $this->defaultLimit;

        return max(1, min($this->maxLimit, $value));
    }

    private function resolveProvider(): AiSearchProviderInterface
    {
        $configured = $this->providers[$this->defaultProvider] ?? null;
        if ($configured instanceof AiSearchProviderInterface && $configured->isConfigured()) {
            return $configured;
        }

        $mock = $this->providers['mock'] ?? null;
        if ($mock instanceof AiSearchProviderInterface) {
            return $mock;
        }

        foreach ($this->providers as $provider) {
            if ($provider->isConfigured()) {
                return $provider;
            }
        }

        throw new \RuntimeException('No wish-search provider is available.');
    }
}
