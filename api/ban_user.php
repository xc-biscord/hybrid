<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/permissions.php';
header('Content-Type: application/json');
session_start();

// Vérifie la connexion
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$currentUserId = $_SESSION['user_id'];

// Vérifie permission P1
if (!isP1($currentUserId, $pdo)) {
    echo json_encode(['success' => false, 'error' => 'Accès refusé : réservé aux P1']);
    exit;
}

// Données reçues
$data = json_decode(file_get_contents("php://input"), true);
$targetUserId = intval($data['user_id'] ?? 0);

if (!$targetUserId) {
    echo json_encode(['success' => false, 'error' => 'user_id invalide']);
    exit;
}

try {
    // Supprime l'utilisateur de tous les serveurs
    $pdo->prepare("DELETE FROM server_members WHERE user_id = ?")->execute([$targetUserId]);

    // Supprime ses messages
    $pdo->prepare("DELETE FROM messages WHERE user_id = ?")->execute([$targetUserId]);

    // Supprime son profil (avatar, bio, status)
    $pdo->prepare("DELETE FROM profiles WHERE user_id = ?")->execute([$targetUserId]);

    // Supprime ses droits P1 (s'il en avait)
    $pdo->prepare("DELETE FROM global_permissions WHERE user_id = ?")->execute([$targetUserId]);

    // Supprime l'utilisateur
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$targetUserId]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur DB : ' . $e->getMessage()]);
}
