<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\ShoppingList;
use App\Repository\ShoppingListProductRepository;
use App\Repository\ShoppingListRepository;
use App\Service\ShoppingListService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ShoppingListServiceTest extends TestCase
{
    private ShoppingListRepository&MockObject $listRepository;
    private ShoppingListProductRepository&MockObject $productRepository;
    private EntityManagerInterface&MockObject $em;
    private ValidatorInterface&MockObject $validator;
    private ShoppingListService $service;

    protected function setUp(): void
    {
        $this->listRepository    = $this->createMock(ShoppingListRepository::class);
        $this->productRepository = $this->createMock(ShoppingListProductRepository::class);
        $this->em                = $this->createMock(EntityManagerInterface::class);
        $this->validator         = $this->createMock(ValidatorInterface::class);

        $this->service = new ShoppingListService(
            $this->listRepository,
            $this->productRepository,
            $this->em,
            $this->validator,
        );
    }

    public function testFindAllByOwner(): void
    {
        $ownerId = 'owner-xyz';
        $list    = $this->makeList('My list', $ownerId);

        $this->listRepository
            ->expects($this->once())
            ->method('findAllByOwner')
            ->with($ownerId)
            ->willReturn([$list]);

        $result = $this->service->findAllByOwner($ownerId);

        $this->assertCount(1, $result);
        $this->assertSame($list, $result[0]);
    }

    public function testCreateShoppingList(): void
    {
        $ownerId = 'owner-1';
        $this->validator->method('validate')->willReturn(new ConstraintViolationList());
        $this->listRepository->expects($this->once())->method('save');

        $list = $this->service->create(['name' => 'Weekly shop', 'products' => []], $ownerId);

        $this->assertSame('Weekly shop', $list->getName());
        $this->assertSame($ownerId, $list->getOwnerId());
    }

    public function testCreateWithProducts(): void
    {
        $ownerId = 'owner-2';
        $this->validator->method('validate')->willReturn(new ConstraintViolationList());
        $this->listRepository->method('save');

        $list = $this->service->create([
            'name'     => 'Groceries',
            'products' => [
                ['name' => 'Milk', 'qty' => 2],
                ['name' => 'Eggs', 'qty' => 12],
            ],
        ], $ownerId);

        $this->assertCount(2, $list->getProducts());
    }

    public function testCreateThrowsOnValidationFailure(): void
    {
        $violations = $this->createMock(ConstraintViolationList::class);
        $violations->method('count')->willReturn(1);

        $this->validator->method('validate')->willReturn($violations);
        $this->listRepository->expects($this->never())->method('save');

        $this->expectException(ValidationFailedException::class);

        $this->service->create(['name' => ''], 'owner-1');
    }

    public function testDeleteCallsRepository(): void
    {
        $list = $this->makeList('To delete', 'owner-1');

        $this->listRepository->expects($this->once())->method('remove')->with($list, true);

        $this->service->delete($list);
    }

    public function testSerializeListReturnsExpectedStructure(): void
    {
        $list = $this->makeList('Test List', 'owner-5');

        $data = $this->service->serializeList($list);

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('products', $data);
        $this->assertArrayHasKey('createdAt', $data);
        $this->assertArrayHasKey('updatedAt', $data);
        $this->assertSame('Test List', $data['name']);
        $this->assertIsArray($data['products']);
    }

    private function makeList(string $name, string $ownerId): ShoppingList
    {
        $list = new ShoppingList();
        $list->setName($name);
        $list->setOwnerId($ownerId);

        return $list;
    }
}
