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

    public function test_create_invite_non_member_is_forbidden_in_body(): void
    {
        $this->actingAsAdmin();

        $response = $this->client->postForm('/api/create_invite.php', ['server_id' => TestDatabaseSeeder::SERVER_1_ID]);

        $this->assertSame(200, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
        $this->assertFalse($response['json']['success']);
    }

    public function test_create_invite_without_auth_returns_documented_error_shape(): void
    {
        $response = $this->client->postForm('/api/create_invite.php', ['server_id' => TestDatabaseSeeder::SERVER_1_ID]);

        $this->assertContains($response['status'], [200, 401]);
        $this->assertHasKeys($response['json'], ['success', 'error']);
        $this->assertFalse($response['json']['success']);
    }
}
