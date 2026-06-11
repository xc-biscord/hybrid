<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'error' => 'Non connecté'], 200);
}

$payload = getJsonInput();
$controller = laravelMake(\App\Http\Controllers\AccountController::class);

respondFromJsonResponse($controller->update((int) $_SESSION['user_id'], $payload));
