<?php

declare(strict_types=1);

namespace Tests\Contract;

final class AcceptInviteContractTest extends ContractTestCase
{
    public function test_accept_invite_success_returns_expected_shape(): void
    {
        $this->actingAsBob();
        $response = $this->client->postForm('/api/accept_invite.php', ['code' => 'INV-OK']);

        $this->assertSame(200, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'server_id']);
        $this->assertTrue($response['json']['success']);
    }

    public function test_accept_invite_missing_code_is_documented_error(): void
    {
        $this->actingAsAlice();
        $response = $this->client->postForm('/api/accept_invite.php', ['code' => '']);

        $this->assertSame(200, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
        $this->assertFalse($response['json']['success']);
    }

    public function test_accept_invite_without_session_returns_documented_error_shape(): void
    {
        $response = $this->client->postForm('/api/accept_invite.php', ['code' => 'INV-OK']);

        // Ambiguïté doc Phase 0 : la matrice endpoint indique erreur body (200), le plan évoque 401.
        // On verrouille seulement l'invariant sûr : échec JSON contractuel.
        $this->assertContains($response['status'], [200, 401]);
        $this->assertHasKeys($response['json'], ['success', 'error']);
        $this->assertFalse($response['json']['success']);
    }
}
