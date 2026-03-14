<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
$user_id = $_SESSION['user_id'];

if ($conversation_id <= 0) {
    echo json_encode(['error' => 'Invalid conversation ID']);
    exit;
}

try {
    // Vérifie que l'utilisateur appartient à la conversation
    $stmt = $pdo->prepare("SELECT user1_id, user2_id FROM dm_conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
    $stmt->execute([$conversation_id, $user_id, $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    // Identifie l'autre utilisateur (le destinataire)
    $other_user_id = ($row['user1_id'] == $user_id) ? $row['user2_id'] : $row['user1_id'];

    // Récupère les infos du destinataire
    $stmt = $pdo->prepare("
        SELECT u.username, p.avatar_url
        FROM users u
        LEFT JOIN profiles p ON u.id = p.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$other_user_id]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recipient) {
        echo json_encode(['error' => 'Recipient not found']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT dm.*, u.username, p.avatar_url AS avatar
        FROM dm_messages dm
        JOIN users u ON dm.sender_id = u.id
        LEFT JOIN profiles p ON u.id = p.user_id
        WHERE dm.conversation_id = ?
        ORDER BY dm.created_at ASC
    ");
    $stmt->execute([$conversation_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $update = $pdo->prepare("
        INSERT INTO dm_reads (user_id, conversation_id, last_read_at)
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE last_read_at = NOW()
    ");
    $update->execute([$user_id, $conversation_id]);

    echo json_encode([
        'messages' => $messages,
        'recipient' => [
            'id' => $other_user_id,
            'username' => $recipient['username'],
            'avatar_url' => $recipient['avatar_url'] ?? null
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'debug' => $e->getMessage()]);
}
