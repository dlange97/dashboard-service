<?php

declare(strict_types=1);

namespace App\Service\WishSearch\Provider;

use App\Service\WishSearch\WishSearchRequest;
interface AiSearchProviderInterface
{
    public function name(): string;

    public function isConfigured(): bool;

    /** @return list<array<string, mixed>> */
    public function search(WishSearchRequest $request): array;
}
