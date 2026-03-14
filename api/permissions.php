<?php
require_once __DIR__ . '/auth.php';

function isP1(int $userId, PDO $pdo): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM global_permissions WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    return $stmt->fetchColumn() !== false;
}

function getServerRole(int $userId, int $serverId, PDO $pdo): ?string
{
    $stmt = $pdo->prepare('SELECT role FROM server_members WHERE user_id = ? AND server_id = ? LIMIT 1');
    $stmt->execute([$userId, $serverId]);
    $role = $stmt->fetchColumn();
    return $role === false ? null : (string) $role;
}

function hasPermission(int $userId, int $serverId, array $requiredRoles, PDO $pdo): bool
{
    if (isP1($userId, $pdo)) {
        return true;
    }

    $role = getServerRole($userId, $serverId, $pdo);
    return $role !== null && in_array($role, $requiredRoles, true);
}
