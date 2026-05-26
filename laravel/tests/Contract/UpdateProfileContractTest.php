<?php

declare(strict_types=1);

namespace Tests\Contract;

/**
 * Contract tests for /api/update_profile.php
 *
 * HTTP contract:
 *   Method : toutes — aucune restriction de méthode
 *   Auth   : dépend de auth.php (session implicite) — PAS de guard explicite dans le fichier
 *   Input  : JSON body { bio?: string, avatar_url?: string, status?: string }
 *   Output :
 *     200 { success: true }    — toujours (aucun chemin d'erreur explicite dans le fichier)
 *
 * @legacy-invariant: Aucune restriction de méthode HTTP (GET fonctionne comme POST).
 * @legacy-invariant: Pas de validation côté serveur — tous les champs sont optionnels.
 * @legacy-invariant: `status` reçoit 'disponible' par défaut si non fourni.
 * @legacy-invariant: Aucun code HTTP non-200 n'est émis dans les cas heureux.
 * @legacy-invariant: L'endpoint utilise un INSERT … ON DUPLICATE KEY UPDATE, donc il crée
 *                    ou met à jour le profil de manière idempotente.
 */
final class UpdateProfileContractTest extends ContractTestCase
{
    public function test_update_profile_success_returns_200_success_true(): void
    {
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/update_profile.php', [
            'bio'        => 'Nouvelle bio',
            'avatar_url' => 'https://cdn.example.test/new.png',
            'status'     => 'hors-ligne',
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertSame(['success' => true], $response['json']);
    }

    public function test_update_profile_accepts_empty_payload_invariant(): void
    {
        // @legacy-invariant: payload vide → les champs reçoivent leurs valeurs par défaut
        // (bio='', avatar_url='', status='disponible') et la réponse est quand même 200/success
        $this->actingAsAlice();
        $response = $this->client->postJson('/api/update_profile.php', []);

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
    }

    public function test_update_profile_default_status_is_disponible_invariant(): void
    {
        // @legacy-invariant: si `status` n'est pas fourni, la valeur 'disponible' est utilisée
        // (défini dans le code source : $status = $data['status'] ?? 'disponible')
        $this->actingAsAlice();
        $this->client->postJson('/api/update_profile.php', [
            'bio' => 'Bio sans status',
        ]);

        // Vérifier via get_profile que le status est bien 'disponible'
        $profile = $this->client->get('/api/get_profile.php');
        $this->assertSame('disponible', $profile['json']['profile']['status'] ?? null);
    }

    public function test_update_profile_accepts_get_method_invariant(): void
    {
        // @legacy-invariant: aucune restriction de méthode — GET fonctionne
        $this->actingAsAlice();
        $response = $this->client->get('/api/update_profile.php');

        // Le GET sans payload retourne success: true (bio='', avatar_url='', status='disponible')
        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
    }
}
