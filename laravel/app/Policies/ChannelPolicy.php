<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Repositories\ServerMemberRepository;
use App\Services\PermissionService;

/**
 * Authorization rules for channel operations within a server.
 *
 * Wraps PermissionService without replicating its logic: the P1 bypass
 * and role matching stay inside PermissionService::hasPermission.
 */
final class ChannelPolicy
{
    public function __construct(
        private PermissionService $permissionService,
        private ServerMemberRepository $serverMemberRepository,
    ) {
    }

    /**
     * Whether the actor may list channels of a server.
     *
     * Mirrors ChannelService::listChannelsForServer: server membership
     * required.
     */
    public function viewAny(User $user, int $serverId): bool
    {
        if ($serverId <= 0) {
            return false;
        }

        return $this->serverMemberRepository->isMember($serverId, (int) $user->id);
    }

    /**
     * Whether the actor may create a channel in the server.
     *
     * Mirrors ChannelService::canCreateChannel: P2/P3 server role or P1
     * global.
     */
    public function create(User $user, int $serverId): bool
    {
        return $this->permissionService->hasPermission((int) $user->id, $serverId, ['P2', 'P3']);
    }
}
