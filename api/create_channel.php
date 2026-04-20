<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

requireMethod('POST');
$userId = requireAuthUserId();
$data = getJsonInput();

$controller = laravelMake(\App\Http\Controllers\ChannelController::class);
respondFromJsonResponse($controller->create($userId, $data));
