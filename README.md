# Biscord

Biscord is a Discord-like web application whose dynamic backend runtime is Laravel.

The public application is served from the project root. Historical API URLs such as `/api/login.php` and `/api/get_servers.php` are preserved for frontend compatibility, but they are routed into the Laravel application under `laravel/`. The legacy `api/*.php` wrappers and legacy `app/*` runtime are no longer active.

## Project Layout

- `api/docs/` - static API documentation assets.
- `laravel/` - Laravel application, controllers, services, repositories, routes, and tests.
- `frontend/`, `styles/`, `*.html`, `*.js` - public frontend assets and pages.
- `config/` - environment-specific PHP configuration loaded before Laravel for the legacy-compatible native session/PDO bridge.
- `invite.php` - minimal Laravel front-controller facade for the historical root invite lookup URL.
- `router.php` - PHP built-in server router that forwards `/api/*.php` and `/invite.php` into Laravel.
- `biscord_db.sql` - database schema dump.

## Requirements

- PHP 8.3 or newer
- Composer
- MySQL or MariaDB
- Node.js and npm, only if rebuilding Laravel frontend assets

## Setup

Install Laravel dependencies:

```bash
cd laravel
composer install
```

Create a Laravel environment file if needed:

```bash
cp .env.example .env
php artisan key:generate
```

Configure database credentials for the target environment in the existing PHP and Laravel configuration files.

## Run Locally

From the project root, the public legacy-compatible entrypoints can be served with PHP's built-in server. The `router.php` script forwards historical `/api/*.php` URLs and `/invite.php` to the Laravel runtime:

```bash
php -S 127.0.0.1:8000 -t . router.php
```

The application is then available at:

```text
http://127.0.0.1:8000/
```

## Tests

Contract tests live in `laravel/tests/Contract` and are the source of truth for legacy API behavior.

Run the full Contract suite from the `laravel/` directory:

```bash
php artisan test --testsuite=Contract
```

Run a targeted contract test:

```bash
php artisan test --filter LoginContractTest
```

On the shared test server, the Contract suite is expected to run against the configured test database.

## Migration Notes

The Laravel runtime owns the dynamic backend surface. During future changes:

- preserve existing JSON payloads, HTTP statuses, headers, and session behavior unless a contract change is intentional;
- keep historical `/api/*.php` URLs working as compatibility routes;
- treat Contract tests as the compatibility gate;
- avoid reintroducing procedural PHP runtime logic outside Laravel.
