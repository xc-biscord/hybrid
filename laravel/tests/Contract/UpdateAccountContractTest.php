<?php

declare(strict_types=1);

namespace Tests\Contract;

/**
 * Contract tests for /api/update_account.php
 *
 * HTTP contract:
 *   Method : toutes (aucune restriction de méthode)
 *   Auth   : session implicite via bootstrap — mais le guard retourne HTTP 200 (pas 401 !)
 *   Input  : JSON body avec les champs de compte à mettre à jour
 *   Output :
 *     200 { success: false, error: 'Non connecté' }                   — non authentifié
 *     200 { success: false, error: 'Aucune donnée à mettre à jour' }  — payload vide
 *     200 { success: true }                                            — mise à jour réussie
 *     200 { success: false, error: string }                            — erreur domaine
 *     500 { success: false, error: 'Erreur SQL', debug: string }       — erreur PDO
 *
 * @legacy-invariant: L'échec d'authentification retourne HTTP 200 avec success=false,
 *                    PAS HTTP 401. Ce comportement diverge du reste de l'API.
 * @legacy-invariant: Payload vide → 200 avec un message d'erreur explicite (pas 400).
 */
final class UpdateAccountContractTest extends ContractTestCase
{
    public function test_update_account_unauthenticated_returns_200_not_401_invariant(): void
    {
        // @legacy-invariant: auth manquante → HTTP 200 { success: false }, PAS HTTP 401
        $response = $this->client->postJson('/api/update_account.php', [
            'username' => 'nouveau_nom',
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Non connecté', $response['json']['error']);
    }

    public function test_update_account_empty_payload_returns_error_message(): void
    {
        // @legacy-invariant: payload vide → 200 + message d'erreur (pas 400 Bad Request)
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/update_account.php', []);

        $this->assertSame(200, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Aucune donnée à mettre à jour', $response['json']['error']);
    }

    public function test_update_account_success_returns_200(): void
    {
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/update_account.php', [
            'username' => 'alice_updated',
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
    }

    public function test_update_account_duplicate_username_returns_200_with_error(): void
    {
        // @legacy-invariant: conflit de données → HTTP 200 { success: false },
        // pas HTTP 409 Conflict
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/update_account.php', [
            'username' => 'bob', // 'bob' est déjà pris
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
    }
}
