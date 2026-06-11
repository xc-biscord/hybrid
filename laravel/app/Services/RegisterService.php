<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\MessageRepository;
use App\Repositories\ProfileRepository;
use App\Repositories\ServerMemberRepository;
use App\Repositories\UserRepository;
use PDO;
use PDOException;

final class RegisterService
{
    private const DEFAULT_AVATAR_URL = 'https://biscord-api-stg.xcsoftworks.com/assets/default-user.png';
    private const HUB_SERVER_ID = 1;
    private const HUB_CHANNEL_ID = 1;

    public function __construct(
        private PDO $pdo,
        private UserRepository $userRepository,
        private ProfileRepository $profileRepository,
        private ServerMemberRepository $serverMemberRepository,
        private MessageRepository $messageRepository,
    ) {
    }

    public function register(string $username, string $email, string $password): int
    {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $this->pdo->beginTransaction();

            $userId = $this->userRepository->create($username, $email, $passwordHash);

            $this->profileRepository->create(
                $userId,
                self::DEFAULT_AVATAR_URL,
                '',
                'En ligne'
            );

            $this->serverMemberRepository->addMemberIgnore(self::HUB_SERVER_ID, $userId);

            $welcomeMessage = "🎉 Bienvenue à @{$username} sur le Hub Biscord !";
            $this->messageRepository->createWithCurrentTimestamp(
                self::HUB_CHANNEL_ID,
                $userId,
                $welcomeMessage
            );

            $this->pdo->commit();

            // Session ouverte seulement une fois le compte réellement créé,
            // avec un nouvel ID de session (anti-fixation).
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;

            return $userId;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }
}
