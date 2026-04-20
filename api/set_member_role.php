<?php

require_once __DIR__ . '/bootstrap.php';

$userId = requireAuthUserId();
$data = getJsonInput();

$controller = apiKernel()->roleModerationController();
respondFromController($controller->setMemberRole($userId, $data));
