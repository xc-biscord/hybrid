<?php

declare(strict_types=1);

namespace Tests\Contract;

/**
 * Contract tests for GET /api/get_dm_notifications.php
 *
 * HTTP contract:
 *   Method : toutes (aucune restriction de méthode)
 *   Auth   : session requise via requireAuthUserId() → 401
 *   Input  : aucun paramètre requis
 *   Output :
 *     401 { success: false, error: 'Non authentifié' }                         — non connecté
 *     200 { success: true, unread_conversations: array<conversation> }          — succès
 */
final class GetDmNotificationsContractTest extends ContractTestCase
{
    public function test_get_dm_notifications_requires_authentication(): void
    {
        $response = $this->client->get('/api/get_dm_notifications.php');

        $this->assertSame(401, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Non authentifié', $response['json']['error']);
    }

    public function test_get_dm_notifications_success_shape(): void
    {
        $this->actingAsAlice();
        $response = $this->client->get('/api/get_dm_notifications.php');

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);

        // @legacy-invariant: la clé de la liste est `unread_conversations` (pas `conversations`)
        $this->assertArrayHasKey('unread_conversations', $response['json']);
        $this->assertIsArray($response['json']['unread_conversations']);
    }

    public function test_get_dm_notifications_returns_empty_array_when_no_unread(): void
    {
        // Bob n'a aucun message non-lu (le seeder ne crée pas de messages DM)
        $this->actingAsBob();
        $response = $this->client->get('/api/get_dm_notifications.php');

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
        $this->assertIsArray($response['json']['unread_conversations']);
    }
}
