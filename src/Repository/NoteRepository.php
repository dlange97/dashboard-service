<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Note;
use App\Traits\SaveRemoveTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Note>
 */
class NoteRepository extends ServiceEntityRepository
{
    use SaveRemoveTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    /**
     * @return Note[]
     */
    public function findAllByOwner(string $ownerId): array
    {
        return $this->findAllAccessibleByUser($ownerId);
    }

    /**
     * @return Note[]
     */
    public function findAllAccessibleByUser(string $userId): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.ownerId = :userId OR n.sharedWithUserIds LIKE :sharedMatch')
            ->setParameter('userId', $userId)
            ->setParameter('sharedMatch', '%"' . $userId . '"%')
            ->orderBy('n.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
