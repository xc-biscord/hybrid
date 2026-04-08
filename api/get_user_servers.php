<?php
require_once __DIR__ . '/bootstrap.php';

$currentUserId = requireAuthUserId();

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    jsonResponse(['success' => false, 'error' => 'Paramètre user_id manquant ou invalide'], 400);
}

$targetUserId = (int) $_GET['user_id'];
$controller = apiKernel()->adminUserController();

respondFromController($controller->listUserServers($currentUserId, $targetUserId));
