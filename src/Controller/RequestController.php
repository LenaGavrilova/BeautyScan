<?php

namespace App\Controller;

use App\Service\RequestManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class RequestController extends AbstractController
{
    private $requestManager;

    public function __construct(RequestManager $requestManager)
    {
        $this->requestManager = $requestManager;
    }

    #[Route('/api/extract-text', name: 'extract_text', methods: ['POST'])]
    public function extractText(Request $request): JsonResponse
    {
        $file = $request->files->get('image');
        $data = $request->request->all();

        if (!$file) {
            return new JsonResponse(['success' => false, 'message' => 'Изображение не было загружено.'], 400);
        }

        // Возвращаем текст состава с фотографии
        $extractedText = $this->requestManager->handleSaveRequest($data,$file);
        return new JsonResponse($extractedText);
    }
}
