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

    public function test_accept_invite_missing_code_keeps_legacy_success_invariant(): void
    {
        $this->actingAsAlice();
        $response = $this->client->postForm('/api/accept_invite.php', ['code' => '']);

        $this->assertSame(200, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
        $this->assertTrue($response['json']['success']);
    }

    public function test_accept_invite_without_session_uses_fresh_client_and_keeps_legacy_success_invariant(): void
    {
        $this->assertFalse($this->client->hasCookie('PHPSESSID'));

        $response = $this->client->postForm('/api/accept_invite.php', ['code' => 'INV-OK']);

        $this->assertSame(200, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
        $this->assertTrue($response['json']['success']);
    }
}
