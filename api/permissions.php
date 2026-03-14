<?php
require_once 'auth.php';

function isP1($userId, $pdo) {
    $stmt = $pdo->prepare("SELECT 1 FROM global_permissions WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch() !== false;
}

function getServerRole($userId, $serverId, $pdo) {
    $stmt = $pdo->prepare("SELECT role FROM server_members WHERE user_id = ? AND server_id = ?");
    $stmt->execute([$userId, $serverId]);
    $result = $stmt->fetch();
    return $result ? $result['role'] : null;
}

function hasPermission($userId, $serverId, $requiredRoles, $pdo) {
    if (isP1($userId, $pdo)) return true;
    $role = getServerRole($userId, $serverId, $pdo);
    return in_array($role, $requiredRoles);
}