<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

$currentUserId = requireAuthUserId();
$controller = laravelMake(\App\Http\Controllers\AdminUserController::class);

respondFromJsonResponse($controller->listUsers($currentUserId));
