<?php

namespace App\Repository;

use App\Entity\Synonym;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SynonymRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Synonym::class);
    }

    /**
     * Находит синоним по имени (с учетом регистра)
     */
    public function findByName(string $name): ?Synonym
    {
        return $this->createQueryBuilder('s')
            ->where('LOWER(s.name) = :name')
            ->setParameter('name', strtolower($name))
            ->getQuery()
            ->getOneOrNullResult();
    }
}