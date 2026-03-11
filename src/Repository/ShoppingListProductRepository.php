<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ShoppingListProduct;
use App\Traits\SaveRemoveTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShoppingListProduct>
 */
class ShoppingListProductRepository extends ServiceEntityRepository
{
    use SaveRemoveTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShoppingListProduct::class);
    }
}
