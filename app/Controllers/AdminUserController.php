<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UserServerService;
use PDOException;

final class AdminUserController extends BaseApiController
{
    public function __construct(
        private UserServerService $userServerService,
    ) {
    }

    public function listUsers(int $currentUserId): array
    {
        if (!$this->userServerService->canViewUserServers($currentUserId)) {
            return $this->error('Accès réservé aux P1', 403);
        }

        try {
            $users = $this->userServerService->listUsers();
            return $this->success(['users' => $users]);
        } catch (PDOException $e) {
            return $this->error('Erreur serveur', 500);
        }
    }

    public function listUserServers(int $currentUserId, int $targetUserId): array
    {
        if (!$this->userServerService->canViewUserServers($currentUserId)) {
            return $this->error('Accès refusé : réservé aux P1', 403);
        }

        try {
            $servers = $this->userServerService->listServersForUser($targetUserId);
            return $this->success(['servers' => $servers]);
        } catch (PDOException $e) {
            return $this->error('Erreur serveur', 500);
        }
    }
}
