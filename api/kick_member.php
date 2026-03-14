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

if (!hasPermission($userId, $serverId, ['P2'], $pdo) && !isP1($userId, $pdo)) {
    echo json_encode(["success" => false, "error" => "Permission refusée"]);
    exit;
}

$targetRole = getServerRole($targetUserId, $serverId, $pdo);
if ($targetRole === 'P2' && !isP1($userId, $pdo)) {
    echo json_encode(["success" => false, "error" => "Impossible de kick un P2 sans être P1"]);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM server_members WHERE user_id = ? AND server_id = ?");
$stmt->execute([$targetUserId, $serverId]);

echo json_encode(["success" => true]);
