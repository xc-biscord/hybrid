<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

$userId = isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$code = trim((string) ($_POST['code'] ?? ''));

$controller = laravelMake(\App\Http\Controllers\InvitationController::class);
respondFromJsonResponse($controller->accept($userId, $code));
