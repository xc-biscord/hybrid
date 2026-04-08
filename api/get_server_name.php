<?php
require_once __DIR__ . '/bootstrap.php';

requireAuthUserId();
$serverId = (int) ($_GET['id'] ?? 0);
$controller = apiKernel()->serverController();

respondFromController($controller->showName($serverId));
