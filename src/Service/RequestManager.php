<?php

namespace App\Service;

use App\Entity\Request as RequestEntity;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RequestManager
{
    private $entityManager;
    private $uploadDir;
    private $security;

    public function __construct(EntityManagerInterface $entityManager, string $uploadDir, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->uploadDir = $uploadDir; // Путь к директории загрузки
        $this->security = $security;
    }

    public function saveImage(UploadedFile $file): string
    {
        $fileName = uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($this->uploadDir, $fileName);
        } catch (FileException $e) {
            throw new \RuntimeException('Не удалось сохранить изображение: ' . $e->getMessage());
        }

        // Возвращаем относительный путь к изображению
        return 'uploads/images/' . $fileName;
    }

    public function handleSaveRequest(array $data, UploadedFile $file = null): array
    {
        if (empty($data['text']) && !$file) {
            return [
                'success' => false,
                'message' => 'Text or image is required.',
            ];
        }

        $imagePath = null;

        if ($file) {
            $imagePath = $this->saveImage($file);
        }

        // Получаем текущего пользователя
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return [
                'success' => false,
                'message' => 'User not authenticated.',
            ];
        }

        // Создание запроса
        $requestEntity = new RequestEntity();
        $requestEntity->setText($data['text'] ?? null);
        $requestEntity->setImage($imagePath);
        $requestEntity->setCreatedAt(new \DateTime());
        $requestEntity->setUser($user);

        $this->entityManager->persist($requestEntity);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => 'Request saved successfully.',
        ];
    }
}

