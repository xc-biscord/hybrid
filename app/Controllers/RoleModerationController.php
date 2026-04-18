<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ModerationService;
use PDOException;

final class RoleModerationController extends BaseApiController
{
    public function __construct(private ModerationService $moderationService)
    {
    }

    public function getMyServerRole(int $userId, ?int $serverId): array
    {
        try {
            $role = $this->moderationService->getMyServerRole($userId, $serverId);
            return $this->success(['role' => $role]);
        } catch (PDOException $e) {
            return $this->error('Erreur serveur', 500);
        }
    }

    public function listUsersInServer(int $actorUserId, int $serverId): array
    {
        try {
            $users = $this->moderationService->listUsersInServer($actorUserId, $serverId);
            return $this->success(['users' => $users]);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), 403);
        } catch (PDOException $e) {
            return $this->error('Erreur serveur', 500);
        }
    }

    public function setMemberRole(int $actorUserId, array $data): array
    {
        $serverId = (int) ($data['server_id'] ?? 0);
        $targetUserId = (int) ($data['target_user_id'] ?? 0);
        $newRole = (string) ($data['new_role'] ?? '');

        try {
            $this->moderationService->setMemberRole($actorUserId, $serverId, $targetUserId, $newRole);
            return $this->success();
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), 403);
        } catch (PDOException $e) {
            return $this->error('Erreur serveur', 500);
        }
    }

    public function kickMember(int $actorUserId, array $data): array
    {
        $serverId = (int) ($data['server_id'] ?? 0);
        $targetUserId = (int) ($data['target_user_id'] ?? 0);

        try {
            $this->moderationService->kickMember($actorUserId, $serverId, $targetUserId);
            return $this->success();
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), 403);
        } catch (PDOException $e) {
            return $this->error('Erreur serveur', 500);
        }
    }
}
