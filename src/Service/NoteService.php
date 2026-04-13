<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Note;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class NoteService
{
    public function __construct(
        private readonly NoteRepository $repository,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /** @return Note[] */
    public function findAllByOwner(string $ownerId): array
    {
        return $this->repository->findAllAccessibleByUser($ownerId);
    }

    /**
     * @param array{title: string, content?: string} $data
     * @throws ValidationFailedException
     */
    public function create(array $data, string $ownerId): Note
    {
        $note = new Note();
        $note->setTitle(trim($data['title']));
        $note->setContent(trim($data['content'] ?? ''));
        $note->setOwnerId($ownerId);
        $actorId = $this->resolveActorId($ownerId);
        $note->setCreatedBy($actorId);
        $note->setUpdatedBy($actorId);

        $this->validate($note);
        $this->repository->save($note, true);

        return $note;
    }

    /**
     * @param array{title?: string, content?: string} $data
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
        $note->setUpdatedBy($this->resolveActorId($ownerId));

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
        $normalizedUserId = trim($userId);
        if ($normalizedUserId === '') {
            throw new \InvalidArgumentException('User ID cannot be empty.');
        }

        if ($note->getOwnerId() === $normalizedUserId) {
            throw new \InvalidArgumentException('Owner already has access to this note.');
        }

        $note->addSharedUserId($normalizedUserId);
        $note->setUpdatedBy($this->resolveActorId($actorId));
        $this->em->flush();

        return $note;
    }

    public function unshareWithUser(Note $note, string $userId, string $actorId): Note
    {
        $note->removeSharedUserId($userId);
        $note->setUpdatedBy($this->resolveActorId($actorId));
        $this->em->flush();

        return $note;
    }

    public function assertOwner(Note $note, string $ownerId): void
    {
        if ($note->getOwnerId() !== $ownerId) {
            throw new AccessDeniedHttpException('You do not own this note.');
        }
    }

    public function assertAccessible(Note $note, string $userId): void
    {
        if ($note->getOwnerId() === $userId) {
            return;
        }

        if (in_array($userId, $note->getSharedWithUserIds(), true)) {
            return;
        }

        throw new AccessDeniedHttpException('You do not have access to this note.');
    }

    /** @return array<string, mixed> */
    public function serialize(Note $note): array
    {
        return [
            'id'                => $note->getId(),
            'title'             => $note->getTitle(),
            'content'           => $note->getContent(),
            'ownerId'           => $note->getOwnerId(),
            'sharedWithUserIds' => $note->getSharedWithUserIds(),
            'createdBy'         => $note->getCreatedBy(),
            'createdAt'         => $note->getCreatedAt()?->format('c'),
            'updatedAt'         => $note->getUpdatedAt()?->format('c'),
        ];
    }

    private function resolveActorId(string $ownerId): int
    {
        if (is_numeric($ownerId)) {
            $numericId = (int) $ownerId;
            if ($numericId > 0 && $numericId <= 2147483647) {
                return $numericId;
            }
        }

        $hash = crc32($ownerId);
        $unsignedHash = (int) sprintf('%u', $hash);

        return ($unsignedHash % 2147483646) + 1;
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
