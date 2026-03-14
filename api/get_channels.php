<?php
require_once __DIR__ . '/../config/config.php';

$server_id = $_GET['server_id'] ?? null;

if (!isset($_SESSION['user_id']) || !$server_id) {
    echo json_encode(['success' => false, 'error' => 'Requête invalide']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name FROM channels WHERE server_id = ?");
$stmt->execute([$server_id]);
$channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'channels' => $channels]);
