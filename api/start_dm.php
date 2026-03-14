<?php

header("Content-Type: application/json");
session_start();

require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$other_user_id = isset($input['other_user_id']) ? (int)$input['other_user_id'] : 0;
$current_user_id = $_SESSION['user_id'];

if ($other_user_id <= 0 || $other_user_id === $current_user_id) {
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

$user1 = min($current_user_id, $other_user_id);
$user2 = max($current_user_id, $other_user_id);

try {
    $stmt = $pdo->prepare("SELECT id FROM dm_conversations WHERE user1_id = ? AND user2_id = ?");
    $stmt->execute([$user1, $user2]);
    $conversation = $stmt->fetch();

    if ($conversation) {
        echo json_encode(['conversation_id' => $conversation['id'], 'status' => 'exists']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO dm_conversations (user1_id, user2_id) VALUES (?, ?)");
    $stmt->execute([$user1, $user2]);

    $conversation_id = $pdo->lastInsertId();
    echo json_encode(['conversation_id' => $conversation_id, 'status' => 'created']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'debug' => $e->getMessage()]);
}
