<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Passkey;

use App\Services\Passkey\PdoPasskeyRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PDO;

/**
 * Gestion des passkeys depuis le profil — utilisateur CONNECTÉ.
 *
 *  - GET  /api/passkey_list.php   : liste des passkeys de l'utilisateur
 *  - POST /api/passkey_delete.php : suppression (contrôle de droits + garde)
 */
final class PasskeyManagementController extends PasskeyController
{
    public function __construct(
        private PdoPasskeyRepository $repository,
        private PDO $pdo,
    ) {}

    public function list(Request $request): JsonResponse
    {
        $userId = $this->sessionUserId();
        if ($userId === null) {
            return $this->error('Non authentifié', 401);
        }

        $passkeys = array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'created_at' => $row['created_at'],
                'last_used_at' => $row['last_used_at'],
            ],
            $this->repository->findByUserId($userId),
        );

        return $this->success(['passkeys' => $passkeys]);
    }

    public function delete(Request $request): JsonResponse
    {
        $userId = $this->sessionUserId();
        if ($userId === null) {
            return $this->error('Non authentifié', 401);
        }

        $data = json_decode((string) $request->getContent(), true);
        $id = is_array($data) && isset($data['id']) && is_numeric($data['id']) ? (int) $data['id'] : 0;
        if ($id <= 0) {
            return $this->error('Identifiant de passkey manquant', 400);
        }

        // Contrôle de droits : la passkey doit appartenir à l'utilisateur connecté.
        $passkey = $this->repository->findOwnedById($id, $userId);
        if ($passkey === null) {
            return $this->error('Passkey introuvable', 404);
        }

        // Garde "dernière méthode de connexion" : on refuse une suppression qui
        // laisserait le compte sans aucun moyen de se connecter.
        //   méthodes restantes = (mot de passe défini ? 1 : 0) + (autres passkeys)
        // Aujourd'hui chaque compte a un mot de passe, donc la suppression est
        // toujours sûre ; la garde reste utile si on ajoute un jour des comptes
        // sans mot de passe (passkey-only).
        $remaining = ($this->hasPassword($userId) ? 1 : 0)
            + ($this->repository->countByUserId($userId) - 1);

        if ($remaining < 1) {
            return $this->error(
                'Impossible de supprimer la dernière méthode de connexion du compte.',
                409,
            );
        }

        $this->repository->deleteOwned($id, $userId);

        return $this->success();
    }

    private function hasPassword(int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        return is_string($hash) && $hash !== '';
    }
}
