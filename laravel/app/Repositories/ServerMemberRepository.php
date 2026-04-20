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


    public function addMemberIgnore(int $serverId, int $userId): void
    {
        $stmt = $this->pdo->prepare('INSERT IGNORE INTO server_members (server_id, user_id) VALUES (?, ?)');
        $stmt->execute([$serverId, $userId]);
    }

    public function isMember(int $serverId, int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM server_members WHERE server_id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$serverId, $userId]);

        return $stmt->fetchColumn() !== false;
    }

    public function findRole(int $userId, int $serverId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT role FROM server_members WHERE user_id = ? AND server_id = ? LIMIT 1');
        $stmt->execute([$userId, $serverId]);
        $role = $stmt->fetchColumn();

        return $role === false ? null : (string) $role;
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

    /**
     * @return array<int, array{id:int,username:string,role:string}>
     */
    public function listUsersWithEffectiveRolesInServer(int $serverId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.username,
                CASE WHEN gp.user_id IS NOT NULL THEN "P1" ELSE m.role END AS role
            FROM server_members m
            JOIN users u ON u.id = m.user_id
            LEFT JOIN global_permissions gp ON gp.user_id = u.id AND gp.permission_level = "P1"
            WHERE m.server_id = ?
            ORDER BY u.username ASC'
        );
        $stmt->execute([$serverId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function updateRole(int $serverId, int $targetUserId, string $newRole): void
    {
        $stmt = $this->pdo->prepare('UPDATE server_members SET role = ? WHERE user_id = ? AND server_id = ?');
        $stmt->execute([$newRole, $targetUserId, $serverId]);
    }

    public function removeMember(int $serverId, int $targetUserId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM server_members WHERE user_id = ? AND server_id = ?');
        $stmt->execute([$targetUserId, $serverId]);
    }
}
