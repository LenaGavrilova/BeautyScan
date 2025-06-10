<?php

namespace App\Controller;

use App\Service\AuthManager;
use App\Service\TokenManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    private $authManager;
    private $tokenManager;

    public function __construct(AuthManager $authManager, TokenManager $tokenManager)
    {
        $this->authManager = $authManager;
        $this->tokenManager = $tokenManager;
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $result = $this->authManager->registerUser($data);
            return new JsonResponse($result, JsonResponse::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], $e->getCode());
        } catch (\RuntimeException $e) {
            return new JsonResponse(['message' => $e->getMessage()], $e->getCode());
        }
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            return new JsonResponse(['message' => 'Пожалуйста, заполните все поля'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = $this->authManager->authenticateUser($email, $password);

        if (!$user) {
            return new JsonResponse(['message' => 'Неверный email или пароль'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $accessToken = $this->tokenManager->createAccessToken($user);
        $refreshToken = $this->tokenManager->createRefreshToken($user);

        return new JsonResponse([
            'message' => 'Вход выполнен успешно',
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getUsername(),
                'roles' => $user->getRoles()
            ]
        ]);
    }

    #[Route('/api/refresh-token', name: 'api_refresh_token', methods: ['POST'])]
    public function refreshToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $refreshToken = $data['refresh_token'] ?? '';

        if (empty($refreshToken)) {
            return new JsonResponse(['message' => 'Refresh Token отсутствует'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $newAccessToken = $this->tokenManager->refreshAccessToken($refreshToken);
            return new JsonResponse(['access_token' => $newAccessToken]);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Недействительный Refresh Token'], JsonResponse::HTTP_UNAUTHORIZED);
        }
    }
}