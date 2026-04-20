<?php



require_once 'auth.php';
$data = json_decode(file_get_contents("php://input"), true);

$bio = $data['bio'] ?? '';
$avatar_url = $data['avatar_url'] ?? '';
$status = $data['status'] ?? 'disponible';

$stmt = $pdo->prepare("INSERT INTO profiles (user_id, bio, avatar_url, status)
                       VALUES (?, ?, ?, ?)
                       ON DUPLICATE KEY UPDATE bio = VALUES(bio), avatar_url = VALUES(avatar_url), status = VALUES(status)");
$stmt->execute([$_SESSION['user_id'], $bio, $avatar_url, $status]);

echo json_encode(['success' => true]);