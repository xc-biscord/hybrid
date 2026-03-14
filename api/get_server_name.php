<?php
require_once __DIR__ . '/../config/config.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID manquant']);
    exit;
}

$stmt = $pdo->prepare("SELECT name FROM servers WHERE id = ?");
$stmt->execute([$id]);
$server = $stmt->fetch(PDO::FETCH_ASSOC);

if ($server) {
    echo json_encode(['success' => true, 'name' => $server['name']]);
} else {
    echo json_encode(['success' => false, 'error' => 'Serveur introuvable']);
}
