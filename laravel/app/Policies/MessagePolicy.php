<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Repositories\MessageRepository;
use App\Services\PermissionService;

/**
 * Authorization rules for message operations.
 *
 * The delete rule wraps PermissionService::hasPermission. The read rule
 * wraps MessageRepository::userCanReadChannelMessages (which encodes the
 * channel-membership invariant used by MessageService::listMessages).
 */
final class MessagePolicy
{
    public function __construct(
        private PermissionService $permissionService,
        private MessageRepository $messageRepository,
    ) {
    }

    /**
     * Whether the actor may read messages of a channel.
     *
     * Mirrors MessageService::listMessages: membership of the server that
     * owns the channel is required. No P1 bypass (matches legacy).
     */
    public function viewInChannel(User $user, int $channelId): bool
    {
        if ($channelId <= 0) {
            return false;
        }

        return $this->messageRepository->userCanReadChannelMessages($channelId, (int) $user->id);
    }

    /**
     * Whether the actor may delete a message in the given server.
     *
     * Mirrors MessageService::canDeleteMessage: P2/P3 server role or P1
     * global.
     */
    public function deleteInServer(User $user, int $serverId): bool
    {
        return $this->permissionService->hasPermission((int) $user->id, $serverId, ['P2', 'P3']);
    }
}
