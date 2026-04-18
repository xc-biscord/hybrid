<?php

declare(strict_types=1);

namespace App\Validators;

final class AccountUpdateValidator
{
    /**
     * @param array<string, mixed>|null $payload
     * @return array{username:?string,email:?string,new_password:?string,current_password:?string}
     */
    public function normalize(?array $payload): array
    {
        $data = is_array($payload) ? $payload : [];

        $username = isset($data['username']) ? trim((string) $data['username']) : null;
        $email = isset($data['email']) ? trim((string) $data['email']) : null;
        $newPassword = array_key_exists('password', $data) ? (string) $data['password'] : null;
        $currentPassword = array_key_exists('current_password', $data) ? (string) $data['current_password'] : null;

        return [
            'username' => $username !== '' ? $username : null,
            'email' => $email !== '' ? $email : null,
            'new_password' => $newPassword !== '' ? $newPassword : null,
            'current_password' => $currentPassword,
        ];
    }

    /**
     * @param array{username:?string,email:?string,new_password:?string,current_password:?string} $data
     */
    public function hasAnyUpdatableField(array $data): bool
    {
        return $data['username'] !== null || $data['email'] !== null || $data['new_password'] !== null;
    }
}
