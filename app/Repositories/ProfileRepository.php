<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ProfileRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(int $userId, string $avatarUrl, string $bio, string $status): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO profiles (user_id, avatar_url, bio, status) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $avatarUrl, $bio, $status]);
    }
}
