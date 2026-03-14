<?php
require_once __DIR__ . '/bootstrap.php';

requireAuthUserId();
$serverId = (int)($_GET['id'] ?? 0);

if ($serverId <= 0) {
    jsonResponse(['success' => false, 'error' => 'ID manquant'], 400);
}

$stmt = $pdo->prepare('SELECT name FROM servers WHERE id = ? LIMIT 1');
$stmt->execute([$serverId]);
$server = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$server) {
    jsonResponse(['success' => false, 'error' => 'Serveur introuvable'], 404);
}

jsonResponse(['success' => true, 'name' => $server['name']]);
