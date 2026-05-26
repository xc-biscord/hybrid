<?php

declare(strict_types=1);

namespace Tests\Contract;

/**
 * Contract tests for GET /api/get_all_users.php
 *
 * HTTP contract:
 *   Method : toutes (aucune restriction de méthode)
 *   Auth   : session requise via requireAuthUserId() → 401
 *   Perm   : réservé aux P1 → 403 sinon
 *   Input  : aucun paramètre
 *   Output :
 *     401 { success: false, error: 'Non authentifié' }      — non connecté
 *     403 { success: false, error: 'Accès réservé aux P1' } — pas P1
 *     200 { success: true, users: array<user> }              — liste complète
 *
 * @legacy-invariant: le message d'erreur 403 est 'Accès réservé aux P1'
 *                    (avec accent, sans le prefixe 'Accès refusé' de certains autres endpoints).
 */
final class GetAllUsersContractTest extends ContractTestCase
{
    public function test_get_all_users_requires_authentication(): void
    {
        $response = $this->client->get('/api/get_all_users.php');

        $this->assertSame(401, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Non authentifié', $response['json']['error']);
    }

    public function test_get_all_users_non_p1_returns_403(): void
    {
        $this->actingAsAlice(); // Alice est P3, pas P1
        $response = $this->client->get('/api/get_all_users.php');

        $this->assertSame(403, $response['status']);
        $this->assertFalse($response['json']['success']);

        // @legacy-invariant: message exact 'Accès réservé aux P1'
        $this->assertSame('Accès réservé aux P1', $response['json']['error']);
    }

    public function test_get_all_users_p1_success_shape(): void
    {
        $this->actingAsAdmin(); // Admin est P1
        $response = $this->client->get('/api/get_all_users.php');

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
        $this->assertArrayHasKey('users', $response['json']);
        $this->assertIsArray($response['json']['users']);
        $this->assertNotEmpty($response['json']['users']);
    }
}
