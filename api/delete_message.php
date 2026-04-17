<?php
require_once __DIR__ . '/bootstrap.php';

$userId = requireAuthUserId();
$data = json_decode(file_get_contents('php://input') ?: '', true);

$controller = apiKernel()->messageController();
respondFromController($controller->delete($userId, is_array($data) ? $data : []));
