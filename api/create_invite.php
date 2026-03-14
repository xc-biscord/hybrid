<?php
require_once __DIR__ . '/../config/config.php';

$server_id = $_POST['server_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$server_id || !$user_id) {
    echo json_encode(['success' => false, 'error' => 'Données manquantes.']);
    exit;
}

// Vérifie que le user fait partie du serveur
$stmt = $pdo->prepare("SELECT 1 FROM server_members WHERE server_id = ? AND user_id = ?");
$stmt->execute([$server_id, $user_id]);

if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé.']);
    exit;
}

$code = bin2hex(random_bytes(8));

$stmt = $pdo->prepare("INSERT INTO invitations (server_id, code) VALUES (?, ?)");
$stmt->execute([$server_id, $code]);

echo json_encode([
    'success' => true,
    'invite_url' => "https://biscord-api-stg.xcsoftworks.com/invitation.html?code=$code"
]);
