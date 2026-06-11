<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

$controller = laravelMake(\App\Http\Controllers\AuthController::class);
$response = $controller->auth();
if ($response instanceof \Illuminate\Http\JsonResponse) {
    respondFromJsonResponse($response);
}

exit;
