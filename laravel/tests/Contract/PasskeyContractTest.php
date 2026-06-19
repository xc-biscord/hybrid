<?php

declare(strict_types=1);

namespace Tests\Contract;

/**
 * Contract tests pour les endpoints Passkeys / WebAuthn (PoC).
 *
 * Couvre la surface HTTP NON cryptographique :
 *   - /api/login_methods.php  : étape 1, anti-énumération
 *   - /api/passkey_list.php    : liste (auth requise)
 *   - /api/passkey_delete.php  : suppression (auth + droits)
 *
 * La vérification cryptographique (create/get signés) est couverte par le test
 * d'intégration Tests\Feature\PasskeyServiceTest (authentificateur logiciel
 * ES256) et par les tests manuels documentés dans docs/passkeys-webauthn.md.
 *
 * Le seed fournit une passkey factice pour alice (id 3001).
 */
final class PasskeyContractTest extends ContractTestCase
{
    // ----------------------------------------------------------- login_methods

    public function test_login_methods_unknown_user_does_not_leak_existence(): void
    {
        // Anti-énumération : password toujours true, passkey false pour un inconnu.
        $response = $this->client->postJson('/api/login_methods.php', [
            'identifier' => 'utilisateur_inexistant',
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
        $this->assertSame(
            ['password' => true, 'passkey' => false],
            $response['json']['methods'],
        );
    }

    public function test_login_methods_user_with_passkey_reports_passkey_true(): void
    {
        $response = $this->client->postJson('/api/login_methods.php', [
            'identifier' => 'alice',
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['methods']['password']);
        $this->assertTrue($response['json']['methods']['passkey']);
    }

    public function test_login_methods_user_without_passkey_reports_passkey_false(): void
    {
        $response = $this->client->postJson('/api/login_methods.php', [
            'identifier' => 'bob',
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['methods']['password']);
        $this->assertFalse($response['json']['methods']['passkey']);
    }

    public function test_login_methods_accepts_email_identifier(): void
    {
        $response = $this->client->postJson('/api/login_methods.php', [
            'identifier' => 'alice@example.test',
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['methods']['passkey']);
    }

    public function test_login_methods_missing_identifier_returns_400(): void
    {
        $response = $this->client->postJson('/api/login_methods.php', ['identifier' => '']);

        $this->assertSame(400, $response['status']);
        $this->assertFalse($response['json']['success']);
    }

    public function test_login_methods_rejects_get_with_405(): void
    {
        $response = $this->client->get('/api/login_methods.php');

        $this->assertSame(405, $response['status']);
    }

    // -------------------------------------------------------------- passkey_list

    public function test_passkey_list_requires_authentication(): void
    {
        $response = $this->client->get('/api/passkey_list.php');

        $this->assertSame(401, $response['status']);
        $this->assertFalse($response['json']['success']);
    }

    public function test_passkey_list_returns_user_passkeys(): void
    {
        $this->actingAsAlice();
        $response = $this->client->get('/api/passkey_list.php');

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
        $this->assertCount(1, $response['json']['passkeys']);
        $this->assertSame('Clé de test Alice', $response['json']['passkeys'][0]['name']);
        $this->assertArrayHasKey('created_at', $response['json']['passkeys'][0]);
        $this->assertArrayHasKey('last_used_at', $response['json']['passkeys'][0]);
    }

    // ------------------------------------------------------------ passkey_delete

    public function test_passkey_delete_requires_authentication(): void
    {
        $response = $this->client->postJson('/api/passkey_delete.php', ['id' => 3001]);

        $this->assertSame(401, $response['status']);
        $this->assertFalse($response['json']['success']);
    }

    public function test_passkey_delete_missing_id_returns_400(): void
    {
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/passkey_delete.php', []);

        $this->assertSame(400, $response['status']);
        $this->assertFalse($response['json']['success']);
    }

    public function test_passkey_delete_rejects_foreign_passkey(): void
    {
        // Bob tente de supprimer la passkey d'alice (id 3001) -> 404 (contrôle de droits).
        $this->actingAsBob();
        $response = $this->client->postJson('/api/passkey_delete.php', ['id' => 3001]);

        $this->assertSame(404, $response['status']);
        $this->assertFalse($response['json']['success']);
    }

    public function test_passkey_delete_owner_succeeds_when_password_fallback_exists(): void
    {
        // Alice a un mot de passe : supprimer sa passkey laisse une méthode valide.
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/passkey_delete.php', ['id' => 3001]);

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);

        // La liste est désormais vide.
        $list = $this->client->get('/api/passkey_list.php');
        $this->assertCount(0, $list['json']['passkeys']);
    }
}
