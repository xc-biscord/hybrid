<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

$userId = requireAuthUserId();
$conversationId = (int) ($_GET['conversation_id'] ?? 0);

$controller = laravelMake(\App\Http\Controllers\DmController::class);
respondFromJsonResponse($controller->messages($userId, $conversationId));
