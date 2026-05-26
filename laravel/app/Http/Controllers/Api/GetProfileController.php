<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GetProfileService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetProfileController extends Controller
{
    public function __construct(private GetProfileService $getProfileService)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $userId = $request->session()->get('user_id');
        if (!is_numeric($userId)) {
            return response()->json(['success' => false, 'error' => 'Utilisateur non connecté'], 401);
        }

        try {
            $profile = $this->getProfileService->getProfile((int) $userId);
            if ($profile === null) {
                // @legacy-invariant: profil introuvable retourne 200, pas 404.
                return response()->json(['success' => false, 'error' => 'Profil introuvable'], 200);
            }

            return response()->json(['success' => true, 'profile' => $profile], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => 'Erreur serveur', 'details' => $e->getMessage()], 500);
        }
    }
}
