<?php

namespace App\Command;

use App\Entity\Ingredient;
use App\Entity\IngredientSynonym;
use App\Repository\IngredientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-ingredients.csv',
    description: 'Импорт ингредиентов и их синонимов из CSV-файла',
)]
class ImportIngredientsCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private IngredientRepository $ingredientRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        IngredientRepository $ingredientRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->ingredientRepository = $ingredientRepository;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Путь к CSV-файлу с ингредиентами')
            ->addArgument('delimiter', InputArgument::OPTIONAL, 'Разделитель полей в CSV (по умолчанию: ";")', ';')
            ->addArgument('enclosure', InputArgument::OPTIONAL, 'Символ обрамления в CSV (по умолчанию: ")"', '"')
            ->addArgument('escape', InputArgument::OPTIONAL, 'Символ экранирования в CSV (по умолчанию: "\")', '\\');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('file');
        $delimiter = $input->getArgument('delimiter');
        $enclosure = $input->getArgument('enclosure');
        $escape = $input->getArgument('escape');

        if (!file_exists($filePath)) {
            $io->error('Файл не найден: ' . $filePath);
            return Command::FAILURE;
        }

        $io->title('Импорт ингредиентов из CSV-файла');
        $io->text('Используется файл: ' . $filePath);

        // Открываем CSV файл
        $file = fopen($filePath, 'r');
        if (!$file) {
            $io->error('Не удалось открыть файл: ' . $filePath);
            return Command::FAILURE;
        }

        // Получаем заголовки
        $headers = fgetcsv($file, 0, $delimiter, $enclosure, $escape);
        if (!$headers) {
            $io->error('Файл пуст или не содержит заголовков.');
            fclose($file);
            return Command::FAILURE;
        }

        // Проверяем наличие необходимых полей в заголовке
        $requiredFields = ['traditional_name', 'latin_name', 'INCI_name', 'danger_factor','naturalness','usage','safety'];
        $missingFields = array_diff($requiredFields, $headers);
        if (!empty($missingFields)) {
            $io->error('В файле отсутствуют следующие обязательные поля: ' . implode(', ', $missingFields));
            fclose($file);
            return Command::FAILURE;
        }

        // Получаем индексы полей
        $traditionalNameIndex = array_search('traditional_name', $headers);
        $latinNameIndex = array_search('latin_name', $headers);
        $INCINameIndex = array_search('INCI_name', $headers);
        $dangerFactorIndex = array_search('danger_factor', $headers);
        $naturalnessIndex = array_search('naturalness', $headers);
        $usageIndex = array_search('usage', $headers);
        $safetyIndex = array_search('safety', $headers);

        $importedCount = 0;
        $updatedCount = 0;
        $synonymsCount = 0;
        $errorCount = 0;

        $io->progressStart(); // Начинаем прогресс-бар

        // Начинаем транзакцию
        $this->entityManager->beginTransaction();

        try {
            // Читаем и обрабатываем данные
            while (($row = fgetcsv($file, 0, $delimiter, $enclosure, $escape)) !== false) {
                $traditionalName = $row[$traditionalNameIndex] ?? '';
                $latinName = $row[$latinNameIndex] ?? '';
                $INCIName = $row[$INCINameIndex] ?? '';
                $dangerFactor = $row[$dangerFactorIndex] ?? '';
                $naturalness = $row[$naturalnessIndex] ?? '';
                $usage = $row[$usageIndex] ?? '';
                $safety = $row[$safetyIndex] ?? '';

                // Пропускаем строки с пустым именем
                if (empty($traditionalName) and empty($latinName) and empty($INCIName)) {
                    $io->warning('Пропущена строка с пустым именем ингредиента');
                    $errorCount++;
                    continue;
                }

                // Проверяем уровень безопасности
                if (!in_array($dangerFactor, ['Низкий', 'Средний', 'Высокий'])) {
                    $io->warning("Некорректный уровень безопасности '{$dangerFactor}' для ингредиента '{$traditionalName}'. Используем 'unknown'.");
                    $dangerFactor = 'unknown';
                }

                // Ищем существующий ингредиент
                $ingredient = $this->ingredientRepository->findByName($traditionalName);
                $isNew = false;

                if (!$ingredient) {
                    $ingredient = new Ingredient();
                    $ingredient->setTraditionalName($traditionalName);
                    $isNew = true;
                }

                $ingredient->setLatinName($latinName);
                $ingredient->setINCIName($INCIName);
                $ingredient->setDangerFactor($dangerFactor);
                $ingredient->setNaturalness($naturalness);
                $ingredient->setUsages($usage);
                $ingredient->setSafety($safety);

                $this->entityManager->persist($ingredient);
                
                // Если это новый ингредиент, сразу сохраняем его для получения ID
                if ($isNew) {
                    $this->entityManager->flush();
                    $importedCount++;
                } else {
                    $updatedCount++;
                }

                /** Обрабатываем синонимы
                if (!empty($synonymsStr)) {
                    $synonyms = array_map('trim', explode(',', $synonymsStr));
                    
                    // Удаляем существующие синонимы, если ингредиент уже был в базе
                    if (!$isNew) {
                        foreach ($ingredient->getSynonyms() as $existingSynonym) {
                            $this->entityManager->remove($existingSynonym);
                        }
                        $this->entityManager->flush();
                    }
                    
                    // Добавляем новые синонимы
                    foreach ($synonyms as $synonymName) {
                        if (empty($synonymName)) continue;
                        
                        $synonym = new IngredientSynonym();
                        $synonym->setName($synonymName);
                        $synonym->setIngredient($ingredient);
                        
                        $this->entityManager->persist($synonym);
                        $synonymsCount++;
                    }
                }
                 */

                $io->progressAdvance(); // Обновляем прогресс-бар
            }

            // Сохраняем все изменения
            $this->entityManager->flush();
            $this->entityManager->commit();

            fclose($file);
            $io->progressFinish(); // Завершаем прогресс-бар

            $io->success([
                'Импорт успешно завершен!',
                "Добавлено новых ингредиентов: {$importedCount}",
                "Обновлено существующих ингредиентов: {$updatedCount}",
                "Добавлено синонимов: {$synonymsCount}",
                "Ошибок: {$errorCount}"
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            // В случае ошибки отменяем транзакцию
            $this->entityManager->rollback();
            
            fclose($file);
            $io->progressFinish(); // Завершаем прогресс-бар
            
            $io->error([
                'Ошибка при импорте: ' . $e->getMessage(),
                'Все изменения отменены.'
            ]);
            
            return Command::FAILURE;
        }
    }
} 