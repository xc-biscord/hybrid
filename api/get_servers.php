<?php
require_once __DIR__ . '/bootstrap.php';

$userId = requireAuthUserId();

$stmt = $pdo->prepare('
    SELECT s.id, s.name
    FROM servers s
    JOIN server_members m ON m.server_id = s.id
    WHERE m.user_id = ?
    ORDER BY s.name ASC
');
$stmt->execute([$userId]);
$servers = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonResponse(['success' => true, 'servers' => $servers]);
