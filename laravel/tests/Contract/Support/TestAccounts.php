<?php

declare(strict_types=1);

namespace Tests\Contract\Support;

final class TestAccounts
{
    public const ALICE_ID = 1001;
    public const BOB_ID = 1002;
    public const ADMIN_ID = 1003;
    public const MOD_ID = 1004;

    /**
     * @var array<string, array{id:int, username:string, email:string, password:string}>
     */
    private const ACCOUNTS = [
        'alice' => [
            'id' => self::ALICE_ID,
            'username' => 'alice',
            'email' => 'alice@example.test',
            'password' => 'alice-pass-123',
        ],
        'bob' => [
            'id' => self::BOB_ID,
            'username' => 'bob',
            'email' => 'bob@example.test',
            'password' => 'bob-pass-123',
        ],
        'admin' => [
            'id' => self::ADMIN_ID,
            'username' => 'admin',
            'email' => 'admin@example.test',
            'password' => 'admin-pass-123',
        ],
        'mod' => [
            'id' => self::MOD_ID,
            'username' => 'mod',
            'email' => 'mod@example.test',
            'password' => 'mod-pass-123',
        ],
    ];

    /**
     * @return array<string, array{id:int, username:string, email:string, password:string}>
     */
    public static function all(): array
    {
        return self::ACCOUNTS;
    }

    /**
     * @return array{id:int, username:string, email:string, password:string}
     */
    public static function get(string $key): array
    {
        if (!isset(self::ACCOUNTS[$key])) {
            throw new \InvalidArgumentException('Unknown contract test account: ' . $key);
        }

        return self::ACCOUNTS[$key];
    }

    public static function id(string $key): int
    {
        return self::get($key)['id'];
    }

    public static function username(string $key): string
    {
        return self::get($key)['username'];
    }

    public static function password(string $key): string
    {
        return self::get($key)['password'];
    }
}
