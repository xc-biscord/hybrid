<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function resolve(Request $request): JsonResponse
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        $payload = $this->invitationService->resolveInvite(
            is_numeric($userId) ? (int) $userId : null,
            $request->query('code'),
        );

        return new JsonResponse($payload);
    }
}
