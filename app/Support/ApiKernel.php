<?php

declare(strict_types=1);

namespace App\Support;

use App\Controllers\AdminUserController;
use App\Controllers\ChannelController;
use App\Controllers\ServerController;
use App\Middleware\AdminMiddleware;
use App\Repositories\ChannelRepository;
use App\Repositories\ServerMemberRepository;
use App\Repositories\ServerRepository;
use App\Services\ChannelService;
use App\Services\ServerService;
use App\Services\UserServerService;
use PDO;

final class ApiKernel
{
    public function __construct(private PDO $pdo)
    {
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
        $service = new UserServerService($adminMiddleware, $serverMemberRepository);

        return new AdminUserController($this->pdo, $service);
    }
}
