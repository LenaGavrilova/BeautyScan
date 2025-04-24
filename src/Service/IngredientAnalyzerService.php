<?php

namespace App\Service;

use App\Repository\IngredientRepository;
use App\Repository\IngredientSynonymRepository;
use Doctrine\ORM\EntityManagerInterface;

class IngredientAnalyzerService
{
    private $entityManager;
    private $ingredientRepository;
    private $ingredientSynonymRepository;
    private array $knownIngredients = [];
    private array $synonyms = [];

    public function __construct(
        EntityManagerInterface $entityManager,
        IngredientRepository $ingredientRepository,
        IngredientSynonymRepository $ingredientSynonymRepository
    ) {
        $this->entityManager = $entityManager;
        $this->ingredientRepository = $ingredientRepository;
        $this->ingredientSynonymRepository = $ingredientSynonymRepository;
        
        // Загружаем ингредиенты из базы данных
        $this->loadIngredients();
        $this->loadSynonyms();
    }
    
    /**
     * Загружает ингредиенты из базы данных
     */
    private function loadIngredients(): void
    {
        $ingredients = $this->ingredientRepository->findAll();
        
        foreach ($ingredients as $ingredient) {
            $key = strtolower($ingredient->getName());
            $this->knownIngredients[$key] = [
                'name' => $ingredient->getName(),
                'safety' => $ingredient->getSafetyLevel(),
                'description' => $ingredient->getDescription()
            ];
        }
        
        // Если в базе нет данных, используем тестовые данные
        if (empty($this->knownIngredients)) {
            $this->loadDefaultIngredients();
        }
    }
    
    /**
     * Загружает синонимы ингредиентов из базы данных
     */
    private function loadSynonyms(): void
    {
        $synonyms = $this->ingredientSynonymRepository->findAll();
        
        foreach ($synonyms as $synonym) {
            $key = strtolower($synonym->getName());
            $ingredientKey = strtolower($synonym->getIngredient()->getName());
            $this->synonyms[$key] = $ingredientKey;
        }
        
        // Если в базе нет данных, используем тестовые данные
        if (empty($this->synonyms)) {
            $this->loadDefaultSynonyms();
        }
    }
    
    /**
     * Загружает тестовые данные ингредиентов (используется, если база данных пуста)
     */
    private function loadDefaultIngredients(): void
    {
        $this->knownIngredients = [
            'aqua' => [
                'name' => 'Aqua',
                'safety' => 'safe',
                'description' => 'Основа большинства косметических средств'
            ],
            'sorbitol' => [
                'name' => 'Sorbitol',
                'safety' => 'safe',
                'description' => 'Увлажняющий компонент'
            ],
            'algae extract' => [
                'name' => 'Algae Extract',
                'safety' => 'safe',
                'description' => 'Увлажняет и питает кожу'
            ],
            'hydroxyethyl urea' => [
                'name' => 'Hydroxyethyl Urea',
                'safety' => 'safe',
                'description' => 'Увлажняющий компонент'
            ],
            'glycerin' => [
                'name' => 'Glycerin',
                'safety' => 'safe',
                'description' => 'Удерживает влагу в коже'
            ],
            'betaine' => [
                'name' => 'Betaine',
                'safety' => 'safe',
                'description' => 'Снижает раздражение'
            ],
            'cocos nucifera fruit extract' => [
                'name' => 'Cocos Nucifera Fruit Extract',
                'safety' => 'safe',
                'description' => 'Питает и смягчает кожу'
            ],
            'almond oil glycereth-8 esters' => [
                'name' => 'Almond Oil Glycereth-8 Esters',
                'safety' => 'safe',
                'description' => 'Смягчает кожу'
            ],
            'polysorbate 20' => [
                'name' => 'Polysorbate 20',
                'safety' => 'caution',
                'description' => 'Эмульгатор, помогает смешивать масла и воду'
            ],
            'parfum' => [
                'name' => 'Parfum',
                'safety' => 'caution',
                'description' => 'Может вызывать аллергические реакции'
            ],
            'methylchloroisothiazolinone' => [
                'name' => 'Methylchloroisothiazolinone',
                'safety' => 'danger',
                'description' => 'Консервант, может вызывать аллергические реакции'
            ],
            'methylisothiazolinone' => [
                'name' => 'Methylisothiazolinone',
                'safety' => 'danger',
                'description' => 'Консервант, может вызывать аллергические реакции'
            ],
            'citric acid' => [
                'name' => 'Citric Acid',
                'safety' => 'safe',
                'description' => 'Регулятор pH'
            ],
            'hexyl cinnamal' => [
                'name' => 'Hexyl Cinnamal',
                'safety' => 'caution',
                'description' => 'Ароматизатор, может вызывать аллергические реакции'
            ],
            'coumarin' => [
                'name' => 'Coumarin',
                'safety' => 'caution',
                'description' => 'Ароматизатор, может вызывать аллергические реакции'
            ]
        ];
    }
    
    /**
     * Загружает тестовые данные синонимов (используется, если база данных пуста)
     */
    private function loadDefaultSynonyms(): void
    {
        $this->synonyms = [
            'water' => 'aqua',
            'h2o' => 'aqua',
            'вода' => 'aqua',
            'глицерин' => 'glycerin',
            'кокосовое масло' => 'cocos nucifera fruit extract',
            'миндальное масло' => 'almond oil glycereth-8 esters',
            'лимонная кислота' => 'citric acid',
            'отдушка' => 'parfum',
            'аромат' => 'parfum'
        ];
    }

    /**
     * Анализирует список ингредиентов и возвращает результаты анализа
     * 
     * @param string $ingredientsText Текст с ингредиентами, разделенными запятыми
     * @return array Результаты анализа
     */
    public function analyzeIngredients(string $ingredientsText): array
    {
        // Разбиваем текст на отдельные ингредиенты
        $ingredientsList = array_map('trim', explode(',', $ingredientsText));
        
        $analyzedIngredients = [];
        $safeCount = 0;
        $cautionCount = 0;
        $dangerCount = 0;
        $unknownCount = 0;
        
        // Анализируем каждый ингредиент
        foreach ($ingredientsList as $position => $ingredient) {
            if (empty($ingredient)) {
                continue;
            }
            
            $ingredientLower = strtolower($ingredient);
            
            // Проверяем, есть ли ингредиент в списке известных
            if (isset($this->knownIngredients[$ingredientLower])) {
                $ingredientData = $this->knownIngredients[$ingredientLower];
                $analyzedIngredients[] = [
                    'position' => $position + 1,
                    'name' => $ingredientData['name'],
                    'safety' => $ingredientData['safety'],
                    'description' => $ingredientData['description'],
                    'unknown' => false
                ];
                
                // Увеличиваем счетчик соответствующей категории безопасности
                if ($ingredientData['safety'] === 'safe') {
                    $safeCount++;
                } elseif ($ingredientData['safety'] === 'caution') {
                    $cautionCount++;
                } elseif ($ingredientData['safety'] === 'danger') {
                    $dangerCount++;
                }
            } 
            // Проверяем, есть ли ингредиент в списке синонимов
            elseif (isset($this->synonyms[$ingredientLower])) {
                $knownIngredientKey = $this->synonyms[$ingredientLower];
                $ingredientData = $this->knownIngredients[$knownIngredientKey];
                $analyzedIngredients[] = [
                    'position' => $position + 1,
                    'name' => $ingredientData['name'],
                    'safety' => $ingredientData['safety'],
                    'description' => $ingredientData['description'],
                    'unknown' => false
                ];
                
                // Увеличиваем счетчик соответствующей категории безопасности
                if ($ingredientData['safety'] === 'safe') {
                    $safeCount++;
                } elseif ($ingredientData['safety'] === 'caution') {
                    $cautionCount++;
                } elseif ($ingredientData['safety'] === 'danger') {
                    $dangerCount++;
                }
            } 
            // Если ингредиент не найден, добавляем его как неизвестный
            else {
                $analyzedIngredients[] = [
                    'position' => $position + 1,
                    'name' => ucfirst($ingredient),
                    'safety' => 'unknown',
                    'description' => 'Неизвестный ингредиент. Информация отсутствует.',
                    'unknown' => true
                ];
                $unknownCount++;
            }
        }
        
        // Рассчитываем общую оценку безопасности
        $totalIngredients = $safeCount + $cautionCount + $dangerCount + $unknownCount;
        $safetyRating = 0;
        $safetyPercentages = [
            'safe' => 0,
            'caution' => 0,
            'danger' => 0,
            'unknown' => 0
        ];
        
        if ($totalIngredients > 0) {
            // Рассчитываем проценты для каждой категории
            $safetyPercentages['safe'] = round(($safeCount / $totalIngredients) * 100);
            $safetyPercentages['caution'] = round(($cautionCount / $totalIngredients) * 100);
            $safetyPercentages['danger'] = round(($dangerCount / $totalIngredients) * 100);
            $safetyPercentages['unknown'] = round(($unknownCount / $totalIngredients) * 100);
            
            // Рассчитываем общую оценку безопасности (от 1 до 5)
            if ($unknownCount === $totalIngredients) {
                $safetyRating = 0; // Если все ингредиенты неизвестны, оценка 0
            } else if ($totalIngredients - $unknownCount > 0) {
                $safetyRating = round(($safeCount * 5 + $cautionCount * 3 + $dangerCount * 1) / ($totalIngredients - $unknownCount), 1);
            } else {
                $safetyRating = 0; // Защита от деления на ноль
            }
        }
        
        // Формируем рекомендацию на основе анализа
        $recommendation = $this->generateRecommendation($safetyPercentages, $unknownCount, $totalIngredients);
        
        return [
            'ingredients' => $analyzedIngredients,
            'safety_rating' => $safetyRating,
            'safety_percentages' => $safetyPercentages,
            'recommendation' => $recommendation,
            'has_unknown_ingredients' => $unknownCount > 0,
            'unknown_count' => $unknownCount
        ];
    }
    
    /**
     * Генерирует рекомендацию на основе результатов анализа
     * 
     * @param array $safetyPercentages Проценты безопасности по категориям
     * @param int $unknownCount Количество неизвестных ингредиентов
     * @param int $totalIngredients Общее количество ингредиентов
     * @return string Рекомендация
     */
    private function generateRecommendation(array $safetyPercentages, int $unknownCount, int $totalIngredients): string
    {
        if ($totalIngredients === 0) {
            return 'Не удалось проанализировать состав. Пожалуйста, проверьте введенный текст.';
        }
        
        if ($unknownCount === $totalIngredients) {
            return 'Все ингредиенты в составе неизвестны. Рекомендуем проконсультироваться со специалистом перед использованием.';
        }
        
        if ($unknownCount > 0) {
            $unknownPercent = round(($unknownCount / $totalIngredients) * 100);
            if ($unknownPercent > 50) {
                return 'Большинство ингредиентов в составе неизвестны. Рекомендуем проконсультироваться со специалистом перед использованием.';
            }
        }
        
        if ($safetyPercentages['danger'] > 30) {
            return 'Состав содержит значительное количество потенциально опасных ингредиентов. Рекомендуем избегать использования или проконсультироваться со специалистом.';
        }
        
        if ($safetyPercentages['danger'] > 15) {
            return 'Состав в целом безопасен, но содержит консерванты, которые могут вызывать аллергические реакции у людей с чувствительной кожей.';
        }
        
        if ($safetyPercentages['caution'] > 50) {
            return 'Состав содержит значительное количество ингредиентов, которые могут вызывать реакции у людей с чувствительной кожей. Рекомендуем проявлять осторожность.';
        }
        
        if ($safetyPercentages['caution'] > 30) {
            return 'Состав преимущественно безопасен, но содержит компоненты, которые могут вызывать реакции у людей с очень чувствительной кожей.';
        }
        
        return 'Состав преимущественно безопасен и подходит для большинства типов кожи.';
    }
} 