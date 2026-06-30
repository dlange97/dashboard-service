<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Note;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class NoteService
{
    public function __construct(
        private readonly NoteRepository $repository,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
        private readonly ActorIdResolver $actorIdResolver,
        private readonly SharedResourceAccessService $sharedResourceAccessService,
    ) {
    }

    /** @return Note[] */
    public function findAllByOwner(string $ownerId): array
    {
        return $this->repository->findAllAccessibleByUser($ownerId);
    }

    /**
     * @param array{title: string, content?: string, color?: string} $data
     * @throws ValidationFailedException
     */
    public function create(array $data, string $ownerId): Note
    {
        $note = new Note();
        $note->setTitle(trim($data['title']));
        $note->setContent(trim($data['content'] ?? ''));
        $note->setColor($this->normalizeColor($data['color'] ?? null));
        $note->setOwnerId($ownerId);
        $actorId = $this->actorIdResolver->resolve($ownerId);
        $note->setCreatedBy($actorId);
        $note->setUpdatedBy($actorId);

        $this->validate($note);
        $this->repository->save($note, true);

        return $note;
    }

    /**
     * @param array{title?: string, content?: string, color?: string} $data
     * @throws ValidationFailedException
     */
    public function update(Note $note, array $data, string $ownerId): Note
    {
        if (isset($data['title'])) {
            $note->setTitle(trim($data['title']));
        }
        if (array_key_exists('content', $data)) {
            $note->setContent(trim($data['content']));
        }
        if (array_key_exists('color', $data)) {
            $note->setColor($this->normalizeColor($data['color']));
        }
        $note->setUpdatedBy($this->actorIdResolver->resolve($ownerId));

        $this->validate($note);
        $this->em->flush();

        return $note;
    }

    public function delete(Note $note): void
    {
        $this->repository->remove($note, true);
    }

    public function shareWithUser(Note $note, string $userId, string $actorId): Note
    {
        $normalizedUserId = $this->sharedResourceAccessService->normalizeShareTarget(
            $note,
            $userId,
            'Owner already has access to this note.',
        );

        $note->addSharedUserId($normalizedUserId);
        $note->setUpdatedBy($this->actorIdResolver->resolve($actorId));
        $this->em->flush();

        return $note;
    }

    public function unshareWithUser(Note $note, string $userId, string $actorId): Note
    {
        $note->removeSharedUserId($userId);
        $note->setUpdatedBy($this->actorIdResolver->resolve($actorId));
        $this->em->flush();

        return $note;
    }

    public function assertOwner(Note $note, string $ownerId): void
    {
        $this->sharedResourceAccessService->assertOwner($note, $ownerId, 'You do not own this note.');
    }

    public function assertAccessible(Note $note, string $userId): void
    {
        $this->sharedResourceAccessService->assertAccessible($note, $userId, 'You do not have access to this note.');
    }

    /** @return array<string, mixed> */
    public function serialize(Note $note): array
    {
        return [
            'id'                => $note->getId(),
            'title'             => $note->getTitle(),
            'content'           => $note->getContent(),
            'color'             => $note->getColor(),
            'ownerId'           => $note->getOwnerId(),
            'sharedWithUserIds' => $note->getSharedWithUserIds(),
            'createdBy'         => $note->getCreatedBy(),
            'createdAt'         => $note->getCreatedAt()?->format('c'),
            'updatedAt'         => $note->getUpdatedAt()?->format('c'),
        ];
    }

    private function normalizeColor(mixed $value): string
    {
        if (!is_string($value)) {
            return '#fef3c7';
        }

        $color = trim($value);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1) {
            return strtolower($color);
        }

        return '#fef3c7';
    }

    /** @throws ValidationFailedException */
    private function validate(Note $note): void
    {
        $errors = $this->validator->validate($note);
        if (count($errors) > 0) {
            throw new ValidationFailedException($note, $errors);
        }
    }
}
