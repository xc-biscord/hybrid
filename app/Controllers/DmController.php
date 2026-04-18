<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\DmService;
use DomainException;
use InvalidArgumentException;
use PDOException;

final class DmController extends BaseApiController
{
    public function __construct(private DmService $dmService)
    {
    }

    public function start(int $currentUserId, array $input): array
    {
        try {
            $result = $this->dmService->startConversationFromPayload($currentUserId, $input);
            $statusCode = $result['status'] === 'created' ? 201 : 200;

            return $this->success($result, $statusCode);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (PDOException $e) {
            return $this->error('Erreur de base de données', 500);
        }
    }

    public function messages(int $userId, int $conversationId): array
    {
        try {
            $result = $this->dmService->listConversationMessages($userId, $conversationId);

            return $this->success($result);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (DomainException $e) {
            $statusCode = $e->getCode() === 404 ? 404 : 403;

            return $this->error($e->getMessage(), $statusCode);
        } catch (PDOException $e) {
            return $this->error('Erreur DB', 500);
        }
    }

    public function send(int $senderId, array $data): array
    {
        try {
            $messageId = $this->dmService->sendMessageFromPayload($senderId, $data);

            return $this->success(['message_id' => $messageId], 201);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), 403);
        } catch (PDOException $e) {
            return $this->error('Erreur de base de données', 500);
        }
    }

    public function notifications(int $userId): array
    {
        try {
            $rows = $this->dmService->listUnreadNotifications($userId);

            return $this->success(['unread_conversations' => $rows]);
        } catch (PDOException $e) {
            return $this->error('Erreur DB', 500);
        }
    }
}
