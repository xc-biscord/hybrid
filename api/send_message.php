<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}


$data = json_decode(file_get_contents("php://input"), true);
$channel_id = $data['channel_id'] ?? null;
$content = trim($data['content'] ?? '');

if (!$channel_id || !$content) {
    echo json_encode(['success' => false, 'error' => 'Message vide ou channel manquant']);
    exit;
}

try {
    $check = $pdo->prepare("SELECT id FROM channels WHERE id = ?");
    $check->execute([$channel_id]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Channel inexistant']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO messages (channel_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$channel_id, $_SESSION['user_id'], $content]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur SQL : ' . $e->getMessage()]);
}
