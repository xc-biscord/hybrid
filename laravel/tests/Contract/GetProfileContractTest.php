<?php

declare(strict_types=1);

namespace Tests\Contract;

/**
 * Contract tests for GET /api/get_profile.php
 *
 * HTTP contract:
 *   Method : toutes (aucune restriction de méthode)
 *   Auth   : session requise — 401 si absente
 *   Output :
 *     401 { success: false, error: 'Utilisateur non connecté' }
 *     200 { success: true,  profile: { username, email, bio, avatar_url, status, is_p1 } }
 *     200 { success: false, error: 'Profil introuvable' }          — user sans profil
 *     500 { success: false, error: 'Erreur serveur', details: str } — exception DB
 *
 * @legacy-invariant: Profil introuvable → HTTP 200 (pas 404).
 * @legacy-invariant: `is_p1` est un booléen injecté dans le tableau `profile` (pas un champ séparé).
 * @legacy-invariant: La réponse de succès intègre `email` dans le profil public
 *                    (fuite de donnée volontairement préservée).
 */
final class GetProfileContractTest extends ContractTestCase
{
    public function test_get_profile_success_shape_for_non_p1_user(): void
    {
        $this->actingAsBob();
        $response = $this->client->get('/api/get_profile.php');

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
        $this->assertArrayHasKey('profile', $response['json']);

        $profile = $response['json']['profile'];
        $this->assertHasKeys($profile, ['username', 'email', 'bio', 'avatar_url', 'status', 'is_p1']);

        // @legacy-invariant: is_p1 est un booléen à l'intérieur de `profile`
        $this->assertFalse((bool) $profile['is_p1']);
    }

    public function test_get_profile_p1_flag_is_true_for_admin_user(): void
    {
        $this->actingAsAdmin();
        $response = $this->client->get('/api/get_profile.php');

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);

        // @legacy-invariant: is_p1 est présent et vrai pour un utilisateur P1
        $this->assertTrue((bool) $response['json']['profile']['is_p1']);
    }

    public function test_get_profile_requires_authentication(): void
    {
        $response = $this->client->get('/api/get_profile.php');

        $this->assertSame(401, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Utilisateur non connecté', $response['json']['error']);
    }

    public function test_get_profile_includes_email_in_profile_invariant(): void
    {
        // @legacy-invariant: l'email est inclus dans la réponse du profil propre
        // (exposé publiquement — comportement legacy à préserver tel quel)
        $this->actingAsAlice();
        $response = $this->client->get('/api/get_profile.php');

        $this->assertSame(200, $response['status']);
        $this->assertArrayHasKey('email', $response['json']['profile']);
        $this->assertNotEmpty($response['json']['profile']['email']);
    }
}
