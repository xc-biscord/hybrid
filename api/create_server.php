<?php
require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');
$userId = requireAuthUserId();
$data = getJsonInput();

$name = trim((string)($data['nom'] ?? $data['name'] ?? ''));
if ($name === '') {
    jsonResponse(['success' => false, 'error' => 'Nom de serveur requis'], 400);
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('INSERT INTO servers (name, owner_id) VALUES (?, ?)');
    $stmt->execute([$name, $userId]);
    $serverId = (int) $pdo->lastInsertId();

    $stmt2 = $pdo->prepare("INSERT INTO server_members (server_id, user_id, role) VALUES (?, ?, 'P2')");
    $stmt2->execute([$serverId, $userId]);

    $pdo->commit();
    jsonResponse(['success' => true, 'server_id' => $serverId], 201);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(['success' => false, 'error' => 'Erreur serveur'], 500);
}
