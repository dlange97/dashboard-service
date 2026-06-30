<?php

declare(strict_types=1);

namespace App\Entity;

interface ShareableResourceInterface
{
    public function getOwnerId(): ?string;

    /** @return list<string> */
    public function getSharedWithUserIds(): array;

    public function addSharedUserId(string $userId): static;

    public function removeSharedUserId(string $userId): static;
}
