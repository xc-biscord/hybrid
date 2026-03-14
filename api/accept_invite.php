<?php

require_once __DIR__ . '/../config/config.php';


header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$code = $_POST['code'] ?? null;

if (!$user_id || !$code) {
    echo json_encode(['success' => false, 'error' => 'Données manquantes.']);
    exit;
}

// Vérifie le code d'invitation
$stmt = $pdo->prepare("SELECT server_id FROM invitations WHERE code = ?");
$stmt->execute([$code]);
$invite = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invite) {
    echo json_encode(['success' => false, 'error' => 'Invitation invalide.']);
    exit;
}

// Vérifie si l'utilisateur est déjà dans le serveur
$stmt = $pdo->prepare("SELECT 1 FROM server_members WHERE server_id = ? AND user_id = ?");
$stmt->execute([$invite['server_id'], $user_id]);

if (!$stmt->fetch()) {
    $stmt = $pdo->prepare("INSERT INTO server_members (server_id, user_id) VALUES (?, ?)");
    $stmt->execute([$invite['server_id'], $user_id]);
}

echo json_encode(['success' => true, 'server_id' => $invite['server_id']]);
