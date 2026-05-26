<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\XxxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class XxxController extends Controller
{
    public function __construct(private XxxService $xxxService)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ]);

        return response()->json($this->xxxService->handle($validated));
    }
}
