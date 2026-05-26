<?php

declare(strict_types=1);

namespace Tests\Contract;

use Tests\Contract\Support\TestDatabaseSeeder;

/**
 * Contract tests for POST /api/send_dm.php
 *
 * HTTP contract:
 *   Method : POST uniquement (405 sinon)
 *   Auth   : session requise via requireAuthUserId() → 401
 *   Input  : JSON body { conversation_id: int, content: string }
 *   Output :
 *     405 { success: false, error: 'Méthode non autorisée' }    — mauvaise méthode
 *     401 { success: false, error: 'Non authentifié' }          — non connecté
 *     400 { success: false, error: string }                      — payload invalide
 *     403 { success: false, error: string }                      — non participant
 *     201 { success: true, message_id: int }                     — message envoyé
 */
final class SendDmContractTest extends ContractTestCase
{
    public function test_send_dm_rejects_get_method_with_405(): void
    {
        $response = $this->client->get('/api/send_dm.php');

        $this->assertSame(405, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Méthode non autorisée', $response['json']['error']);
    }

    public function test_send_dm_requires_authentication(): void
    {
        $response = $this->client->postJson('/api/send_dm.php', [
            'conversation_id' => TestDatabaseSeeder::DM_CONVERSATION_ID,
            'content'         => 'Bonjour',
        ]);

        $this->assertSame(401, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Non authentifié', $response['json']['error']);
    }

    public function test_send_dm_success_returns_201_with_message_id(): void
    {
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/send_dm.php', [
            'conversation_id' => TestDatabaseSeeder::DM_CONVERSATION_ID,
            'content'         => 'Bonjour Bob !',
        ]);

        $this->assertSame(201, $response['status']);
        $this->assertTrue($response['json']['success']);
        $this->assertArrayHasKey('message_id', $response['json']);
        $this->assertIsInt($response['json']['message_id']);
    }

    public function test_send_dm_non_participant_returns_403(): void
    {
        // Admin n'est pas dans la conversation Alice-Bob
        $this->actingAsAdmin();
        $response = $this->client->postJson('/api/send_dm.php', [
            'conversation_id' => TestDatabaseSeeder::DM_CONVERSATION_ID,
            'content'         => 'Intrusion',
        ]);

        $this->assertSame(403, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertArrayHasKey('error', $response['json']);
    }

    public function test_send_dm_empty_content_returns_400(): void
    {
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/send_dm.php', [
            'conversation_id' => TestDatabaseSeeder::DM_CONVERSATION_ID,
            'content'         => '',
        ]);

        $this->assertSame(400, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertArrayHasKey('error', $response['json']);
    }
}
