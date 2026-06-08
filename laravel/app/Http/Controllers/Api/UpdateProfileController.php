<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UpdateProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UpdateProfileController extends Controller
{
    public function __construct(private UpdateProfileService $service)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $userId = isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])
            ? (int) $_SESSION['user_id']
            : 0;
        $bio = (string) $request->input('bio', '');
        $avatarUrl = (string) $request->input('avatar_url', '');
        // @legacy-invariant: status par défaut à 'disponible'.
        $status = (string) $request->input('status', 'disponible');

        $this->service->upsertProfile($userId, $bio, $avatarUrl, $status);

        return new JsonResponse(['success' => true], 200);
    }
}
