<?php

namespace App\Command;

use App\Entity\Synonym;
use App\Repository\SynonymRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-synonyms.csv',
    description: 'Импорт синонимов из CSV-файла',
)]
class ImportSynonymsCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private SynonymRepository $synonymRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        SynonymRepository $synonymRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->synonymRepository = $synonymRepository;
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

                // Ищем существующий синоним
                $synonym = $this->synonymRepository->findByName($name);
                $isNew = false;

                if (!$synonym) {
                    $synonym = new Synonym();
                    $synonym->setName($name);
                    $isNew = true;
                }

                $this->entityManager->persist($synonym);

                // Если это новый синоним, сразу сохраняем его для получения ID
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
                "Добавлено новых синонимов: {$importedCount}",
                "Обновлено существующих синонимов: {$updatedCount}",
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