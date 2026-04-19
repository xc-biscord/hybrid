<?php

declare(strict_types=1);

namespace Tests\Contract;

final class GetServersContractTest extends ContractTestCase
{
    public function test_get_servers_success_shape(): void
    {
        $this->actingAsAlice();
        $response = $this->client->get('/api/get_servers.php');

        $this->assertSame(200, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'servers']);
        $this->assertTrue($response['json']['success']);
        $this->assertIsArray($response['json']['servers']);

        $first = $response['json']['servers'][0] ?? null;
        if (is_array($first)) {
            $this->assertArrayHasKey('id', $first);
            $this->assertArrayHasKey('name', $first);
        }
    }

    public function test_get_servers_requires_authentication(): void
    {
        $response = $this->client->get('/api/get_servers.php');

        $this->assertSame(401, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
    }
}
