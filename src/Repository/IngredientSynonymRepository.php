<?php

namespace App\Repository;

use App\Entity\IngredientSynonym;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class IngredientSynonymRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IngredientSynonym::class);
    }
    
    /**
     * Находит синоним по имени (с учетом регистра)
     */
    public function findByName(string $name): ?IngredientSynonym
    {
        return $this->createQueryBuilder('s')
            ->where('LOWER(s.name) = :name')
            ->setParameter('name', strtolower($name))
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    /**
     * Находит все синонимы для указанного ингредиента
     */
    public function findByIngredientId(int $ingredientId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.ingredient = :ingredientId')
            ->setParameter('ingredientId', $ingredientId)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 