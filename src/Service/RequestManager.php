<?php

namespace App\Service;

use App\Entity\Request as RequestEntity;
use App\Entity\User;
use CURLFile;
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
        $this->uploadDir = $uploadDir;
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

        return $this->uploadDir . '/' . $fileName;
    }

    function extractText($imagePath) {
        $url = 'http://127.0.0.1:5000/ocr';

        $curl = curl_init();
        $postFields = [
            'file' => new CURLFile($imagePath)
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_RETURNTRANSFER => true
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }


    public function handleSaveRequest(array $data, UploadedFile $file = null): array
    {
        if (!$file && empty($data['text'])) {
            return [
                'success' => false,
                'message' => 'Пожалуйста, загрузите изображение или введите текст вручную.',
            ];
        }

        $imagePath = null;
        $extractedText = null;

        if ($file) {
            try {
                // Сохраняем изображение и получаем путь
                $imagePath = $this->saveImage($file);

                // Распознаем текст из изображения
                $extractedText = $this->extractText($imagePath);

                if (empty($extractedText)) {
                    return [
                        'success' => false,
                        'message' => 'Не удалось распознать текст. Попробуйте загрузить более четкое изображение или введите текст вручную.',
                    ];
                }

                // Возвращаем результат с распознанным текстом и путем к изображению
                return [
                    'success' => true,
                    'extractedText' => $extractedText,
                    'imagePath' => $imagePath, // Передаем путь к изображению
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Не удалось распознать текст. Попробуйте загрузить более четкое изображение или введите текст вручную.',
                ];
            }
        }

        // Сохраняем текст в БД
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return [
                'success' => false,
                'message' => 'Пользователь не аутентифицирован.',
            ];
        }

        // Создаем новую запись в базе данных
        $requestEntity = new RequestEntity();
        $requestEntity->setText($data['text'] ?? null);
        $requestEntity->setImage($imagePath); // Сохраняем путь к изображению
        $requestEntity->setCreatedAt(new \DateTime());
        $requestEntity->setUser($user);

        $this->entityManager->persist($requestEntity);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => 'Запрос успешно сохранен.',
        ];
    }

}
