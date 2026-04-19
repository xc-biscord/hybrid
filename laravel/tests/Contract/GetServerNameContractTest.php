<?php

declare(strict_types=1);

namespace Tests\Contract;

use Tests\Contract\Support\TestDatabaseSeeder;

final class GetServerNameContractTest extends ContractTestCase
{
    public function test_get_server_name_success_shape(): void
    {
        $this->actingAsAlice();
        $response = $this->client->get('/api/get_server_name.php?id=' . TestDatabaseSeeder::SERVER_1_ID);

        $this->assertSame(200, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'name']);
        $this->assertTrue($response['json']['success']);
    }

    public function test_get_server_name_keeps_non_member_access_invariant(): void
    {
        $this->actingAsAdmin();
        $response = $this->client->get('/api/get_server_name.php?id=' . TestDatabaseSeeder::SERVER_1_ID);

        // Invariant D3: pas de vérification de membership.
        $this->assertSame(200, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'name']);
        $this->assertTrue($response['json']['success']);
    }

    public function test_get_server_name_returns_404_for_unknown_id(): void
    {
        $this->actingAsAlice();
        $response = $this->client->get('/api/get_server_name.php?id=999999');

        $this->assertSame(404, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
    }
}
