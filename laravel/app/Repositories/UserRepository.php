<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }


    public function create(string $username, string $email, string $passwordHash): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$username, $email, $passwordHash]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateIdentityFields(int $userId, ?string $username, ?string $email): void
    {
        $fields = [];
        $params = [];

        if ($username !== null) {
            $fields[] = 'username = ?';
            $params[] = $username;
        }

        if ($email !== null) {
            $fields[] = 'email = ?';
            $params[] = $email;
        }

        if ($fields === []) {
            return;
        }

        $params[] = $userId;
        $stmt = $this->pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
        $stmt->execute($params);
    }

    public function findPasswordHashById(int $userId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false || !is_string($row['password_hash'] ?? null)) {
            return null;
        }

        return $row['password_hash'];
    }

    public function updatePasswordHash(int $userId, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$passwordHash, $userId]);
    }

    /**
     * @return array<int, array{id:int,username:string,email:string,created_at:string,permission_level:?string}>
     */
    public function listAllWithGlobalPermission(): array
    {
        $stmt = $this->pdo->query(
            'SELECT
                u.id, u.username, u.email, u.created_at,
                gp.permission_level
            FROM users u
            LEFT JOIN global_permissions gp ON u.id = gp.user_id
            ORDER BY u.created_at DESC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
