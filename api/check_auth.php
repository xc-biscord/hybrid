<?php

require_once __DIR__ . '/bootstrap.php';

if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    jsonResponse(['logged_in' => false]);
}

$username = null;
if (isset($_SESSION['username']) && is_string($_SESSION['username']) && $_SESSION['username'] !== '') {
    $username = $_SESSION['username'];
}

$payload = ['logged_in' => true];
if ($username !== null) {
    $payload['username'] = $username;
}

jsonResponse($payload);
