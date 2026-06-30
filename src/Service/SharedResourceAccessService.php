<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ShareableResourceInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class SharedResourceAccessService
{
    public function assertOwner(ShareableResourceInterface $resource, string $ownerId, string $message): void
    {
        if ($resource->getOwnerId() !== $ownerId) {
            throw new AccessDeniedHttpException($message);
        }
    }

    public function assertAccessible(ShareableResourceInterface $resource, string $userId, string $message): void
    {
        if ($resource->getOwnerId() === $userId) {
            return;
        }

        if (in_array($userId, $resource->getSharedWithUserIds(), true)) {
            return;
        }

        throw new AccessDeniedHttpException($message);
    }

    public function normalizeShareTarget(ShareableResourceInterface $resource, string $userId, string $ownerMessage): string
    {
        $normalizedUserId = trim($userId);
        if ($normalizedUserId === '') {
            throw new \InvalidArgumentException('User ID cannot be empty.');
        }

        if ($resource->getOwnerId() === $normalizedUserId) {
            throw new \InvalidArgumentException($ownerMessage);
        }

        return $normalizedUserId;
    }
}
