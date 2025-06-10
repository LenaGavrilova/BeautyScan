<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthManager
{
    private $entityManager;
    private $passwordHasher;
    private $validator;
    private $userRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        UserRepository $userRepository
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->validator = $validator;
        $this->userRepository = $userRepository;
    }

    public function registerUser(array $data): array
    {
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            throw new \InvalidArgumentException('Все поля обязательны', Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors, Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            if ($this->isDuplicateKeyError($e)) {
                throw new \RuntimeException('Email или имя пользователя уже зарегистрированы.', Response::HTTP_CONFLICT);
            }

            throw new \RuntimeException('Ошибка сохранения: что-то пошло не так', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return ['message' => 'Регистрация прошла успешно'];
    }

    public function authenticateUser(string $email, string $password): ?User
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return null;
        }

        return $user;
    }

    private function isDuplicateKeyError(\Exception $e): bool
    {
        return
            str_contains($e->getMessage(), 'повторяющееся значение ключа') ||
            str_contains($e->getMessage(), 'duplicate key') ||
            str_contains($e->getMessage(), 'unique constraint') ||
            (method_exists($e, 'getPrevious') && $e->getPrevious() instanceof \PDOException && $e->getPrevious()->getCode() == '23505');
    }
}