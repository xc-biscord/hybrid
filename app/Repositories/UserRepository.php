<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
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
}
