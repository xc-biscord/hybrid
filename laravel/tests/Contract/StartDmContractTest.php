<?php

declare(strict_types=1);

namespace Tests\Contract;

use Tests\Contract\Support\TestDatabaseSeeder;

/**
 * Contract tests for POST /api/start_dm.php
 *
 * HTTP contract:
 *   Method : POST uniquement (405 sinon)
 *   Auth   : session requise via requireAuthUserId() → 401
 *   Input  : JSON body { target_user_id: int }
 *   Output :
 *     405 { success: false, error: 'Méthode non autorisée' }    — mauvaise méthode
 *     401 { success: false, error: 'Non authentifié' }          — non connecté
 *     400 { success: false, error: string }                      — payload invalide
 *     201 { success: true, status: 'created', conversation_id: int } — nouvelle conversation
 *     200 { success: true, status: 'existing', conversation_id: int } — conversation existante
 *
 * @legacy-invariant: Si la conversation existe déjà, retourne HTTP 200 (pas 201).
 * @legacy-invariant: Si la conversation est créée, retourne HTTP 201 (pas 200).
 *                    Cette distinction status='created'/'existing' doit être préservée.
 */
final class StartDmContractTest extends ContractTestCase
{
    public function test_start_dm_rejects_get_method_with_405(): void
    {
        $response = $this->client->get('/api/start_dm.php');

        $this->assertSame(405, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Méthode non autorisée', $response['json']['error']);
    }

    public function test_start_dm_requires_authentication(): void
    {
        $response = $this->client->postJson('/api/start_dm.php', [
            'target_user_id' => TestDatabaseSeeder::USER_BOB_ID,
        ]);

        $this->assertSame(401, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Non authentifié', $response['json']['error']);
    }

    public function test_start_dm_existing_conversation_returns_200_invariant(): void
    {
        // @legacy-invariant: conversation déjà existante → HTTP 200 { status: 'existing' }
        // (le seeder crée DM_CONVERSATION_ID entre Alice et Bob)
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/start_dm.php', [
            'target_user_id' => TestDatabaseSeeder::USER_BOB_ID,
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
        $this->assertArrayHasKey('conversation_id', $response['json']);
    }

    public function test_start_dm_new_conversation_returns_201_invariant(): void
    {
        // @legacy-invariant: nouvelle conversation → HTTP 201 { status: 'created' }
        // Alice et Admin n'ont pas encore de conversation DM
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/start_dm.php', [
            'target_user_id' => TestDatabaseSeeder::USER_ADMIN_ID,
        ]);

        $this->assertSame(201, $response['status']);
        $this->assertTrue($response['json']['success']);
        $this->assertArrayHasKey('conversation_id', $response['json']);
    }

    public function test_start_dm_missing_target_user_id_returns_400(): void
    {
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/start_dm.php', []);

        $this->assertSame(400, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertArrayHasKey('error', $response['json']);
    }
}
