<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

laravelApp();
$request = \Illuminate\Http\Request::createFromGlobals();
$controller = laravelMake(\App\Http\Controllers\Api\GetProfileController::class);
respondFromJsonResponse($controller->handle($request));
