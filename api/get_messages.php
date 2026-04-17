<?php
require_once __DIR__ . '/bootstrap.php';

$userId = requireAuthUserId();
$channelId = (int)($_GET['channel_id'] ?? 0);

$controller = apiKernel()->messageController();
respondFromController($controller->index($userId, $channelId));
