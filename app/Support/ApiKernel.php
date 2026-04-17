<?php

declare(strict_types=1);

namespace App\Support;

use App\Controllers\AccountController;
use App\Controllers\AdminUserController;
use App\Controllers\ChannelController;
use App\Controllers\MessageController;
use App\Controllers\AuthController;
use App\Controllers\ServerController;
use App\Middleware\AdminMiddleware;
use App\Repositories\ChannelRepository;
use App\Repositories\ServerMemberRepository;
use App\Repositories\MessageRepository;
use App\Repositories\ProfileRepository;
use App\Repositories\ServerRepository;
use App\Repositories\UserRepository;
use App\Services\AccountService;
use App\Services\ChannelService;
use App\Services\MessageService;
use App\Services\RegisterService;
use App\Services\ServerService;
use App\Services\UserServerService;
use App\Validators\AccountUpdateValidator;
use PDO;

final class ApiKernel
{
    public function __construct(private PDO $pdo)
    {
    }


    public function authController(): AuthController
    {
        $userRepository = new UserRepository($this->pdo);
        $profileRepository = new ProfileRepository($this->pdo);
        $messageRepository = new MessageRepository($this->pdo);
        $serverMemberRepository = new ServerMemberRepository($this->pdo);
        $service = new RegisterService(
            $this->pdo,
            $userRepository,
            $profileRepository,
            $serverMemberRepository,
            $messageRepository,
        );

        return new AuthController($service);
    }

    public function accountController(): AccountController
    {
        $userRepository = new UserRepository($this->pdo);
        $service = new AccountService($userRepository);
        $validator = new AccountUpdateValidator();

        return new AccountController($service, $validator);
    }

    public function serverController(): ServerController
    {
        $serverRepository = new ServerRepository($this->pdo);
        $serverMemberRepository = new ServerMemberRepository($this->pdo);
        $service = new ServerService($this->pdo, $serverRepository, $serverMemberRepository);

        return new ServerController($service);
    }

    public function channelController(): ChannelController
    {
        $adminMiddleware = new AdminMiddleware($this->pdo);
        $channelRepository = new ChannelRepository($this->pdo);
        $serverMemberRepository = new ServerMemberRepository($this->pdo);
        $service = new ChannelService($channelRepository, $serverMemberRepository, $adminMiddleware);

        return new ChannelController($service);
    }

    public function adminUserController(): AdminUserController
    {
        $adminMiddleware = new AdminMiddleware($this->pdo);
        $serverMemberRepository = new ServerMemberRepository($this->pdo);
        $userRepository = new UserRepository($this->pdo);
        $service = new UserServerService($adminMiddleware, $serverMemberRepository, $userRepository);

        return new AdminUserController($service);
    }

    public function messageController(): MessageController
    {
        $adminMiddleware = new AdminMiddleware($this->pdo);
        $messageRepository = new MessageRepository($this->pdo);
        $serverMemberRepository = new ServerMemberRepository($this->pdo);
        $service = new MessageService($messageRepository, $serverMemberRepository, $adminMiddleware);

        return new MessageController($service);
    }
}
