<?php

declare(strict_types=1);

namespace Tests\Contract;

use Tests\Contract\Support\TestDatabaseSeeder;

final class CreateInviteContractTest extends ContractTestCase
{
    public function test_create_invite_success_returns_invite_url(): void
    {
        $this->actingAsAlice();

        $response = $this->client->postForm('/api/create_invite.php', ['server_id' => TestDatabaseSeeder::SERVER_1_ID]);

        $this->assertSame(200, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'invite_url']);
        $this->assertTrue($response['json']['success']);
    }

    public function test_create_invite_non_member_keeps_legacy_success_invariant(): void
    {
        $this->actingAsAdmin();

        $response = $this->client->postForm('/api/create_invite.php', ['server_id' => TestDatabaseSeeder::SERVER_1_ID]);

        $this->assertSame(200, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
        $this->assertTrue($response['json']['success']);
    }

    public function test_create_invite_without_auth_uses_fresh_client_and_keeps_legacy_success_invariant(): void
    {
        $this->assertFalse($this->client->hasCookie('PHPSESSID'));

        $response = $this->client->postForm('/api/create_invite.php', ['server_id' => TestDatabaseSeeder::SERVER_1_ID]);

        $this->assertSame(200, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
        $this->assertTrue($response['json']['success']);
    }
}
