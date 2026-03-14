<?php
require_once __DIR__ . '/../config/config.php';

$data = json_decode(file_get_contents("php://input"), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :username");
$stmt->execute(['username' => $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['user_id'] = $user['id'];
    echo json_encode(['success' => true, 'user_id' => $user['id']]);
} else {
    echo json_encode(['error' => 'Identifiants invalides']);
}
?>