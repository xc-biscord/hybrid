<?php

declare(strict_types=1);

namespace Tests\Contract;

/**
 * Contract tests for GET /api/check_auth.php
 *
 * HTTP contract:
 *   Method : toutes (aucune restriction)
 *   Auth   : optionnelle — répond dans tous les cas
 *   Output :
 *     200 { logged_in: false }                         — non connecté
 *     200 { logged_in: true, username: string }        — connecté et utilisateur trouvé
 *     200 { logged_in: false }                         — session avec user_id introuvable en DB
 *
 * @legacy-invariant: La réponse utilise `logged_in` (boolean), PAS `success`.
 *                    Ce contrat est différent de tous les autres endpoints de l'API.
 * @legacy-invariant: Toutes les réponses retournent HTTP 200 (même quand non authentifié).
 * @legacy-invariant: Si la session contient un user_id qui n'existe plus en base,
 *                    retourne { logged_in: false } sans erreur.
 */
final class CheckAuthContractTest extends ContractTestCase
{
    public function test_check_auth_unauthenticated_returns_logged_in_false(): void
    {
        $response = $this->client->get('/api/check_auth.php');

        // @legacy-invariant: HTTP 200, pas 401
        $this->assertSame(200, $response['status']);

        // @legacy-invariant: clé `logged_in`, pas `success`
        $this->assertArrayHasKey('logged_in', $response['json']);
        $this->assertArrayNotHasKey('success', $response['json']);
        $this->assertFalse($response['json']['logged_in']);
    }

    public function test_check_auth_authenticated_returns_logged_in_true_with_username(): void
    {
        $this->actingAsAlice();
        $response = $this->client->get('/api/check_auth.php');

        $this->assertSame(200, $response['status']);
        $this->assertHasKeys($response['json'], ['logged_in', 'username']);
        $this->assertTrue($response['json']['logged_in']);
        $this->assertSame('alice', $response['json']['username']);
    }

    public function test_check_auth_response_has_no_success_key_invariant(): void
    {
        // @legacy-invariant: le schéma de réponse de check_auth ne contient PAS de clé `success`,
        // contrairement à tous les autres endpoints. Ce comportement doit être préservé
        // lors de la migration vers Laravel pour ne pas casser les clients existants.
        $this->actingAsBob();
        $response = $this->client->get('/api/check_auth.php');

        $this->assertSame(200, $response['status']);
        $this->assertArrayNotHasKey('success', $response['json']);
        $this->assertArrayHasKey('logged_in', $response['json']);
    }
}
