<?php
require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');
$senderId = requireAuthUserId();
$data = getJsonInput();

$controller = apiKernel()->dmController();
respondFromController($controller->send($senderId, $data));
