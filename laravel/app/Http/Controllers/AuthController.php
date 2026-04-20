<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\RegisterService;
use Illuminate\Http\JsonResponse;
use PDOException;

final class AuthController extends BaseApiController
{
    public function __construct(private RegisterService $registerService)
    {
    }

    /**
     * @param array<string, mixed> $payload Pre-validated by RegisterRequest.
     */
    public function register(array $payload): JsonResponse
    {
        $username = trim((string) ($payload['username'] ?? ''));
        $email    = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        try {
            $this->registerService->register($username, $email, $password);

            return $this->success([], 201);
        } catch (PDOException $e) {
            $isDuplicate = ($e->errorInfo[1] ?? null) === 1062;

            return $this->error(
                $isDuplicate ? 'Nom d\'utilisateur ou email déjà utilisé' : 'Erreur SQL',
                $isDuplicate ? 409 : 500
            );
        }
    }
}
