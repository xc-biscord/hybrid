<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ChannelRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    public function findByServerId(int $serverId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, name FROM channels WHERE server_id = ? ORDER BY id ASC');
        $stmt->execute([$serverId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(int $serverId, string $name): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO channels (server_id, name) VALUES (?, ?)');
        $stmt->execute([$serverId, $name]);

        return (int) $this->pdo->lastInsertId();
    }
}
