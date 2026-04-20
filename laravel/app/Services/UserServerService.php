<?php

declare(strict_types=1);

namespace App\Services;

use App\Middleware\AdminMiddleware;
use App\Repositories\ServerMemberRepository;
use App\Repositories\UserRepository;

final class UserServerService
{
    public function __construct(
        private AdminMiddleware $adminMiddleware,
        private ServerMemberRepository $serverMemberRepository,
        private UserRepository $userRepository,
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

    /**
     * @return array<int, array{id:int,username:string,email:string,created_at:string,permission_level:?string}>
     */
    public function listUsers(): array
    {
        return $this->userRepository->listAllWithGlobalPermission();
    }
}
