<?php
require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');
$data = getJsonInput();

$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($username === '' || $password === '') {
    jsonResponse(['success' => false, 'error' => 'Identifiants manquants'], 400);
}

$stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE username = :username OR email = :username LIMIT 1');
$stmt->execute(['username' => $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonResponse(['success' => false, 'error' => 'Identifiants invalides'], 401);
}

$_SESSION['user_id'] = (int) $user['id'];
jsonResponse(['success' => true, 'user_id' => (int) $user['id']]);
