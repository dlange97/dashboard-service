<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\TodoItem;
use App\Repository\TodoItemRepository;
use App\Service\TodoService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TodoServiceTest extends TestCase
{
    private TodoItemRepository&MockObject $repository;
    private EntityManagerInterface&MockObject $em;
    private ValidatorInterface&MockObject $validator;
    private TodoService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(TodoItemRepository::class);
        $this->em         = $this->createMock(EntityManagerInterface::class);
        $this->validator  = $this->createMock(ValidatorInterface::class);

        $this->service = new TodoService($this->repository, $this->em, $this->validator);
    }

    public function testFindAllByOwner(): void
    {
        $ownerId = 'owner-123';
        $item    = $this->makeTodoItem('Buy milk', false, $ownerId);

        $this->repository
            ->expects($this->once())
            ->method('findAllByOwner')
            ->with($ownerId)
            ->willReturn([$item]);

        $result = $this->service->findAllByOwner($ownerId);

        $this->assertCount(1, $result);
        $this->assertSame($item, $result[0]);
    }

    public function testCreateTodoItem(): void
    {
        $ownerId = 'owner-abc';

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->repository
            ->expects($this->once())
            ->method('save');

        $item = $this->service->create(['text' => 'Write tests'], $ownerId);

        $this->assertSame('Write tests', $item->getText());
        $this->assertFalse($item->isDone());
        $this->assertSame($ownerId, $item->getOwnerId());
    }

    public function testCreateTrimsTodoText(): void
    {
        $this->validator->method('validate')->willReturn(new ConstraintViolationList());
        $this->repository->method('save');

        $item = $this->service->create(['text' => '  padded text  '], 'owner-1');

        $this->assertSame('padded text', $item->getText());
    }

    public function testCreateThrowsOnValidationFailure(): void
    {
        $violations = $this->createMock(ConstraintViolationList::class);
        $violations->method('count')->willReturn(1);

        $this->validator->method('validate')->willReturn($violations);
        $this->repository->expects($this->never())->method('save');

        $this->expectException(ValidationFailedException::class);

        $this->service->create(['text' => ''], 'owner-1');
    }

    public function testToggleItemFlipsDoneState(): void
    {
        $item = $this->makeTodoItem('Task', false, 'owner-1');

        $this->em->expects($this->exactly(2))->method('flush');

        $result = $this->service->toggle($item, 'owner-1');

        $this->assertTrue($result->isDone());

        $this->service->toggle($item, 'owner-1');
        $this->assertFalse($item->isDone());
    }

    public function testUpdateChangesTextAndDone(): void
    {
        $item = $this->makeTodoItem('Old text', false, 'owner-1');

        $this->validator->method('validate')->willReturn(new ConstraintViolationList());
        $this->em->expects($this->once())->method('flush');

        $this->service->update($item, ['text' => 'New text', 'done' => true], 'owner-1');

        $this->assertSame('New text', $item->getText());
        $this->assertTrue($item->isDone());
    }

    public function testDeleteCallsRepository(): void
    {
        $item = $this->makeTodoItem('Task', false, 'owner-1');

        $this->repository->expects($this->once())->method('remove')->with($item, true);

        $this->service->delete($item);
    }

    public function testSerializeReturnsExpectedKeys(): void
    {
        $item = $this->makeTodoItem('Buy bread', true, 'owner-7');

        $data = $this->service->serialize($item);

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('text', $data);
        $this->assertArrayHasKey('done', $data);
        $this->assertArrayHasKey('createdAt', $data);
        $this->assertArrayHasKey('updatedAt', $data);
        $this->assertSame('Buy bread', $data['text']);
        $this->assertTrue($data['done']);
    }

    private function makeTodoItem(string $text, bool $done, string $ownerId): TodoItem
    {
        $item = new TodoItem();
        $item->setText($text);
        $item->setDone($done);
        $item->setOwnerId($ownerId);

        return $item;
    }
}
