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
        $name = (string) ($data['nom'] ?? $data['name'] ?? '');

        try {
            $serverId = $this->serverService->createServer($userId, $name);
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
}
