<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class BaseApiController extends Controller
{
    protected function success(array $payload = [], int $statusCode = 200): JsonResponse
    {
        return new JsonResponse(['success' => true] + $payload, $statusCode);
    }

    protected function error(string $message, int $statusCode = 400, array $payload = []): JsonResponse
    {
        return new JsonResponse(['success' => false, 'error' => $message] + $payload, $statusCode);
    }
}
