<?php

declare(strict_types=1);

namespace Tests\Contract;

use Tests\Contract\Support\TestDatabaseSeeder;

final class SendMessageContractTest extends ContractTestCase
{
    public function test_send_message_success_shape(): void
    {
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/send_message.php', [
            'channel_id' => TestDatabaseSeeder::CHANNEL_1_ID,
            'content' => 'Contract message',
        ]);

        $this->assertSame(201, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'message_id']);
        $this->assertTrue($response['json']['success']);
    }

    public function test_send_message_requires_post_method(): void
    {
        $response = $this->client->get('/api/send_message.php');

        $this->assertSame(405, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
    }

    public function test_send_message_preserves_non_member_gap_invariant_d2(): void
    {
        $this->actingAsAdmin();
        $response = $this->client->postJson('/api/send_message.php', [
            'channel_id' => TestDatabaseSeeder::CHANNEL_1_ID,
            'content' => 'Posted by non-member',
        ]);

        // Invariant D2: pas de vérification d'appartenance serveur.
        $this->assertSame(201, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'message_id']);
        $this->assertTrue($response['json']['success']);
    }

    public function test_send_message_invalid_payload_returns_400(): void
    {
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/send_message.php', [
            'channel_id' => TestDatabaseSeeder::CHANNEL_1_ID,
            'content' => '',
        ]);

        $this->assertSame(400, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
    }

    public function test_send_message_unknown_channel_returns_404(): void
    {
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/send_message.php', [
            'channel_id' => 999999,
            'content' => 'test',
        ]);

        $this->assertSame(404, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
    }
}
