<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\RegisterService;
use Illuminate\Http\JsonResponse;
use PDO;
use PDOException;

final class AuthController extends BaseApiController
{
    public function __construct(
        private RegisterService $registerService,
        private PDO $pdo,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function login(array $payload): JsonResponse
    {
        $username = trim((string) ($payload['username'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($username === '' || $password === '') {
            return $this->error('Identifiants manquants', 400);
        }

        $stmt = $this->pdo->prepare('SELECT id, password_hash FROM users WHERE username = :username OR email = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return $this->error('Identifiants invalides', 401);
        }

        $_SESSION['user_id'] = (int) $user['id'];

        return $this->success(['user_id' => (int) $user['id']]);
    }

    public function auth(): ?JsonResponse
    {
        if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
            return $this->error('Non authentifié', 401);
        }

        return null;
    }

    public function logout(): never
    {
        session_unset();
        session_destroy();
        header('Location: /index.html');
        exit;
    }

    public function checkAuth(): JsonResponse
    {
        if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
            return new JsonResponse(['logged_in' => false]);
        }

        $username = null;
        if (isset($_SESSION['username']) && is_string($_SESSION['username']) && $_SESSION['username'] !== '') {
            $username = $_SESSION['username'];
        } else {
            $stmt = $this->pdo->prepare('SELECT username FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([(int) $_SESSION['user_id']]);
            $dbUsername = $stmt->fetchColumn();
            if (is_string($dbUsername) && $dbUsername !== '') {
                $username = $dbUsername;
            }
        }

        $payload = ['logged_in' => true];
        if ($username !== null) {
            $payload['username'] = $username;
        }

        return new JsonResponse($payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function register(array $payload): JsonResponse
    {
        $username = trim((string) ($payload['username'] ?? ''));
        $email    = trim((string) ($payload['email'] ?? ''));
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
