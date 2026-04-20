<?php

declare(strict_types=1);

namespace Tests\Contract;

use PHPUnit\Framework\TestCase;
use Tests\Contract\Support\BiscordHttpClient;
use Tests\Contract\Support\SessionHelper;
use Tests\Contract\Support\TestAccounts;
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
        SessionHelper::actingAs($this->client, TestAccounts::id('alice'));
    }

    protected function actingAsBob(): void
    {
        SessionHelper::actingAs($this->client, TestAccounts::id('bob'));
    }

    protected function actingAsAdmin(): void
    {
        SessionHelper::actingAs($this->client, TestAccounts::id('admin'));
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
