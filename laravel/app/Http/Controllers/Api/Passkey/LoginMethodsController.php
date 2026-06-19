<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Passkey;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PDO;

/**
 * Étape 1 du login en deux étapes : POST /api/login_methods.php
 *
 * Entrée  : { identifier: string }  (nom d'utilisateur OU email)
 * Sortie  : { success: true, methods: { password: bool, passkey: bool } }
 *
 * Anti-énumération : on renvoie TOUJOURS password=true, y compris pour un
 * identifiant inconnu. Ainsi l'attaquant ne peut pas savoir si le compte existe :
 * le champ mot de passe s'affiche dans tous les cas, et c'est /api/login.php
 * (inchangé) qui tranchera ensuite avec un message générique.
 *
 * Limite assumée du PoC : un compte qui POSSÈDE une passkey est distinguable
 * (passkey=true). C'est documenté ; le compromis est jugé acceptable pour une
 * preuve de concept (cf. docs/passkeys-webauthn.md).
 */
final class LoginMethodsController extends PasskeyController
{
    public function __construct(private PDO $pdo) {}

    public function handle(Request $request): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true);
        $identifier = is_array($data) ? trim((string) ($data['identifier'] ?? '')) : '';

        if ($identifier === '') {
            return $this->error('Identifiant manquant', 400);
        }

        // Le mot de passe est toujours proposé (fallback principal + anti-énumération).
        $hasPasskey = false;

        $stmt = $this->pdo->prepare(
            'SELECT u.id FROM users u WHERE u.username = :id OR u.email = :id LIMIT 1'
        );
        $stmt->execute(['id' => $identifier]);
        $userId = $stmt->fetchColumn();

        if ($userId !== false) {
            $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM user_passkeys WHERE user_id = :uid');
            $countStmt->execute(['uid' => (int) $userId]);
            $hasPasskey = ((int) $countStmt->fetchColumn()) > 0;
        }

        return $this->success([
            'methods' => [
                'password' => true,
                'passkey' => $hasPasskey,
            ],
        ]);
    }
}
