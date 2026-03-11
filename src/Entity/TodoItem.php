<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TodoItemRepository;
use App\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TodoItemRepository::class)]
#[ORM\Table(name: 'todo_item')]
#[ORM\HasLifecycleCallbacks]
class TodoItem
{
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

    /** Owner UUID from auth-service (no FK – cross-service boundary). */
    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $ownerId = null;

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

    public function getOwnerId(): ?string
    {
        return $this->ownerId;
    }

    public function setOwnerId(?string $ownerId): static
    {
        $this->ownerId = $ownerId;
        return $this;
    }
}
