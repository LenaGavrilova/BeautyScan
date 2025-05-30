<?php

namespace App\Controller;

use App\Entity\Ingredient;
use App\Entity\IngredientSynonym;
use App\Repository\IngredientCategoryRepository;
use App\Repository\IngredientEffectivenessRepository;
use App\Repository\IngredientRepository;
use App\Repository\IngredientSynonymRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/api')]
class IngredientController extends AbstractController
{
    private $entityManager;
    private $security;
    private $ingredientRepository;
    private $ingredientSynonymRepository;
    private $ingredientCategoryRepository;
    private $ingredientEffectivenessRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security,
        IngredientRepository $ingredientRepository,
        IngredientSynonymRepository $ingredientSynonymRepository,
        IngredientCategoryRepository $ingredientCategoryRepository,
        IngredientEffectivenessRepository $ingredientEffectivenessRepository
    ) {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->ingredientRepository = $ingredientRepository;
        $this->ingredientSynonymRepository = $ingredientSynonymRepository;
        $this->ingredientCategoryRepository = $ingredientCategoryRepository;
        $this->ingredientEffectivenessRepository = $ingredientEffectivenessRepository;
    }

    #[Route('/ingredients', name: 'get_ingredients', methods: ['GET'])]
    public function getIngredients(Request $request): JsonResponse
    {
        $safetyLevel = $request->query->get('danger_factor');
        
        if ($safetyLevel) {
            $ingredients = $this->ingredientRepository->findBySafetyLevel($safetyLevel);
        } else {
            $ingredients = array_reverse($this->ingredientRepository->findAll());
        }
        
        $data = [];
        foreach ($ingredients as $ingredient) {
            $formattedName = mb_convert_case($ingredient->getTraditionalName(), MB_CASE_TITLE, 'UTF-8');

            $category = $this->ingredientCategoryRepository->findOneBy(['ingredientName'=> $formattedName]);
            $categoryName = !is_null($category) ? $category->getCategoryName() : '';

            $effectiveness = $this->ingredientEffectivenessRepository->findOneBy(['ingredientName'=> $formattedName]);
            $effectivenessName = !is_null($effectiveness) ? $effectiveness->getEffectivenessName() : '';
            $data[] = [
                'id' => $ingredient->getId(),
                'traditional_name' => $ingredient->getTraditionalName(),
                'latin_name' => $ingredient->getLatinName(),
                'inci_name' => $ingredient->getINCIName(),
                'danger_factor' => $ingredient->getDangerFactor(),
                'naturalness' => $ingredient->getNaturalness(),
                'usages' => $ingredient->getUsages() ?? $ingredient->getSafety(),
                'safety' => $ingredient->getSafety(),
                'category' => $categoryName,
                'effectiveness' => $effectivenessName
            ];
        }
        
        return new JsonResponse($data);
    }

    #[Route('/ingredients/{id}', name: 'get_ingredient', methods: ['GET'])]
    public function getIngredient(int $id): JsonResponse
    {
        $ingredient = $this->ingredientRepository->find($id);
        
        if (!$ingredient) {
            return new JsonResponse(['success' => false, 'message' => 'Ингредиент не найден.'], 404);
        }
        $formattedName = mb_convert_case($ingredient->getTraditionalName(), MB_CASE_TITLE, 'UTF-8');

        $category = $this->ingredientCategoryRepository->findOneBy(['ingredientName'=> $formattedName]);
        $categoryName = !is_null($category) ? $category->getCategoryName() : '';

        $effectiveness = $this->ingredientEffectivenessRepository->findOneBy(['ingredientName'=> $formattedName]);
        $effectivenessName = !is_null($effectiveness) ? $effectiveness->getEffectivenessName() : '';

        $data = [
            'id' => $ingredient->getId(),
            'traditional_name' => $ingredient->getTraditionalName(),
            'latin_name' => $ingredient->getLatinName(),
            'inci_name' => $ingredient->getINCIName(),
            'danger_factor' => $ingredient->getDangerFactor(),
            'naturalness' => $ingredient->getNaturalness(),
            'usages' => $ingredient->getUsages(),
            'safety' => $ingredient->getSafety(),
            'category' => $categoryName,
            'effectiveness' => $effectivenessName
        ];
        
        return new JsonResponse($data);
    }

    #[Route('/ingredients', name: 'create_ingredient', methods: ['POST'])]
    public function createIngredient(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'message' => 'Доступ запрещен.'], 403);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['traditional_name']) || !isset($data['latin_name']) || !isset($data['inci_name']) ||
            !isset($data['danger_factor']) || !isset($data['naturalness']) || !isset($data['usages'])
            || !isset($data['safety'])) {
            return new JsonResponse(['success' => false, 'message' => 'Неполные данные для создания ингредиента.'], 400);
        }
        
        // Проверяем, существует ли уже ингредиент с таким именем
        $existingIngredient = $this->ingredientRepository->findByName($data['traditional_name']);
        if ($existingIngredient) {
            return new JsonResponse(['success' => false, 'message' => 'Ингредиент с таким именем уже существует.'], 400);
        }
        
        $ingredient = new Ingredient();
        $ingredient->setTraditionalName($data['traditional_name']);
        $ingredient->setLatinName($data['latin_name']);
        $ingredient->setINCIName($data['inci_name']);
        $ingredient->setDangerFactor($data['danger_factor']);
        $ingredient->setNaturalness($data['naturalness']);
        $ingredient->setUsages($data['usages']);
        $ingredient->setSafety($data['safety']);
        
        $this->entityManager->persist($ingredient);

        $this->entityManager->flush();
        
        return new JsonResponse([
            'success' => true, 
            'message' => 'Ингредиент успешно создан.',
            'id' => $ingredient->getId()
        ], 201);
    }

    #[Route('/ingredients/{id}', name: 'update_ingredient', methods: ['PUT'])]
    public function updateIngredient(int $id, Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'message' => 'Доступ запрещен.'], 403);
        }
        
        $ingredient = $this->ingredientRepository->find($id);
        
        if (!$ingredient) {
            return new JsonResponse(['success' => false, 'message' => 'Ингредиент не найден.'], 404);
        }
        
        $data = json_decode($request->getContent(), true);
        
        // Обновляем только предоставленные поля
        if (isset($data['traditional_name'])) {
            // Проверяем, не занято ли имя другим ингредиентом
            $existingIngredient = $this->ingredientRepository->findByName($data['traditional_name']);
            if ($existingIngredient && $existingIngredient->getId() !== $ingredient->getId()) {
                return new JsonResponse(['success' => false, 'message' => 'Ингредиент с таким именем уже существует.'], 400);
            }
            
            $ingredient->setTraditionalName($data['traditional_name']);
        }
        
        if (isset($data['latin_name'])) {
            $ingredient->setLatinName($data['latin_name']);
        }
        
        if (isset($data['inci_name'])) {
            $ingredient->setINCINane($data['inci_name']);
        }

        if (isset($data['danger_factor'])) {
            $ingredient->setDangerFactor($data['danger_factor']);
        }

        if (isset($data['naturalness'])) {
            $ingredient->setNaturalness($data['naturalness']);
        }

        if (isset($data['usages'])) {
            $ingredient->setUsages($data['usages']);
        }

        if (isset($data['safety'])) {
            $ingredient->setSafety($data['safety']);
        }

        $this->entityManager->flush();
        
        return new JsonResponse([
            'success' => true, 
            'message' => 'Ингредиент успешно обновлен.',
            'id' => $ingredient->getId()
        ]);
    }

    #[Route('/ingredients/{id}', name: 'delete_ingredient', methods: ['DELETE'])]
    public function deleteIngredient(int $id): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'message' => 'Доступ запрещен.'], 403);
        }
        
        $ingredient = $this->ingredientRepository->find($id);
        
        if (!$ingredient) {
            return new JsonResponse(['success' => false, 'message' => 'Ингредиент не найден.'], 404);
        }

        $this->entityManager->remove($ingredient);
        $this->entityManager->flush();
        
        return new JsonResponse(['success' => true, 'message' => 'Ингредиент успешно удален.']);
    }
} 