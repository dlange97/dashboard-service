<?php

declare(strict_types=1);

namespace App\Service\Input;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class DateInputParser
{
    public function parseNullableDate(mixed $value, string $field): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $normalized);
        if ($parsed !== false) {
            return $parsed;
        }

        try {
            return new \DateTimeImmutable($normalized);
        } catch (\Exception) {
            throw new UnprocessableEntityHttpException(sprintf('Field "%s" must be a valid date.', $field));
        }
    }
}
