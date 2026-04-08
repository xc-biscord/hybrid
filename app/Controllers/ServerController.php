<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ServerService;
use InvalidArgumentException;
use PDOException;

final class ServerController extends BaseApiController
{
    public function __construct(private ServerService $serverService)
    {
    }

    public function create(int $userId, array $data): array
    {
        try {
            $serverId = $this->serverService->createServerFromPayload($userId, $data);
            return $this->success(['server_id' => $serverId], 201);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (PDOException $e) {
            return $this->error('Erreur serveur', 500);
        }
    }

    public function index(int $userId): array
    {
        try {
            $servers = $this->serverService->listUserServers($userId);
            return $this->success(['servers' => $servers]);
        } catch (PDOException $e) {
            return $this->error('Erreur serveur', 500);
        }
    }

    public function showName(int $serverId): array
    {
        if ($serverId <= 0) {
            return $this->error('ID manquant', 400);
        }

        try {
            $server = $this->serverService->getServerById($serverId);
            if ($server === null) {
                return $this->error('Serveur introuvable', 404);
            }

            return $this->success(['name' => $server->name]);
        } catch (PDOException $e) {
            return $this->error('Erreur serveur', 500);
        }
    }
}
