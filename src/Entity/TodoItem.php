<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TodoItemRepository;
use App\Traits\HasInstanceId;
use App\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TodoItemRepository::class)]
#[ORM\Table(name: 'todo_item')]
#[ORM\HasLifecycleCallbacks]
class TodoItem
{
    use HasInstanceId;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank]
    private ?string $text = null;

    #[ORM\Column(type: 'boolean')]
    private bool $done = false;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

    /** Owner UUID from auth-service (no FK – cross-service boundary). */
    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $ownerId = null;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $sharedWithUserIds = [];

    // ─── Getters / Setters ─────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    public function isDone(): bool
    {
        return $this->done;
    }

    public function setDone(bool $done): static
    {
        $this->done = $done;
        return $this;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeImmutable $dueDate): static
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function getOwnerId(): ?string
    {
        return $this->ownerId;
    }

    public function setOwnerId(?string $ownerId): static
    {
        $this->ownerId = $ownerId;
        return $this;
    }

    /** @return list<string> */
    public function getSharedWithUserIds(): array
    {
        return array_values(array_unique(array_filter(
            $this->sharedWithUserIds,
            static fn(mixed $value): bool => is_string($value) && trim($value) !== '',
        )));
    }

    /** @param list<string> $userIds */
    public function setSharedWithUserIds(array $userIds): static
    {
        $normalized = [];
        foreach ($userIds as $userId) {
            $trimmed = trim((string) $userId);
            if ($trimmed === '') {
                continue;
            }
            if (!in_array($trimmed, $normalized, true)) {
                $normalized[] = $trimmed;
            }
        }

        $this->sharedWithUserIds = $normalized;

        return $this;
    }

    public function addSharedUserId(string $userId): static
    {
        $trimmed = trim($userId);
        if ($trimmed === '') {
            return $this;
        }

        $shared = $this->getSharedWithUserIds();
        if (!in_array($trimmed, $shared, true)) {
            $shared[] = $trimmed;
            $this->sharedWithUserIds = $shared;
        }

        return $this;
    }

    public function removeSharedUserId(string $userId): static
    {
        $trimmed = trim($userId);
        if ($trimmed === '') {
            return $this;
        }

        $this->sharedWithUserIds = array_values(array_filter(
            $this->getSharedWithUserIds(),
            static fn(string $existing): bool => $existing !== $trimmed,
        ));

        return $this;
    }
}
