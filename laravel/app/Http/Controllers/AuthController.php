<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\RegisterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use PDO;
use PDOException;

final class AuthController extends BaseApiController
{
    private const LOGIN_MAX_ATTEMPTS = 10;
    private const LOGIN_WINDOW_SECONDS = 300;

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

        $throttleKey = 'login_attempts:' . hash('sha256', mb_strtolower($username) . '|' . ($_SERVER['REMOTE_ADDR'] ?? ''));
        $attempts = (int) Cache::store('file')->get($throttleKey, 0);
        if ($attempts >= self::LOGIN_MAX_ATTEMPTS) {
            return $this->error('Trop de tentatives. Réessaie dans quelques minutes.', 429);
        }

        $stmt = $this->pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = :username OR email = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Cache::store('file')->put($throttleKey, $attempts + 1, self::LOGIN_WINDOW_SECONDS);

            return $this->error('Identifiants invalides', 401);
        }

        Cache::store('file')->forget($throttleKey);

        // Nouvel ID de session au changement de privilège (anti-fixation)
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = (string) $user['username'];

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

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

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

        if (mb_strlen($username) < 2 || mb_strlen($username) > 32) {
            return $this->error('Le nom d\'utilisateur doit faire entre 2 et 32 caractères', 400);
        }

        // La force du mot de passe est vérifiée côté client, mais le serveur
        // doit imposer sa propre limite basse.
        if (strlen($password) < 8) {
            return $this->error('Le mot de passe doit contenir au moins 8 caractères', 400);
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
