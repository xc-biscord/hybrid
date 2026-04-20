<?php
require_once __DIR__ . '/config/config.php';

session_start();
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$code = $_GET['code'] ?? null;

if (!$user_id || !$code) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté ou lien invalide.']);
    exit;
}

$stmt = $pdo->prepare("SELECT server_id, created_at FROM invitations WHERE code = ?");
$stmt->execute([$code]);
$invite = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invite) {
    echo json_encode(['success' => false, 'error' => 'Lien invalide.']);
    exit;
}

// (Optionnel) Vérifier expiration...

// Récupère le nom du serveur
$stmt = $pdo->prepare("SELECT name FROM servers WHERE id = ?");
$stmt->execute([$invite['server_id']]);
$server = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'server_id' => $invite['server_id'],
    'server_name' => $server['name'],
]);
