<?php

declare(strict_types=1);

namespace Tests\Contract;

use Tests\Contract\Support\TestDatabaseSeeder;

final class CreateChannelContractTest extends ContractTestCase
{
    public function test_create_channel_success_with_p3_role(): void
    {
        $this->actingAsAlice();

        $response = $this->client->postJson('/api/create_channel.php', [
            'server_id' => TestDatabaseSeeder::SERVER_1_ID,
            'name' => 'contract-channel',
        ]);

        $this->assertSame(201, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'channel_id']);
        $this->assertTrue($response['json']['success']);
    }

    public function test_create_channel_requires_authentication(): void
    {
        $response = $this->client->postJson('/api/create_channel.php', ['server_id' => TestDatabaseSeeder::SERVER_1_ID, 'name' => 'x']);

        $this->assertSame(401, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
    }

    public function test_create_channel_rejects_get_method(): void
    {
        $response = $this->client->get('/api/create_channel.php');

        $this->assertSame(405, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
    }
}
