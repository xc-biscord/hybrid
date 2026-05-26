<?php

declare(strict_types=1);

namespace Tests\Contract;

use Tests\Contract\Support\TestDatabaseSeeder;

/**
 * Contract tests for POST /api/set_member_role.php
 *
 * HTTP contract:
 *   Method : toutes (aucune restriction de méthode)
 *   Auth   : session requise via requireAuthUserId() → 401
 *   Input  : JSON body { server_id: int, target_user_id: int, new_role: string }
 *   Output :
 *     401 { success: false, error: 'Non authentifié' }    — non connecté
 *     400 { success: false, error: string }                — role invalide ou IDs manquants
 *     403 { success: false, error: string }                — permission insuffisante
 *     200 { success: true }                                — rôle mis à jour
 *
 * @legacy-invariant: server_id et target_user_id absents sont castés en int 0
 *                    (même logique que kick_member.php).
 * @legacy-invariant: new_role absent est casté en chaîne vide '' (pas de 400 pour champ manquant
 *                    dans le contrôleur — la validation est dans le service).
 */
final class SetMemberRoleContractTest extends ContractTestCase
{
    public function test_set_member_role_requires_authentication(): void
    {
        $response = $this->client->postJson('/api/set_member_role.php', [
            'server_id'      => TestDatabaseSeeder::SERVER_1_ID,
            'target_user_id' => TestDatabaseSeeder::USER_BOB_ID,
            'new_role'       => 'P2',
        ]);

        $this->assertSame(401, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Non authentifié', $response['json']['error']);
    }

    public function test_set_member_role_insufficient_permission_returns_403(): void
    {
        // Bob (member) ne peut pas changer le rôle d'Alice (P3)
        $this->actingAsBob();
        $response = $this->client->postJson('/api/set_member_role.php', [
            'server_id'      => TestDatabaseSeeder::SERVER_1_ID,
            'target_user_id' => TestDatabaseSeeder::USER_ALICE_ID,
            'new_role'       => 'member',
        ]);

        $this->assertSame(403, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertArrayHasKey('error', $response['json']);
    }

    public function test_set_member_role_p3_can_update_member_role(): void
    {
        // Alice (P3) peut modifier le rôle de Bob (member)
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/set_member_role.php', [
            'server_id'      => TestDatabaseSeeder::SERVER_1_ID,
            'target_user_id' => TestDatabaseSeeder::USER_BOB_ID,
            'new_role'       => 'P2',
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
    }

    public function test_set_member_role_invalid_role_returns_400(): void
    {
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/set_member_role.php', [
            'server_id'      => TestDatabaseSeeder::SERVER_1_ID,
            'target_user_id' => TestDatabaseSeeder::USER_BOB_ID,
            'new_role'       => 'ROLE_INEXISTANT',
        ]);

        $this->assertSame(400, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertArrayHasKey('error', $response['json']);
    }
}
