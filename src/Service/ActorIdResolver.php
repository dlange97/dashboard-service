<?php

declare(strict_types=1);

namespace App\Service;

class ActorIdResolver
{
    private const MAX_SIGNED_INT32 = 2147483647;
    private const HASH_SPACE_SIZE = self::MAX_SIGNED_INT32 - 1;

    public function resolve(string $actorId): int
    {
        if (is_numeric($actorId)) {
            $numericId = (int) $actorId;
            if ($numericId > 0 && $numericId <= self::MAX_SIGNED_INT32) {
                return $numericId;
            }
        }

        $hash = crc32($actorId);
        $unsignedHash = (int) sprintf('%u', $hash);

        return ($unsignedHash % self::HASH_SPACE_SIZE) + 1;
    }
}
