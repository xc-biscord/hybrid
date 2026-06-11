<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

requireMethod('POST');
$currentUserId = requireAuthUserId();
$input = getJsonInput();

$controller = laravelMake(\App\Http\Controllers\DmController::class);
respondFromJsonResponse($controller->start($currentUserId, $input));
