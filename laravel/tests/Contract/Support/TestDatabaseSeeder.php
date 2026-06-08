<?php

declare(strict_types=1);

namespace Tests\Contract\Support;

use PDO;

final class TestDatabaseSeeder
{
    public const USER_ALICE_ID = TestAccounts::ALICE_ID;
    public const USER_BOB_ID = TestAccounts::BOB_ID;
    public const USER_ADMIN_ID = TestAccounts::ADMIN_ID;
    public const USER_MOD_ID = TestAccounts::MOD_ID;

    public const SERVER_1_ID = 1101;
    public const CHANNEL_1_ID = 1201;
    public const DM_CONVERSATION_ID = 2001;

    public static function resetAndSeed(): void
    {
        $pdo = self::connect();

        $dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
        if ($dbName !== 'biscord_db_tests') {
            throw new \RuntimeException('Contract tests must run against biscord_db_tests. Current DB: ' . $dbName);
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        foreach ([
            'dm_reads',
            'dm_messages',
            'dm_conversations',
            'messages',
            'invitations',
            'server_members',
            'channels',
            'servers',
            'profiles',
            'global_permissions',
            'users',
        ] as $table) {
            $pdo->exec('TRUNCATE TABLE ' . $table);
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        $usersStmt = $pdo->prepare('INSERT INTO users (id, username, email, password_hash, created_at) VALUES (?, ?, ?, ?, NOW())');

        foreach (TestAccounts::all() as $account) {
            $usersStmt->execute([
                $account['id'],
                $account['username'],
                $account['email'],
                password_hash($account['password'], PASSWORD_DEFAULT),
            ]);
        }

        $profileStmt = $pdo->prepare('INSERT INTO profiles (user_id, bio, avatar_url, status) VALUES (?, ?, ?, ?)');
        $profileStmt->execute([self::USER_ALICE_ID, 'Alice profile', 'https://cdn.example.test/alice.png', 'online']);
        $profileStmt->execute([self::USER_BOB_ID, 'Bob profile', 'https://cdn.example.test/bob.png', 'away']);
        $profileStmt->execute([self::USER_ADMIN_ID, 'Admin profile', 'https://cdn.example.test/admin.png', 'busy']);
        $profileStmt->execute([self::USER_MOD_ID, 'Mod profile', 'https://cdn.example.test/mod.png', 'online']);

        $pdo->prepare('INSERT INTO global_permissions (user_id, permission_level) VALUES (?, ?)')
            ->execute([self::USER_ADMIN_ID, 'P1']);

        $serverStmt = $pdo->prepare('INSERT INTO servers (id, name, owner_id, created_at) VALUES (?, ?, ?, NOW())');
        $serverStmt->execute([1, 'Hub Biscord', self::USER_ADMIN_ID]);
        $serverStmt->execute([self::SERVER_1_ID, 'Server One', self::USER_ALICE_ID]);

        $channelStmt = $pdo->prepare('INSERT INTO channels (id, server_id, name, created_at) VALUES (?, ?, ?, NOW())');
        $channelStmt->execute([1, 1, 'general']);
        $channelStmt->execute([self::CHANNEL_1_ID, self::SERVER_1_ID, 'general']);

        $memberStmt = $pdo->prepare('INSERT INTO server_members (user_id, server_id, role, joined_at) VALUES (?, ?, ?, NOW())');
        $memberStmt->execute([self::USER_ALICE_ID, self::SERVER_1_ID, 'P3']);
        $memberStmt->execute([self::USER_BOB_ID, self::SERVER_1_ID, 'member']);
        $memberStmt->execute([self::USER_MOD_ID, self::SERVER_1_ID, 'P2']);

        $inviteStmt = $pdo->prepare('INSERT INTO invitations (id, server_id, code, created_at) VALUES (?, ?, ?, NOW())');
        $inviteStmt->execute([1301, self::SERVER_1_ID, 'INV-OK']);

        $messageStmt = $pdo->prepare('INSERT INTO messages (id, channel_id, user_id, content, created_at) VALUES (?, ?, ?, ?, NOW())');
        $messageStmt->execute([1401, self::CHANNEL_1_ID, self::USER_ALICE_ID, 'Hello from Alice']);
        $messageStmt->execute([1402, self::CHANNEL_1_ID, self::USER_BOB_ID, 'Hello from Bob']);

        $dmConversationStmt = $pdo->prepare('INSERT INTO dm_conversations (id, user1_id, user2_id, created_at) VALUES (?, ?, ?, NOW())');
        $dmConversationStmt->execute([self::DM_CONVERSATION_ID, self::USER_ALICE_ID, self::USER_BOB_ID]);
    }

    private static function connect(): PDO
    {
        $host = self::resolveConfigValue('CONTRACT_TEST_DB_HOST', 'DB_HOST', '127.0.0.1');
        $port = self::resolveConfigValue('CONTRACT_TEST_DB_PORT', 'DB_PORT', '3306');
        $db = self::resolveConfigValue('CONTRACT_TEST_DB_DATABASE', 'DB_DATABASE', 'biscord_db_tests');
        $user = self::resolveConfigValue('CONTRACT_TEST_DB_USERNAME', 'DB_USERNAME', 'adminweb');
        $pass = self::resolveConfigValue('CONTRACT_TEST_DB_PASSWORD', 'DB_PASSWORD', 'MazdeoAchaqui');

        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db),
            (string) $user,
            (string) $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        return $pdo;
    }

    private static function resolveConfigValue(string $contractEnvKey, string $laravelEnvKey, string $default): string
    {
        $contractValue = getenv($contractEnvKey);
        if (is_string($contractValue) && $contractValue !== '') {
            return $contractValue;
        }

        $laravelValue = getenv($laravelEnvKey);
        if (is_string($laravelValue) && $laravelValue !== '') {
            return $laravelValue;
        }

        $dotenvValues = self::readLaravelDotEnv();
        $dotenvValue = $dotenvValues[$laravelEnvKey] ?? null;
        if (is_string($dotenvValue) && $dotenvValue !== '') {
            return $dotenvValue;
        }

        return $default;
    }

    /**
     * @return array<string,string>
     */
    private static function readLaravelDotEnv(): array
    {
        static $cachedValues = null;
        if (is_array($cachedValues)) {
            return $cachedValues;
        }

        $dotenvPath = dirname(__DIR__, 3) . '/.env';
        if (!is_file($dotenvPath)) {
            $cachedValues = [];
            return $cachedValues;
        }

        $values = [];
        $lines = file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            $cachedValues = [];
            return $cachedValues;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            $parts = explode('=', $trimmed, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);
            if ($key === '') {
                continue;
            }

            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            $values[$key] = $value;
        }

        $cachedValues = $values;

        return $cachedValues;
    }
}
