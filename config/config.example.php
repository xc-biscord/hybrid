<?php

/**
 * Modèle de configuration native Biscord.
 *
 * Copier ce fichier en `config/config.php` puis renseigner les identifiants
 * réels de la base de données :
 *
 *     cp config/config.example.php config/config.php
 *
 * `config/config.php` est volontairement exclu du dépôt (voir .gitignore) car
 * il contient des secrets. Ce fichier est chargé par `router.php` avant Laravel
 * pour ouvrir la connexion PDO et démarrer la session PHP native partagée avec
 * le runtime Laravel.
 */

$DB_HOST = 'localhost';
$DB_NAME = 'biscord_db';
$DB_USER = 'biscord_app';
$DB_PASS = 'changeme';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Connexion à la base de données échouée']));
}

if (session_status() === PHP_SESSION_NONE) {
    // Refuse les identifiants de session non initialisés (anti-fixation).
    ini_set('session.use_strict_mode', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);

    session_start();
}
