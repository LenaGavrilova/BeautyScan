<?php

namespace App\Repository;

use App\Entity\Analysis;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

class AnalysisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Analysis::class);
    }

    /**
     * Получить историю анализов пользователя с пагинацией и фильтрацией
     */
    public function getUserAnalysesHistory(User $user, int $page = 1, int $limit = 10, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.createdAt', 'DESC');

        // Применяем фильтр по дате
        if (!empty($filters['date'])) {
            $date = new \DateTime($filters['date']);
            $nextDay = clone $date;
            $nextDay->modify('+1 day');
            
            $qb->andWhere('a.createdAt >= :date AND a.createdAt < :nextDay')
                ->setParameter('date', $date->format('Y-m-d'))
                ->setParameter('nextDay', $nextDay->format('Y-m-d'));
        }

        // Применяем фильтр по ингредиенту
        if (!empty($filters['ingredient'])) {
            $qb->andWhere('a.queryContent LIKE :ingredient OR a.result LIKE :ingredient')
                ->setParameter('ingredient', '%' . $filters['ingredient'] . '%');
        }

        // Создаем пагинатор
        $paginator = new Paginator($qb);

        // Устанавливаем ограничение и смещение
        $paginator
            ->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        // Получаем общее количество записей
        $total = count($paginator);

        // Получаем записи для текущей страницы
        $items = [];
        foreach ($paginator as $analysis) {
            $items[] = $analysis;
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit)
        ];
    }

    /**
     * Получить анализ по ID и пользователю
     */
    public function findOneByIdAndUser(int $id, User $user): ?Analysis
    {
        return $this->createQueryBuilder('a')
            ->where('a.id = :id')
            ->andWhere('a.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
} 