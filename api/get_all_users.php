<?php
require_once __DIR__ . '/bootstrap.php';

$currentUserId = requireAuthUserId();
$controller = apiKernel()->adminUserController();

respondFromController($controller->listUsers($currentUserId));
