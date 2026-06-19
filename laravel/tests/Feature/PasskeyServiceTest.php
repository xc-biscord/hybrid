<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Passkey\PasskeyService;
use App\Services\Passkey\PdoPasskeyRepository;
use CBOR\ByteStringObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use OpenSSLAsymmetricKey;
use ParagonIE\ConstantTime\Base64UrlSafe;
use PDO;
use RuntimeException;
use Tests\Contract\Support\TestDatabaseSeeder;
use Tests\TestCase;

/**
 * Test d'intégration du PasskeyService avec un AUTHENTIFICATEUR LOGICIEL ES256.
 *
 * On émule en PHP ce que ferait une vraie clé FIDO2 / un téléphone : génération
 * d'une paire de clés, construction des réponses WebAuthn (clientDataJSON,
 * attestationObject, signature), puis vérification par le service. Cela prouve,
 * de façon automatisée, que :
 *   - une passkey ES256 (privilégiée) s'enregistre et se vérifie de bout en bout ;
 *   - le fallback RS256 (compat. Windows Hello) fonctionne aussi de bout en bout ;
 *   - une signature falsifiée est REJETÉE (la vérification est réelle).
 *
 * NB : ce test couvre la cryptographie côté serveur. Le flux navigateur réel
 * (navigator.credentials) se teste manuellement (cf. docs/passkeys-webauthn.md).
 */
final class PasskeyServiceTest extends TestCase
{
    private const RP_ID = 'localhost';

    private const HOST = 'localhost';

    private const ORIGIN = 'https://localhost';

    private const USER_ID = TestDatabaseSeeder::USER_ALICE_ID;

    private PDO $pdo;

    private PasskeyService $service;

    private OpenSSLAsymmetricKey $authenticatorKey;

    private string $credentialId;

    protected function setUp(): void
    {
        parent::setUp();

        // Base de test propre + utilisateur alice présent (FK user_passkeys).
        TestDatabaseSeeder::resetAndSeed();

        $this->pdo = $this->connect();
        $this->pdo->exec('DELETE FROM user_passkeys WHERE user_id = '.self::USER_ID);

        $this->service = new PasskeyService(new PdoPasskeyRepository($this->pdo));

        $this->authenticatorKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        $this->credentialId = random_bytes(32);
    }

    public function test_register_then_login_with_real_es256_signature(): void
    {
        $row = $this->registerSoftwarePasskey('Clé de test');
        $this->assertSame('Clé de test', $row['name']);
        $this->assertSame(self::USER_ID, (int) $row['user_id']);

        $stored = (new PdoPasskeyRepository($this->pdo))->findByUserId(self::USER_ID);
        $options = $this->service->assertionOptions($stored);

        $assertion = $this->buildAssertion($options->challenge, signCount: 1);
        $result = $this->service->finishAssertion($options, $assertion, self::HOST);

        $this->assertSame(self::USER_ID, (int) $result['user_id']);

        // Le compteur anti-rejeu a été mis à jour (0 -> 1).
        $after = (new PdoPasskeyRepository($this->pdo))->findOwnedById((int) $row['id'], self::USER_ID);
        $this->assertSame(1, (int) $after['sign_count']);
        $this->assertNotNull($after['last_used_at']);
    }

    public function test_register_then_login_with_rs256_fallback(): void
    {
        // Émule un authentificateur qui n'expose que RS256 (cas Windows Hello).
        // Le fallback RS256 doit être accepté de bout en bout, sans rien casser.
        $this->authenticatorKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ]);

        $row = $this->registerSoftwarePasskey('Clé RS256');
        $this->assertSame(self::USER_ID, (int) $row['user_id']);

        $stored = (new PdoPasskeyRepository($this->pdo))->findByUserId(self::USER_ID);
        $options = $this->service->assertionOptions($stored);

        $assertion = $this->buildAssertion($options->challenge, signCount: 1);
        $result = $this->service->finishAssertion($options, $assertion, self::HOST);

        $this->assertSame(self::USER_ID, (int) $result['user_id']);
    }

    public function test_tampered_signature_is_rejected(): void
    {
        $this->registerSoftwarePasskey('Clé de test');

        $stored = (new PdoPasskeyRepository($this->pdo))->findByUserId(self::USER_ID);
        $options = $this->service->assertionOptions($stored);

        // Signature volontairement corrompue : la vérification DOIT échouer.
        $assertion = $this->buildAssertion($options->challenge, signCount: 1, tamperSignature: true);

        $this->expectException(\Throwable::class);
        $this->service->finishAssertion($options, $assertion, self::HOST);
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Enregistre une passkey via une réponse d'attestation "none" émulée.
     *
     * @return array<string,mixed>
     */
    private function registerSoftwarePasskey(string $name): array
    {
        $options = $this->service->registrationOptions(self::USER_ID, 'alice');

        $clientData = $this->clientDataJson('webauthn.create', $options->challenge);
        $attestedCredentialData = $this->attestedCredentialData();
        $authData = $this->authenticatorData(0x45 /* UP|UV|AT */, 0, $attestedCredentialData);

        $attestationObject = MapObject::create()
            ->add(TextStringObject::create('fmt'), TextStringObject::create('none'))
            ->add(TextStringObject::create('attStmt'), MapObject::create())
            ->add(TextStringObject::create('authData'), ByteStringObject::create($authData));

        $credential = json_encode([
            'id' => $this->b64u($this->credentialId),
            'rawId' => $this->b64u($this->credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => $this->b64u($clientData),
                'attestationObject' => $this->b64u((string) $attestationObject),
            ],
        ], JSON_THROW_ON_ERROR);

        return $this->service->finishRegistration($options, $credential, self::HOST, self::USER_ID, $name);
    }

    private function buildAssertion(string $challenge, int $signCount, bool $tamperSignature = false): string
    {
        $clientData = $this->clientDataJson('webauthn.get', $challenge);
        $authData = $this->authenticatorData(0x05 /* UP|UV */, $signCount);

        $signedData = $authData.hash('sha256', $clientData, true);
        if (! openssl_sign($signedData, $signature, $this->authenticatorKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('openssl_sign a échoué.');
        }
        if ($tamperSignature) {
            $signature[0] = $signature[0] === "\x00" ? "\x01" : "\x00";
        }

        return json_encode([
            'id' => $this->b64u($this->credentialId),
            'rawId' => $this->b64u($this->credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => $this->b64u($clientData),
                'authenticatorData' => $this->b64u($authData),
                'signature' => $this->b64u($signature),
                'userHandle' => $this->b64u((string) self::USER_ID),
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function attestedCredentialData(): string
    {
        $aaguid = str_repeat("\0", 16);

        return $aaguid.pack('n', strlen($this->credentialId)).$this->credentialId.$this->coseKey();
    }

    /**
     * Construit la clé publique COSE selon le type de l'authentificateur émulé :
     * ES256 (EC2) par défaut, RS256 (RSA) pour le test du fallback.
     */
    private function coseKey(): string
    {
        $details = openssl_pkey_get_details($this->authenticatorKey);

        if (isset($details['rsa'])) {
            // COSE RSA : kty=3, alg=-257 (RS256), n(-1)=modulus, e(-2)=exposant.
            $cose = MapObject::create()
                ->add(UnsignedIntegerObject::create(1), UnsignedIntegerObject::create(3))
                ->add(UnsignedIntegerObject::create(3), NegativeIntegerObject::create(-257))
                ->add(NegativeIntegerObject::create(-1), ByteStringObject::create($details['rsa']['n']))
                ->add(NegativeIntegerObject::create(-2), ByteStringObject::create($details['rsa']['e']));

            return (string) $cose;
        }

        // COSE EC2 : kty=2, alg=-7 (ES256), crv=P-256, x, y.
        $x = str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT);
        $y = str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);

        $cose = MapObject::create()
            ->add(UnsignedIntegerObject::create(1), UnsignedIntegerObject::create(2))
            ->add(UnsignedIntegerObject::create(3), NegativeIntegerObject::create(-7))
            ->add(NegativeIntegerObject::create(-1), UnsignedIntegerObject::create(1))
            ->add(NegativeIntegerObject::create(-2), ByteStringObject::create($x))
            ->add(NegativeIntegerObject::create(-3), ByteStringObject::create($y));

        return (string) $cose;
    }

    private function authenticatorData(int $flags, int $signCount, string $attested = ''): string
    {
        return hash('sha256', self::RP_ID, true).chr($flags).pack('N', $signCount).$attested;
    }

    private function clientDataJson(string $type, string $challenge): string
    {
        return json_encode([
            'type' => $type,
            'challenge' => $this->b64u($challenge),
            'origin' => self::ORIGIN,
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function b64u(string $bytes): string
    {
        return Base64UrlSafe::encodeUnpadded($bytes);
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
