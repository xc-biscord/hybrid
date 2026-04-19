<?php

declare(strict_types=1);

namespace Tests\Contract;

use PHPUnit\Framework\TestCase;
use Tests\Contract\Support\BiscordHttpClient;
use Tests\Contract\Support\SessionHelper;
use Tests\Contract\Support\TestDatabaseSeeder;

abstract class ContractTestCase extends TestCase
{
    protected BiscordHttpClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        TestDatabaseSeeder::resetAndSeed();
        $this->client = new BiscordHttpClient();
    }

    protected function actingAsAlice(): void
    {
        SessionHelper::actingAs($this->client, TestDatabaseSeeder::USER_ALICE_ID);
    }

    protected function actingAsBob(): void
    {
        SessionHelper::actingAs($this->client, TestDatabaseSeeder::USER_BOB_ID);
    }

    protected function actingAsAdmin(): void
    {
        SessionHelper::actingAs($this->client, TestDatabaseSeeder::USER_ADMIN_ID);
    }

    /**
     * @param array<string,mixed>|null $json
     */
    protected function assertHasKeys(?array $json, array $keys): void
    {
        $this->assertIsArray($json);

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $json);
        }
    }
}
