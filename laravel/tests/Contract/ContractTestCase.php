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

    /**
     * @var array{account:string,login_status:int,has_php_sessid:bool,php_sessid:?string,base_url:string,host:string,login_path:string}|null
     */
    protected ?array $lastAuthAttempt = null;

    protected function setUp(): void
    {
        parent::setUp();

        TestDatabaseSeeder::resetAndSeed();
        $this->client = new BiscordHttpClient();
        $this->lastAuthAttempt = null;
    }

    protected function actingAsAlice(): void
    {
        $this->authenticateAs('alice');
    }

    protected function actingAsBob(): void
    {
        $this->authenticateAs('bob');
    }

    protected function actingAsAdmin(): void
    {
        $this->authenticateAs('admin');
    }

    /**
     * @return array{account:string,login_status:int,has_php_sessid:bool,php_sessid:?string,base_url:string,host:string,login_path:string}|null
     */
    protected function lastAuthAttempt(): ?array
    {
        return $this->lastAuthAttempt;
    }

    protected function authenticateAs(string $accountKey): void
    {
        $this->lastAuthAttempt = SessionHelper::actingAs($this->client, $accountKey);

        $this->assertSame(
            200,
            $this->lastAuthAttempt['login_status'],
            sprintf(
                'Legacy login failed for "%s". status=%d base_url=%s host=%s',
                $accountKey,
                $this->lastAuthAttempt['login_status'],
                $this->lastAuthAttempt['base_url'],
                $this->lastAuthAttempt['host'],
            ),
        );

        $this->assertTrue(
            $this->lastAuthAttempt['has_php_sessid'],
            sprintf(
                'Legacy login did not return PHPSESSID for "%s". status=%d base_url=%s host=%s',
                $accountKey,
                $this->lastAuthAttempt['login_status'],
                $this->lastAuthAttempt['base_url'],
                $this->lastAuthAttempt['host'],
            ),
        );

        $this->assertNotSame('', trim($this->lastAuthAttempt['host']));
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
