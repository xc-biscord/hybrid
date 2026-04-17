<?php
require_once __DIR__ . '/bootstrap.php';

$userId = requireAuthUserId();
$serverId = (int)($_GET['server_id'] ?? 0);

$controller = apiKernel()->channelController();
respondFromController($controller->index($userId, $serverId));
