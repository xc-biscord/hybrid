<?php

declare(strict_types=1);

namespace Tests\Contract;

/**
 * Contract tests for POST /api/login.php
 *
 * HTTP contract:
 *   Method : POST uniquement (405 sinon)
 *   Input  : JSON body { username: string, password: string }
 *            `username` accepte un nom d'utilisateur OU une adresse e-mail
 *   Output :
 *     200 { success: true,  user_id: int }           — authentification réussie
 *     400 { success: false, error: string }           — champs vides
 *     401 { success: false, error: string }           — credentials invalides
 *     405 { success: false, error: string }           — mauvaise méthode HTTP
 */
final class LoginContractTest extends ContractTestCase
{
    // ------------------------------------------------------------------ succès

    public function test_login_success_with_username_returns_200_and_user_id(): void
    {
        $response = $this->client->postJson('/api/login.php', [
            'username' => 'alice',
            'password' => 'password_alice',
        ]);

        // @legacy-invariant: login réussi retourne HTTP 200, pas 201
        $this->assertSame(200, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'user_id']);
        $this->assertTrue($response['json']['success']);
        $this->assertIsInt($response['json']['user_id']);
    }

    public function test_login_accepts_email_in_username_field_invariant(): void
    {
        // @legacy-invariant: le champ `username` accepte aussi une adresse e-mail
        // (la requête SQL fait : WHERE username = :username OR email = :username)
        $response = $this->client->postJson('/api/login.php', [
            'username' => 'alice@example.test',
            'password' => 'password_alice',
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
        $this->assertArrayHasKey('user_id', $response['json']);
    }

    // ------------------------------------------------------------------ méthode

    public function test_login_rejects_get_method_with_405(): void
    {
        $response = $this->client->get('/api/login.php');

        $this->assertSame(405, $response['status']);
        $this->assertHasKeys($response['json'], ['success', 'error']);
        $this->assertFalse($response['json']['success']);
    }

    // ------------------------------------------------------------------ inputs invalides

    public function test_login_missing_username_returns_400(): void
    {
        $response = $this->client->postJson('/api/login.php', [
            'username' => '',
            'password' => 'password_alice',
        ]);

        $this->assertSame(400, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Identifiants manquants', $response['json']['error']);
    }

    public function test_login_missing_password_returns_400(): void
    {
        $response = $this->client->postJson('/api/login.php', [
            'username' => 'alice',
            'password' => '',
        ]);

        $this->assertSame(400, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Identifiants manquants', $response['json']['error']);
    }

    public function test_login_wrong_password_returns_401(): void
    {
        $response = $this->client->postJson('/api/login.php', [
            'username' => 'alice',
            'password' => 'mauvais_mot_de_passe',
        ]);

        $this->assertSame(401, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Identifiants invalides', $response['json']['error']);
    }

    public function test_login_unknown_user_returns_401(): void
    {
        $response = $this->client->postJson('/api/login.php', [
            'username' => 'utilisateur_inexistant',
            'password' => 'anything',
        ]);

        $this->assertSame(401, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Identifiants invalides', $response['json']['error']);
    }
}
