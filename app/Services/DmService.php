<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\DmRepository;
use DomainException;
use InvalidArgumentException;

final class DmService
{
    public function __construct(private DmRepository $dmRepository)
    {
    }

    /**
     * @return array{conversation_id:int,status:string}
     */
    public function startConversationFromPayload(int $currentUserId, array $input): array
    {
        $otherUserId = (int) ($input['target_user_id'] ?? $input['other_user_id'] ?? 0);
        if ($otherUserId <= 0 || $otherUserId === $currentUserId) {
            throw new InvalidArgumentException('Identifiant utilisateur invalide');
        }

        $user1 = min($currentUserId, $otherUserId);
        $user2 = max($currentUserId, $otherUserId);

        $conversationId = $this->dmRepository->findConversationByUsers($user1, $user2);
        if ($conversationId !== null) {
            return ['conversation_id' => $conversationId, 'status' => 'exists'];
        }

        $newConversationId = $this->dmRepository->createConversation($user1, $user2);

        return ['conversation_id' => $newConversationId, 'status' => 'created'];
    }

    /**
     * @return array{messages:array<int,array{id:int,conversation_id:int,sender_id:int,content:string,created_at:string,username:string,avatar:?string}>,recipient:array{id:int,username:string,avatar_url:?string}}
     */
    public function listConversationMessages(int $userId, int $conversationId): array
    {
        if ($conversationId <= 0) {
            throw new InvalidArgumentException('Conversation invalide');
        }

        $conversation = $this->dmRepository->findConversationForUser($conversationId, $userId);
        if ($conversation === null) {
            throw new DomainException('Accès refusé', 403);
        }

        $otherUserId = $conversation['user1_id'] === $userId
            ? $conversation['user2_id']
            : $conversation['user1_id'];

        $recipient = $this->dmRepository->findRecipientProfile($otherUserId);
        if ($recipient === null) {
            throw new DomainException('Destinataire introuvable', 404);
        }

        $messages = $this->dmRepository->findMessagesByConversationId($conversationId);
        $this->dmRepository->markConversationAsRead($userId, $conversationId);

        return [
            'messages' => $messages,
            'recipient' => [
                'id' => $otherUserId,
                'username' => $recipient['username'],
                'avatar_url' => $recipient['avatar_url'],
            ],
        ];
    }

    public function sendMessageFromPayload(int $senderId, array $data): int
    {
        $conversationId = (int) ($data['conversation_id'] ?? 0);
        $content = trim((string) ($data['content'] ?? ''));

        if ($conversationId <= 0 || $content === '') {
            throw new InvalidArgumentException('Conversation ou contenu manquant');
        }

        if (!$this->dmRepository->userHasConversationAccess($conversationId, $senderId)) {
            throw new DomainException('Accès refusé');
        }

        return $this->dmRepository->createMessage($conversationId, $senderId, $content);
    }

    /**
     * @return array<int, array{conversation_id:int,sender_id:int,unread_count:int,username:string,avatar_url:?string,last_message:string}>
     */
    public function listUnreadNotifications(int $userId): array
    {
        return $this->dmRepository->findUnreadConversationNotifications($userId);
    }
}
