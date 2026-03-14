<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/permissions.php';

header('Content-Type: application/json');

// Authentification
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$userId = $_SESSION['user_id'];

// Récupération des données
$data = json_decode(file_get_contents("php://input"), true);
$server_id = $data['server_id'] ?? null;
$name = trim($data['name'] ?? '');

if (!$server_id || !$name) {
    echo json_encode(['success' => false, 'error' => 'Requête invalide']);
    exit;
}

// Vérification des permissions (P1, P2, P3)
if (!hasPermission($userId, $server_id, ['P2', 'P3'], $pdo)) {
    echo json_encode(['success' => false, 'error' => 'Permission refusée']);
    exit;
}

// Création du channel
$stmt = $pdo->prepare("INSERT INTO channels (server_id, name) VALUES (?, ?)");
$stmt->execute([$server_id, $name]);

echo json_encode(['success' => true, 'channel_id' => $pdo->lastInsertId()]);
