<?php

declare(strict_types=1);

namespace Tests\Contract;

use Tests\Contract\Support\TestDatabaseSeeder;

/**
 * Contract tests for GET /api/get_user_servers.php
 *
 * HTTP contract:
 *   Method : toutes (aucune restriction de méthode)
 *   Auth   : session requise via requireAuthUserId() → 401
 *   Input  : GET ?user_id=<int>
 *   Output :
 *     401 { success: false, error: 'Non authentifié' }                   — non connecté
 *     400 { success: false, error: 'Paramètre user_id manquant ou invalide' } — user_id absent
 *     403 { success: false, error: 'Accès refusé : réservé aux P1' }     — pas P1
 *     200 { success: true, servers: array }                               — P1 uniquement
 */
final class GetUserServersContractTest extends ContractTestCase
{
    public function test_get_user_servers_requires_authentication(): void
    {
        $response = $this->client->get(
            '/api/get_user_servers.php?user_id=' . TestDatabaseSeeder::USER_ALICE_ID
        );

        $this->assertSame(401, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Non authentifié', $response['json']['error']);
    }

    public function test_get_user_servers_missing_user_id_returns_400(): void
    {
        $this->actingAsAdmin();
        $response = $this->client->get('/api/get_user_servers.php');

        $this->assertSame(400, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Paramètre user_id manquant ou invalide', $response['json']['error']);
    }

    public function test_get_user_servers_non_numeric_user_id_returns_400(): void
    {
        $this->actingAsAdmin();
        $response = $this->client->get('/api/get_user_servers.php?user_id=abc');

        $this->assertSame(400, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Paramètre user_id manquant ou invalide', $response['json']['error']);
    }

    public function test_get_user_servers_non_p1_returns_403(): void
    {
        $this->actingAsAlice(); // Alice est P3, pas P1
        $response = $this->client->get(
            '/api/get_user_servers.php?user_id=' . TestDatabaseSeeder::USER_BOB_ID
        );

        $this->assertSame(403, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Accès refusé : réservé aux P1', $response['json']['error']);
    }

    public function test_get_user_servers_p1_success_shape(): void
    {
        $this->actingAsAdmin(); // Admin est P1
        $response = $this->client->get(
            '/api/get_user_servers.php?user_id=' . TestDatabaseSeeder::USER_ALICE_ID
        );

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
        $this->assertArrayHasKey('servers', $response['json']);
        $this->assertIsArray($response['json']['servers']);
    }
}
