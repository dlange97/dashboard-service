<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Note;
use App\Repository\NoteRepository;
use App\Service\ActorIdResolver;
use App\Service\NoteService;
use App\Service\SharedResourceAccessService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class NoteServiceTest extends TestCase
{
    private NoteRepository&MockObject $repository;
    private EntityManagerInterface&MockObject $em;
    private ValidatorInterface&MockObject $validator;
    private ActorIdResolver&MockObject $actorIdResolver;
    private NoteService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(NoteRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->actorIdResolver = $this->createMock(ActorIdResolver::class);

        $this->actorIdResolver->method('resolve')->willReturn(789);
        $this->validator->method('validate')->willReturn(new ConstraintViolationList());

        $this->service = new NoteService(
            $this->repository,
            $this->em,
            $this->validator,
            $this->actorIdResolver,
            new SharedResourceAccessService(),
        );
    }

    public function testCreateAssignsResolvedActorId(): void
    {
        $this->repository->expects($this->once())->method('save');

        $note = $this->service->create(['title' => 'Test note', 'content' => 'Body'], 'owner-1');

        $this->assertSame('Test note', $note->getTitle());
        $this->assertSame('owner-1', $note->getOwnerId());
        $this->assertSame(789, $note->getCreatedBy());
        $this->assertSame(789, $note->getUpdatedBy());
    }

    public function testUpdateUsesResolvedActorId(): void
    {
        $note = new Note();
        $note->setTitle('Old');
        $note->setOwnerId('owner-1');

        $this->em->expects($this->once())->method('flush');

        $updated = $this->service->update($note, ['title' => 'New'], 'owner-1');

        $this->assertSame('New', $updated->getTitle());
        $this->assertSame(789, $updated->getUpdatedBy());
    }
}
