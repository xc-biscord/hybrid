<?php

declare(strict_types=1);

namespace Tests\Contract;

use Tests\Contract\Support\TestDatabaseSeeder;

/**
 * Contract tests for /api/delete_message.php
 *
 * HTTP contract:
 *   Method : toutes (aucune restriction de méthode)
 *   Auth   : session requise via requireAuthUserId() → 401
 *   Input  : JSON body { message_id: int }
 *   Output :
 *     401 { success: false, error: 'Non authentifié' }     — non connecté
 *     200 { success: true }                                 — suppression réussie
 *     200 { success: false, error: string }                 — erreur domaine (pas 403 !)
 *
 * @legacy-invariant: Les erreurs de domaine (message introuvable, droits insuffisants)
 *                    retournent HTTP 200 { success: false }, PAS HTTP 403/404.
 *                    (MessageController::delete() passe le code 200 à $this->error())
 */
final class DeleteMessageContractTest extends ContractTestCase
{
    public function test_delete_message_requires_authentication(): void
    {
        $response = $this->client->postJson('/api/delete_message.php', [
            'message_id' => 1401,
        ]);

        $this->assertSame(401, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Non authentifié', $response['json']['error']);
    }

    public function test_delete_own_message_returns_200_success(): void
    {
        // Alice peut supprimer son propre message (id=1401)
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/delete_message.php', [
            'message_id' => 1401,
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
    }

    public function test_delete_other_user_message_returns_200_with_error_invariant(): void
    {
        // @legacy-invariant: accès refusé → HTTP 200 { success: false }, PAS HTTP 403
        $this->actingAsBob();
        $response = $this->client->postJson('/api/delete_message.php', [
            'message_id' => 1401, // message d'Alice
        ]);

        // @legacy-invariant: DomainException dans delete() retourne toujours HTTP 200
        $this->assertSame(200, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
    }

    public function test_delete_nonexistent_message_returns_200_with_error_invariant(): void
    {
        // @legacy-invariant: message introuvable → HTTP 200 { success: false }, PAS HTTP 404
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/delete_message.php', [
            'message_id' => 999999,
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertFalse($response['json']['success']);
    }
}
