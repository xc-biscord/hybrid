<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

$userId = requireAuthUserId();

$controller = laravelMake(\App\Http\Controllers\DmController::class);
respondFromJsonResponse($controller->notifications($userId));
