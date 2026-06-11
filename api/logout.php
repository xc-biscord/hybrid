<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/laravel_proxy.php';

$controller = laravelMake(\App\Http\Controllers\AuthController::class);
$controller->logout();
