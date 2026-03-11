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
        private readonly TodoItemRepository   $repository,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface    $validator,
    ) {}

    /** @return TodoItem[] */
    public function findAllByOwner(string $ownerId): array
    {
        return $this->repository->findAllByOwner($ownerId);
    }

    /**
     * @param array{text: string} $data
     * @throws ValidationFailedException
     */
    public function create(array $data, string $ownerId): TodoItem
    {
        $item = new TodoItem();
        $item->setText(trim($data['text'] ?? ''));
        $item->setDone(false);
        $item->setOwnerId($ownerId);

        $this->validate($item);
        $this->repository->save($item, true);

        return $item;
    }

    /**
     * @param array{text?: string, done?: bool} $data
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

        $this->validate($item);
        $this->em->flush();

        return $item;
    }

    public function toggle(TodoItem $item, string $ownerId): TodoItem
    {
        $item->setDone(!$item->isDone());
        $this->em->flush();

        return $item;
    }

    public function delete(TodoItem $item): void
    {
        $this->repository->remove($item, true);
    }

    /** @return array<string, mixed> */
    public function serialize(TodoItem $item): array
    {
        return [
            'id'        => $item->getId(),
            'text'      => $item->getText(),
            'done'      => $item->isDone(),
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
