<?php

namespace App\Command;


use App\Entity\IngredientCategory;
use App\Repository\IngredientCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-ingredientCategory',
    description: 'Импорт ингредиентов и категорий из CSV-файла',
)]
class ImportIngredientCategoryCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private IngredientCategoryRepository $ingredientCategoryRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        IngredientCategoryRepository $ingredientCategoryRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->ingredientCategoryRepository = $ingredientCategoryRepository;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Путь к CSV-файлу')
            ->addArgument('delimiter', InputArgument::OPTIONAL, 'Разделитель полей (по умолчанию ";")', ';');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('file');
        $delimiter = $input->getArgument('delimiter');

        if (!file_exists($filePath)) {
            $io->error("Файл не найден: $filePath");
            return Command::FAILURE;
        }

        $file = fopen($filePath, 'r');
        if (!$file) {
            $io->error("Не удалось открыть файл: $filePath");
            return Command::FAILURE;
        }

        $headers = fgetcsv($file, 0, $delimiter);
        $ingredientIndex = array_search('ingredient_name', $headers);
        $categoryIndex = array_search('category_name', $headers);

        if ($ingredientIndex === false || $categoryIndex === false) {
            $io->error("Файл должен содержать заголовки: ingredient_name и synonym_name");
            fclose($file);
            return Command::FAILURE;
        }

        $imported = 0;
        $updated = 0;

        $this->entityManager->beginTransaction();

        try {
            while (($row = fgetcsv($file, 0, $delimiter)) !== false) {
                $ingredientName = trim($row[$ingredientIndex] ?? '');
                $categoryRaw = trim($row[$categoryIndex] ?? '');

                if (empty($ingredientName)) {
                    continue;
                }

                $ingredient = $this->ingredientCategoryRepository->findOneBy(['ingredientName' => $ingredientName]);

                if (!$ingredient) {
                    $ingredient = new IngredientCategory();
                    $ingredient->setIngredientName($ingredientName);
                    $imported++;
                } else {
                    $updated++;
                }

                $ingredient->setCategoryName($categoryRaw);
                $this->entityManager->persist($ingredient);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
            fclose($file);

            $io->success("Импорт завершен. Новых: $imported, обновлено: $updated");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            fclose($file);
            $io->error('Ошибка импорта: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
