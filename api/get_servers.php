<?php
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$stmt = $pdo->prepare("SELECT s.id, s.name
                       FROM servers s
                       JOIN server_members m ON m.server_id = s.id
                       WHERE m.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$servers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'servers' => $servers]);
?>
