<?php

require_once __DIR__ . '/bootstrap.php';

$userId = requireAuthUserId();
$rawServerId = $_GET['server_id'] ?? null;
$serverId = is_numeric($rawServerId) ? (int) $rawServerId : null;

$controller = apiKernel()->roleModerationController();
respondFromController($controller->getMyServerRole($userId, $serverId));
