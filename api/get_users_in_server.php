<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

$userId = requireAuthUserId();
$serverId = (int)($_GET['server_id'] ?? 0);

$controller = laravelMake(\App\Http\Controllers\RoleModerationController::class);
respondFromJsonResponse($controller->listUsersInServer($userId, $serverId));
