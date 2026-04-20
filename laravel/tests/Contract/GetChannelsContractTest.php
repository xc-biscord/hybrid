<?php

declare(strict_types=1);

namespace Tests\Contract;

use Tests\Contract\Support\TestDatabaseSeeder;

final class GetChannelsContractTest extends ContractTestCase
{
    public function test_get_channels_success_shape(): void
    {
        $this->actingAsAlice();
        $response = $this->client->get('/api/get_channels.php?server_id=' . TestDatabaseSeeder::SERVER_1_ID);

        $this->assertSame(200, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'channels']);
        $this->assertTrue($response['json']['success']);
        $this->assertIsArray($response['json']['channels']);

        $first = $response['json']['channels'][0] ?? null;
        $this->assertIsArray($first);
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('name', $first);
    }

    public function test_get_channels_requires_auth(): void
    {
        $response = $this->client->get('/api/get_channels.php?server_id=' . TestDatabaseSeeder::SERVER_1_ID);

        $this->assertSame(401, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
    }

    public function test_get_channels_rejects_invalid_server_id(): void
    {
        $this->actingAsAlice();
        $response = $this->client->get('/api/get_channels.php?server_id=0');

        $this->assertSame(400, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
    }
}
