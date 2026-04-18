<?php
require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');
$currentUserId = requireAuthUserId();
$input = getJsonInput();

$controller = apiKernel()->dmController();
respondFromController($controller->start($currentUserId, $input));
