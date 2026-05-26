<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\GetUserProfileRepository;

final class GetUserProfileService
{
    public function __construct(private GetUserProfileRepository $repository)
    {
    }

    /** @return array<string,mixed>|null */
    public function getById(int $userId): ?array
    {
        return $this->repository->findUserProfile($userId);
    }
}
