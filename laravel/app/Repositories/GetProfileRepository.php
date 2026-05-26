<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class GetProfileRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<string,mixed>|null */
    public function findProfile(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT u.username, u.email, p.bio, p.avatar_url, p.status FROM users u LEFT JOIN profiles p ON u.id = p.user_id WHERE u.id = ?');
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        return $profile === false ? null : $profile;
    }

    public function isP1(int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM global_permissions WHERE user_id = ?');
        $stmt->execute([$userId]);

        return $stmt->fetch() !== false;
    }
}
