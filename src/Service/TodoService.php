<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\TodoItem;
use App\Repository\TodoItemRepository;
use App\Service\Input\DateInputParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TodoService
{
    public function __construct(
        private readonly TodoItemRepository $repository,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
        private readonly ActorIdResolver $actorIdResolver,
        private readonly DateInputParser $dateInputParser,
        private readonly SharedResourceAccessService $sharedResourceAccessService,
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
        $item->setDueDate($this->dateInputParser->parseNullableDate($data['dueDate'] ?? null, 'dueDate'));
        $item->setOwnerId($ownerId);
        $actorId = $this->actorIdResolver->resolve($ownerId);
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
            $item->setDueDate($this->dateInputParser->parseNullableDate($data['dueDate'], 'dueDate'));
        }
        $item->setUpdatedBy($this->actorIdResolver->resolve($ownerId));

        $this->validate($item);
        $this->em->flush();

        return $item;
    }

    public function toggle(TodoItem $item, string $ownerId): TodoItem
    {
        $item->setDone(!$item->isDone());
        $item->setUpdatedBy($this->actorIdResolver->resolve($ownerId));
        $this->em->flush();

        return $item;
    }

    public function delete(TodoItem $item): void
    {
        $this->repository->remove($item, true);
    }

    public function shareWithUser(TodoItem $item, string $userId, string $actorId): TodoItem
    {
        $normalizedUserId = $this->sharedResourceAccessService->normalizeShareTarget(
            $item,
            $userId,
            'Owner already has access to this todo item.',
        );

        $item->addSharedUserId($normalizedUserId);
        $item->setUpdatedBy($this->actorIdResolver->resolve($actorId));
        $this->em->flush();

        return $item;
    }

    public function unshareWithUser(TodoItem $item, string $userId, string $actorId): TodoItem
    {
        $item->removeSharedUserId($userId);
        $item->setUpdatedBy($this->actorIdResolver->resolve($actorId));
        $this->em->flush();

        return $item;
    }

    public function assertOwner(TodoItem $item, string $ownerId): void
    {
        $this->sharedResourceAccessService->assertOwner($item, $ownerId, 'You do not own this todo item.');
    }

    public function assertAccessible(TodoItem $item, string $userId): void
    {
        $this->sharedResourceAccessService->assertAccessible($item, $userId, 'You do not have access to this todo item.');
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

    /** @throws ValidationFailedException */
    private function validate(TodoItem $item): void
    {
        $errors = $this->validator->validate($item);
        if (count($errors) > 0) {
            throw new ValidationFailedException($item, $errors);
        }
    }
}
