<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Configuration Passkeys / WebAuthn (PoC expérimental)
|--------------------------------------------------------------------------
|
| WebAuthn lie une clé à un "Relying Party" (le site). Le rpId DOIT être le
| domaine (sans schéma ni port) servant l'application en HTTPS, ou un domaine
| parent. En local on autorise "localhost" (seul host accepté par les
| navigateurs hors HTTPS pour WebAuthn).
|
| Tout est surchargé par variables d'environnement pour ne rien coder en dur.
|
*/

return [
    // Identifiant du Relying Party = domaine. Ex : biscord-api-stg.xcsoftworks.com
    'rp_id' => env('PASSKEY_RP_ID', 'localhost'),

    // Nom lisible affiché par le navigateur lors de la création de la passkey.
    'rp_name' => env('PASSKEY_RP_NAME', 'Biscord'),

    // Origine(s) autorisée(s) (schéma + host + port). Laisser vide => le contrôle
    // d'origine standard de la lib s'applique à partir du rpId.
    // Ex : "https://biscord-api-stg.xcsoftworks.com,http://localhost:8000"
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('PASSKEY_ALLOWED_ORIGINS', ''))
    ))),

    // Durée de validité d'un challenge (secondes). Court par sécurité : un
    // challenge est à usage unique et expire vite.
    'challenge_ttl' => (int) env('PASSKEY_CHALLENGE_TTL', 120),
];
