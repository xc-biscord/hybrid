<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BanUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PDOException;

final class BanUserController extends Controller
{
    public function __construct(private BanUserService $banUserService)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!is_numeric($userId)) {
            // @legacy-invariant: auth manquante retourne HTTP 200 et message legacy.
            return response()->json(['success' => false, 'error' => 'Non authentifié'], 200);
        }

        $currentUserId = (int) $userId;

        if (!$this->banUserService->isP1($currentUserId)) {
            // @legacy-invariant: refus permission retourne HTTP 200 (pas 403).
            return response()->json(['success' => false, 'error' => 'Accès refusé : réservé aux P1'], 200);
        }

        $targetUserId = (int) ($request->input('user_id', 0));
        if (!$targetUserId) {
            return response()->json(['success' => false, 'error' => 'user_id invalide'], 200);
        }

        try {
            $this->banUserService->banUser($targetUserId);
            return response()->json(['success' => true], 200);
        } catch (PDOException $e) {
            return response()->json(['success' => false, 'error' => 'Erreur DB : ' . $e->getMessage()], 200);
        }
    }
}
