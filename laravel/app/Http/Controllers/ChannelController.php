<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ChannelService;
use Illuminate\Http\JsonResponse;
use DomainException;
use InvalidArgumentException;
use PDOException;

final class ChannelController extends BaseApiController
{
    public function __construct(private ChannelService $channelService)
    {
    }

    public function index(int $userId, int $serverId): JsonResponse
    {
        try {
            $channels = $this->channelService->listChannelsForServer($userId, $serverId);

            return $this->success(['channels' => $channels]);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), 403);
        } catch (PDOException $e) {
            return $this->error('Erreur serveur', 500);
        }
    }

    public function create(int $userId, array $data): JsonResponse
    {
        try {
            $channelId = $this->channelService->createChannelFromPayload($userId, $data);

            return $this->success(['channel_id' => $channelId], 201);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), 403);
        } catch (PDOException $e) {
            return $this->error('Erreur serveur', 500);
        }
    }
}
