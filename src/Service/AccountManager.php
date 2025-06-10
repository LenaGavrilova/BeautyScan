<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AccountManager
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private UserPasswordHasherInterface $passwordHasher;
    private JWTTokenManagerInterface $jwtManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ) {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->passwordHasher = $passwordHasher;
        $this->jwtManager = $jwtManager;
    }

    public function handleGetAccount(User $user): JsonResponse
    {
        return new JsonResponse([
            'email' => $user->getEmail(),
            'name' => $user->getUsername(),
            'roles' => $user->getRoles(),
        ]);
    }

    public function handleUpdateAccount(User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

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
                return new JsonResponse(['error' => 'Пароль должен содержать минимум 8 символов'], 400);
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
            return new JsonResponse(['errors' => $errorMessages], 400);
        }

        $this->entityManager->flush();

        $newToken = $this->jwtManager->create($user);
        return new JsonResponse([
            'success' => 'Изменения применены успешно',
            'token' => $newToken,
        ]);
    }

    public function handleDeleteAccount(User $user): JsonResponse
    {
        try {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            return new JsonResponse(['success' => 'Аккаунт успешно удален']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Произошла ошибка во время удаления аккаунта'], 400);
        }
    }
}
