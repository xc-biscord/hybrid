<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class GetUserProfileRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<string,mixed>|null */
    public function findUserProfile(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT users.id, users.username, profiles.avatar_url, profiles.bio, profiles.status FROM users LEFT JOIN profiles ON users.id = profiles.user_id WHERE users.id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user === false ? null : $user;
    }
}
