<?php

declare(strict_types=1);

namespace Tests\Contract;

use Tests\Contract\Support\TestDatabaseSeeder;

final class GetMessagesContractTest extends ContractTestCase
{
    public function test_get_messages_success_shape_and_keys(): void
    {
        $this->actingAsAlice();
        $response = $this->client->get('/api/get_messages.php?channel_id=' . TestDatabaseSeeder::CHANNEL_1_ID);

        $this->assertSame(200, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'messages']);
        $this->assertTrue($response['json']['success']);
        $this->assertIsArray($response['json']['messages']);

        $first = $response['json']['messages'][0] ?? null;
        $this->assertIsArray($first);
        foreach (['id', 'content', 'created_at', 'username', 'user_id', 'avatar_url'] as $key) {
            $this->assertArrayHasKey($key, $first);
        }
    }

    public function test_get_messages_non_member_is_forbidden(): void
    {
        $this->actingAsAdmin();
        $response = $this->client->get('/api/get_messages.php?channel_id=' . TestDatabaseSeeder::CHANNEL_1_ID);

        $this->assertSame(403, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
    }

    public function test_get_messages_invalid_channel_id_returns_400(): void
    {
        $this->actingAsAlice();
        $response = $this->client->get('/api/get_messages.php?channel_id=0');

        $this->assertSame(400, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
    }
}
