<?php
require_once __DIR__ . '/bootstrap.php';

$userId = requireAuthUserId();
$serverId = (int)($_GET['server_id'] ?? 0);

if ($serverId <= 0) {
    jsonResponse(['success' => false, 'error' => 'Paramètre server_id invalide'], 400);
}

$check = $pdo->prepare('SELECT 1 FROM server_members WHERE server_id = ? AND user_id = ? LIMIT 1');
$check->execute([$serverId, $userId]);
if (!$check->fetchColumn()) {
    jsonResponse(['success' => false, 'error' => 'Accès refusé'], 403);
}

$stmt = $pdo->prepare('SELECT id, name FROM channels WHERE server_id = ? ORDER BY id ASC');
$stmt->execute([$serverId]);
$channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonResponse(['success' => true, 'channels' => $channels]);
