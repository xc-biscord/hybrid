<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/config.php';

$data = json_decode(file_get_contents("php://input"), true);
$username = isset($data['username']) ? trim($data['username']) : '';
$email = isset($data['email']) ? trim($data['email']) : '';
$password = isset($data['password']) ? $data['password'] : '';

if (!$username || !$email || !$password) {
    echo json_encode(['error' => 'Missing fields']);
    exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT        );

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $hash]);

    $user_id = $pdo->lastInsertId();
    $_SESSION['user_id'] = $user_id;

    $stmt = $pdo->prepare("INSERT INTO profiles (user_id, avatar_url, bio, status) VALUES (?, ?, '', 'En ligne')");
    $stmt->execute([
        $user_id,
        "https://biscord-api-stg.xcsoftworks.com/assets/default-user.png"
    ]);

    $stmt = $pdo->prepare("INSERT INTO server_members (server_id, user_id) VALUES (?, ?)");
    $stmt->execute([1, $user_id]);

    $welcomeMessage = "🎉 Bienvenue à @$username sur le Hub Biscord !";
    $stmt = $pdo->prepare("INSERT INTO messages (channel_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([1, $user_id, $welcomeMessage]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(400);
    echo json_encode(['error' => 'SQL error']);
}
