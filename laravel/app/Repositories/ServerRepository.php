<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Server;
use PDO;

final class ServerRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(string $name, int $ownerId): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO servers (name, owner_id) VALUES (?, ?)');
        $stmt->execute([$name, $ownerId]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    public function findByMemberUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.name
            FROM servers s
            JOIN server_members m ON m.server_id = s.id
            WHERE m.user_id = ?
            ORDER BY s.name ASC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function find(int $serverId): ?Server
    {
        $stmt = $this->pdo->prepare('SELECT id, name, owner_id FROM servers WHERE id = ? LIMIT 1');
        $stmt->execute([$serverId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : Server::fromArray($row);
    }
}
