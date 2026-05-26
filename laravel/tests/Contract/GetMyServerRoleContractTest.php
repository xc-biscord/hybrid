<?php

declare(strict_types=1);

namespace Tests\Contract;

use Tests\Contract\Support\TestDatabaseSeeder;

/**
 * Contract tests for GET /api/get_my_server_role.php
 *
 * HTTP contract:
 *   Method : toutes (aucune restriction de méthode)
 *   Auth   : session requise via requireAuthUserId() → 401
 *   Input  : GET ?server_id=<int|null>
 *   Output :
 *     401 { success: false, error: 'Non authentifié' }         — non connecté
 *     200 { success: true, role: string }                       — rôle trouvé
 *     200 { success: true, role: null }                         — non-membre (null, pas d'erreur)
 *
 * @legacy-invariant: server_id non-numérique est silencieusement converti en null
 *                    (pas d'erreur 400 — is_numeric() retourne false → $serverId = null).
 * @legacy-invariant: Un non-membre retourne { success: true, role: null }
 *                    plutôt qu'une erreur 403 ou 404.
 */
final class GetMyServerRoleContractTest extends ContractTestCase
{
    public function test_get_my_server_role_requires_authentication(): void
    {
        $response = $this->client->get(
            '/api/get_my_server_role.php?server_id=' . TestDatabaseSeeder::SERVER_1_ID
        );

        $this->assertSame(401, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Non authentifié', $response['json']['error']);
    }

    public function test_get_my_server_role_returns_role_for_member(): void
    {
        $this->actingAsAlice(); // Alice est P3 dans SERVER_1_ID
        $response = $this->client->get(
            '/api/get_my_server_role.php?server_id=' . TestDatabaseSeeder::SERVER_1_ID
        );

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
        $this->assertArrayHasKey('role', $response['json']);
        $this->assertNotNull($response['json']['role']);
    }

    public function test_get_my_server_role_returns_null_for_non_member_invariant(): void
    {
        // @legacy-invariant: non-membre → { success: true, role: null }, pas d'erreur HTTP
        $this->actingAsAdmin(); // Admin n'est pas dans SERVER_1_ID
        $response = $this->client->get(
            '/api/get_my_server_role.php?server_id=' . TestDatabaseSeeder::SERVER_1_ID
        );

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
        $this->assertNull($response['json']['role']);
    }

    public function test_get_my_server_role_non_numeric_server_id_silently_nulled_invariant(): void
    {
        // @legacy-invariant: server_id='abc' → is_numeric() = false → $serverId = null
        // Pas d'erreur 400 — le null est passé au service qui retourne null pour le rôle.
        $this->actingAsAlice();
        $response = $this->client->get('/api/get_my_server_role.php?server_id=abc');

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
        $this->assertArrayHasKey('role', $response['json']);
    }
}
