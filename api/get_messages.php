<?php
require_once __DIR__ . '/bootstrap.php';

$userId = requireAuthUserId();
$channelId = (int)($_GET['channel_id'] ?? 0);

if ($channelId <= 0) {
    jsonResponse(['success' => false, 'error' => 'Paramètre channel_id invalide'], 400);
}

// Évite de dévoiler les messages d'un channel hors serveur utilisateur.
$access = $pdo->prepare('
    SELECT 1
    FROM channels c
    JOIN server_members sm ON sm.server_id = c.server_id
    WHERE c.id = ? AND sm.user_id = ?
    LIMIT 1
');
$access->execute([$channelId, $userId]);
if (!$access->fetchColumn()) {
    jsonResponse(['success' => false, 'error' => 'Accès refusé'], 403);
}

$stmt = $pdo->prepare('
    SELECT m.id, m.content, m.created_at, u.username, u.id AS user_id, p.avatar_url
    FROM messages m
    JOIN users u ON u.id = m.user_id
    LEFT JOIN profiles p ON p.user_id = u.id
    WHERE m.channel_id = ?
    ORDER BY m.created_at ASC, m.id ASC
');
$stmt->execute([$channelId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonResponse(['success' => true, 'messages' => $messages]);
