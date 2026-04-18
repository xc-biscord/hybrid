<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

requireAuthUserId();
$serverId = (int) ($_GET['id'] ?? 0);

$controller = laravelMake(\App\Http\Controllers\ServerController::class);
respondFromJsonResponse($controller->showName($serverId));
