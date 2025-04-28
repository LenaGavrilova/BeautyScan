<?php

namespace App\Controller;

use App\Entity\Ingredient;
use App\Entity\IngredientSynonym;
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

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security,
        IngredientRepository $ingredientRepository,
        IngredientSynonymRepository $ingredientSynonymRepository
    ) {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->ingredientRepository = $ingredientRepository;
        $this->ingredientSynonymRepository = $ingredientSynonymRepository;
    }

    #[Route('/ingredients.csv', name: 'get_ingredients', methods: ['GET'])]
    public function getIngredients(Request $request): JsonResponse
    {
        $safetyLevel = $request->query->get('safety_level');
        
        if ($safetyLevel) {
            $ingredients = $this->ingredientRepository->findBySafetyLevel($safetyLevel);
        } else {
            $ingredients = $this->ingredientRepository->findAll();
        }
        
        $data = [];
        foreach ($ingredients as $ingredient) {
            $data[] = [
                'id' => $ingredient->getId(),
                'name' => $ingredient->getName(),
                'safety_level' => $ingredient->getSafetyLevel(),
                'description' => $ingredient->getDescription()
            ];
        }
        
        return new JsonResponse($data);
    }

    #[Route('/ingredients.csv/{id}', name: 'get_ingredient', methods: ['GET'])]
    public function getIngredient(int $id): JsonResponse
    {
        $ingredient = $this->ingredientRepository->find($id);
        
        if (!$ingredient) {
            return new JsonResponse(['success' => false, 'message' => 'Ингредиент не найден.'], 404);
        }
        
        $synonyms = [];
        foreach ($ingredient->getSynonyms() as $synonym) {
            $synonyms[] = [
                'id' => $synonym->getId(),
                'name' => $synonym->getName()
            ];
        }
        
        $data = [
            'id' => $ingredient->getId(),
            'name' => $ingredient->getName(),
            'safety_level' => $ingredient->getSafetyLevel(),
            'description' => $ingredient->getDescription(),
            'synonyms' => $synonyms
        ];
        
        return new JsonResponse($data);
    }

    #[Route('/ingredients.csv', name: 'create_ingredient', methods: ['POST'])]
    public function createIngredient(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'message' => 'Доступ запрещен.'], 403);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['name']) || !isset($data['safety_level']) || !isset($data['description'])) {
            return new JsonResponse(['success' => false, 'message' => 'Неполные данные для создания ингредиента.'], 400);
        }
        
        // Проверяем, существует ли уже ингредиент с таким именем
        $existingIngredient = $this->ingredientRepository->findByName($data['name']);
        if ($existingIngredient) {
            return new JsonResponse(['success' => false, 'message' => 'Ингредиент с таким именем уже существует.'], 400);
        }
        
        $ingredient = new Ingredient();
        $ingredient->setName($data['name']);
        $ingredient->setSafetyLevel($data['safety_level']);
        $ingredient->setDescription($data['description']);
        
        $this->entityManager->persist($ingredient);
        
        // Обрабатываем синонимы, если они есть
        if (isset($data['synonyms']) && is_array($data['synonyms'])) {
            foreach ($data['synonyms'] as $synonymName) {
                $existingSynonym = $this->ingredientSynonymRepository->findByName($synonymName);
                if ($existingSynonym) {
                    continue; // Пропускаем дублирующиеся синонимы
                }
                
                $synonym = new IngredientSynonym();
                $synonym->setName($synonymName);
                $synonym->setIngredient($ingredient);
                
                $this->entityManager->persist($synonym);
            }
        }
        
        $this->entityManager->flush();
        
        return new JsonResponse([
            'success' => true, 
            'message' => 'Ингредиент успешно создан.',
            'id' => $ingredient->getId()
        ], 201);
    }

    #[Route('/ingredients.csv/{id}', name: 'update_ingredient', methods: ['PUT'])]
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
        if (isset($data['name'])) {
            // Проверяем, не занято ли имя другим ингредиентом
            $existingIngredient = $this->ingredientRepository->findByName($data['name']);
            if ($existingIngredient && $existingIngredient->getId() !== $ingredient->getId()) {
                return new JsonResponse(['success' => false, 'message' => 'Ингредиент с таким именем уже существует.'], 400);
            }
            
            $ingredient->setName($data['name']);
        }
        
        if (isset($data['safety_level'])) {
            $ingredient->setSafetyLevel($data['safety_level']);
        }
        
        if (isset($data['description'])) {
            $ingredient->setDescription($data['description']);
        }
        
        // Обрабатываем синонимы, если они есть
        if (isset($data['synonyms']) && is_array($data['synonyms'])) {
            // Удаляем существующие синонимы
            foreach ($ingredient->getSynonyms() as $existingSynonym) {
                $this->entityManager->remove($existingSynonym);
            }
            
            // Добавляем новые синонимы
            foreach ($data['synonyms'] as $synonymName) {
                $synonym = new IngredientSynonym();
                $synonym->setName($synonymName);
                $synonym->setIngredient($ingredient);
                
                $this->entityManager->persist($synonym);
            }
        }
        
        $this->entityManager->flush();
        
        return new JsonResponse([
            'success' => true, 
            'message' => 'Ингредиент успешно обновлен.',
            'id' => $ingredient->getId()
        ]);
    }

    #[Route('/ingredients.csv/{id}', name: 'delete_ingredient', methods: ['DELETE'])]
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
        
        // Удаляем связанные синонимы
        foreach ($ingredient->getSynonyms() as $synonym) {
            $this->entityManager->remove($synonym);
        }
        
        $this->entityManager->remove($ingredient);
        $this->entityManager->flush();
        
        return new JsonResponse(['success' => true, 'message' => 'Ингредиент успешно удален.']);
    }
} 