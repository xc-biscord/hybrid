<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/permissions.php';
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$currentUserId = $_SESSION['user_id'];

// vérifie que l'utilisateur est P1
if (!isP1($currentUserId, $pdo)) {
    echo json_encode(['success' => false, 'error' => 'Accès refusé : réservé aux P1']);
    exit;
}

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Paramètre user_id manquant ou invalide']);
    exit;
}

$userId = intval($_GET['user_id']);

try {
    $stmt = $pdo->prepare("
        SELECT s.id, s.name
        FROM server_members sm
        JOIN servers s ON s.id = sm.server_id
        WHERE sm.user_id = ?
        ORDER BY s.name ASC
    ");
    $stmt->execute([$userId]);
    $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'servers' => $servers]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur DB : ' . $e->getMessage()]);
}
