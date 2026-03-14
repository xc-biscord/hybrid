<?php
header("Content-Type: application/json");
session_start();

require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$conversation_id = isset($data['conversation_id']) ? (int)$data['conversation_id'] : 0;
$content = isset($data['content']) ? trim($data['content']) : '';
$sender_id = $_SESSION['user_id'];

if ($conversation_id <= 0 || $content === '') {
    echo json_encode(['error' => 'Missing conversation ID or content']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM dm_conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
    $stmt->execute([$conversation_id, $sender_id, $sender_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['error' => 'Not part of this conversation']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO dm_messages (conversation_id, sender_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$conversation_id, $sender_id, $content]);

    echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données', 'debug' => $e->getMessage()]);
}
