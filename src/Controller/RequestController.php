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

        // Отладочное сообщение
        if (!$file) {
            return new JsonResponse(['success' => false, 'message' => 'Изображение не было загружено.'], 400);
        }

        if (empty(trim($data['text'] ?? '')) && !$file) {
            return new JsonResponse(['success' => false, 'message' => 'Either text or image is required.'], 400);
        }

        $result = $this->requestManager->handleSaveRequest($data, $file);

        return new JsonResponse($result, $result['success'] ? 200 : 400);
    }

    #[Route('/api/save-request', name: 'save_request', methods: ['POST'])]
    public function saveRequest(Request $request): JsonResponse
    {
        $data = $request->request->all();
        if (!isset($data['text'])) {
            return new JsonResponse(['success' => false, 'message' => 'Text is required.'], 400);
        }

        $result = $this->requestManager->handleSaveRequest($data);
        return new JsonResponse($result, $result['success'] ? 200 : 400);
    }
}
