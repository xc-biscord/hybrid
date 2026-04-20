<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnsureGlobalAdmin
{
    public function __construct(private PermissionService $permissionService)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

        if (!$this->permissionService->isP1($userId)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Accès refusé : réservé aux P1',
            ], 403);
        }

        return $next($request);
    }
}
