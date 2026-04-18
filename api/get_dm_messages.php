<?php
require_once __DIR__ . '/bootstrap.php';

$userId = requireAuthUserId();
$conversationId = (int) ($_GET['conversation_id'] ?? 0);

$controller = apiKernel()->dmController();
respondFromController($controller->messages($userId, $conversationId));
