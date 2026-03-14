<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            c.id AS conversation_id,
            MAX(m.sender_id) AS sender_id,
            COUNT(*) AS unread_count,
            u.username,
            p.avatar_url,
            MAX(m.created_at) AS last_message
        FROM dm_conversations c
        JOIN dm_messages m ON m.conversation_id = c.id
        LEFT JOIN dm_reads r ON r.conversation_id = c.id AND r.user_id = :uid
        JOIN users u ON m.sender_id = u.id
        LEFT JOIN profiles p ON p.user_id = u.id
        WHERE (c.user1_id = :uid OR c.user2_id = :uid)
          AND m.sender_id != :uid
          AND (r.last_read_at IS NULL OR m.created_at > r.last_read_at)
        GROUP BY c.id, u.id, u.username, p.avatar_url
        ORDER BY last_message DESC
    ");
    $stmt->execute(['uid' => $user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['unread_conversations' => $rows]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'DB error', 'debug' => $e->getMessage()]);
}
