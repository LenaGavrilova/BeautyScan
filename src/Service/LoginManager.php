<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

class LoginManager
{
    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    public function authenticateUser(string $email, string $password): ?User
    {
        // Ищем пользователя по email
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        // Если пользователь не найден
        if (!$user) {
            return null;
        }

        // Проверяем пароль
        if ($this->passwordHasher->isPasswordValid($user, $password)) {
            return $user;
        }

        return null;
    }
}
