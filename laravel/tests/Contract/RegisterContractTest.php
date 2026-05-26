<?php

declare(strict_types=1);

namespace Tests\Contract;

/**
 * Contract tests for POST /api/register.php
 *
 * HTTP contract:
 *   Method : POST uniquement (405 sinon — via requireMethod)
 *   Input  : JSON body { username: string, email: string, password: string }
 *   Output :
 *     201 { success: true }                               — inscription réussie
 *     400 { success: false, error: 'Champs requis manquants' }   — champs vides
 *     400 { success: false, error: 'Email invalide' }            — format e-mail
 *     405 { success: false, error: 'Méthode non autorisée' }     — mauvaise méthode
 *     409 { success: false, error: string }                      — doublon username/email
 */
final class RegisterContractTest extends ContractTestCase
{
    // ------------------------------------------------------------------ succès

    public function test_register_success_returns_201(): void
    {
        $response = $this->client->postJson('/api/register.php', [
            'username' => 'nouveau_user',
            'email'    => 'nouveau@example.test',
            'password' => 'MotDePasse1!',
        ]);

        $this->assertSame(201, $response['status']);
        $this->assertTrue($response['json']['success']);
    }

    // ------------------------------------------------------------------ méthode

    public function test_register_rejects_get_method_with_405(): void
    {
        $response = $this->client->get('/api/register.php');

        $this->assertSame(405, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Méthode non autorisée', $response['json']['error']);
    }

    // ------------------------------------------------------------------ inputs invalides

    public function test_register_missing_username_returns_400(): void
    {
        $response = $this->client->postJson('/api/register.php', [
            'username' => '',
            'email'    => 'test@example.test',
            'password' => 'MotDePasse1!',
        ]);

        $this->assertSame(400, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Champs requis manquants', $response['json']['error']);
    }

    public function test_register_missing_email_returns_400(): void
    {
        $response = $this->client->postJson('/api/register.php', [
            'username' => 'testuser',
            'email'    => '',
            'password' => 'MotDePasse1!',
        ]);

        $this->assertSame(400, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Champs requis manquants', $response['json']['error']);
    }

    public function test_register_invalid_email_format_returns_400(): void
    {
        $response = $this->client->postJson('/api/register.php', [
            'username' => 'testuser',
            'email'    => 'pas-un-email',
            'password' => 'MotDePasse1!',
        ]);

        $this->assertSame(400, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertSame('Email invalide', $response['json']['error']);
    }

    public function test_register_duplicate_username_returns_409(): void
    {
        // alice est déjà créée par TestDatabaseSeeder
        $response = $this->client->postJson('/api/register.php', [
            'username' => 'alice',
            'email'    => 'autre_alice@example.test',
            'password' => 'MotDePasse1!',
        ]);

        $this->assertSame(409, $response['status']);
        $this->assertFalse($response['json']['success']);
        $this->assertArrayHasKey('error', $response['json']);
    }

    public function test_register_duplicate_email_returns_409(): void
    {
        // L'email d'alice est déjà utilisé
        $response = $this->client->postJson('/api/register.php', [
            'username' => 'alice_bis',
            'email'    => 'alice@example.test',
            'password' => 'MotDePasse1!',
        ]);

        $this->assertSame(409, $response['status']);
        $this->assertFalse($response['json']['success']);
    }
}
