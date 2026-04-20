<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\RegisterService;
use PDOException;

final class AuthController extends BaseApiController
{
    public function __construct(private RegisterService $registerService)
    {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{statusCode:int,payload:array<string,mixed>}
     */
    public function register(array $payload): array
    {
        $username = trim((string) ($payload['username'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($username === '' || $email === '' || $password === '') {
            return $this->error('Champs requis manquants', 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Email invalide', 400);
        }

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
