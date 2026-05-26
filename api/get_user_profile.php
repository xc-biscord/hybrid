<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

$controller = laravelMake(\App\Http\Controllers\Api\GetUserProfileController::class);
respondFromJsonResponse($controller->handle(request()));
