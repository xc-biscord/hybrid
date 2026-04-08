<?php

declare(strict_types=1);

namespace App\Services;

use App\Middleware\AdminMiddleware;
use App\Repositories\ServerMemberRepository;

final class UserServerService
{
    public function __construct(
        private AdminMiddleware $adminMiddleware,
        private ServerMemberRepository $serverMemberRepository,
    ) {
    }

    public function canViewUserServers(int $actorUserId): bool
    {
        return $this->adminMiddleware->isGlobalAdmin($actorUserId);
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    public function listServersForUser(int $targetUserId): array
    {
        return $this->serverMemberRepository->listServersForUser($targetUserId);
    }
}
