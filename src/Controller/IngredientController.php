<?php

namespace App\Controller;

use App\Manager\IngredientManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class IngredientController extends AbstractController
{
    #[Route('/ingredients', name: 'get_ingredients', methods: ['GET'])]
    public function getIngredients(Request $request, IngredientManager $manager): JsonResponse
    {
        $safetyLevel = $request->query->get('danger_factor');
        return new JsonResponse($manager->getAll($safetyLevel));
    }

    #[Route('/ingredients/{id}', name: 'get_ingredient', methods: ['GET'])]
    public function getIngredient(int $id, IngredientManager $manager): JsonResponse
    {
        $ingredient = $manager->getOne($id);
        return $ingredient
            ? new JsonResponse($ingredient)
            : new JsonResponse(['success' => false, 'message' => 'Ингредиент не найден.'], 404);
    }

    #[Route('/ingredients', name: 'create_ingredient', methods: ['POST'])]
    public function createIngredient(Request $request, IngredientManager $manager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'message' => 'Доступ запрещен.'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $result = $manager->create($data);

        return is_array($result)
            ? new JsonResponse(['success' => true, 'message' => 'Ингредиент успешно создан.', 'id' => $result['id']], 201)
            : new JsonResponse(['success' => false, 'message' => $result], 400);
    }

    #[Route('/ingredients/{id}', name: 'update_ingredient', methods: ['PUT'])]
    public function updateIngredient(int $id, Request $request, IngredientManager $manager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'message' => 'Доступ запрещен.'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $result = $manager->update($id, $data);

        return is_array($result)
            ? new JsonResponse(['success' => true, 'message' => 'Ингредиент успешно обновлен.', 'id' => $result['id']])
            : new JsonResponse(['success' => false, 'message' => $result], 400);
    }

    #[Route('/ingredients/{id}', name: 'delete_ingredient', methods: ['DELETE'])]
    public function deleteIngredient(int $id, IngredientManager $manager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'message' => 'Доступ запрещен.'], 403);
        }

        return $manager->delete($id)
            ? new JsonResponse(['success' => true, 'message' => 'Ингредиент успешно удален.'])
            : new JsonResponse(['success' => false, 'message' => 'Ингредиент не найден.'], 404);
    }
}
