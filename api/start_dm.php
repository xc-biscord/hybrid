<?php
require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');
$currentUserId = requireAuthUserId();
$input = getJsonInput();

$otherUserId = (int)($input['other_user_id'] ?? 0);
if ($otherUserId <= 0 || $otherUserId === $currentUserId) {
    jsonResponse(['success' => false, 'error' => 'Identifiant utilisateur invalide'], 400);
}

$user1 = min($currentUserId, $otherUserId);
$user2 = max($currentUserId, $otherUserId);

try {
    $stmt = $pdo->prepare('SELECT id FROM dm_conversations WHERE user1_id = ? AND user2_id = ? LIMIT 1');
    $stmt->execute([$user1, $user2]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($conversation) {
        jsonResponse(['success' => true, 'conversation_id' => (int) $conversation['id'], 'status' => 'exists']);
    }

    $stmt = $pdo->prepare('INSERT INTO dm_conversations (user1_id, user2_id) VALUES (?, ?)');
    $stmt->execute([$user1, $user2]);
    jsonResponse(['success' => true, 'conversation_id' => (int) $pdo->lastInsertId(), 'status' => 'created'], 201);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'error' => 'Erreur de base de données'], 500);
}
