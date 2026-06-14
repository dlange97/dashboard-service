<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ActorIdResolver;
use PHPUnit\Framework\TestCase;

final class ActorIdResolverTest extends TestCase
{
    public function testResolveReturnsPositiveNumericIdAsIs(): void
    {
        $resolver = new ActorIdResolver();

        $this->assertSame(42, $resolver->resolve('42'));
    }

    public function testResolveHashesNonNumericIdIntoPositiveSignedIntRange(): void
    {
        $resolver = new ActorIdResolver();

        $resolved = $resolver->resolve('owner-abc');

        $this->assertGreaterThan(0, $resolved);
        $this->assertLessThanOrEqual(2147483647, $resolved);
    }

    public function testResolveHashesNumericIdOutsideSignedInt32Range(): void
    {
        $resolver = new ActorIdResolver();

        $resolved = $resolver->resolve('2147483648');

        $this->assertGreaterThan(0, $resolved);
        $this->assertLessThanOrEqual(2147483647, $resolved);
        $this->assertNotSame(2147483648, $resolved);
    }
}
