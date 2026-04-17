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

    public function channelExists(int $channelId): bool
    {
        $check = $this->pdo->prepare('SELECT 1 FROM channels WHERE id = ? LIMIT 1');
        $check->execute([$channelId]);

        return $check->fetchColumn() !== false;
    }

    public function userCanReadChannelMessages(int $channelId, int $userId): bool
    {
        $access = $this->pdo->prepare('
            SELECT 1
            FROM channels c
            JOIN server_members sm ON sm.server_id = c.server_id
            WHERE c.id = ? AND sm.user_id = ?
            LIMIT 1
        ');
        $access->execute([$channelId, $userId]);

        return $access->fetchColumn() !== false;
    }

    /**
     * @return array<int, array{id:int,content:string,created_at:string,username:string,user_id:int,avatar_url:?string}>
     */
    public function findByChannelId(int $channelId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT m.id, m.content, m.created_at, u.username, u.id AS user_id, p.avatar_url
            FROM messages m
            JOIN users u ON u.id = m.user_id
            LEFT JOIN profiles p ON p.user_id = u.id
            WHERE m.channel_id = ?
            ORDER BY m.created_at ASC, m.id ASC
        ');
        $stmt->execute([$channelId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(int $channelId, int $userId, string $content): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO messages (channel_id, user_id, content) VALUES (?, ?, ?)');
        $stmt->execute([$channelId, $userId, $content]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return array{channel_id:int,server_id:int}|null
     */
    public function findMessageChannelAndServer(int $messageId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT m.channel_id, c.server_id FROM messages m JOIN channels c ON m.channel_id = c.id WHERE m.id = ?');
        $stmt->execute([$messageId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        return [
            'channel_id' => (int) $row['channel_id'],
            'server_id' => (int) $row['server_id'],
        ];
    }

    public function deleteById(int $messageId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM messages WHERE id = ?');
        $stmt->execute([$messageId]);
    }
}
