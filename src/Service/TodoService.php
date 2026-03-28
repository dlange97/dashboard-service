<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\TodoItem;
use App\Repository\TodoItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TodoService
{
    public function __construct(
        private readonly TodoItemRepository $repository,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /** @return TodoItem[] */
    public function findAllByOwner(string $ownerId): array
    {
        return $this->repository->findAllAccessibleByUser($ownerId);
    }

    /**
     * @param array{text: string, dueDate?: string|null} $data
     * @throws ValidationFailedException
     */
    public function create(array $data, string $ownerId): TodoItem
    {
        $item = new TodoItem();
        $item->setText(trim($data['text']));
        $item->setDone(false);
        $item->setDueDate($this->parseDueDate($data['dueDate'] ?? null));
        $item->setOwnerId($ownerId);
        $actorId = $this->resolveActorId($ownerId);
        $item->setCreatedBy($actorId);
        $item->setUpdatedBy($actorId);

        $this->validate($item);
        $this->repository->save($item, true);

        return $item;
    }

    /**
     * @param array{text?: string, done?: bool, dueDate?: string|null} $data
     * @throws ValidationFailedException
     */
    public function update(TodoItem $item, array $data, string $ownerId): TodoItem
    {
        if (isset($data['text'])) {
            $item->setText(trim($data['text']));
        }
        if (isset($data['done'])) {
            $item->setDone((bool) $data['done']);
        }
        if (array_key_exists('dueDate', $data)) {
            $item->setDueDate($this->parseDueDate($data['dueDate']));
        }
        $item->setUpdatedBy($this->resolveActorId($ownerId));

        $this->validate($item);
        $this->em->flush();

        return $item;
    }

    public function toggle(TodoItem $item, string $ownerId): TodoItem
    {
        $item->setDone(!$item->isDone());
        $item->setUpdatedBy($this->resolveActorId($ownerId));
        $this->em->flush();

        return $item;
    }

    public function delete(TodoItem $item): void
    {
        $this->repository->remove($item, true);
    }

    public function shareWithUser(TodoItem $item, string $userId, string $actorId): TodoItem
    {
        $normalizedUserId = trim($userId);
        if ($normalizedUserId === '') {
            throw new \InvalidArgumentException('User ID cannot be empty.');
        }

        if ($item->getOwnerId() === $normalizedUserId) {
            throw new \InvalidArgumentException('Owner already has access to this todo item.');
        }

        $item->addSharedUserId($normalizedUserId);
        $item->setUpdatedBy($this->resolveActorId($actorId));
        $this->em->flush();

        return $item;
    }

    public function unshareWithUser(TodoItem $item, string $userId, string $actorId): TodoItem
    {
        $item->removeSharedUserId($userId);
        $item->setUpdatedBy($this->resolveActorId($actorId));
        $this->em->flush();

        return $item;
    }

    /** @return array<string, mixed> */
    public function serialize(TodoItem $item): array
    {
        return [
            'id'        => $item->getId(),
            'text'      => $item->getText(),
            'done'      => $item->isDone(),
            'dueDate'   => $item->getDueDate()?->format('Y-m-d'),
            'ownerId'   => $item->getOwnerId(),
            'sharedWithUserIds' => $item->getSharedWithUserIds(),
            'createdBy' => $item->getCreatedBy(),
            'createdAt' => $item->getCreatedAt()?->format('c'),
            'updatedAt' => $item->getUpdatedAt()?->format('c'),
        ];
    }

    private function parseDueDate(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized == '') {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $normalized);
        if ($parsed !== false) {
            return $parsed;
        }

        return new \DateTimeImmutable($normalized);
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
    private function validate(TodoItem $item): void
    {
        $errors = $this->validator->validate($item);
        if (count($errors) > 0) {
            throw new ValidationFailedException($item, $errors);
        }
    }
}
