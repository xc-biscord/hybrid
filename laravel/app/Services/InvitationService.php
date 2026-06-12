<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\InvitationRepository;

final class InvitationService
{
    public function __construct(private InvitationRepository $invitationRepository)
    {
    }

    /**
     * @return array{success:bool,server_id?:int,error?:string}
     */
    public function acceptInvite(?int $userId, string $code): array
    {
        if ($userId === null || $userId <= 0 || $code === '') {
            return ['success' => false, 'error' => 'Données manquantes.'];
        }

        $serverId = $this->invitationRepository->findServerIdByCode($code);
        if ($serverId === null) {
            return ['success' => false, 'error' => 'Invitation invalide.'];
        }

        if (!$this->invitationRepository->isUserMemberOfServer($serverId, $userId)) {
            $this->invitationRepository->addUserToServer($serverId, $userId);
        }

        return ['success' => true, 'server_id' => $serverId];
    }

    /**
     * @return array{success:bool,server_id?:mixed,server_name?:mixed,error?:string}
     */
    public function resolveInvite(?int $userId, ?string $code): array
    {
        if (!$userId || !$code) {
            return ['success' => false, 'error' => 'Utilisateur non connecté ou lien invalide.'];
        }

        $invite = $this->invitationRepository->findInviteServerSummaryByCode($code);
        if ($invite === null) {
            return ['success' => false, 'error' => 'Lien invalide.'];
        }

        return ['success' => true] + $invite;
    }

    /**
     * @return array{success:bool,invite_url?:string,error?:string}
     */
    public function createInvite(?int $userId, ?int $serverId): array
    {
        if ($serverId === null || $serverId <= 0 || $userId === null || $userId <= 0) {
            return ['success' => false, 'error' => 'Données manquantes.'];
        }

        if (!$this->invitationRepository->isUserMemberOfServer($serverId, $userId)) {
            return ['success' => false, 'error' => 'Non autorisé.'];
        }

        $code = bin2hex(random_bytes(8));
        $this->invitationRepository->createInvitation($serverId, $code);

        return [
            'success' => true,
            'invite_url' => "https://biscord-api-stg.xcsoftworks.com/invitation.html?code={$code}",
        ];
    }
}
