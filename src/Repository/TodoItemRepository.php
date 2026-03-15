<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TodoItem;
use App\Traits\SaveRemoveTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TodoItem>
 */
class TodoItemRepository extends ServiceEntityRepository
{
    use SaveRemoveTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TodoItem::class);
    }

    /**
     * @return TodoItem[]
     */
    public function findAllByOwner(string $ownerId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.ownerId = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->orderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
