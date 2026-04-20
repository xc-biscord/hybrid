<?php

declare(strict_types=1);

namespace App\Services;

use App\Middleware\AdminMiddleware;
use App\Repositories\ChannelRepository;
use App\Repositories\ServerMemberRepository;
use DomainException;
use InvalidArgumentException;

final class ChannelService
{
    public function __construct(
        private ChannelRepository $channelRepository,
        private ServerMemberRepository $serverMemberRepository,
        private AdminMiddleware $adminMiddleware,
    ) {
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    public function listChannelsForServer(int $userId, int $serverId): array
    {
        if ($serverId <= 0) {
            throw new InvalidArgumentException('Paramètre server_id invalide');
        }

        if (!$this->serverMemberRepository->isMember($serverId, $userId)) {
            throw new DomainException('Accès refusé');
        }

        return $this->channelRepository->findByServerId($serverId);
    }

    public function createChannelFromPayload(int $userId, array $data): int
    {
        $serverId = (int) ($data['server_id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));

        if ($serverId <= 0 || $name === '') {
            throw new InvalidArgumentException('Requête invalide');
        }

        if (!$this->canCreateChannel($userId, $serverId)) {
            throw new DomainException('Permission refusée');
        }

        return $this->channelRepository->create($serverId, $name);
    }

    private function canCreateChannel(int $userId, int $serverId): bool
    {
        if ($this->adminMiddleware->isGlobalAdmin($userId)) {
            return true;
        }

        $role = $this->serverMemberRepository->findRole($userId, $serverId);

        return $role !== null && in_array($role, ['P2', 'P3'], true);
    }
}
