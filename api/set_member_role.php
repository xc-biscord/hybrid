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
$data = json_decode(file_get_contents("php://input"), true);

$targetUserId = intval($data['target_user_id'] ?? 0);
$serverId = intval($data['server_id'] ?? 0);
$newRole = $data['new_role'] ?? '';

$validRoles = ['P2', 'P3', 'member'];
if (!in_array($newRole, $validRoles)) {
    echo json_encode(["success" => false, "error" => "Rôle invalide"]);
    exit;
}

// seuls les P1 ou P2 peuvent modifier les rôles
if (!hasPermission($userId, $serverId, ['P2'], $pdo) && !isP1($userId, $pdo)) {
    echo json_encode(["success" => false, "error" => "Permission refusée"]);
    exit;
}

$stmt = $pdo->prepare("UPDATE server_members SET role = ? WHERE user_id = ? AND server_id = ?");
$stmt->execute([$newRole, $targetUserId, $serverId]);

echo json_encode(["success" => true]);
