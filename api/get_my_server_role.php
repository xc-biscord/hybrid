<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

$userId = requireAuthUserId();
$rawServerId = $_GET['server_id'] ?? null;
$serverId = is_numeric($rawServerId) ? (int) $rawServerId : null;

$controller = laravelMake(\App\Http\Controllers\RoleModerationController::class);
respondFromJsonResponse($controller->getMyServerRole($userId, $serverId));
