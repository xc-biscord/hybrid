<?php

declare(strict_types=1);

namespace Tests\Contract;

use Tests\Contract\Support\TestDatabaseSeeder;

/**
 * Contract tests for POST /api/ban_user.php
 *
 * HTTP contract:
 *   Method : toutes (aucune restriction de méthode)
 *   Auth   : session vérifiée manuellement — mais retourne HTTP 200 (pas 401 !)
 *   Perm   : réservé aux P1 (global_permissions)
 *   Input  : JSON body { user_id: int }
 *   Output :
 *     200 { success: false, error: 'Non authentifié' }              — non connecté
 *     200 { success: false, error: 'Accès refusé : réservé aux P1' } — pas P1
 *     200 { success: false, error: 'user_id invalide' }             — user_id = 0
 *     200 { success: true }                                          — ban réussi
 *     200 { success: false, error: 'Erreur DB : ...' }              — PDOException
 *
 * @legacy-invariant: TOUTES les réponses (y compris les erreurs d'auth) retournent HTTP 200.
 *                    ban_user.php ne définit jamais de code HTTP non-200.
 * @legacy-invariant: Le ban supprime en cascade : server_members, messages, profiles,
 *                    global_permissions, puis users. Pas de soft-delete.
 * @legacy-invariant: user_id=0 est considéré invalide (intval(0) est falsy en PHP).
 */
final class BanUserContractTest extends ContractTestCase
{
    public function test_ban_user_unauthenticated_returns_200_not_401_invariant(): void
    {
        // @legacy-invariant: auth manquante → HTTP 200 { success: false }, PAS HTTP 401
        $response = $this->client->postJson('/api/ban_user.php', [
            'user_id' => TestDatabaseSeeder::USER_BOB_ID,
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Non authentifié', $response['json']['error']);
    }

    public function test_ban_user_non_p1_returns_200_access_denied_invariant(): void
    {
        // @legacy-invariant: permission refusée → HTTP 200 { success: false }, PAS HTTP 403
        $this->actingAsAlice(); // Alice est P3, pas P1
        $response = $this->client->postJson('/api/ban_user.php', [
            'user_id' => TestDatabaseSeeder::USER_BOB_ID,
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Accès refusé : réservé aux P1', $response['json']['error']);
    }

    public function test_ban_user_zero_user_id_returns_200_invalid_invariant(): void
    {
        // @legacy-invariant: user_id=0 est rejeté car intval(0) est falsy en PHP
        $this->actingAsAdmin();
        $response = $this->client->postJson('/api/ban_user.php', [
            'user_id' => 0,
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('user_id invalide', $response['json']['error']);
    }

    public function test_ban_user_missing_user_id_returns_200_invalid_invariant(): void
    {
        // @legacy-invariant: user_id absent → intval(null ?? 0) = 0 → rejeté
        $this->actingAsAdmin();
        $response = $this->client->postJson('/api/ban_user.php', []);

        $this->assertSame(200, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('user_id invalide', $response['json']['error']);
    }

    public function test_ban_user_p1_can_ban_target_user(): void
    {
        $this->actingAsAdmin();
        $response = $this->client->postJson('/api/ban_user.php', [
            'user_id' => TestDatabaseSeeder::USER_BOB_ID,
        ]);

        // @legacy-invariant: succès → HTTP 200 { success: true }
        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
    }
}
