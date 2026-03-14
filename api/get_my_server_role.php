<?php
require_once 'auth.php';
session_start();

$userId = $_SESSION['user_id'];
$serverId = $_GET['server_id'] ?? null;

$stmt = $pdo->prepare("SELECT 1 FROM global_permissions WHERE user_id = ?");
$stmt->execute([$userId]);
if ($stmt->fetch()) {
    echo json_encode(['success' => true, 'role' => 'P1']);
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM server_members WHERE user_id = ? AND server_id = ?");
$stmt->execute([$userId, $serverId]);
$role = $stmt->fetchColumn();

echo json_encode(['success' => true, 'role' => $role]);
