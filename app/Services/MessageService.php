<?php

declare(strict_types=1);

namespace App\Services;

use App\Middleware\AdminMiddleware;
use App\Repositories\MessageRepository;
use App\Repositories\ServerMemberRepository;
use DomainException;
use InvalidArgumentException;

final class MessageService
{
    public function __construct(
        private MessageRepository $messageRepository,
        private ServerMemberRepository $serverMemberRepository,
        private AdminMiddleware $adminMiddleware,
    ) {
    }

    /**
     * @return array<int, array{id:int,content:string,created_at:string,username:string,user_id:int,avatar_url:?string}>
     */
    public function listMessages(int $userId, int $channelId): array
    {
        if ($channelId <= 0) {
            throw new InvalidArgumentException('Paramètre channel_id invalide');
        }

        if (!$this->messageRepository->userCanReadChannelMessages($channelId, $userId)) {
            throw new DomainException('Accès refusé');
        }

        return $this->messageRepository->findByChannelId($channelId);
    }

    public function sendMessageFromPayload(int $userId, array $data): int
    {
        $channelId = (int) ($data['channel_id'] ?? 0);
        $content = trim((string) ($data['content'] ?? ''));

        if ($channelId <= 0 || $content === '') {
            throw new InvalidArgumentException('Message vide ou channel manquant');
        }

        if (!$this->messageRepository->channelExists($channelId)) {
            throw new DomainException('Channel inexistant', 404);
        }

        return $this->messageRepository->create($channelId, $userId, $content);
    }

    public function deleteMessageFromPayload(int $userId, array $data): void
    {
        $messageId = (int) ($data['message_id'] ?? 0);

        $info = $this->messageRepository->findMessageChannelAndServer($messageId);
        if ($info === null) {
            throw new DomainException('Message introuvable');
        }

        if (!$this->canDeleteMessage($userId, $info['server_id'])) {
            throw new DomainException('Permission refusée');
        }

        $this->messageRepository->deleteById($messageId);
    }

    private function canDeleteMessage(int $userId, int $serverId): bool
    {
        if ($this->adminMiddleware->isGlobalAdmin($userId)) {
            return true;
        }

        $role = $this->serverMemberRepository->findRole($userId, $serverId);

        return $role !== null && in_array($role, ['P2', 'P3'], true);
    }
}
