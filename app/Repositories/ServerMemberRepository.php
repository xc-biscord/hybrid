<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ServerMemberRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function addMember(int $serverId, int $userId, string $role = 'P2'): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO server_members (server_id, user_id, role) VALUES (?, ?, ?)');
        $stmt->execute([$serverId, $userId, $role]);
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    public function listServersForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.name
            FROM server_members sm
            JOIN servers s ON s.id = sm.server_id
            WHERE sm.user_id = ?
            ORDER BY s.name ASC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
