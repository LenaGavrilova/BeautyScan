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
    #[Route('/api/save-request', name: 'save_request', methods: ['POST'])]
    public function saveRequest(Request $request): JsonResponse
    {
        $data = $request->request->all(); // Получаем текстовые данные
        $file = $request->files->get('image'); // Получаем файл

        // Передача данных в менеджер
        $result = $this->requestManager->handleSaveRequest($data, $file);

        return new JsonResponse($result, $result['success'] ? 200 : 400);
    }
}


