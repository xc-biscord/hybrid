<?php

require_once __DIR__ . '/config/config.php';

$query = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
$_SERVER['REQUEST_URI'] = '/api/invite.php' . ($query ? '?' . $query : '');
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/laravel/public/index.php';

require __DIR__ . '/laravel/public/index.php';
