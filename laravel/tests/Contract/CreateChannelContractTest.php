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

    public function test_create_channel_post_non_auth_returns_legacy_401_contract(): void
    {
        $response = $this->client->postJson('/api/create_channel.php', ['server_id' => TestDatabaseSeeder::SERVER_1_ID, 'name' => 'x']);

        $this->assertSame(401, $response['status']);
        $this->assertSame([
            'success' => false,
            'error' => 'Non authentifié',
        ], $response['json']);
    }

    public function test_create_channel_get_non_auth_returns_legacy_405_contract(): void
    {
        $response = $this->client->get('/api/create_channel.php');

        $this->assertSame(405, $response['status']);
        $this->assertSame([
            'success' => false,
            'error' => 'Méthode non autorisée',
        ], $response['json']);
    }

    public function test_create_channel_rejects_invalid_json_with_legacy_contract(): void
    {
        $this->actingAsAlice();

        $response = $this->client->request(
            'POST',
            '/api/create_channel.php',
            [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            '{"server_id":',
        );

        $this->assertSame(400, $response['status']);
        $this->assertSame([
            'success' => false,
            'error' => 'JSON invalide',
        ], $response['json']);
    }
}
