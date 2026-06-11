<?php

/*
 * Le frontend est servi depuis la même origine que l'API : aucun besoin
 * du joker « * » par défaut du framework. Seules les origines connues
 * du projet sont autorisées.
 */

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST'],

    'allowed_origins' => [
        'https://biscord-api-stg.xcsoftworks.com',
        'http://localhost:8000',
        'http://127.0.0.1:8000',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Internal-Ping'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => true,

];
