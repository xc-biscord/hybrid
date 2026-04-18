<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

$serverId = isset($_POST['server_id']) && is_numeric($_POST['server_id']) ? (int) $_POST['server_id'] : null;
$userId = isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

$controller = laravelMake(\App\Http\Controllers\InvitationController::class);
respondFromJsonResponse($controller->create($userId, $serverId));
