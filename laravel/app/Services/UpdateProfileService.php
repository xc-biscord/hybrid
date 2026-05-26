<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UpdateProfileRepository;

final class UpdateProfileService
{
    public function __construct(private UpdateProfileRepository $repository)
    {
    }

    public function upsertProfile(int $userId, string $bio, string $avatarUrl, string $status): void
    {
        $this->repository->upsertProfile($userId, $bio, $avatarUrl, $status);
    }
}
