# Biscord

Biscord is a Discord-like web application currently maintained as a hybrid PHP and Laravel codebase.

The public application is served from the project root. Legacy API wrappers remain in `api/*.php`, while migrated behavior is implemented in the Laravel application under `laravel/`.

## Project Layout

- `api/` - public API wrapper files kept for legacy contract compatibility.
- `app/` - legacy PHP domain code still used by some wrappers.
- `laravel/` - Laravel application, controllers, services, repositories, routes, and tests.
- `frontend/`, `styles/`, `*.html`, `*.js` - public frontend assets and pages.
- `config/` - environment-specific PHP configuration.
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

From the project root, the public legacy-compatible entrypoints can be served with PHP's built-in server. The `router.php` script forwards the historical `/api/*.php` URLs to the Laravel runtime (the legacy `api/*.php` wrappers no longer exist):

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

The project is being migrated endpoint by endpoint from legacy PHP to Laravel. During migration:

- keep public `api/*.php` wrappers in place;
- preserve existing JSON payloads, HTTP statuses, headers, and session behavior;
- avoid global rewrites;
- treat Contract tests as the compatibility gate.
