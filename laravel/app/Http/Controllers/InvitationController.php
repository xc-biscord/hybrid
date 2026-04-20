<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;

final class InvitationController extends BaseApiController
{
    public function __construct(private InvitationService $invitationService)
    {
    }

    public function accept(?int $userId, string $code): JsonResponse
    {
        return $this->success($this->invitationService->acceptInvite($userId, $code));
    }

    public function create(?int $userId, ?int $serverId): JsonResponse
    {
        return $this->success($this->invitationService->createInvite($userId, $serverId));
    }
}
