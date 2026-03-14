<?php
require_once __DIR__ . '/permissions.php';

requireMethod('POST');
$userId = requireAuthUserId();
$data = getJsonInput();

$serverId = (int)($data['server_id'] ?? 0);
$name = trim((string)($data['name'] ?? ''));

if ($serverId <= 0 || $name === '') {
    jsonResponse(['success' => false, 'error' => 'Requête invalide'], 400);
}

if (!hasPermission($userId, $serverId, ['P2', 'P3'], $pdo)) {
    jsonResponse(['success' => false, 'error' => 'Permission refusée'], 403);
}

$stmt = $pdo->prepare('INSERT INTO channels (server_id, name) VALUES (?, ?)');
$stmt->execute([$serverId, $name]);

jsonResponse(['success' => true, 'channel_id' => (int) $pdo->lastInsertId()], 201);
