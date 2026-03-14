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
$messageId = intval($data['message_id'] ?? 0);

$stmt = $pdo->prepare("SELECT m.channel_id, c.server_id FROM messages m JOIN channels c ON m.channel_id = c.id WHERE m.id = ?");
$stmt->execute([$messageId]);
$info = $stmt->fetch();

if (!$info) {
    echo json_encode(["success" => false, "error" => "Message introuvable"]);
    exit;
}

$serverId = $info['server_id'];

if (!hasPermission($userId, $serverId, ['P2', 'P3'], $pdo) && !isP1($userId, $pdo)) {
    echo json_encode(["success" => false, "error" => "Permission refusée"]);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
$stmt->execute([$messageId]);

echo json_encode(["success" => true]);
