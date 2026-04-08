<?php
require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');
$userId = requireAuthUserId();
$data = getJsonInput();

$controller = apiKernel()->serverController();
respondFromController($controller->create($userId, $data));
