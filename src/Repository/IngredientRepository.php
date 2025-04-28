<?php

namespace App\Repository;

use App\Entity\Ingredient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class IngredientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ingredient::class);
    }
    
    /**
     * Находит ингредиент по имени (с учетом регистра)
     */
    public function findByName(string $name): ?Ingredient
    {
        return $this->createQueryBuilder('i')
            ->where('LOWER(i.traditionalName) = :name')
            ->setParameter('name', strtolower($name))
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    /**
     * Находит ингредиенты по уровню безопасности
     */
    public function findBySafetyLevel(string $safetyLevel): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.safetyLevel = :safetyLevel')
            ->setParameter('safetyLevel', $safetyLevel)
            ->orderBy('i.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 