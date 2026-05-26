<?php

require_once __DIR__ . '/bootstrap.php';

if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    jsonResponse(['logged_in' => false]);
}

$username = null;
if (isset($_SESSION['username']) && is_string($_SESSION['username']) && $_SESSION['username'] !== '') {
    $username = $_SESSION['username'];
} else {
    global $pdo;
    $stmt = $pdo->prepare('SELECT username FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $dbUsername = $stmt->fetchColumn();
    if (is_string($dbUsername) && $dbUsername !== '') {
        $username = $dbUsername;
    }
}

$payload = ['logged_in' => true];
if ($username !== null) {
    $payload['username'] = $username;
}

jsonResponse($payload);
