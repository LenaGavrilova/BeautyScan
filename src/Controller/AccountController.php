<?php

namespace App\Controller;

use App\Service\AccountManager;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AccountController extends AbstractController
{
    private AccountManager $accountManager;
    private $jwtManager;

    public function __construct(AccountManager $accountManager, JWTTokenManagerInterface $jwtManager)
    {
        $this->accountManager = $accountManager;
        $this->jwtManager          = $jwtManager;
    }

    #[Route('/api/account', name: 'get_account', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getAccount(): JsonResponse
    {
        $user = $this->getUser();
        $userData = $this->accountManager->getUserData($user);

        return $this->json($userData);
    }

    #[Route('/api/account', name: 'update_account', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function updateAccount(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $result = $this->accountManager->updateUserData($user, $data);

        if (isset($result['errors'])) {
            return $this->json($result, 400);
        }
        $newToken = $this->jwtManager->create($user);
        return new JsonResponse([$result, 'token' => $newToken]);
    }

    #[Route('/api/account', name: 'delete_account', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function deleteAccount(): JsonResponse
    {
        $user = $this->getUser();

        $result = $this->accountManager->deleteAccount($user);

        if (isset($result['error'])) {
            return $this->json($result, 400);
        }

        return $this->json($result, 200);
    }

}
