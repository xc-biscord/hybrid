<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GetUserProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PDOException;

final class GetUserProfileController extends Controller
{
    public function __construct(private GetUserProfileService $service)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $sessionUserId = $_SESSION['user_id'] ?? null;
        if (!isset($sessionUserId)) {
            // @legacy-invariant: non connecté retourne 200, pas 401.
            return response()->json(['success' => false, 'error' => 'Non connecté'], 200);
        }

        $rawUserId = $request->query('user_id');
        if (!is_numeric($rawUserId)) {
            return response()->json(['success' => false, 'error' => 'Paramètre user_id invalide'], 200);
        }

        try {
            $user = $this->service->getById((int) $rawUserId);
            if ($user === null) {
                return response()->json(['success' => false, 'error' => 'Utilisateur non trouvé'], 200);
            }

            return response()->json(['success' => true, 'user' => $user], 200);
        } catch (PDOException $e) {
            return response()->json(['success' => false, 'error' => 'Erreur DB : ' . $e->getMessage()], 200);
        }
    }
}
