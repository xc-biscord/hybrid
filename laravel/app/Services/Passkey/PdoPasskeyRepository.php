<?php

declare(strict_types=1);

namespace App\Services\Passkey;

use PDO;

/**
 * Accès base de données pour la table `user_passkeys`.
 *
 * Cette classe ne contient AUCUNE logique WebAuthn/cryptographique : elle se
 * contente de lire et écrire des lignes via PDO (comme le reste du projet).
 * Toute la cryptographie est isolée dans {@see PasskeyService}.
 *
 * Rappel sécurité : on ne stocke QUE des données publiques (clé publique COSE,
 * identifiant public du credential, compteur). La clé privée ne quitte jamais
 * l'authentificateur de l'utilisateur.
 */
final class PdoPasskeyRepository
{
    public function __construct(private PDO $pdo) {}

    /**
     * Retrouve une passkey par son identifiant public (base64url).
     *
     * @return array<string,mixed>|null
     */
    public function findByCredentialId(string $credentialIdBase64Url): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM user_passkeys WHERE credential_id = :cid LIMIT 1'
        );
        $stmt->execute(['cid' => $credentialIdBase64Url]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * Liste les passkeys d'un utilisateur (pour la section profil).
     *
     * @return array<int,array<string,mixed>>
     */
    public function findByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, credential_id, sign_count, created_at, last_used_at
             FROM user_passkeys WHERE user_id = :uid ORDER BY created_at ASC, id ASC'
        );
        $stmt->execute(['uid' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countByUserId(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM user_passkeys WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Récupère une passkey appartenant à un utilisateur donné (contrôle de droits).
     *
     * @return array<string,mixed>|null
     */
    public function findOwnedById(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM user_passkeys WHERE id = :id AND user_id = :uid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO user_passkeys
                (user_id, credential_id, public_key, sign_count, name, user_handle, transports, aaguid, created_at)
             VALUES
                (:user_id, :credential_id, :public_key, :sign_count, :name, :user_handle, :transports, :aaguid, NOW())'
        );
        $stmt->execute([
            'user_id' => $data['user_id'],
            'credential_id' => $data['credential_id'],
            'public_key' => $data['public_key'],
            'sign_count' => $data['sign_count'],
            'name' => $data['name'],
            'user_handle' => $data['user_handle'],
            'transports' => $data['transports'] ?? null,
            'aaguid' => $data['aaguid'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Met à jour le compteur anti-rejeu et la date de dernière utilisation après
     * une connexion réussie par passkey.
     */
    public function touchAfterAssertion(int $id, int $newSignCount): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE user_passkeys SET sign_count = :c, last_used_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['c' => $newSignCount, 'id' => $id]);
    }

    public function deleteOwned(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM user_passkeys WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute(['id' => $id, 'uid' => $userId]);

        return $stmt->rowCount() > 0;
    }
}
