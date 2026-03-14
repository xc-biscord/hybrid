<?php

require_once __DIR__ . '/../config/config.php';
session_start();


$channel_id = $_GET['channel_id'] ?? null;

if (!isset($_SESSION['user_id']) || !$channel_id) {
    echo json_encode(['success' => false, 'error' => 'Requête invalide']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT m.id AS id, m.content, m.created_at, u.username, u.id AS user_id, p.avatar_url
    FROM messages m
    JOIN users u ON u.id = m.user_id
    LEFT JOIN profiles p ON p.user_id = u.id
    WHERE m.channel_id = ?
    ORDER BY m.created_at ASC
");

$stmt->execute([$channel_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'messages' => $messages]);
