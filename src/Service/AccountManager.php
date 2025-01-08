<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AccountManager
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->passwordHasher = $passwordHasher;

    }

    public function getUserData(User $user): array
    {
        return [
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'roles' => $user->getRoles(),
        ];
    }

    public function updateUserData(User $user, array $data): array
    {
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['username'])) {
            $user->setUsername($data['username']);
        }

        if (isset($data['newPassword'])) {
            $violations = $this->validator->validate($data['newPassword'], [
                new NotBlank(),
                new Length(['min' => 8]),
            ]);

            if (count($violations) > 0) {
                return ['error' => 'Пароль должен содержать минимум 8 символов'];
            }

            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['newPassword']);

            $user->setPassword($hashedPassword);
        }

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return ['errors' => $errorMessages];
        }

        $this->entityManager->flush();

        return ['success' => 'Изменения применены успешно'];
    }


    public function deleteAccount(User $user): array
    {
        try {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            return ['success' => 'Аккаунт успешно удален'];
        } catch (\Exception $e) {
            return ['error' => 'Произошла ошибка во время удаления аккаунта'];
        }
    }

}
