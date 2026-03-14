<?php
require_once __DIR__ . '/bootstrap.php';

$userId = requireAuthUserId();

try {
    $stmt = $pdo->prepare('
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
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(['success' => true, 'unread_conversations' => $rows]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'error' => 'Erreur DB'], 500);
}
