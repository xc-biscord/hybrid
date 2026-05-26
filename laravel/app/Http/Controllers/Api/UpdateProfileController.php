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
        $userId = (int) $request->session()->get('user_id');
        $bio = (string) $request->input('bio', '');
        $avatarUrl = (string) $request->input('avatar_url', '');
        // @legacy-invariant: status par défaut à 'disponible'.
        $status = (string) $request->input('status', 'disponible');

        $this->service->upsertProfile($userId, $bio, $avatarUrl, $status);

        return response()->json(['success' => true], 200);
    }
}
