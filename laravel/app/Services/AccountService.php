<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use DomainException;

final class AccountService
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    /**
     * @param array{username:?string,email:?string,new_password:?string,current_password:?string} $data
     */
    public function updateAccount(int $userId, array $data): void
    {
        $this->userRepository->updateIdentityFields($userId, $data['username'], $data['email']);

        if ($data['new_password'] === null) {
            return;
        }

        if (($data['current_password'] ?? '') === '') {
            throw new DomainException('Mot de passe actuel requis');
        }

        $currentHash = $this->userRepository->findPasswordHashById($userId);
        if ($currentHash === null || !password_verify($data['current_password'], $currentHash)) {
            throw new DomainException('Mot de passe actuel incorrect');
        }

        $newHash = password_hash($data['new_password'], PASSWORD_BCRYPT);
        $this->userRepository->updatePasswordHash($userId, $newHash);
    }
}
