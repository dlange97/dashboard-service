<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\NoteRepository;
use App\Traits\HasInstanceId;
use App\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NoteRepository::class)]
#[ORM\Table(name: 'note')]
#[ORM\HasLifecycleCallbacks]
class Note
{
    use HasInstanceId;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private string $content = '';

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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
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
