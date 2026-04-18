<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Repositories\ServerMemberRepository;
use App\Services\PermissionService;

/**
 * Thin authorization layer around ModerationService rules.
 *
 * Each method mirrors the precondition of the corresponding service call,
 * without duplicating it. The service remains the single source of truth
 * and will still throw if invoked with an unauthorized actor.
 */
final class ServerMemberPolicy
{
    public function __construct(
        private PermissionService $permissionService,
        private ServerMemberRepository $serverMemberRepository,
    ) {
    }

    /**
     * Whether the actor may list members of a server.
     *
     * Mirrors ModerationService::listUsersInServer: the actor must be a
     * member of the server. There is no P1 bypass here, matching the
     * existing behavior.
     */
    public function viewAny(User $user, int $serverId): bool
    {
        if ($serverId <= 0) {
            return false;
        }

        return $this->serverMemberRepository->isMember($serverId, (int) $user->id);
    }

    /**
     * Whether the actor may change another member's role.
     *
     * Mirrors ModerationService::setMemberRole authorization: P2 server role
     * or P1 global.
     */
    public function updateRole(User $user, int $serverId): bool
    {
        return $this->permissionService->hasPermission((int) $user->id, $serverId, ['P2']);
    }

    /**
     * Whether the actor may kick a target from the server.
     *
     * Mirrors ModerationService::kickMember authorization:
     *  - P2 server role or P1 global is required, AND
     *  - if the target is P2, only a P1 global admin may kick them.
     *
     * $targetUserId is optional. When omitted the policy only checks the
     * base P2+ permission; the "P1 required to kick a P2" invariant is
     * re-enforced inside the service itself, so passing the target is an
     * optimization that lets callers short-circuit before hitting the
     * service.
     */
    public function kick(User $user, int $serverId, ?int $targetUserId = null): bool
    {
        if (!$this->permissionService->hasPermission((int) $user->id, $serverId, ['P2'])) {
            return false;
        }

        if ($targetUserId !== null) {
            $targetRole = $this->permissionService->getServerRole($targetUserId, $serverId);
            if ($targetRole === 'P2' && !$this->permissionService->isP1((int) $user->id)) {
                return false;
            }
        }

        return true;
    }
}
