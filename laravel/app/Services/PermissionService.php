<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\GlobalPermissionRepository;
use App\Repositories\ServerMemberRepository;

final class PermissionService
{
    public function __construct(
        private GlobalPermissionRepository $globalPermissionRepository,
        private ServerMemberRepository $serverMemberRepository,
    ) {
    }

    public function isP1(int $userId): bool
    {
        return $this->globalPermissionRepository->isGlobalAdmin($userId);
    }

    public function getServerRole(int $userId, int $serverId): ?string
    {
        return $this->serverMemberRepository->findRole($userId, $serverId);
    }

    /**
     * @param array<int, string> $requiredRoles
     */
    public function hasPermission(int $userId, int $serverId, array $requiredRoles): bool
    {
        if ($this->isP1($userId)) {
            return true;
        }

        $role = $this->getServerRole($userId, $serverId);

        return $role !== null && in_array($role, $requiredRoles, true);
    }
}
