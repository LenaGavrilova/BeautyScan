<?php

namespace App\Controller;

use App\Service\IngredientAnalyzerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AnalysisController extends AbstractController
{
    private $analysisManager;

    public function __construct(IngredientAnalyzerService $analysisManager)
    {
        $this->analysisManager = $analysisManager;
    }

    #[Route('/api/analyze', name: 'analyze_ingredients', methods: ['POST'])]
    public function analyzeIngredients(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['ingredients']) || empty($data['ingredients'])) {
            return new JsonResponse(['success' => false, 'message' => 'Ингредиенты не указаны.'], 400);
        }

        try {
            $result = $this->analysisManager->analyzeIngredients($data['ingredients']);
            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
}