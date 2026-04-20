<?php

declare(strict_types=1);

namespace Tests\Contract\Support;

final class SessionHelper
{
    /**
     * @return array{account:string,login_status:int,has_php_sessid:bool,php_sessid:?string,base_url:string,host:string,login_path:string}
     */
    public static function actingAs(BiscordHttpClient $client, string $accountKey): array
    {
        $account = TestAccounts::get($accountKey);

        // Ensure each authentication flow starts from a clean, isolated session.
        $client->clearCookies();

        $response = $client->postJson('/api/login.php', [
            'username' => $account['username'],
            'password' => $account['password'],
        ]);

        return [
            'account' => $accountKey,
            'login_status' => $response['status'],
            'has_php_sessid' => $client->hasCookie('PHPSESSID'),
            'php_sessid' => $client->getCookie('PHPSESSID'),
            'base_url' => $client->getBaseUrl(),
            'host' => $client->getHost(),
            'login_path' => '/api/login.php',
        ];
    }
}
