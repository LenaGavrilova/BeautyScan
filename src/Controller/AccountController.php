<?php

namespace App\Controller;

use App\Service\AccountManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AccountController extends AbstractController
{
    private AccountManager $accountManager;

    public function __construct(AccountManager $accountManager)
    {
        $this->accountManager = $accountManager;
    }

    #[Route('/api/account', name: 'get_account', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getAccount(): JsonResponse
    {
        return $this->accountManager->handleGetAccount($this->getUser());
    }

    #[Route('/api/account', name: 'update_account', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function updateAccount(Request $request): JsonResponse
    {
        return $this->accountManager->handleUpdateAccount($this->getUser(), $request);
    }

    #[Route('/api/account', name: 'delete_account', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function deleteAccount(): JsonResponse
    {
        return $this->accountManager->handleDeleteAccount($this->getUser());
    }
}
