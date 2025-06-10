<?php

namespace App\Service;

use App\Entity\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class TokenManager
{
    private $jwtManager;
    private $secretKey;

    public function __construct(JWTTokenManagerInterface $jwtManager, string $secretKey)
    {
        $this->jwtManager = $jwtManager;
        $this->secretKey = $secretKey;
    }

    public function createAccessToken(User $user): string
    {
        return $this->jwtManager->create($user);
    }

    public function createRefreshToken(User $user): string
    {
        return JWT::encode([
            'user_id' => $user->getId(),
            'exp' => time() + 604800, // 1 week
        ], $this->secretKey, 'HS256');
    }

    public function refreshAccessToken(string $refreshToken): string
    {
        $decoded = JWT::decode($refreshToken, new Key($this->secretKey, 'HS256'));
        return $this->jwtManager->createFromPayload($decoded->user_id, ['user_id' => $decoded->user_id]);
    }

    public function validateRefreshToken(string $refreshToken): bool
    {
        try {
            JWT::decode($refreshToken, new Key($this->secretKey, 'HS256'));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}