<?php

require_once __DIR__ . '/bootstrap.php';

$userId = requireAuthUserId();
$serverId = (int)($_GET['server_id'] ?? 0);

$controller = apiKernel()->roleModerationController();
respondFromController($controller->listUsersInServer($userId, $serverId));
