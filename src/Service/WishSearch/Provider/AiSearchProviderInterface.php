<?php

declare(strict_types=1);

namespace App\Service\WishSearch\Provider;

use App\Service\WishSearch\WishSearchRequest;

/**
 * Polymorphic AI search backend.
 *
 * Each provider knows how to talk to one model vendor (mock, Anthropic, AWS
 * Bedrock, …). Depending on the configured provider AND model, the concrete
 * implementation can do different things (plain completion, web-search tool,
 * SigV4-signed calls, …).
 */
interface AiSearchProviderInterface
{
    /**
     * Provider name as referenced by the AI_PROVIDER env var (e.g. "anthropic").
     */
    public function name(): string;

    /**
     * Whether this provider is usable with the current configuration
     * (e.g. an API key is present). Used to fall back gracefully.
     */
    public function isConfigured(): bool;

    /**
     * Run the search and return a list of raw result rows (associative arrays).
     *
     * @return list<array<string, mixed>>
     */
    public function search(WishSearchRequest $request): array;
}
