<?php

namespace App\Service;

use App\Entity\Analysis;
use App\Repository\AnalysisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class HistoryManager
{
    private $entityManager;
    private $security;
    private $analysisRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security,
        AnalysisRepository $analysisRepository
    ) {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->analysisRepository = $analysisRepository;
    }

    public function saveAnalysis(array $data): array
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new \RuntimeException('Пользователь не аутентифицирован.', 401);
        }

        $analysis = new Analysis();
        $analysis->setQueryType($data['query_type']);
        $analysis->setQueryContent($data['query_content']);
        $analysis->setResult($data['result']);
        $analysis->setCreatedAt(new \DateTime());
        $analysis->setUser($user);

        $this->entityManager->persist($analysis);
        $this->entityManager->flush();

        return ['success' => true, 'message' => 'Анализ успешно сохранен.'];
    }

    public function getHistory(int $page, int $limit, array $filters = []): array
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new \RuntimeException('Пользователь не аутентифицирован.', 401);
        }

        $history = $this->analysisRepository->getUserAnalysesHistory($user, $page, $limit, $filters);

        $items = [];
        foreach ($history['items'] as $item) {
            $items[] = [
                'id' => $item->getId(),
                'query_type' => $item->getQueryType(),
                'query_content' => $item->getQueryContent(),
                'result' => $item->getResult(),
                'created_at' => $item->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }

        $history['items'] = $items;

        return $history;
    }

    public function getHistoryItem(int $id): array
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new \RuntimeException('Пользователь не аутентифицирован.', 401);
        }

        $analysis = $this->analysisRepository->findOneByIdAndUser($id, $user);

        if (!$analysis) {
            throw new \RuntimeException('Запись не найдена.', 404);
        }

        return [
            'id' => $analysis->getId(),
            'query_type' => $analysis->getQueryType(),
            'query_content' => $analysis->getQueryContent(),
            'result' => $analysis->getResult(),
            'created_at' => $analysis->getCreatedAt()->format('Y-m-d H:i:s')
        ];
    }

    public function updateHistoryItem(int $id, array $data): array
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new \RuntimeException('Пользователь не аутентифицирован.', 401);
        }

        $analysis = $this->analysisRepository->findOneByIdAndUser($id, $user);

        if (!$analysis) {
            throw new \RuntimeException('Запись не найдена.', 404);
        }

        if (isset($data['query_type'])) {
            $analysis->setQueryType($data['query_type']);
        }

        if (isset($data['query_content'])) {
            $analysis->setQueryContent($data['query_content']);
        }

        if (isset($data['result'])) {
            $analysis->setResult($data['result']);
        }

        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => 'Запись успешно обновлена.',
            'id' => $analysis->getId()
        ];
    }

    public function deleteHistoryItem(int $id): array
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new \RuntimeException('Пользователь не аутентифицирован.', 401);
        }

        $analysis = $this->analysisRepository->findOneByIdAndUser($id, $user);

        if (!$analysis) {
            throw new \RuntimeException('Запись не найдена.', 404);
        }

        $this->entityManager->remove($analysis);
        $this->entityManager->flush();

        return ['success' => true, 'message' => 'Запись успешно удалена.'];
    }
}