<?php
require_once __DIR__ . '/bootstrap.php';

$userId = requireAuthUserId();
$serverId = (int)($_GET['server_id'] ?? 0);

if ($serverId <= 0) {
    jsonResponse(['success' => false, 'error' => 'Requête invalide'], 400);
}

$memberCheck = $pdo->prepare('SELECT 1 FROM server_members WHERE server_id = ? AND user_id = ? LIMIT 1');
$memberCheck->execute([$serverId, $userId]);
if (!$memberCheck->fetchColumn()) {
    jsonResponse(['success' => false, 'error' => 'Accès refusé'], 403);
}

$stmt = $pdo->prepare('
    SELECT u.id, u.username,
           CASE WHEN gp.user_id IS NOT NULL THEN "P1" ELSE m.role END AS role
    FROM server_members m
    JOIN users u ON u.id = m.user_id
    LEFT JOIN global_permissions gp ON gp.user_id = u.id AND gp.permission_level = "P1"
    WHERE m.server_id = ?
    ORDER BY u.username ASC
');
$stmt->execute([$serverId]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonResponse(['success' => true, 'users' => $users]);
