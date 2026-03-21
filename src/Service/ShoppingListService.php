<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ShoppingList;
use App\Entity\ShoppingListProduct;
use App\Repository\ShoppingListProductRepository;
use App\Repository\ShoppingListRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ShoppingListService
{
    public function __construct(
        private readonly ShoppingListRepository $listRepository,
        private readonly ShoppingListProductRepository $productRepository,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /** @return ShoppingList[] */
    public function findAllByOwner(string $ownerId): array
    {
        return $this->listRepository->findAllByOwner($ownerId);
    }

    /**
     * @param array{name: string, dueDate?: string|null, status?: string, products?: array<array-key, array{name: string, qty?: int, weight?: string|null, bought?: bool}>} $data
     * @throws ValidationFailedException
     */
    public function create(array $data, string $ownerId): ShoppingList
    {
        $list = new ShoppingList();
        $list->setName($data['name']);
        $list->setDueDate($this->parseDueDate($data['dueDate'] ?? null));
        if (array_key_exists('status', $data)) {
            $list->setStatus((string) $data['status']);
        }
        $list->setOwnerId($ownerId);
        $actorId = $this->resolveActorId($ownerId);
        $list->setCreatedBy($actorId);
        $list->setUpdatedBy($actorId);

        $this->validate($list);

        foreach (($data['products'] ?? []) as $i => $pData) {
            $list->addProduct($this->buildProduct($pData, $i, $ownerId));
        }

        $this->listRepository->save($list, true);

        return $list;
    }

    /**
     * @param array{name?: string, dueDate?: string|null, status?: string, products?: array<array-key, array{name?: string, qty?: int, weight?: string|null, bought?: bool}>} $data
     * @throws ValidationFailedException
     */
    public function update(ShoppingList $list, array $data, string $ownerId): ShoppingList
    {
        if (isset($data['name'])) {
            $list->setName($data['name']);
        }

        if (array_key_exists('dueDate', $data)) {
            $list->setDueDate($this->parseDueDate($data['dueDate']));
        }

        if (isset($data['status'])) {
            $list->setStatus((string) $data['status']);
        }

        $list->setUpdatedBy($this->resolveActorId($ownerId));

        if (array_key_exists('products', $data) && is_array($data['products'])) {
            foreach ($list->getProducts()->toArray() as $product) {
                $list->removeProduct($product);
                $this->em->remove($product);
            }

            foreach ($data['products'] as $i => $pData) {
                $list->addProduct($this->buildProduct($pData, $i, $ownerId));
            }
        }

        $this->validate($list);
        $this->em->flush();

        return $list;
    }

    public function delete(ShoppingList $list): void
    {
        $this->listRepository->remove($list, true);
    }

    /**
     * @throws ValidationFailedException
     */
    public function updateStatus(ShoppingList $list, string $status, string $ownerId): ShoppingList
    {
        $list->setStatus($status);
        $list->setUpdatedBy($this->resolveActorId($ownerId));
        $this->validate($list);
        $this->em->flush();

        return $list;
    }

    /**
     * @param array{name: string, qty?: int, weight?: string|null} $data
     * @throws ValidationFailedException
     */
    public function addProduct(ShoppingList $list, array $data, string $ownerId): ShoppingListProduct
    {
        $product = $this->buildProduct($data, $list->getProducts()->count(), $ownerId);
        $list->addProduct($product);

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            throw new ValidationFailedException($product, $errors);
        }

        $this->em->flush();

        return $product;
    }

    public function removeProduct(ShoppingListProduct $product): void
    {
        $this->productRepository->remove($product, true);
    }

    /** @return array<string, mixed> */
    public function serializeList(ShoppingList $list): array
    {
        return [
            'id'        => $list->getId(),
            'name'      => $list->getName(),
            'status'    => $list->getStatus(),
            'dueDate'   => $list->getDueDate()?->format('Y-m-d'),
            'ownerId'   => $list->getOwnerId(),
            'createdBy' => $list->getCreatedBy(),
            'products'  => array_map($this->serializeProduct(...), $list->getProducts()->toArray()),
            'createdAt' => $list->getCreatedAt()?->format('c'),
            'updatedAt' => $list->getUpdatedAt()?->format('c'),
        ];
    }

    /** @return array<string, mixed> */
    public function serializeProduct(ShoppingListProduct $product): array
    {
        return [
            'id'       => $product->getId(),
            'name'     => $product->getName(),
            'qty'      => $product->getQty(),
            'weight'   => $product->getWeight(),
            'bought'   => $product->isBought(),
            'position' => $product->getPosition(),
            'createdBy' => $product->getCreatedBy(),
        ];
    }

    /**
     * @param array{name?: string, qty?: int, weight?: string|null, bought?: bool} $data
     */
    private function buildProduct(array $data, int $position, string $ownerId): ShoppingListProduct
    {
        $product = new ShoppingListProduct();
        $product->setName($data['name'] ?? '');
        $product->setQty((int) ($data['qty'] ?? 1));
        $product->setWeight($data['weight'] ?? null);
        $product->setBought((bool) ($data['bought'] ?? false));
        $product->setPosition($position);
        $actorId = $this->resolveActorId($ownerId);
        $product->setCreatedBy($actorId);
        $product->setUpdatedBy($actorId);

        return $product;
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
    private function validate(ShoppingList $list): void
    {
        $errors = $this->validator->validate($list);
        if (count($errors) > 0) {
            throw new ValidationFailedException($list, $errors);
        }
    }
}
