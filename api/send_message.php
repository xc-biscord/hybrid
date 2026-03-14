<?php
require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');
$userId = requireAuthUserId();
$data = getJsonInput();

$channelId = (int) ($data['channel_id'] ?? 0);
$content = trim((string)($data['content'] ?? ''));

if ($channelId <= 0 || $content === '') {
    jsonResponse(['success' => false, 'error' => 'Message vide ou channel manquant'], 400);
}

try {
    $check = $pdo->prepare('SELECT 1 FROM channels WHERE id = ? LIMIT 1');
    $check->execute([$channelId]);
    if (!$check->fetchColumn()) {
        jsonResponse(['success' => false, 'error' => 'Channel inexistant'], 404);
    }

    $stmt = $pdo->prepare('INSERT INTO messages (channel_id, user_id, content) VALUES (?, ?, ?)');
    $stmt->execute([$channelId, $userId, $content]);

    jsonResponse(['success' => true, 'message_id' => (int) $pdo->lastInsertId()], 201);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'error' => 'Erreur SQL'], 500);
}
