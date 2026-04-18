<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

$userId = requireAuthUserId();
$channelId = (int) ($_GET['channel_id'] ?? 0);

$controller = laravelMake(\App\Http\Controllers\MessageController::class);
respondFromJsonResponse($controller->index($userId, $channelId));
