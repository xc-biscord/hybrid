<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;
use Throwable;

/**
 * Crée (ou met à jour) les comptes de démonstration et un petit jeu de données
 * navigable pour la soutenance / le PoC.
 *
 * La commande est idempotente : les comptes sont identifiés par leur e-mail,
 * on peut donc la relancer sans dupliquer les lignes. Les mots de passe sont
 * hachés avec password_hash(), exactement comme le fait le flux d'inscription
 * réel (voir AuthController::register), donc les comptes fonctionnent avec
 * login.php sans traitement particulier.
 */
final class SeedDemoDataCommand extends Command
{
    protected $signature = 'biscord:seed-demo {--fresh : Vide les tables métier avant de réinsérer le jeu de démonstration}';

    protected $description = 'Insère les comptes de démonstration (super admin / admin / utilisateur) et des données PoC';

    /**
     * @var array<int, array{key:string, username:string, email:string, password:string, status:string, bio:string}>
     */
    private const ACCOUNTS = [
        [
            'key' => 'superadmin',
            'username' => 'superadmin',
            'email' => 'admin@example.com',
            'password' => 'Admin123!',
            'status' => 'disponible',
            'bio' => 'Compte Super Administrateur de démonstration.',
        ],
        [
            'key' => 'moderator',
            'username' => 'moderator',
            'email' => 'moderator@example.com',
            'password' => 'Moderator123!',
            'status' => 'disponible',
            'bio' => 'Compte Administrateur / modérateur de démonstration.',
        ],
        [
            'key' => 'user',
            'username' => 'user',
            'email' => 'user@example.com',
            'password' => 'User123!',
            'status' => 'disponible',
            'bio' => 'Compte utilisateur standard de démonstration.',
        ],
    ];

    private const DEMO_SERVER_NAME = 'Serveur de démonstration';
    private const DEMO_CHANNEL_NAME = 'general';
    private const DEMO_INVITE_CODE = 'DEMO-INVITE';

    public function handle(PDO $pdo): int
    {
        if ($this->option('fresh') && ! $this->confirmFresh()) {
            $this->warn('Annulé.');

            return self::FAILURE;
        }

        try {
            $pdo->beginTransaction();

            if ($this->option('fresh')) {
                $this->truncateBusinessTables($pdo);
            }

            $ids = [];
            foreach (self::ACCOUNTS as $account) {
                $ids[$account['key']] = $this->upsertUser($pdo, $account);
            }

            // Super administrateur global (table global_permissions, niveau P1).
            $this->grantGlobalPermission($pdo, $ids['superadmin']);

            // Serveur de démonstration appartenant au super administrateur.
            $serverId = $this->ensureServer($pdo, self::DEMO_SERVER_NAME, $ids['superadmin']);
            $channelId = $this->ensureChannel($pdo, $serverId, self::DEMO_CHANNEL_NAME);

            // Rôles de serveur : P2 = administrateur de serveur, member = standard.
            $this->ensureMembership($pdo, $ids['superadmin'], $serverId, 'P2');
            $this->ensureMembership($pdo, $ids['moderator'], $serverId, 'P2');
            $this->ensureMembership($pdo, $ids['user'], $serverId, 'member');

            $this->ensureInvite($pdo, $serverId, self::DEMO_INVITE_CODE);
            $this->ensureWelcomeMessage($pdo, $channelId, $ids['superadmin']);
            $this->ensureDemoDm($pdo, $ids['moderator'], $ids['user']);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->error('Échec du seeding de démonstration : ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->renderSummary($ids);

        return self::SUCCESS;
    }

    private function confirmFresh(): bool
    {
        return $this->confirm('--fresh va VIDER toutes les tables métier (utilisateurs, serveurs, messages...). Continuer ?');
    }

    private function truncateBusinessTables(PDO $pdo): void
    {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ([
            'user_passkeys', 'dm_reads', 'dm_messages', 'dm_conversations',
            'messages', 'invitations', 'server_members', 'channels', 'servers',
            'profiles', 'global_permissions', 'users',
        ] as $table) {
            $pdo->exec('TRUNCATE TABLE ' . $table);
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * @param array{key:string, username:string, email:string, password:string, status:string, bio:string} $account
     */
    private function upsertUser(PDO $pdo, array $account): int
    {
        $hash = password_hash($account['password'], PASSWORD_BCRYPT);

        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash)
             VALUES (:username, :email, :hash)
             ON DUPLICATE KEY UPDATE username = VALUES(username), password_hash = VALUES(password_hash)'
        );
        $stmt->execute([
            ':username' => $account['username'],
            ':email' => $account['email'],
            ':hash' => $hash,
        ]);

        $userId = (int) $pdo->query(
            'SELECT id FROM users WHERE email = ' . $pdo->quote($account['email'])
        )->fetchColumn();

        $pdo->prepare(
            'INSERT INTO profiles (user_id, bio, status)
             VALUES (:uid, :bio, :status)
             ON DUPLICATE KEY UPDATE bio = VALUES(bio), status = VALUES(status)'
        )->execute([
            ':uid' => $userId,
            ':bio' => $account['bio'],
            ':status' => $account['status'],
        ]);

        return $userId;
    }

    private function grantGlobalPermission(PDO $pdo, int $userId): void
    {
        $pdo->prepare(
            'INSERT INTO global_permissions (user_id, permission_level)
             VALUES (:uid, :level)
             ON DUPLICATE KEY UPDATE permission_level = VALUES(permission_level)'
        )->execute([':uid' => $userId, ':level' => 'P1']);
    }

    private function ensureServer(PDO $pdo, string $name, int $ownerId): int
    {
        $stmt = $pdo->prepare('SELECT id FROM servers WHERE name = ? AND owner_id = ? LIMIT 1');
        $stmt->execute([$name, $ownerId]);
        $existing = $stmt->fetchColumn();
        if ($existing !== false) {
            return (int) $existing;
        }

        $pdo->prepare('INSERT INTO servers (name, owner_id) VALUES (?, ?)')->execute([$name, $ownerId]);

        return (int) $pdo->lastInsertId();
    }

    private function ensureChannel(PDO $pdo, int $serverId, string $name): int
    {
        $stmt = $pdo->prepare('SELECT id FROM channels WHERE server_id = ? AND name = ? LIMIT 1');
        $stmt->execute([$serverId, $name]);
        $existing = $stmt->fetchColumn();
        if ($existing !== false) {
            return (int) $existing;
        }

        $pdo->prepare('INSERT INTO channels (server_id, name) VALUES (?, ?)')->execute([$serverId, $name]);

        return (int) $pdo->lastInsertId();
    }

    private function ensureMembership(PDO $pdo, int $userId, int $serverId, string $role): void
    {
        $pdo->prepare(
            'INSERT INTO server_members (user_id, server_id, role)
             VALUES (:uid, :sid, :role)
             ON DUPLICATE KEY UPDATE role = VALUES(role)'
        )->execute([':uid' => $userId, ':sid' => $serverId, ':role' => $role]);
    }

    private function ensureInvite(PDO $pdo, int $serverId, string $code): void
    {
        $pdo->prepare(
            'INSERT INTO invitations (server_id, code)
             VALUES (:sid, :code)
             ON DUPLICATE KEY UPDATE server_id = VALUES(server_id)'
        )->execute([':sid' => $serverId, ':code' => $code]);
    }

    private function ensureWelcomeMessage(PDO $pdo, int $channelId, int $userId): void
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE channel_id = ?');
        $stmt->execute([$channelId]);
        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }

        $pdo->prepare('INSERT INTO messages (channel_id, user_id, content) VALUES (?, ?, ?)')
            ->execute([$channelId, $userId, 'Bienvenue sur le serveur de démonstration Biscord !']);
    }

    private function ensureDemoDm(PDO $pdo, int $userA, int $userB): void
    {
        [$u1, $u2] = $userA < $userB ? [$userA, $userB] : [$userB, $userA];

        $stmt = $pdo->prepare('SELECT id FROM dm_conversations WHERE user1_id = ? AND user2_id = ? LIMIT 1');
        $stmt->execute([$u1, $u2]);
        $conversationId = $stmt->fetchColumn();

        if ($conversationId === false) {
            $pdo->prepare('INSERT INTO dm_conversations (user1_id, user2_id) VALUES (?, ?)')->execute([$u1, $u2]);
            $conversationId = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO dm_messages (conversation_id, sender_id, content) VALUES (?, ?, ?)')
                ->execute([$conversationId, $u1, 'Salut, ceci est un message privé de démonstration.']);
        }
    }

    /**
     * @param array<string,int> $ids
     */
    private function renderSummary(array $ids): void
    {
        $this->info('Comptes de démonstration prêts :');
        $this->table(
            ['Rôle', 'E-mail', 'Mot de passe', 'user_id'],
            [
                ['Super Administrateur (P1)', 'admin@example.com', 'Admin123!', $ids['superadmin']],
                ['Administrateur de serveur (P2)', 'moderator@example.com', 'Moderator123!', $ids['moderator']],
                ['Utilisateur standard', 'user@example.com', 'User123!', $ids['user']],
            ]
        );
        $this->line('Serveur de démonstration, salon « general », invitation « ' . self::DEMO_INVITE_CODE . ' » créés.');
    }
}
