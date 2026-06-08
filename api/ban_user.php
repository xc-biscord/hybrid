<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/laravel_proxy.php';

$input = getJsonInput();
$request = new class ($input) {
    public function __construct(private array $input)
    {
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->input;
        }

        return $this->input[$key] ?? $default;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        return $this->input($key, $default);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->input($key, $default);
    }

    public function all(): array
    {
        return $this->input;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->input);
    }
};
$controller = laravelMake(\App\Http\Controllers\Api\BanUserController::class);
respondFromJsonResponse($controller->handle($request));
