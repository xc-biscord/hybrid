<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\BanUserRepository;

final class BanUserService
{
    public function __construct(private BanUserRepository $repository)
    {
    }

    public function isP1(int $userId): bool
    {
        return $this->repository->isP1($userId);
    }

    public function banUser(int $targetUserId): void
    {
        $this->repository->banUser($targetUserId);
    }
}
