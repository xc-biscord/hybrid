<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\MessageService;
use DomainException;
use InvalidArgumentException;
use PDOException;

final class MessageController extends BaseApiController
{
    public function __construct(private MessageService $messageService)
    {
    }

    public function index(int $userId, int $channelId): array
    {
        try {
            $messages = $this->messageService->listMessages($userId, $channelId);

            return $this->success(['messages' => $messages]);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), 403);
        } catch (PDOException $e) {
            return $this->error('Erreur serveur', 500);
        }
    }

    public function create(int $userId, array $data): array
    {
        try {
            $messageId = $this->messageService->sendMessageFromPayload($userId, $data);

            return $this->success(['message_id' => $messageId], 201);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (DomainException $e) {
            $statusCode = $e->getCode() === 404 ? 404 : 400;

            return $this->error($e->getMessage(), $statusCode);
        } catch (PDOException $e) {
            return $this->error('Erreur SQL', 500);
        }
    }

    public function delete(int $userId, array $data): array
    {
        try {
            $this->messageService->deleteMessageFromPayload($userId, $data);

            return $this->success();
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), 200);
        }
    }
}
