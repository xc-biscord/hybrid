<?php

declare(strict_types=1);

namespace App\Controllers;

abstract class BaseApiController
{
    protected function success(array $payload = [], int $statusCode = 200): array
    {
        return [
            'statusCode' => $statusCode,
            'payload' => ['success' => true] + $payload,
        ];
    }

    protected function error(string $message, int $statusCode = 400, array $payload = []): array
    {
        return [
            'statusCode' => $statusCode,
            'payload' => ['success' => false, 'error' => $message] + $payload,
        ];
    }
}
