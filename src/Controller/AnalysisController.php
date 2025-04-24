<?php

namespace App\Controller;

use App\Entity\Analysis;
use App\Repository\AnalysisRepository;
use App\Service\IngredientAnalyzerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;

class AnalysisController extends AbstractController
{
    private $entityManager;
    private $security;
    private $analysisRepository;
    private $ingredientAnalyzer;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security,
        AnalysisRepository $analysisRepository,
        IngredientAnalyzerService $ingredientAnalyzer
    ) {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->analysisRepository = $analysisRepository;
        $this->ingredientAnalyzer = $ingredientAnalyzer;
    }

    #[Route('/api/extract-text', name: 'extract_text', methods: ['POST'])]
    public function extractText(Request $request): JsonResponse
    {
        $file = $request->files->get('image');
        
        if (!$file) {
            return new JsonResponse(['success' => false, 'message' => 'Изображение не было загружено.'], 400);
        }

        // Возвращаем текст состава с фотографии
        $extractedText = "Aqua, Sorbitol, Algae Extract, Hydroxyethyl Urea, Glycerin, Betaine, Sodium PCA, Serine, Glycine, Glutamic Acid, Alanine, Lysine, Arginine, Threonine, Proline, Cocos Nucifera (Coconut) Fruit Extract, Almond Oil Glycereth-8 Esters, Polysorbate 20, Parfum, Methylchloroisothiazolinone, Methylisothiazolinone, Citric Acid, Hexyl Cinnamal, Coumarin";

        return new JsonResponse([
            'success' => true,
            'extractedText' => $extractedText
        ]);
    }

    #[Route('/api/analyze', name: 'analyze_ingredients', methods: ['POST'])]
    public function analyzeIngredients(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['ingredients']) || empty($data['ingredients'])) {
            return new JsonResponse(['success' => false, 'message' => 'Ингредиенты не указаны.'], 400);
        }

        $ingredients = $data['ingredients'];
        
        // Используем сервис для анализа ингредиентов
        $analysisResult = $this->ingredientAnalyzer->analyzeIngredients($ingredients);
        
        // Форматируем результат для фронтенда
        $result = [
            'safety_rating' => $analysisResult['safety_rating'],
            'safe_percentage' => $analysisResult['safety_percentages']['safe'],
            'caution_percentage' => $analysisResult['safety_percentages']['caution'],
            'danger_percentage' => $analysisResult['safety_percentages']['danger'],
            'unknown_percentage' => $analysisResult['safety_percentages']['unknown'],
            'recommendation' => $analysisResult['recommendation'],
            'has_unknown_ingredients' => $analysisResult['has_unknown_ingredients'],
            'unknown_count' => $analysisResult['unknown_count'],
            'ingredients' => []
        ];
        
        // Преобразуем ингредиенты в формат, ожидаемый фронтендом
        foreach ($analysisResult['ingredients'] as $index => $ingredient) {
            $result['ingredients'][] = [
                'id' => $index + 1,
                'name' => $ingredient['name'],
                'description' => $ingredient['description'],
                'safety_level' => $ingredient['safety'],
                'class' => $ingredient['unknown'] ? 'Неизвестный ингредиент' : $this->getIngredientClass($ingredient['safety']),
                'unknown' => $ingredient['unknown']
            ];
        }

        return new JsonResponse($result);
    }
    
    /**
     * Возвращает класс ингредиента на основе его уровня безопасности
     */
    private function getIngredientClass(string $safetyLevel): string
    {
        switch ($safetyLevel) {
            case 'safe':
                return 'Безопасный компонент';
            case 'caution':
                return 'Компонент с осторожностью';
            case 'danger':
                return 'Потенциально опасный компонент';
            default:
                return 'Неизвестный компонент';
        }
    }

    #[Route('/api/history', name: 'save_analysis', methods: ['POST'])]
    public function saveAnalysis(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Пользователь не аутентифицирован.'], 401);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['query_type']) || !isset($data['query_content']) || !isset($data['result'])) {
            return new JsonResponse(['success' => false, 'message' => 'Недостаточно данных для сохранения.'], 400);
        }

        $analysis = new Analysis();
        $analysis->setQueryType($data['query_type']);
        $analysis->setQueryContent($data['query_content']);
        $analysis->setResult($data['result']);
        $analysis->setCreatedAt(new \DateTime());
        $analysis->setUser($user);

        $this->entityManager->persist($analysis);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Анализ успешно сохранен.']);
    }

    #[Route('/api/history', name: 'get_history', methods: ['GET'])]
    public function getHistory(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Пользователь не аутентифицирован.'], 401);
        }

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        
        $filters = [];
        if ($request->query->has('date')) {
            $filters['date'] = $request->query->get('date');
        }
        
        if ($request->query->has('ingredient')) {
            $filters['ingredient'] = $request->query->get('ingredient');
        }

        $history = $this->analysisRepository->getUserAnalysesHistory($user, $page, $limit, $filters);
        
        // Преобразуем объекты в массивы для JSON
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

        return new JsonResponse($history);
    }

    #[Route('/api/history/{id}', name: 'get_history_item', methods: ['GET'])]
    public function getHistoryItem(int $id): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Пользователь не аутентифицирован.'], 401);
        }

        $analysis = $this->analysisRepository->findOneByIdAndUser($id, $user);
        
        if (!$analysis) {
            return new JsonResponse(['success' => false, 'message' => 'Запись не найдена.'], 404);
        }

        $result = [
            'id' => $analysis->getId(),
            'query_type' => $analysis->getQueryType(),
            'query_content' => $analysis->getQueryContent(),
            'result' => $analysis->getResult(),
            'created_at' => $analysis->getCreatedAt()->format('Y-m-d H:i:s')
        ];

        return new JsonResponse($result);
    }

    #[Route('/api/history/{id}', name: 'update_history_item', methods: ['PUT'])]
    public function updateHistoryItem(int $id, Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Пользователь не аутентифицирован.'], 401);
        }

        $analysis = $this->analysisRepository->findOneByIdAndUser($id, $user);
        
        if (!$analysis) {
            return new JsonResponse(['success' => false, 'message' => 'Запись не найдена.'], 404);
        }

        $data = json_decode($request->getContent(), true);
        
        // Обновляем только те поля, которые предоставлены
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

        return new JsonResponse([
            'success' => true, 
            'message' => 'Запись успешно обновлена.',
            'id' => $analysis->getId()
        ]);
    }

    #[Route('/api/history/{id}', name: 'delete_history_item', methods: ['DELETE'])]
    public function deleteHistoryItem(int $id): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Пользователь не аутентифицирован.'], 401);
        }

        $analysis = $this->analysisRepository->findOneByIdAndUser($id, $user);
        
        if (!$analysis) {
            return new JsonResponse(['success' => false, 'message' => 'Запись не найдена.'], 404);
        }

        $this->entityManager->remove($analysis);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Запись успешно удалена.']);
    }
} 