<?php
require_once __DIR__ . '/bootstrap.php';

$userId = requireAuthUserId();
$controller = apiKernel()->serverController();

respondFromController($controller->index($userId));
