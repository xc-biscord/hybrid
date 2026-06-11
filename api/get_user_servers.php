<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

$currentUserId = requireAuthUserId();

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    jsonResponse(['success' => false, 'error' => 'Paramètre user_id manquant ou invalide'], 400);
}

$targetUserId = (int) $_GET['user_id'];
$controller = laravelMake(\App\Http\Controllers\AdminUserController::class);

respondFromJsonResponse($controller->listUserServers($currentUserId, $targetUserId));
