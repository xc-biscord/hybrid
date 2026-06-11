<?php

/**
 * Router script for the PHP built-in server (php -S ... -t <project root> router.php).
 *
 * Phase 6: Laravel is the single runtime for the historical /api/*.php URLs,
 * without the legacy api/*.php wrappers.
 *
 * - Requests to /api/<name>.php are forwarded to the Laravel front controller.
 *   config/config.php is loaded first — exactly as the historical api/bootstrap.php
 *   did — so the native PHP session is started and the global $pdo (with prepared
 *   statement emulation on) is exposed for the controllers the same way the
 *   wrappers provided it.
 * - Every other request (static assets, front HTML, the root invite.php, etc.)
 *   is served as-is by the built-in server. /invite.php at the root is NOT captured.
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (is_string($uri) && preg_match('#^/api/[A-Za-z0-9_]+\.php$#', $uri)) {
    require_once __DIR__ . '/config/config.php';

    // Normalise SAPI vars so Laravel derives "/api/<name>.php" as the path info
    // and treats laravel/public/index.php as the front controller at the web root.
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/laravel/public/index.php';

    require __DIR__ . '/laravel/public/index.php';

    return true;
}

// Let the built-in server serve the requested resource as-is.
return false;
