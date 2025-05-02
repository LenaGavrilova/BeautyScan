<?php

namespace App\Command;

use App\Entity\Categories;
use App\Repository\CategoriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-categories.csv',
    description: 'Импорт категорий из CSV-файла',
)]
class ImportCategoriesCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private CategoriesRepository $categoriesRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        CategoriesRepository $categoriesRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->categoriesRepository = $categoriesRepository;
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
        $requiredFields = ['name'];
        $missingFields = array_diff($requiredFields, $headers);
        if (!empty($missingFields)) {
            $io->error('В файле отсутствуют следующие обязательные поля: ' . implode(', ', $missingFields));
            fclose($file);
            return Command::FAILURE;
        }

        // Получаем индексы полей
        $nameIndex = array_search('name', $headers);

        $importedCount = 0;
        $updatedCount = 0;
        $errorCount = 0;

        $io->progressStart(); // Начинаем прогресс-бар

        // Начинаем транзакцию
        $this->entityManager->beginTransaction();

        try {
            // Читаем и обрабатываем данные
            while (($row = fgetcsv($file, 0, $delimiter, $enclosure, $escape)) !== false) {
                $name = $row[$nameIndex] ?? '';

                // Пропускаем строки с пустым именем
                if (empty($name)) {
                    $io->warning('Пропущена строка с пустым именем ингредиента');
                    $errorCount++;
                    continue;
                }

                // Ищем существующую категорию
                $category = $this->categoriesRepository->findByName($name);
                $isNew = false;

                if (!$category) {
                    $category = new Categories();
                    $category->setName($name);
                    $isNew = true;
                }

                $this->entityManager->persist($category);

                // Если это новая категория, сразу сохраняем его для получения ID
                if ($isNew) {
                    $this->entityManager->flush();
                    $importedCount++;
                } else {
                    $updatedCount++;
                }

                $io->progressAdvance(); // Обновляем прогресс-бар
            }

            // Сохраняем все изменения
            $this->entityManager->flush();
            $this->entityManager->commit();

            fclose($file);
            $io->progressFinish(); // Завершаем прогресс-бар

            $io->success([
                'Импорт успешно завершен!',
                "Добавлено новых категорий: {$importedCount}",
                "Обновлено существующих категорий: {$updatedCount}",
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