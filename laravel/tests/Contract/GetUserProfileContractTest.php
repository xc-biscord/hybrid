<?php

declare(strict_types=1);

namespace Tests\Contract;

use Tests\Contract\Support\TestDatabaseSeeder;

/**
 * Contract tests for GET /api/get_user_profile.php
 *
 * HTTP contract:
 *   Method : toutes (aucune restriction de méthode)
 *   Auth   : session vérifiée manuellement (config.php implicite) — mais HTTP 200 si absent !
 *   Input  : GET ?user_id=<int>
 *   Output :
 *     200 { success: false, error: 'Non connecté' }              — non authentifié
 *     200 { success: false, error: 'Paramètre user_id invalide' } — user_id absent ou non-numérique
 *     200 { success: true,  user: { id, username, avatar_url, bio, status } }
 *     200 { success: false, error: 'Utilisateur non trouvé' }    — user_id inconnu
 *     200 { success: false, error: 'Erreur DB : ...' }           — PDOException
 *
 * @legacy-invariant: TOUTES les réponses retournent HTTP 200 (même l'échec d'auth).
 * @legacy-invariant: La clé racine est `user` (pas `profile`), contrairement à get_profile.php.
 * @legacy-invariant: Les champs renvoyés sont : id, username, avatar_url, bio, status
 *                    (pas d'email exposé, contrairement à get_profile.php).
 */
final class GetUserProfileContractTest extends ContractTestCase
{
    public function test_get_user_profile_unauthenticated_returns_200_invariant(): void
    {
        // @legacy-invariant: non authentifié → HTTP 200 { success: false }, PAS HTTP 401
        $response = $this->client->get(
            '/api/get_user_profile.php?user_id=' . TestDatabaseSeeder::USER_ALICE_ID
        );

        $this->assertSame(200, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Non connecté', $response['json']['error']);
    }

    public function test_get_user_profile_success_shape(): void
    {
        $this->actingAsAlice();
        $response = $this->client->get(
            '/api/get_user_profile.php?user_id=' . TestDatabaseSeeder::USER_BOB_ID
        );

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
        $this->assertArrayHasKey('user', $response['json']);

        $user = $response['json']['user'];
        $this->assertHasKeys($user, ['id', 'username', 'avatar_url', 'bio', 'status']);
    }

    public function test_get_user_profile_missing_user_id_returns_200_with_error_invariant(): void
    {
        // @legacy-invariant: paramètre absent → HTTP 200 { success: false }, pas HTTP 400
        $this->actingAsAlice();
        $response = $this->client->get('/api/get_user_profile.php');

        $this->assertSame(200, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Paramètre user_id invalide', $response['json']['error']);
    }

    public function test_get_user_profile_non_numeric_user_id_returns_200_with_error_invariant(): void
    {
        // @legacy-invariant: user_id non-numérique → HTTP 200, pas HTTP 400
        $this->actingAsAlice();
        $response = $this->client->get('/api/get_user_profile.php?user_id=abc');

        $this->assertSame(200, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Paramètre user_id invalide', $response['json']['error']);
    }

    public function test_get_user_profile_unknown_user_returns_200_not_found_invariant(): void
    {
        // @legacy-invariant: user introuvable → HTTP 200 { success: false }, PAS HTTP 404
        $this->actingAsAlice();
        $response = $this->client->get('/api/get_user_profile.php?user_id=999999');

        $this->assertSame(200, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Utilisateur non trouvé', $response['json']['error']);
    }

    public function test_get_user_profile_does_not_expose_email_invariant(): void
    {
        // @legacy-invariant: get_user_profile n'expose PAS l'email,
        // contrairement à get_profile.php qui l'expose.
        $this->actingAsAlice();
        $response = $this->client->get(
            '/api/get_user_profile.php?user_id=' . TestDatabaseSeeder::USER_BOB_ID
        );

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
        $this->assertArrayNotHasKey('email', $response['json']['user']);
    }
}
