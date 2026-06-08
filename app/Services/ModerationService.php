<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ServerMemberRepository;

final class ModerationService
{
    private const VALID_MEMBER_ROLES = ['P2', 'P3', 'member'];

    public function __construct(
        private PermissionService $permissionService,
        private ServerMemberRepository $serverMemberRepository,
    ) {
    }

    public function getMyServerRole(int $userId, ?int $serverId): ?string
    {
        if ($serverId === null) {
            return null;
        }

        return $this->permissionService->getServerRole($userId, $serverId);
    }

    /**
     * @return array<int, array{id:int,username:string,role:string}>
     */
    public function listUsersInServer(int $actorUserId, int $serverId): array
    {
        if ($serverId <= 0) {
            throw new \InvalidArgumentException('Requête invalide');
        }

        if (!$this->serverMemberRepository->isMember($serverId, $actorUserId)) {
            throw new \DomainException('Accès refusé');
        }

        return $this->serverMemberRepository->listUsersWithEffectiveRolesInServer($serverId);
    }

    public function setMemberRole(int $actorUserId, int $serverId, int $targetUserId, string $newRole): void
    {
        if (!in_array($newRole, self::VALID_MEMBER_ROLES, true)) {
            throw new \InvalidArgumentException('Rôle invalide');
        }

        if (!$this->permissionService->hasPermission($actorUserId, $serverId, ['P2', 'P3'])) {
            throw new \DomainException('Permission refusée');
        }

        $this->serverMemberRepository->updateRole($serverId, $targetUserId, $newRole);
    }

    public function kickMember(int $actorUserId, int $serverId, int $targetUserId): void
    {
        if (!$this->permissionService->hasPermission($actorUserId, $serverId, ['P2', 'P3'])) {
            throw new \DomainException('Permission refusée');
        }

        $targetRole = $this->permissionService->getServerRole($targetUserId, $serverId);
        if ($targetRole === 'P2' && !$this->permissionService->isP1($actorUserId)) {
            throw new \DomainException('Impossible de kick un P2 sans être P1');
        }

        $this->serverMemberRepository->removeMember($serverId, $targetUserId);
    }
}
