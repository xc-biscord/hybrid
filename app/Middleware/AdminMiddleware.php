<?php

declare(strict_types=1);

namespace App\Middleware;

use PDO;

final class AdminMiddleware
{
    public function __construct(private PDO $pdo)
    {
    }

    public function isGlobalAdmin(int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM global_permissions WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);

        return $stmt->fetchColumn() !== false;
    }
}
