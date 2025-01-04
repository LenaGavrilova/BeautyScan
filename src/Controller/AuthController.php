<?php

namespace App\Controller;

use App\Service\LoginManager;
use App\Service\RegistrationManager;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManager;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class AuthController extends AbstractController

{
    private $registrationManager;
    private $loginManager;

    private $jwtManager;

    public function __construct(RegistrationManager $registrationManager, LoginManager $loginManager, JWTTokenManagerInterface $jwtManager)
    {
        $this->registrationManager = $registrationManager;
        $this->loginManager        = $loginManager;
        $this->jwtManager          = $jwtManager;
    }


    /**
     * @throws \Exception
     */
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);


        $user = $this->registrationManager->registerUser($data);

        return $user;
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
            $token = $this->jwtManager->create($user);

        return new JsonResponse(['message' => 'Вход выполнен успешно', 'token' => $token]);
    }
}
