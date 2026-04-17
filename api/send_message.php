<?php
require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');
$userId = requireAuthUserId();
$data = getJsonInput();

$controller = apiKernel()->messageController();
respondFromController($controller->create($userId, $data));
