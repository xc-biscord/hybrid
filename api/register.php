<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

requireMethod('POST');
$data = getJsonInput();

$controller = laravelMake(\App\Http\Controllers\AuthController::class);
respondFromJsonResponse($controller->register($data));
