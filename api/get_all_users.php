<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/permissions.php';

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "Non authentifié"]);
    exit;
}

$userId = $_SESSION['user_id'];

// Vérifie que l'utilisateur est bien P1
if (!isP1($userId, $pdo)) {
    echo json_encode(["success" => false, "error" => "Accès réservé aux P1"]);
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT 
            u.id, u.username, u.email, u.created_at,
            gp.permission_level
        FROM users u
        LEFT JOIN global_permissions gp ON u.id = gp.user_id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "users" => $users]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Erreur DB : " . $e->getMessage()]);
}
