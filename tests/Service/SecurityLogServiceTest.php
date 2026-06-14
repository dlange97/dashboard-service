<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Repository\SecurityLogRepository;
use App\Service\SecurityLogService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SecurityLogServiceTest extends TestCase
{
    private SecurityLogRepository&MockObject $repository;
    private SecurityLogService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SecurityLogRepository::class);
        $this->service    = new SecurityLogService($this->repository);
    }

    public function testGetPaginatedListReturnsMappedItems(): void
    {
        $this->repository->method('countAll')->willReturn(5);
        $this->repository->method('findPaginated')->with(50, 0)->willReturn([
            ['id' => '2', 'ip' => '172.16.0.1', 'path' => '/dashboard/todos', 'method' => 'GET', 'instance_id' => 'inst-4', 'is_sensitive' => '0', 'user_agent' => 'curl', 'created_at' => '2026-01-04 00:00:00'],
        ]);

        $result = $this->service->getPaginatedList(1, 50, 'dashboard');

        $this->assertSame('dashboard', $result['service']);
        $this->assertSame(5, $result['total']);
        $this->assertCount(1, $result['items']);
        $this->assertSame(2, $result['items'][0]['id']);
        $this->assertFalse($result['items'][0]['isSensitive']);
    }

    public function testGetPaginatedListClampsPageAndPerPage(): void
    {
        $this->repository->method('countAll')->willReturn(0);
        $this->repository->expects($this->once())->method('findPaginated')->with(100, 0)->willReturn([]);

        $this->service->getPaginatedList(0, 999, 'dashboard');
    }

    public function testGetPaginatedListCalculatesOffsetCorrectly(): void
    {
        $this->repository->method('countAll')->willReturn(200);
        $this->repository->expects($this->once())->method('findPaginated')->with(25, 50)->willReturn([]);

        $this->service->getPaginatedList(3, 25, 'dashboard');
    }

    public function testClearDelegatesAndReturnsCount(): void
    {
        $this->repository->expects($this->once())->method('clearAll')->willReturn(99);

        $this->assertSame(99, $this->service->clear());
    }
}
