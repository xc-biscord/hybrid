<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;

function laravelApp(): Application
{
    static $app = null;

    if ($app === null) {
        require_once __DIR__ . '/../laravel/vendor/autoload.php';

        $created = require __DIR__ . '/../laravel/bootstrap/app.php';

        $created->bootstrapWith([
            \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
            \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
            \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
            \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
            \Illuminate\Foundation\Bootstrap\BootProviders::class,
        ]);

        $app = $created;
    }

    return $app;
}

function laravelMake(string $abstract): mixed
{
    return laravelApp()->make($abstract);
}

function respondFromJsonResponse(JsonResponse $response): void
{
    http_response_code($response->getStatusCode());
    echo $response->getContent();
    exit;
}
