<?php
require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');
$data = getJsonInput();

$username = trim((string)($data['username'] ?? ''));
$email = trim((string)($data['email'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($username === '' || $email === '' || $password === '') {
    jsonResponse(['success' => false, 'error' => 'Champs requis manquants'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'error' => 'Email invalide'], 400);
}

$hash = password_hash($password, PASSWORD_BCRYPT);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$username, $email, $hash]);

    $userId = (int) $pdo->lastInsertId();
    $_SESSION['user_id'] = $userId;

    $stmt = $pdo->prepare('INSERT INTO profiles (user_id, avatar_url, bio, status) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        $userId,
        'https://biscord-api-stg.xcsoftworks.com/assets/default-user.png',
        '',
        'En ligne',
    ]);

    // Inscription auto sur le hub public.
    $stmt = $pdo->prepare('INSERT IGNORE INTO server_members (server_id, user_id) VALUES (?, ?)');
    $stmt->execute([1, $userId]);

    $welcomeMessage = "🎉 Bienvenue à @{$username} sur le Hub Biscord !";
    $stmt = $pdo->prepare('INSERT INTO messages (channel_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([1, $userId, $welcomeMessage]);

    $pdo->commit();
    jsonResponse(['success' => true], 201);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $isDuplicate = ($e->errorInfo[1] ?? null) === 1062;
    jsonResponse(
        ['success' => false, 'error' => $isDuplicate ? 'Nom d\'utilisateur ou email déjà utilisé' : 'Erreur SQL'],
        $isDuplicate ? 409 : 500
    );
}
