<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RegistrationManager
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->validator = $validator;
    }

    /**
     * @throws \Exception
     */
    public function registerUser(array $data): JsonResponse
    {
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return new JsonResponse(['message' => 'Все поля обязательны'], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse(['message' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // Проверяем различные варианты сообщений об ошибке уникальности
            if (
                str_contains($e->getMessage(), 'повторяющееся значение ключа') ||
                str_contains($e->getMessage(), 'duplicate key') ||
                str_contains($e->getMessage(), 'unique constraint') ||
                (method_exists($e, 'getPrevious') && $e->getPrevious() instanceof \PDOException && $e->getPrevious()->getCode() == '23505')
            ) {
                return new JsonResponse(
                    ['message' => 'Email или имя пользователя уже зарегистрированы.'],
                    Response::HTTP_CONFLICT
                );
            }

            // Логируем ошибку для отладки
            error_log('Registration error: ' . $e->getMessage());
            
            return new JsonResponse(
                ['message' => 'Ошибка сохранения: что-то пошло не так'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new JsonResponse(['message' => 'Регистрация прошла успешно'], Response::HTTP_CREATED);
    }
}
