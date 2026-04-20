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
        $this->assertSame([
            'success' => false,
            'error' => 'Méthode non autorisée',
        ], $response['json']);
    }

    public function test_create_server_rejects_invalid_json_with_legacy_contract(): void
    {
        $this->actingAsAlice();

        $response = $this->client->request(
            'POST',
            '/api/create_server.php',
            [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            '{"nom":',
        );

        $this->assertSame(400, $response['status']);
        $this->assertSame([
            'success' => false,
            'error' => 'JSON invalide',
        ], $response['json']);
    }
}
