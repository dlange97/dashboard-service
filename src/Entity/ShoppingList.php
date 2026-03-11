<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ShoppingListRepository;
use App\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ShoppingListRepository::class)]
#[ORM\Table(name: 'shopping_list')]
#[ORM\HasLifecycleCallbacks]
class ShoppingList
{
    use TimestampableTrait;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 20, options: ['default' => self::STATUS_ACTIVE])]
    #[Assert\Choice(choices: [self::STATUS_ACTIVE, self::STATUS_ARCHIVED])]
    private string $status = self::STATUS_ACTIVE;

    /** Owner UUID from auth-service (no FK – cross-service boundary). */
    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $ownerId = null;

    /** @var Collection<int, ShoppingListProduct> */
    #[ORM\OneToMany(targetEntity: ShoppingListProduct::class, mappedBy: 'shoppingList', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    // ─── Getters / Setters ─────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
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

    /** @return Collection<int, ShoppingListProduct> */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(ShoppingListProduct $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setShoppingList($this);
        }
        return $this;
    }

    public function removeProduct(ShoppingListProduct $product): static
    {
        if ($this->products->removeElement($product)) {
            if ($product->getShoppingList() === $this) {
                $product->setShoppingList(null);
            }
        }
        return $this;
    }
}
