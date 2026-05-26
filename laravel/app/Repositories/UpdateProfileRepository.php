<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UpdateProfileRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function upsertProfile(int $userId, string $bio, string $avatarUrl, string $status): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO profiles (user_id, bio, avatar_url, status) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE bio = VALUES(bio), avatar_url = VALUES(avatar_url), status = VALUES(status)');
        $stmt->execute([$userId, $bio, $avatarUrl, $status]);
    }
}
