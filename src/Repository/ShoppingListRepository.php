<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ShoppingList;
use App\Traits\SaveRemoveTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShoppingList>
 */
class ShoppingListRepository extends ServiceEntityRepository
{
    use SaveRemoveTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShoppingList::class);
    }

    /**
     * @return ShoppingList[]
     */
    public function findAllByOwner(string $ownerId): array
    {
        return $this->createQueryBuilder('sl')
            ->andWhere('sl.ownerId = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->orderBy('sl.status', 'ASC')
            ->addOrderBy('sl.updatedAt', 'DESC')
            ->addOrderBy('sl.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
