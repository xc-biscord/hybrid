<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['logged_in' => false]);
    exit;
}

require_once __DIR__ . '/../config/config.php';

$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo json_encode([
        'logged_in' => true,
        'username' => $user['username']
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}
