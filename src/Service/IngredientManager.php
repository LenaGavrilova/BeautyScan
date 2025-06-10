<?php

namespace App\Manager;

use App\Entity\Ingredient;
use App\Repository\IngredientCategoryRepository;
use App\Repository\IngredientEffectivenessRepository;
use App\Repository\IngredientRepository;
use Doctrine\ORM\EntityManagerInterface;

class IngredientManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private IngredientRepository $ingredientRepository,
        private IngredientCategoryRepository $ingredientCategoryRepository,
        private IngredientEffectivenessRepository $ingredientEffectivenessRepository
    ) {}

    public function getAll(?string $safetyLevel): array
    {
        $ingredients = $safetyLevel
            ? $this->ingredientRepository->findBySafetyLevel($safetyLevel)
            : array_reverse($this->ingredientRepository->findAll());

        return array_map(function (Ingredient $ingredient) {
            return $this->formatIngredient($ingredient);
        }, $ingredients);
    }

    public function getOne(int $id): ?array
    {
        $ingredient = $this->ingredientRepository->find($id);
        return $ingredient ? $this->formatIngredient($ingredient) : null;
    }

    public function create(array $data): array|string
    {
        if ($this->ingredientRepository->findByName($data['traditional_name'])) {
            return 'Ингредиент с таким именем уже существует.';
        }

        $ingredient = (new Ingredient())
            ->setTraditionalName($data['traditional_name'])
            ->setLatinName($data['latin_name'])
            ->setINCIName($data['inci_name'])
            ->setDangerFactor($data['danger_factor'])
            ->setNaturalness($data['naturalness'])
            ->setUsages($data['usages'])
            ->setSafety($data['safety']);

        $this->entityManager->persist($ingredient);
        $this->entityManager->flush();

        return ['id' => $ingredient->getId()];
    }

    public function update(int $id, array $data): array|string
    {
        $ingredient = $this->ingredientRepository->find($id);
        if (!$ingredient) {
            return 'Ингредиент не найден.';
        }

        if (isset($data['traditional_name'])) {
            $existing = $this->ingredientRepository->findByName($data['traditional_name']);
            if ($existing && $existing->getId() !== $ingredient->getId()) {
                return 'Ингредиент с таким именем уже существует.';
            }
            $ingredient->setTraditionalName($data['traditional_name']);
        }

        foreach (['latin_name', 'inci_name', 'danger_factor', 'naturalness', 'usages', 'safety'] as $field) {
            $setter = 'set' . str_replace('_', '', ucwords($field, '_'));
            if (isset($data[$field])) {
                $ingredient->$setter($data[$field]);
            }
        }

        $this->entityManager->flush();
        return ['id' => $ingredient->getId()];
    }

    public function delete(int $id): bool
    {
        $ingredient = $this->ingredientRepository->find($id);
        if (!$ingredient) {
            return false;
        }

        $this->entityManager->remove($ingredient);
        $this->entityManager->flush();
        return true;
    }

    private function formatIngredient(Ingredient $ingredient): array
    {
        $formattedName = mb_convert_case($ingredient->getTraditionalName(), MB_CASE_TITLE, 'UTF-8');

        $category = $this->ingredientCategoryRepository->findOneBy(['ingredientName' => $formattedName]);
        $effectiveness = $this->ingredientEffectivenessRepository->findOneBy(['ingredientName' => $formattedName]);

        return [
            'id' => $ingredient->getId(),
            'traditional_name' => $ingredient->getTraditionalName(),
            'latin_name' => $ingredient->getLatinName(),
            'inci_name' => $ingredient->getINCIName(),
            'danger_factor' => $ingredient->getDangerFactor(),
            'naturalness' => $ingredient->getNaturalness(),
            'usages' => $ingredient->getUsages(),
            'safety' => $ingredient->getSafety(),
            'category' => $category?->getCategoryName() ?? '',
            'effectiveness' => $effectiveness?->getEffectivenessName() ?? '',
        ];
    }
}
