<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

$userId = requireAuthUserId();
$data = json_decode(file_get_contents('php://input') ?: '', true);

$controller = laravelMake(\App\Http\Controllers\MessageController::class);
respondFromJsonResponse($controller->delete($userId, is_array($data) ? $data : []));
