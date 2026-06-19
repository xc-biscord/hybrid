<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Passkey;

use App\Services\Passkey\PasskeyService;
use App\Services\Passkey\PdoPasskeyRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PDO;
use Throwable;

/**
 * Connexion par passkey (étape 2, voie WebAuthn) — utilisateur NON connecté.
 *
 *  - POST /api/passkey_login_options.php  : génère le challenge (assertion)
 *  - POST /api/passkey_login_verify.php   : vérifie la réponse signée et ouvre la session
 *
 * Le mot de passe reste le fallback : /api/login.php n'est pas touché.
 */
final class PasskeyAuthController extends PasskeyController
{
    private const SESSION_KEY = 'passkey_auth_options';

    public function __construct(
        private PasskeyService $passkeyService,
        private PdoPasskeyRepository $repository,
        private PDO $pdo,
    ) {}

    /**
     * Étape 2 (voie passkey) : renvoie les options d'assertion (challenge +
     * credentials autorisés). Pour un identifiant inconnu ou sans passkey, la
     * liste est vide : le navigateur se comportera comme s'il n'y avait pas de
     * passkey (pas de fuite d'existence de compte).
     */
    public function options(Request $request): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true);
        $identifier = is_array($data) ? trim((string) ($data['identifier'] ?? '')) : '';

        if ($identifier === '') {
            return $this->error('Identifiant manquant', 400);
        }

        $passkeys = [];
        $userId = $this->findUserId($identifier);
        if ($userId !== null) {
            $passkeys = $this->repository->findByUserId($userId);
        }

        $options = $this->passkeyService->assertionOptions($passkeys);
        $serialized = $this->passkeyService->serializeOptions($options);
        $this->storeChallenge(self::SESSION_KEY, $serialized);

        return $this->success([
            'options' => json_decode($serialized, true),
        ]);
    }

    /**
     * Vérifie l'assertion. Le corps de la requête est la réponse de
     * navigator.credentials.get() sérialisée (PublicKeyCredential JSON).
     */
    public function verify(Request $request): JsonResponse
    {
        $serializedOptions = $this->takeChallenge(self::SESSION_KEY);
        if ($serializedOptions === null) {
            return $this->error('Challenge expiré ou absent. Recommence.', 400);
        }

        try {
            $options = $this->passkeyService->deserializeRequestOptions($serializedOptions);
            $row = $this->passkeyService->finishAssertion(
                $options,
                (string) $request->getContent(),
                $request->getHost(),
            );
        } catch (Throwable $e) {
            // Message générique : on ne révèle pas la cause exacte de l'échec.
            return $this->error('Connexion par passkey impossible.', 401);
        }

        $userId = (int) $row['user_id'];

        // Même ouverture de session que le login mot de passe (anti-fixation).
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $username = $this->fetchUsername($userId);
        $_SESSION['user_id'] = $userId;
        if ($username !== null) {
            $_SESSION['username'] = $username;
        }

        return $this->success(['user_id' => $userId]);
    }

    private function findUserId(string $identifier): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM users WHERE username = :id OR email = :id LIMIT 1'
        );
        $stmt->execute(['id' => $identifier]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    private function fetchUsername(int $userId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT username FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $username = $stmt->fetchColumn();

        return is_string($username) && $username !== '' ? $username : null;
    }
}
