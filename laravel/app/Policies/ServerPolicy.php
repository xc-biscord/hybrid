<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Server;
use App\Models\User;
use App\Repositories\ServerMemberRepository;
use App\Services\PermissionService;

/**
 * Server-scoped authorization rules.
 *
 * Kept intentionally small: channel and message specific rules live in
 * their own policies. This class only covers server-level reads and the
 * generic server permission check.
 */
final class ServerPolicy
{
    public function __construct(
        private PermissionService $permissionService,
        private ServerMemberRepository $serverMemberRepository,
    ) {
    }

    /**
     * Any ability performed by a P1 global admin is allowed.
     *
     * This mirrors the P1 bypass already baked into
     * PermissionService::hasPermission. It is repeated here so that
     * policy methods that do not go through hasPermission (e.g. `view`)
     * also honor the global admin bypass.
     *
     * Returning null lets the per-ability method decide; returning true
     * short-circuits to "allowed".
     */
    public function before(User $user, string $ability): ?bool
    {
        return $this->permissionService->isP1((int) $user->id) ? true : null;
    }

    /**
     * Whether the actor may view the server (its channels / basic info).
     *
     * Currently "being a member" is the only requirement in the legacy
     * services (ChannelService::listChannelsForServer).
     */
    public function view(User $user, Server $server): bool
    {
        return $this->serverMemberRepository->isMember($server->id, (int) $user->id);
    }

    /**
     * Generic server permission check. Prefer specific policies where
     * they exist; this is the escape hatch for ad-hoc role checks.
     *
     * @param  array<int, string>  $requiredRoles
     */
    public function hasRole(User $user, int $serverId, array $requiredRoles): bool
    {
        return $this->permissionService->hasPermission((int) $user->id, $serverId, $requiredRoles);
    }
}
