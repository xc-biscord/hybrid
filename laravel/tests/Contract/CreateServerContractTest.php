<?php

declare(strict_types=1);

namespace Tests\Contract;

final class CreateServerContractTest extends ContractTestCase
{
    public function test_create_server_accepts_nom_field_invariant(): void
    {
        $this->actingAsAlice();

        $response = $this->client->postJson('/api/create_server.php', ['nom' => 'Serveur Nom']);

        $this->assertSame(201, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'server_id']);
        $this->assertTrue($response['json']['success']);
    }

    public function test_create_server_accepts_name_field_invariant(): void
    {
        $this->actingAsAlice();

        $response = $this->client->postJson('/api/create_server.php', ['name' => 'Server Name']);

        $this->assertSame(201, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'server_id']);
        $this->assertTrue($response['json']['success']);
    }

    public function test_create_server_rejects_get_method(): void
    {
        $response = $this->client->get('/api/create_server.php');

        $this->assertSame(405, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
    }
}
