<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Passkey;

use App\Services\Passkey\PasskeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PDO;
use Throwable;

/**
 * Enregistrement d'une passkey depuis le profil — utilisateur CONNECTÉ.
 *
 *  - POST /api/passkey_register_options.php : génère le challenge (création)
 *  - POST /api/passkey_register_verify.php  : vérifie l'attestation et stocke la clé publique
 */
final class PasskeyRegisterController extends PasskeyController
{
    private const SESSION_KEY = 'passkey_reg_options';

    public function __construct(
        private PasskeyService $passkeyService,
        private PDO $pdo,
    ) {}

    public function options(Request $request): JsonResponse
    {
        $userId = $this->sessionUserId();
        if ($userId === null) {
            return $this->error('Non authentifié', 401);
        }

        $username = $this->fetchUsername($userId) ?? ('user-'.$userId);

        $options = $this->passkeyService->registrationOptions($userId, $username);
        $serialized = $this->passkeyService->serializeOptions($options);
        $this->storeChallenge(self::SESSION_KEY, $serialized);

        return $this->success([
            'options' => json_decode($serialized, true),
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $userId = $this->sessionUserId();
        if ($userId === null) {
            return $this->error('Non authentifié', 401);
        }

        $serializedOptions = $this->takeChallenge(self::SESSION_KEY);
        if ($serializedOptions === null) {
            return $this->error('Challenge expiré ou absent. Recommence.', 400);
        }

        $data = json_decode((string) $request->getContent(), true);
        if (! is_array($data) || ! isset($data['credential'])) {
            return $this->error('Réponse de credential manquante', 400);
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $name = 'Passkey';
        }
        if (mb_strlen($name) > 100) {
            $name = mb_substr($name, 0, 100);
        }

        try {
            $options = $this->passkeyService->deserializeCreationOptions($serializedOptions);
            $passkey = $this->passkeyService->finishRegistration(
                $options,
                (string) json_encode($data['credential']),
                $request->getHost(),
                $userId,
                $name,
            );
        } catch (Throwable $e) {
            return $this->error('Enregistrement de la passkey impossible : '.$e->getMessage(), 422);
        }

        return $this->success([
            'passkey' => [
                'id' => (int) $passkey['id'],
                'name' => $passkey['name'],
                'created_at' => $passkey['created_at'] ?? null,
                'last_used_at' => $passkey['last_used_at'] ?? null,
            ],
        ], 201);
    }

    private function fetchUsername(int $userId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT username FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $username = $stmt->fetchColumn();

        return is_string($username) && $username !== '' ? $username : null;
    }
}
