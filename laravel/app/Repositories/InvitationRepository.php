<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class InvitationRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findServerIdByCode(string $code): ?int
    {
        $stmt = $this->pdo->prepare('SELECT server_id FROM invitations WHERE code = ? LIMIT 1');
        $stmt->execute([$code]);
        $serverId = $stmt->fetchColumn();

        if ($serverId === false) {
            return null;
        }

        return (int) $serverId;
    }

    /**
     * @return array{server_id:mixed,server_name:mixed}|null
     */
    public function findInviteServerSummaryByCode(string $code): ?array
    {
        $stmt = $this->pdo->prepare('SELECT server_id, created_at FROM invitations WHERE code = ?');
        $stmt->execute([$code]);
        $invite = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($invite === false) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT name FROM servers WHERE id = ?');
        $stmt->execute([$invite['server_id']]);
        $server = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'server_id' => $invite['server_id'],
            'server_name' => $server['name'],
        ];
    }

    public function isUserMemberOfServer(int $serverId, int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM server_members WHERE server_id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$serverId, $userId]);

        return $stmt->fetchColumn() !== false;
    }

    public function addUserToServer(int $serverId, int $userId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO server_members (server_id, user_id) VALUES (?, ?)');
        $stmt->execute([$serverId, $userId]);
    }

    public function createInvitation(int $serverId, string $code): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO invitations (server_id, code) VALUES (?, ?)');
        $stmt->execute([$serverId, $code]);
    }
}
