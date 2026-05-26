<?php

declare(strict_types=1);

namespace Tests\Contract;

use Tests\Contract\Support\TestDatabaseSeeder;

/**
 * Contract tests for POST /api/kick_member.php
 *
 * HTTP contract:
 *   Method : toutes (aucune restriction de méthode — getJsonInput() lit php://input)
 *   Auth   : session requise via requireAuthUserId() → 401
 *   Input  : JSON body { server_id: int, target_user_id: int }
 *   Output :
 *     401 { success: false, error: 'Non authentifié' }    — non connecté
 *     403 { success: false, error: string }                — permission insuffisante
 *     200 { success: true }                                — exclusion réussie
 *
 * @legacy-invariant: server_id ou target_user_id absents sont castés en int 0
 *                    (int)($data['server_id'] ?? 0) — pas de validation de présence.
 */
final class KickMemberContractTest extends ContractTestCase
{
    public function test_kick_member_requires_authentication(): void
    {
        $response = $this->client->postJson('/api/kick_member.php', [
            'server_id'      => TestDatabaseSeeder::SERVER_1_ID,
            'target_user_id' => TestDatabaseSeeder::USER_BOB_ID,
        ]);

        $this->assertSame(401, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Non authentifié', $response['json']['error']);
    }

    public function test_kick_member_insufficient_role_returns_403(): void
    {
        // Bob (member) ne peut pas expulser Alice (P3)
        $this->actingAsBob();
        $response = $this->client->postJson('/api/kick_member.php', [
            'server_id'      => TestDatabaseSeeder::SERVER_1_ID,
            'target_user_id' => TestDatabaseSeeder::USER_ALICE_ID,
        ]);

        $this->assertSame(403, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertArrayHasKey('error', $response['json']);
    }

    public function test_kick_member_p3_can_kick_regular_member(): void
    {
        // Alice (P3) peut expulser Bob (member)
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/kick_member.php', [
            'server_id'      => TestDatabaseSeeder::SERVER_1_ID,
            'target_user_id' => TestDatabaseSeeder::USER_BOB_ID,
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
    }

    public function test_kick_member_zero_ids_returns_403_or_error(): void
    {
        // @legacy-invariant: IDs absents → castés en 0 → le service lèvera une DomainException
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/kick_member.php', []);

        // Retourne une erreur (403 ou autre) selon la validation dans ModerationService
        $this->assertNotSame(200, $response['status']);
        $this->assertFalse($response['json']['success']);
    }
}
