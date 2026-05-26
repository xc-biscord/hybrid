<?php

declare(strict_types=1);

namespace Tests\Contract;

/**
 * Contract tests for /api/auth.php
 *
 * @legacy-invariant: endpoint vide si authentifié (body vide, HTTP 200).
 * @legacy-invariant: endpoint renvoie 401 JSON uniquement quand non authentifié.
 */
final class AuthContractTest extends ContractTestCase
{
    public function test_auth_requires_session(): void
    {
        $response = $this->client->get('/api/auth.php');

        $this->assertSame(401, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Non authentifié', $response['json']['error']);
    }

    public function test_auth_authenticated_returns_200_with_empty_body_invariant(): void
    {
        $this->actingAsAlice();
        $response = $this->client->get('/api/auth.php');

        $this->assertSame(200, $response['status']);
        // @legacy-invariant: requireAuthUserId() ne renvoie aucun payload en cas de succès.
        $this->assertSame('', trim($response['raw']));
    }
}
