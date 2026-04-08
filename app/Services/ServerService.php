<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ServerMemberRepository;
use App\Repositories\ServerRepository;
use App\Models\Server;
use PDO;
use PDOException;

final class ServerService
{
    public function __construct(
        private PDO $pdo,
        private ServerRepository $serverRepository,
        private ServerMemberRepository $serverMemberRepository,
    ) {
    }

    public function createServer(int $ownerId, string $name): int
    {
        $trimmedName = trim($name);
        if ($trimmedName === '') {
            throw new \InvalidArgumentException('Nom de serveur requis');
        }

        try {
            $this->pdo->beginTransaction();
            $serverId = $this->serverRepository->create($trimmedName, $ownerId);
            $this->serverMemberRepository->addMember($serverId, $ownerId, 'P2');
            $this->pdo->commit();

            return $serverId;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    public function listUserServers(int $userId): array
    {
        return $this->serverRepository->findByMemberUserId($userId);
    }

    public function getServerById(int $serverId): ?Server
    {
        return $this->serverRepository->find($serverId);
    }
}
