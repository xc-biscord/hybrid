<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use PDO;

final class MessageRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    // PDO conservé : utilise NOW() côté SQL, hors périmètre de la migration initiale
    public function createWithCurrentTimestamp(int $channelId, int $userId, string $content): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO messages (channel_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$channelId, $userId, $content]);
    }

    // PDO conservé : pattern fetchColumn + LIMIT 1, requête simple mais hors liste cible
    public function channelExists(int $channelId): bool
    {
        $check = $this->pdo->prepare('SELECT 1 FROM channels WHERE id = ? LIMIT 1');
        $check->execute([$channelId]);

        return $check->fetchColumn() !== false;
    }

    // PDO conservé : JOIN de contrôle d'accès, requête sensible aux permissions
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
        return DB::table('messages as m')
            ->join('users as u', 'u.id', '=', 'm.user_id')
            ->leftJoin('profiles as p', 'p.user_id', '=', 'u.id')
            ->where('m.channel_id', $channelId)
            ->orderBy('m.created_at')
            ->orderBy('m.id')
            ->select('m.id', 'm.content', 'm.created_at', 'u.username', 'u.id as user_id', 'p.avatar_url')
            ->get()
            ->map(fn(object $r): array => (array) $r)
            ->all();
    }

    public function create(int $channelId, int $userId, string $content): int
    {
        return (int) DB::table('messages')->insertGetId([
            'channel_id' => $channelId,
            'user_id'    => $userId,
            'content'    => $content,
        ]);
    }

    // PDO conservé : JOIN messages+channels, migration prévue en phase 2
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
            'server_id'  => (int) $row['server_id'],
        ];
    }

    // PDO conservé : DELETE simple, migration prévue en phase 2
    public function deleteById(int $messageId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM messages WHERE id = ?');
        $stmt->execute([$messageId]);
    }
}
