<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Passkey\PasskeyService;
use App\Services\Passkey\PdoPasskeyRepository;
use Illuminate\Console\Command;
use PDO;

/**
 * Outil de debug : affiche le JSON `publicKey` des options de création WebAuthn
 * tel qu'il est envoyé au navigateur (avant conversion JS en ArrayBuffer).
 *
 * Usage : php artisan passkey:debug-options [user_id] [--username=alice]
 *
 * Sert à inspecter rapidement pubKeyCredParams, authenticatorSelection, etc.
 * lors d'un diagnostic de compatibilité (Edge/Windows Hello, Safari…).
 */
final class PasskeyDebugOptionsCommand extends Command
{
    protected $signature = 'passkey:debug-options {user_id=1 : Id utilisateur} {--username=debug-user : Nom affiché}';

    protected $description = 'Affiche le JSON publicKey des options de création WebAuthn (debug compatibilité).';

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');
        $username = (string) $this->option('username');

        $service = new PasskeyService(new PdoPasskeyRepository($this->connect()));
        $options = $service->registrationOptions($userId, $username);
        $json = $service->serializeOptions($options);

        $this->line(json_encode(json_decode($json), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $algs = array_map(static fn ($p) => $p->alg, $options->pubKeyCredParams);
        $origins = (array) config('passkey.allowed_origins', []);
        $originsLabel = $origins === [] ? '(aucune)' : implode(',', $origins);

        $this->newLine();
        $this->info('pubKeyCredParams (ordre de préférence) : '.implode(', ', $algs).'  [ES256=-7, RS256=-257]');
        $this->info('rpId : '.config('passkey.rp_id').' | allowed_origins : '.$originsLabel);

        return self::SUCCESS;
    }

    private function connect(): PDO
    {
        $host = getenv('CONTRACT_TEST_DB_HOST') ?: 'localhost';
        $db = getenv('CONTRACT_TEST_DB_DATABASE') ?: 'biscord_db_tests';
        $user = getenv('CONTRACT_TEST_DB_USERNAME') ?: 'biscord_test_app';
        $pass = getenv('CONTRACT_TEST_DB_PASSWORD') ?: 'cc30ae7b760219d01afd74f3a826fe8363cbf86d914f1d07';

        return new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $db),
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
    }
}
