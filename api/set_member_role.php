<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

$userId = requireAuthUserId();
$data = getJsonInput();

$controller = laravelMake(\App\Http\Controllers\RoleModerationController::class);
respondFromJsonResponse($controller->setMemberRole($userId, $data));
