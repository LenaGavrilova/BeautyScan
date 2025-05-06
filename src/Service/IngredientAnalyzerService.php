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

        $this->loadIngredients();
    }

    private function loadIngredients(): void
    {
        $ingredients = $this->ingredientRepository->findAll();

        foreach ($ingredients as $ingredient) {
            $names = [
                $ingredient->getTraditionalName(),
                $ingredient->getLatinName(),
                $ingredient->getINCIName()
            ];

            $ingredientData = [
                'traditional_name' => $ingredient->getTraditionalName(),
                'latin_name' => $ingredient->getLatinName(),
                'inci_name' => $ingredient->getINCIName(),
                'danger_factor' => $ingredient->getDangerFactor(),
                'naturalness' => $ingredient->getNaturalness(),
                'usages' => $ingredient->getUsages(),
                'safety' => $ingredient->getSafety()
            ];

            foreach (array_filter($names) as $name) {
                // Сохраняем оригинальное название
                $this->knownIngredients[$name] = $ingredientData;

                // Сохраняем lowercase версию
                $lowerName = mb_strtolower($name, 'UTF-8');
                if (!isset($this->knownIngredients[$lowerName])) {
                    $this->knownIngredients[$lowerName] = $ingredientData;
                }

                // Сохраняем версию с первой заглавной буквой
                $titleName = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
                if (!isset($this->knownIngredients[$titleName])) {
                    $this->knownIngredients[$titleName] = $ingredientData;
                }
            }
        }
    }
    public function analyzeIngredients(string $ingredientsText): array
    {
        $ingredientsList = array_map('trim', explode(',', $ingredientsText));

        $analyzedIngredients = [];
        $safeCount = 0;
        $cautionCount = 0;
        $dangerCount = 0;
        $unknownCount = 0;

        foreach ($ingredientsList as $position => $ingredient) {
            if (empty($ingredient)) {
                continue;
            }

            // Ищем в разных вариантах регистра
            $ingredientData = $this->knownIngredients[$ingredient]
                ?? $this->knownIngredients[mb_strtolower($ingredient, 'UTF-8')]
                ?? $this->knownIngredients[mb_convert_case($ingredient, MB_CASE_TITLE, 'UTF-8')]
                ?? $this->findSimilarIngredient($ingredient); // Добавляем поиск похожих

            if ($ingredientData) {
                $formattedName = $this->formatOutputName($ingredientData['traditional_name']);

                $analyzedIngredients[] = [
                    'position' => $position + 1,
                    'traditional_name' => $formattedName,
                    'latin_name' => $this->formatOutputName($ingredientData['latin_name']),
                    'inci_name' => $this->formatOutputName($ingredientData['inci_name']),
                    'danger_factor' => $ingredientData['danger_factor'],
                    'naturalness' => $ingredientData['naturalness'],
                    'usages' => $ingredientData['usages'],
                    'safety' => $ingredientData['safety'],
                    'unknown' => false,
                    'original_input' => $ingredient
                ];

                // Счетчики безопасности
                switch ($ingredientData['danger_factor']) {
                    case 'Низкий': $safeCount++; break;
                    case 'Средний': $cautionCount++; break;
                    case 'Высокий': $dangerCount++; break;
                }
            } else {
                $formattedName = $this->formatOutputName($ingredient);

                $analyzedIngredients[] = [
                    'position' => $position + 1,
                    'traditional_name' => $formattedName,
                    'latin_name' => $formattedName,
                    'inci_name' => $formattedName,
                    'danger_factor' => 'unknown',
                    'naturalness' => 'unknown',
                    'usages' => 'Неизвестный ингредиент. Информация отсутствует.',
                    'safety' => 'unknown',
                    'unknown' => true,
                    'original_input' => $ingredient
                ];
                $unknownCount++;
            }
        }

        // Расчет показателей безопасности
        $totalIngredients = count($ingredientsList);
        $safetyRating = $this->calculateSafetyRating($analyzedIngredients);
        $safetyPercentages = $this->calculateSafetyPercentages($safeCount, $cautionCount, $dangerCount, $unknownCount, $totalIngredients);

        return [
            'ingredients' => $analyzedIngredients,
            'safety_rating' => $safetyRating,
            'safety_percentages' => $safetyPercentages,
            'recommendation' => $this->generateRecommendation($safetyPercentages, $unknownCount, $totalIngredients),
            'has_unknown_ingredients' => $unknownCount > 0,
            'unknown_count' => $unknownCount
        ];
    }

    private function formatOutputName(string $name): string
    {
        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }

    private function calculateSafetyRating(array $analyzedIngredients): float
    {
        $total = count($analyzedIngredients);
        if ($total === 0) {
            return 0;
        }

        $weightedSum = 0;
        $totalWeights = 0;
        $unknownCount = 0;
        $unknownWeight = 3; // Вес для неизвестных ингредиентов (можно настроить)

        foreach ($analyzedIngredients as $ingredient) {
            $position = $ingredient['position'];
            $weight = 1 + (1 / sqrt($position));

            if ($ingredient['unknown']) {
                // Учитываем неизвестные ингредиенты с понижающим коэффициентом
                $unknownCount++;
                $weightedSum += $weight * $unknownWeight;
            } else {
                switch ($ingredient['danger_factor']) {
                    case 'Низкий':
                        $weightedSum += $weight * 5;
                        break;
                    case 'Средний':
                        $weightedSum += $weight * 3;
                        break;
                    case 'Высокий':
                        $weightedSum += $weight * 1;
                        break;
                }
            }
            $totalWeights += $weight;
        }

        if ($totalWeights === 0) {
            return 0;
        }

        // Корректировка оценки при наличии неизвестных ингредиентов
        $baseRating = $weightedSum / $totalWeights;
        $unknownPenalty = min($unknownCount / $total, 1) * 2; // Штраф до 2 пунктов

        $finalRating = max(0, $baseRating - $unknownPenalty);
        return round($finalRating, 1);
    }

    private function calculateSafetyPercentages(int $safe, int $caution, int $danger, int $unknown, int $total): array
    {
        if ($total === 0) {
            return ['safe' => 0, 'caution' => 0, 'danger' => 0, 'unknown' => 0];
        }

        // Сначала считаем проценты без округления
        $percentages = [
            'safe' => ($safe / $total) * 100,
            'caution' => ($caution / $total) * 100,
            'danger' => ($danger / $total) * 100,
            'unknown' => ($unknown / $total) * 100
        ];

        // Округляем все значения
        $rounded = array_map('round', $percentages);

        // Проверяем сумму округленных значений
        $sum = array_sum($rounded);

        // Корректируем разницу (если сумма не 100)
        if ($sum != 100) {
            // Находим категорию с наибольшей дробной частью
            $maxFraction = 0;
            $adjustCategory = null;

            foreach ($percentages as $category => $value) {
                $fraction = $value - floor($value);
                if ($fraction > $maxFraction) {
                    $maxFraction = $fraction;
                    $adjustCategory = $category;
                }
            }

            // Корректируем выбранную категорию
            if ($adjustCategory !== null) {
                $rounded[$adjustCategory] += (100 - $sum);
            }
        }

        return $rounded;
    }

    private function generateRecommendation(array $percentages, int $unknownCount, int $total): string
    {
        if ($total === 0) {
            return 'Не удалось проанализировать состав.';
        }

        // Рассчитываем процент известных ингредиентов
        $knownPercent = 100 - ($unknownCount / $total * 100);

        // 1. Проверка на неизвестные компоненты (по стандартам EU Cosmetics Regulation)
        if ($unknownCount === $total) {
            return 'Внимание! Все компоненты состава не идентифицированы. По требованиям EU Regulation 1223/2009, продукт не может быть признан безопасным без полной декларации состава.';
        }

        if ($knownPercent < 70) {
            return 'Высокий риск! Более 30% состава не идентифицировано. Согласно исследованиям EWG, такие продукты могут содержать неучтённые аллергены или токсичные вещества.';
        }

        // 2. Оценка опасных ингредиентов (по классификации FDA)
        $dangerThresholds = [
            'high' => 15,  // Более 15% высокоопасных - критический уровень
            'medium' => 5   // Более 5% - требует предупреждения
        ];

        if ($percentages['danger'] > $dangerThresholds['high']) {
            return 'Опасный состав! Содержит ' . $percentages['danger'] . '% компонентов с высоким риском (по классификации FDA). Может вызывать раздражение, аллергические реакции или долгосрочные негативные эффекты.';
        }

        if ($percentages['danger'] > $dangerThresholds['medium']) {
            $msg = 'Осторожно! Содержит ' . $percentages['danger'] . '% потенциально опасных компонентов.';
            $msg .= ($percentages['danger'] > 10) ? ' Рекомендуется ограниченное применение.' : ' Подходит для краткосрочного использования.';
            return $msg;
        }

        // 3. Оценка ингредиентов средней опасности (по данным EWG Skin Deep)
        if ($percentages['caution'] > 40) {
            return 'Состав требует внимания! ' . $percentages['caution'] . '% компонентов могут вызывать раздражение у чувствительной кожи (по данным EWG).';
        }

        if ($percentages['caution'] > 20) {
            return 'Умеренный риск. ' . $percentages['caution'] . '% ингредиентов средней опасности. Проверьте индивидуальную переносимость.';
        }

        // 4. Оценка безопасных ингредиентов
        $safetyScore = $percentages['safe'] - ($percentages['danger'] * 2) - $percentages['caution'];

        if ($safetyScore > 80) {
            return 'Отличный состав! Более ' . $percentages['safe'] . '% безопасных компонентов. Соответствует стандартам ECOCERT и COSMOS Organic.';
        }

        if ($safetyScore > 60) {
            return 'Хороший состав. Преобладают безопасные ингредиенты (' . $percentages['safe'] . '%). Подходит для регулярного использования.';
        }

        // 5. Комбинированная оценка
        if (($percentages['danger'] < 5) && ($percentages['caution'] < 15)) {
            return 'Приемлемый состав. Незначительное содержание потенциально проблемных компонентов.';
        }

        return 'Нейтральный состав. Рекомендуется провести patch-тест перед полным применением.';
    }

    private function levenshteinDistance(string $s1, string $s2): int
    {
        $s1 = preg_split('//u', $s1, -1, PREG_SPLIT_NO_EMPTY);
        $s2 = preg_split('//u', $s2, -1, PREG_SPLIT_NO_EMPTY);

        $len1 = count($s1);
        $len2 = count($s2);

        if ($len1 == 0) return $len2;
        if ($len2 == 0) return $len1;

        $prevRow = range(0, $len2);

        for ($i = 0; $i < $len1; $i++) {
            $currentRow = [];
            $currentRow[0] = $i + 1;

            for ($j = 0; $j < $len2; $j++) {
                $cost = ($s1[$i] === $s2[$j]) ? 0 : 1;
                $currentRow[$j + 1] = min(
                    $prevRow[$j + 1] + 1,
                    $currentRow[$j] + 1,
                    $prevRow[$j] + $cost
                );
            }

            $prevRow = $currentRow;
        }

        return $prevRow[$len2];
    }

    private function findSimilarIngredient(string $searchTerm): ?array
    {
        $searchTerm = mb_strtolower(trim($searchTerm), 'UTF-8');

        // 1. Сначала проверяем точные совпадения (быстро)
        foreach ($this->knownIngredients as $name => $ingredientData) {
            if (mb_strtolower($name, 'UTF-8') === $searchTerm) {
                return $ingredientData;
            }
        }

        // 2. Проверяем подстроки (довольно быстро)
        foreach ($this->knownIngredients as $name => $ingredientData) {
            if (mb_stripos($name, $searchTerm) !== false) {
                return $ingredientData;
            }
        }

        // 3. Только если не нашли - используем Левенштейна для TOP 100 похожих по длине
        $searchLength = mb_strlen($searchTerm);
        $candidates = [];

        foreach ($this->knownIngredients as $name => $ingredientData) {
            $nameLength = mb_strlen($name);
            if (abs($nameLength - $searchLength) <= 3) { // Сравниваем только с похожими по длине
                $candidates[$name] = $ingredientData;
                if (count($candidates) >= 100) break; // Лимит кандидатов
            }
        }

        $bestMatch = null;
        $minDistance = PHP_INT_MAX;

        foreach ($candidates as $name => $ingredientData) {
            $distance = $this->levenshteinDistance($searchTerm, mb_strtolower($name, 'UTF-8'));
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $bestMatch = $ingredientData;
            }
        }

        return $minDistance <= 2 ? $bestMatch : null;
    }
}