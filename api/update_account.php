<?php

require_once __DIR__ . '/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'error' => 'Non connecté'], 200);
}

$payload = getJsonInput();
$controller = apiKernel()->accountController();

respondFromController($controller->update((int) $_SESSION['user_id'], $payload));
