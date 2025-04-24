<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\LoginManager;
use App\Service\RegistrationManager;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
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

    private $userRepository;

    private $secretKey;

    public function __construct(RegistrationManager $registrationManager, LoginManager $loginManager,
                                JWTTokenManagerInterface $jwtManager, UserRepository $userRepository,string $secretKey)
    {
        $this->registrationManager = $registrationManager;
        $this->loginManager        = $loginManager;
        $this->jwtManager          = $jwtManager;
        $this->userRepository      = $userRepository;
        $this->secretKey           = $secretKey;
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

        $accessToken = $this->jwtManager->create($user);
        $refreshToken = JWT::encode([
            'user_id' => $user->getId(),
            'exp' => time() + 604800,
        ], $this->secretKey, 'HS256');

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

        // Проверка валидности Refresh Token
        try {
            // Декодируем Refresh Token
            $decoded = JWT::decode($refreshToken, new Key($this->secretKey, 'HS256'));
            $userId = $decoded->user_id;

            // Здесь вы должны найти пользователя в базе данных
            // Например, используя UserRepository
            $user = $this->userRepository->find($userId);

            if (!$user) {
                return new JsonResponse(['message' => 'Пользователь не найден'], JsonResponse::HTTP_UNAUTHORIZED);
            }

            // Создаем новый Access Token
            $newAccessToken = $this->jwtManager->create($user);

            return new JsonResponse([
                'access_token' => $newAccessToken,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Недействительный Refresh Token'], JsonResponse::HTTP_UNAUTHORIZED);
        }
    }
}
