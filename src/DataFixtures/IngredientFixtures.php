<?php

namespace App\DataFixtures;

use App\Entity\Ingredient;
use App\Entity\IngredientSynonym;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class IngredientFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Создаем ингредиенты
        $ingredients = [
            [
                'name' => 'Aqua',
                'safety' => 'safe',
                'description' => 'Основа большинства косметических средств',
                'synonyms' => ['Water', 'H2O', 'Вода', 'Eau']
            ],
            [
                'name' => 'Glycerin',
                'safety' => 'safe',
                'description' => 'Удерживает влагу в коже',
                'synonyms' => ['Глицерин', 'Glycerol', 'Глицерол']
            ],
            [
                'name' => 'Parfum',
                'safety' => 'caution',
                'description' => 'Может вызывать аллергические реакции',
                'synonyms' => ['Fragrance', 'Отдушка', 'Аромат']
            ],
            [
                'name' => 'Methylchloroisothiazolinone',
                'safety' => 'danger',
                'description' => 'Консервант, может вызывать аллергические реакции',
                'synonyms' => ['MCI', 'Метилхлоризотиазолинон']
            ]
        ];
        
        foreach ($ingredients as $ingredientData) {
            $ingredient = new Ingredient();
            $ingredient->setName($ingredientData['name']);
            $ingredient->setSafetyLevel($ingredientData['safety']);
            $ingredient->setDescription($ingredientData['description']);
            
            $manager->persist($ingredient);
            
            // Создаем синонимы для ингредиента
            foreach ($ingredientData['synonyms'] as $synonymName) {
                $synonym = new IngredientSynonym();
                $synonym->setName($synonymName);
                $synonym->setIngredient($ingredient);
                
                $manager->persist($synonym);
            }
        }

        $manager->flush();
    }
} 