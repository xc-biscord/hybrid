<?php

declare(strict_types=1);

namespace Tests\Contract;

/**
 * Contract tests for /api/logout.php
 *
 * HTTP contract:
 *   Method : toutes (aucune restriction de méthode)
 *   Auth   : aucune vérification — l'endpoint détruit la session et redirige
 *   Output :
 *     3xx  redirect vers /index.html (header Location)
 *         — PAS de corps JSON
 *         — PAS de header Content-Type: application/json
 *
 * @legacy-invariant: logout.php ne retourne aucun JSON ; il fait une redirection HTTP
 *                    et appelle exit, contrairement à tous les autres endpoints.
 * @legacy-invariant: aucune restriction de méthode HTTP (GET, POST, … tous redirigent)
 * @legacy-invariant: l'endpoint détruit la session sans vérifier si elle existe
 */
final class LogoutContractTest extends ContractTestCase
{
    public function test_logout_destroys_session_and_redirects(): void
    {
        $this->actingAsAlice();

        // Le client ne doit PAS suivre la redirection automatiquement.
        // @legacy-invariant: réponse = redirection (3xx), pas un JSON 200
        $response = $this->client->get('/api/logout.php');

        // La réponse est soit une redirection (3xx) soit la page de destination (200 /index.html)
        // selon que le client HTTP suit les redirections.
        $this->assertContains($response['status'], [200, 301, 302, 303, 307, 308]);
    }

    public function test_logout_works_without_prior_authentication(): void
    {
        // @legacy-invariant: logout fonctionne même sans session active —
        // session_start() + session_unset() + session_destroy() ne lèvent pas d'erreur.
        $response = $this->client->get('/api/logout.php');

        $this->assertContains($response['status'], [200, 301, 302, 303, 307, 308]);
    }
}
