<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Passkey;

use App\Http\Controllers\BaseApiController;

/**
 * Base commune aux contrôleurs Passkeys.
 *
 * Fournit deux briques transverses :
 *  - l'authentification par session native ($_SESSION), comme le reste de l'API ;
 *  - le stockage TEMPORAIRE du challenge WebAuthn en session, avec expiration.
 *
 * Le challenge est conservé côté serveur entre l'étape "options" (génération du
 * challenge) et l'étape "verify" (vérification de la réponse signée). Il est à
 * usage unique : on le consomme (supprime) dès qu'on le lit, et il expire vite.
 */
abstract class PasskeyController extends BaseApiController
{
    /**
     * @return int|null l'id utilisateur en session, ou null si non connecté
     */
    protected function sessionUserId(): ?int
    {
        if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
            return (int) $_SESSION['user_id'];
        }

        return null;
    }

    /**
     * Enregistre des options WebAuthn (JSON sérialisé) en session, avec une date
     * d'expiration. Le challenge vit à l'intérieur de ce JSON.
     */
    protected function storeChallenge(string $key, string $serializedOptions): void
    {
        $_SESSION[$key] = $serializedOptions;
        $_SESSION[$key.'_expires'] = time() + (int) config('passkey.challenge_ttl', 120);
    }

    /**
     * Consomme un challenge : le lit, le supprime (usage unique) et renvoie null
     * s'il est absent ou expiré.
     */
    protected function takeChallenge(string $key): ?string
    {
        $value = $_SESSION[$key] ?? null;
        $expires = $_SESSION[$key.'_expires'] ?? 0;

        unset($_SESSION[$key], $_SESSION[$key.'_expires']);

        if (! is_string($value) || ! is_numeric($expires) || time() > (int) $expires) {
            return null;
        }

        return $value;
    }
}
