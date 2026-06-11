<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

requireMethod('POST');
$senderId = requireAuthUserId();
$data = getJsonInput();

$controller = laravelMake(\App\Http\Controllers\DmController::class);
respondFromJsonResponse($controller->send($senderId, $data));
