<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class BanUserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function isP1(int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM global_permissions WHERE user_id = ? AND permission_level = ? LIMIT 1');
        $stmt->execute([$userId, 'P1']);

        return $stmt->fetchColumn() !== false;
    }

    public function banUser(int $targetUserId): void
    {
        $this->pdo->prepare('DELETE FROM server_members WHERE user_id = ?')->execute([$targetUserId]);
        $this->pdo->prepare('DELETE FROM messages WHERE user_id = ?')->execute([$targetUserId]);
        $this->pdo->prepare('DELETE FROM profiles WHERE user_id = ?')->execute([$targetUserId]);
        $this->pdo->prepare('DELETE FROM global_permissions WHERE user_id = ?')->execute([$targetUserId]);
        $this->pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$targetUserId]);
    }
}
