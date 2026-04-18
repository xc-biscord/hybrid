<?php

require_once __DIR__ . '/bootstrap.php';

use App\Repositories\GlobalPermissionRepository;
use App\Repositories\ServerMemberRepository;
use App\Services\PermissionService;

function permissionService(PDO $pdo): PermissionService
{
    static $services = [];
    $key = spl_object_id($pdo);

    if (!isset($services[$key])) {
        $globalPermissionRepository = new GlobalPermissionRepository($pdo);
        $serverMemberRepository = new ServerMemberRepository($pdo);
        $services[$key] = new PermissionService($globalPermissionRepository, $serverMemberRepository);
    }

    return $services[$key];
}

function isP1(int $userId, PDO $pdo): bool
{
    return permissionService($pdo)->isP1($userId);
}

function getServerRole(int $userId, int $serverId, PDO $pdo): ?string
{
    return permissionService($pdo)->getServerRole($userId, $serverId);
}

function hasPermission(int $userId, int $serverId, array $requiredRoles, PDO $pdo): bool
{
    return permissionService($pdo)->hasPermission($userId, $serverId, $requiredRoles);
}
