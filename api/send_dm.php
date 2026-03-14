<?php
require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');
$senderId = requireAuthUserId();
$data = getJsonInput();

$conversationId = (int)($data['conversation_id'] ?? 0);
$content = trim((string)($data['content'] ?? ''));

if ($conversationId <= 0 || $content === '') {
    jsonResponse(['success' => false, 'error' => 'Conversation ou contenu manquant'], 400);
}

try {
    $stmt = $pdo->prepare('SELECT 1 FROM dm_conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?) LIMIT 1');
    $stmt->execute([$conversationId, $senderId, $senderId]);
    if (!$stmt->fetchColumn()) {
        jsonResponse(['success' => false, 'error' => 'Accès refusé'], 403);
    }

    $stmt = $pdo->prepare('INSERT INTO dm_messages (conversation_id, sender_id, content) VALUES (?, ?, ?)');
    $stmt->execute([$conversationId, $senderId, $content]);

    jsonResponse(['success' => true, 'message_id' => (int) $pdo->lastInsertId()], 201);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'error' => 'Erreur de base de données'], 500);
}
