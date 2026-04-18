<?php

declare(strict_types=1);

namespace Tests\Contract\Support;

use PDO;

final class TestDatabaseSeeder
{
    public const USER_ALICE_ID = 1001;
    public const USER_BOB_ID = 1002;
    public const USER_ADMIN_ID = 1003;
    public const USER_MOD_ID = 1004;

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

        $passwordHash = password_hash('contract-password', PASSWORD_BCRYPT);

        $usersStmt = $pdo->prepare('INSERT INTO users (id, username, email, password_hash, created_at) VALUES (?, ?, ?, ?, NOW())');
        $usersStmt->execute([self::USER_ALICE_ID, 'alice', 'alice@example.test', $passwordHash]);
        $usersStmt->execute([self::USER_BOB_ID, 'bob', 'bob@example.test', $passwordHash]);
        $usersStmt->execute([self::USER_ADMIN_ID, 'admin', 'admin@example.test', $passwordHash]);
        $usersStmt->execute([self::USER_MOD_ID, 'mod', 'mod@example.test', $passwordHash]);

        $profileStmt = $pdo->prepare('INSERT INTO profiles (user_id, bio, avatar_url, status) VALUES (?, ?, ?, ?)');
        $profileStmt->execute([self::USER_ALICE_ID, 'Alice profile', 'https://cdn.example.test/alice.png', 'online']);
        $profileStmt->execute([self::USER_BOB_ID, 'Bob profile', 'https://cdn.example.test/bob.png', 'away']);
        $profileStmt->execute([self::USER_ADMIN_ID, 'Admin profile', 'https://cdn.example.test/admin.png', 'busy']);
        $profileStmt->execute([self::USER_MOD_ID, 'Mod profile', 'https://cdn.example.test/mod.png', 'online']);

        $pdo->prepare('INSERT INTO global_permissions (user_id, permission_level) VALUES (?, ?)')
            ->execute([self::USER_ADMIN_ID, 'P1']);

        $pdo->prepare('INSERT INTO servers (id, name, owner_id, created_at) VALUES (?, ?, ?, NOW())')
            ->execute([self::SERVER_1_ID, 'Server One', self::USER_ALICE_ID]);

        $channelStmt = $pdo->prepare('INSERT INTO channels (id, server_id, name, created_at) VALUES (?, ?, ?, NOW())');
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
        $host = getenv('CONTRACT_TEST_DB_HOST') ?: 'localhost';
        $port = getenv('CONTRACT_TEST_DB_PORT') ?: '3306';
        $db = getenv('CONTRACT_TEST_DB_DATABASE') ?: 'biscord_db_tests';
        $user = getenv('CONTRACT_TEST_DB_USERNAME') ?: 'adminweb';
        $pass = getenv('CONTRACT_TEST_DB_PASSWORD') ?: 'MazdeoAchaqui';

        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db),
            (string) $user,
            (string) $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        return $pdo;
    }
}
