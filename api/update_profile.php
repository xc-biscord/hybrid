<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

$request = new \Illuminate\Http\Request([], getJsonInput());
$controller = laravelMake(\App\Http\Controllers\Api\UpdateProfileController::class);
respondFromJsonResponse($controller->handle($request));
