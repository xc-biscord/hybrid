<?php
require_once __DIR__ . '/bootstrap.php';

$userId = requireAuthUserId();
$conversationId = (int)($_GET['conversation_id'] ?? 0);

if ($conversationId <= 0) {
    jsonResponse(['success' => false, 'error' => 'Conversation invalide'], 400);
}

try {
    $stmt = $pdo->prepare('SELECT user1_id, user2_id FROM dm_conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)');
    $stmt->execute([$conversationId, $userId, $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        jsonResponse(['success' => false, 'error' => 'Accès refusé'], 403);
    }

    $otherUserId = ((int)$row['user1_id'] === $userId) ? (int)$row['user2_id'] : (int)$row['user1_id'];

    $stmt = $pdo->prepare('SELECT u.username, p.avatar_url FROM users u LEFT JOIN profiles p ON u.id = p.user_id WHERE u.id = ? LIMIT 1');
    $stmt->execute([$otherUserId]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recipient) {
        jsonResponse(['success' => false, 'error' => 'Destinataire introuvable'], 404);
    }

    $stmt = $pdo->prepare('
        SELECT dm.id, dm.conversation_id, dm.sender_id, dm.content, dm.created_at, u.username, p.avatar_url AS avatar
        FROM dm_messages dm
        JOIN users u ON dm.sender_id = u.id
        LEFT JOIN profiles p ON u.id = p.user_id
        WHERE dm.conversation_id = ?
        ORDER BY dm.created_at ASC, dm.id ASC
    ');
    $stmt->execute([$conversationId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $update = $pdo->prepare('
        INSERT INTO dm_reads (user_id, conversation_id, last_read_at)
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE last_read_at = NOW()
    ');
    $update->execute([$userId, $conversationId]);

    jsonResponse([
        'success' => true,
        'messages' => $messages,
        'recipient' => [
            'id' => $otherUserId,
            'username' => $recipient['username'],
            'avatar_url' => $recipient['avatar_url'] ?? null,
        ],
    ]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'error' => 'Erreur DB'], 500);
}
