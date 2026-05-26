<?php

declare(strict_types=1);

namespace Tests\Contract;

use Tests\Contract\Support\TestDatabaseSeeder;

/**
 * Contract tests for GET /api/get_users_in_server.php
 *
 * HTTP contract:
 *   Method : toutes (aucune restriction de méthode)
 *   Auth   : session requise via requireAuthUserId() → 401
 *   Input  : GET ?server_id=<int>
 *   Output :
 *     401 { success: false, error: 'Non authentifié' }         — non connecté
 *     400 { success: false, error: string }                     — server_id invalide ou absent
 *     403 { success: false, error: string }                     — non-membre du serveur
 *     200 { success: true, users: array<{id, username, role, avatar_url}> }
 */
final class GetUsersInServerContractTest extends ContractTestCase
{
    public function test_get_users_in_server_requires_authentication(): void
    {
        $response = $this->client->get(
            '/api/get_users_in_server.php?server_id=' . TestDatabaseSeeder::SERVER_1_ID
        );

        $this->assertSame(401, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Non authentifié', $response['json']['error']);
    }

    public function test_get_users_in_server_member_success_shape(): void
    {
        $this->actingAsAlice();
        $response = $this->client->get(
            '/api/get_users_in_server.php?server_id=' . TestDatabaseSeeder::SERVER_1_ID
        );

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
        $this->assertArrayHasKey('users', $response['json']);
        $this->assertIsArray($response['json']['users']);
        $this->assertNotEmpty($response['json']['users']);
    }

    public function test_get_users_in_server_invalid_server_id_returns_400(): void
    {
        $this->actingAsAlice();
        $response = $this->client->get('/api/get_users_in_server.php?server_id=0');

        $this->assertSame(400, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertArrayHasKey('error', $response['json']);
    }

    public function test_get_users_in_server_non_member_returns_403(): void
    {
        // Admin n'est pas membre du SERVER_1_ID
        $this->actingAsAdmin();
        $response = $this->client->get(
            '/api/get_users_in_server.php?server_id=' . TestDatabaseSeeder::SERVER_1_ID
        );

        $this->assertSame(403, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertArrayHasKey('error', $response['json']);
    }
}
