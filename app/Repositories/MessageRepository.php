<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class MessageRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function createWithCurrentTimestamp(int $channelId, int $userId, string $content): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO messages (channel_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$channelId, $userId, $content]);
    }
}
