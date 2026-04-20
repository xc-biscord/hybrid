<?php
require_once __DIR__ . '/bootstrap.php';

$userId = requireAuthUserId();

$controller = apiKernel()->dmController();
respondFromController($controller->notifications($userId));
