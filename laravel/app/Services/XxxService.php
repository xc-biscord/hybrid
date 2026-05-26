<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\XxxRepository;

final class XxxService
{
    public function __construct(private XxxRepository $xxxRepository)
    {
    }

    /** @param array{id:int} $payload */
    public function handle(array $payload): array
    {
        return $this->xxxRepository->handle((int) $payload['id']);
    }
}
