<?php

namespace App\Repository;

use App\Entity\IngredientCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class IngredientCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IngredientCategory::class);
    }

    /**
     * Находит категорию по имени категории (с учетом регистра)
     */
    public function findByCategoryName(string $name): ?IngredientCategory
    {
        return $this->createQueryBuilder('ic')
            ->where('LOWER(ic.categoryName) = :name')
            ->setParameter('name', strtolower($name))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Находит все категории для указанного имени ингредиента
     */
    public function findByIngredientName(string $ingredientName): array
    {
        return $this->createQueryBuilder('ic')
            ->where('ic.ingredientName = :ingredientName')
            ->setParameter('ingredientName', $ingredientName)
            ->orderBy('ic.categoryName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}