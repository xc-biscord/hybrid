<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\GetProfileRepository;

final class GetProfileService
{
    public function __construct(private GetProfileRepository $repository)
    {
    }

    /** @return array<string,mixed>|null */
    public function getProfile(int $userId): ?array
    {
        $profile = $this->repository->findProfile($userId);
        if ($profile === null) {
            return null;
        }

        $profile['is_p1'] = $this->repository->isP1($userId);
        return $profile;
    }
}
