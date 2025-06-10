<?php

namespace App\Controller;

use App\Service\HistoryManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class HistoryController extends AbstractController
{
    private $historyManager;

    public function __construct(HistoryManager $historyManager)
    {
        $this->historyManager = $historyManager;
    }

    #[Route('/api/history', name: 'save_analysis', methods: ['POST'])]
    public function saveAnalysis(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['query_type']) || !isset($data['query_content']) || !isset($data['result'])) {
            return new JsonResponse(['success' => false, 'message' => 'Недостаточно данных для сохранения.'], 400);
        }

        try {
            $result = $this->historyManager->saveAnalysis($data);
            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    #[Route('/api/history', name: 'get_history', methods: ['GET'])]
    public function getHistory(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $filters = [];
        if ($request->query->has('date')) {
            $filters['date'] = $request->query->get('date');
        }

        if ($request->query->has('ingredient')) {
            $filters['ingredient'] = $request->query->get('ingredient');
        }

        try {
            $history = $this->historyManager->getHistory($page, $limit, $filters);
            return new JsonResponse($history);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    #[Route('/api/history/{id}', name: 'get_history_item', methods: ['GET'])]
    public function getHistoryItem(int $id): JsonResponse
    {
        try {
            $result = $this->historyManager->getHistoryItem($id);
            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    #[Route('/api/history/{id}', name: 'update_history_item', methods: ['PUT'])]
    public function updateHistoryItem(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $result = $this->historyManager->updateHistoryItem($id, $data);
            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    #[Route('/api/history/{id}', name: 'delete_history_item', methods: ['DELETE'])]
    public function deleteHistoryItem(int $id): JsonResponse
    {
        try {
            $result = $this->historyManager->deleteHistoryItem($id);
            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
}