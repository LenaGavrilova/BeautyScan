<?php

namespace App\Controller;

use App\Service\LoginManager;
use App\Service\RegistrationManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class AuthController extends AbstractController

{
    private $registrationManager;
    private $loginManager;

    public function __construct(RegistrationManager $registrationManager, LoginManager $loginManager)
    {
        $this->registrationManager = $registrationManager;
        $this->loginManager = $loginManager;
    }


    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Простая проверка для демонстрации
        if (!isset($data['username'], $data['email'], $data['password'])) {
            return new JsonResponse(['message' => 'Некорректные данные'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Попытка зарегистрировать пользователя через сервис
        try {
            $user = $this->registrationManager->registerUser($data);
            return new JsonResponse(['message' => 'Пользователь успешно зарегистрирован'], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Ошибка при регистрации: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Вход пользователя
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        // Проверка на пустые поля
        if (empty($email) || empty($password)) {
            return new JsonResponse(['message' => 'Пожалуйста, заполните все поля'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Вызов сервиса для аутентификации
        $user = $this->loginManager->authenticateUser($email, $password);

        if (!$user) {
            return new JsonResponse(['message' => 'Неверный email или пароль'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // В случае успеха — возвращаем успешный ответ
        return new JsonResponse(['message' => 'Вход выполнен успешно']);
    }
}
