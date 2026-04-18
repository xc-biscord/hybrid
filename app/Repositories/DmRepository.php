<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class DmRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findConversationByUsers(int $user1Id, int $user2Id): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM dm_conversations WHERE user1_id = ? AND user2_id = ? LIMIT 1');
        $stmt->execute([$user1Id, $user2Id]);

        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($conversation === false) {
            return null;
        }

        return (int) $conversation['id'];
    }

    public function createConversation(int $user1Id, int $user2Id): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO dm_conversations (user1_id, user2_id) VALUES (?, ?)');
        $stmt->execute([$user1Id, $user2Id]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return array{user1_id:int,user2_id:int}|null
     */
    public function findConversationForUser(int $conversationId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT user1_id, user2_id FROM dm_conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)');
        $stmt->execute([$conversationId, $userId, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return [
            'user1_id' => (int) $row['user1_id'],
            'user2_id' => (int) $row['user2_id'],
        ];
    }

    /**
     * @return array{username:string,avatar_url:?string}|null
     */
    public function findRecipientProfile(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT u.username, p.avatar_url FROM users u LEFT JOIN profiles p ON u.id = p.user_id WHERE u.id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return [
            'username' => (string) $row['username'],
            'avatar_url' => $row['avatar_url'] !== null ? (string) $row['avatar_url'] : null,
        ];
    }

    /**
     * @return array<int, array{id:int,conversation_id:int,sender_id:int,content:string,created_at:string,username:string,avatar:?string}>
     */
    public function findMessagesByConversationId(int $conversationId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT dm.id, dm.conversation_id, dm.sender_id, dm.content, dm.created_at, u.username, p.avatar_url AS avatar
            FROM dm_messages dm
            JOIN users u ON dm.sender_id = u.id
            LEFT JOIN profiles p ON u.id = p.user_id
            WHERE dm.conversation_id = ?
            ORDER BY dm.created_at ASC, dm.id ASC
        ');
        $stmt->execute([$conversationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function markConversationAsRead(int $userId, int $conversationId): void
    {
        $update = $this->pdo->prepare('
            INSERT INTO dm_reads (user_id, conversation_id, last_read_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE last_read_at = NOW()
        ');
        $update->execute([$userId, $conversationId]);
    }

    public function userHasConversationAccess(int $conversationId, int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM dm_conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?) LIMIT 1');
        $stmt->execute([$conversationId, $userId, $userId]);

        return $stmt->fetchColumn() !== false;
    }

    public function createMessage(int $conversationId, int $senderId, string $content): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO dm_messages (conversation_id, sender_id, content) VALUES (?, ?, ?)');
        $stmt->execute([$conversationId, $senderId, $content]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return array<int, array{conversation_id:int,sender_id:int,unread_count:int,username:string,avatar_url:?string,last_message:string}>
     */
    public function findUnreadConversationNotifications(int $userId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                c.id AS conversation_id,
                m.sender_id,
                COUNT(*) AS unread_count,
                u.username,
                p.avatar_url,
                MAX(m.created_at) AS last_message
            FROM dm_conversations c
            JOIN dm_messages m ON m.conversation_id = c.id
            LEFT JOIN dm_reads r ON r.conversation_id = c.id AND r.user_id = :uid
            JOIN users u ON m.sender_id = u.id
            LEFT JOIN profiles p ON p.user_id = u.id
            WHERE (c.user1_id = :uid OR c.user2_id = :uid)
              AND m.sender_id != :uid
              AND (r.last_read_at IS NULL OR m.created_at > r.last_read_at)
            GROUP BY c.id, m.sender_id, u.username, p.avatar_url
            ORDER BY last_message DESC
        ');
        $stmt->execute(['uid' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
