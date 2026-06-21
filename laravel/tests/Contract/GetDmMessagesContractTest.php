<?php

declare(strict_types=1);

namespace Tests\Contract;

use Tests\Contract\Support\TestDatabaseSeeder;

/**
 * Contract tests for GET /api/get_dm_messages.php
 *
 * HTTP contract:
 *   Method : toutes (aucune restriction de méthode)
 *   Auth   : session requise via requireAuthUserId() → 401
 *   Input  : GET ?conversation_id=<int>
 *   Output :
 *     401 { success: false, error: 'Non authentifié' }    — non connecté
 *     400 { success: false, error: string }                — conversation_id invalide
 *     403 { success: false, error: string }                — non participant
 *     404 { success: false, error: string }                — conversation inexistante
 *     200 { success: true, messages?: array, ... }         — succès
 *
 * @legacy-invariant: conversation_id=0 est passé au service tel quel
 *                    (int) ($_GET['conversation_id'] ?? 0) → 0 si absent.
 */
final class GetDmMessagesContractTest extends ContractTestCase
{
    public function test_get_dm_messages_requires_authentication(): void
    {
        $response = $this->client->get(
            '/api/get_dm_messages.php?conversation_id=' . TestDatabaseSeeder::DM_CONVERSATION_ID
        );

        $this->assertSame(401, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Non authentifié', $response['json']['error']);
    }

    public function test_get_dm_messages_participant_returns_200(): void
    {
        // Alice et Bob sont participants à DM_CONVERSATION_ID=2001
        $this->actingAsAlice();
        $response = $this->client->get(
            '/api/get_dm_messages.php?conversation_id=' . TestDatabaseSeeder::DM_CONVERSATION_ID
        );

        $this->assertSame(200, $response['status']);
    }

    public function test_get_dm_messages_participant_response_exposes_messages_array(): void
    {
        // Alice et Bob sont participants à DM_CONVERSATION_ID=2001
        $this->actingAsAlice();
        $response = $this->client->get(
            '/api/get_dm_messages.php?conversation_id=' . TestDatabaseSeeder::DM_CONVERSATION_ID
        );

        $this->assertTrue($response['json']['success']);
        $this->assertArrayHasKey('messages', $response['json']);
        $this->assertIsArray($response['json']['messages']);
    }

    public function test_get_dm_messages_non_participant_returns_403(): void
    {
        // Admin n'est pas participant à la conversation Alice-Bob
        $this->actingAsAdmin();
        $response = $this->client->get(
            '/api/get_dm_messages.php?conversation_id=' . TestDatabaseSeeder::DM_CONVERSATION_ID
        );

        $this->assertSame(403, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertArrayHasKey('error', $response['json']);
    }

    public function test_get_dm_messages_unknown_conversation_returns_404(): void
    {
        $this->actingAsAlice();
        $response = $this->client->get('/api/get_dm_messages.php?conversation_id=999999');

        $this->assertSame(404, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertArrayHasKey('error', $response['json']);
    }

    public function test_get_dm_messages_missing_conversation_id_returns_400(): void
    {
        // @legacy-invariant: conversation_id absent → (int)(null ?? 0) = 0.
        // DmService rejette 0 (<= 0) via InvalidArgumentException → 400 déterministe.
        $this->actingAsAlice();
        $response = $this->client->get('/api/get_dm_messages.php');

        $this->assertSame(400, $response['status']);
    }

    public function test_get_dm_messages_missing_conversation_id_returns_error_payload(): void
    {
        $this->actingAsAlice();
        $response = $this->client->get('/api/get_dm_messages.php');

        $this->assertFalse($response['json']['success']);
        $this->assertArrayHasKey('error', $response['json']);
    }
}
