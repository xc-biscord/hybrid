<?php

declare(strict_types=1);

namespace App\Services\Passkey;

use CBOR\Decoder;
use CBOR\Normalizable;
use Cose\Algorithm\Manager as CoseAlgorithmManager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\RSA\RS256;
use Cose\Algorithms;
use Cose\Key\Key as CoseKey;
use ParagonIE\ConstantTime\Base64UrlSafe;
use RuntimeException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\CredentialRecord;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\StringStream;
use Webauthn\TrustPath\EmptyTrustPath;

/**
 * Couche d'isolation WebAuthn.
 *
 * Tout l'usage de web-auth/webauthn-lib est concentré ici afin de ne pas
 * disperser la logique cryptographique dans les contrôleurs ou le reste de
 * l'application. Les contrôleurs ne manipulent que des tableaux JSON et la
 * session ; ils délèguent à ce service toute la génération de challenge et la
 * vérification des réponses de l'authentificateur.
 *
 * Choix d'algorithme :
 *  - ES256 (ECDSA P-256 + SHA-256) est l'algorithme PRIVILÉGIÉ (proposé en 1er).
 *  - RS256 (RSASSA-PKCS1-v1_5 + SHA-256) est ajouté UNIQUEMENT comme fallback de
 *    compatibilité : certains authentificateurs (notamment Windows Hello / Edge)
 *    refusent une liste ES256 seule. Ce fallback est EXPLICITE et documenté.
 *
 * Aucun fallback silencieux : seuls les algorithmes explicitement déclarés
 * ci-dessous ({@see ALLOWED_ALGS}) sont acceptés à la vérification. Tout autre
 * algorithme est rejeté (voir {@see algorithmManager()} et {@see assertAllowedAlgorithm()}).
 */
final class PasskeyService
{
    /**
     * COSE alg ES256 = -7 (ECDSA w/ SHA-256) — algorithme privilégié.
     */
    public const ALG_ES256 = Algorithms::COSE_ALGORITHM_ES256;

    /**
     * COSE alg RS256 = -257 (RSASSA-PKCS1-v1_5 w/ SHA-256) — fallback de
     * compatibilité authentificateur (Windows Hello, etc.).
     */
    public const ALG_RS256 = Algorithms::COSE_ALGORITHM_RS256;

    /**
     * Liste EXHAUSTIVE des algorithmes acceptés, dans l'ordre de préférence
     * (ES256 d'abord). Rien d'autre n'est toléré.
     */
    private const ALLOWED_ALGS = [self::ALG_ES256, self::ALG_RS256];

    private SerializerInterface $serializer;

    private AttestationStatementSupportManager $attestationSupport;

    public function __construct(private PdoPasskeyRepository $repository)
    {
        // Format d'attestation "none" : suffisant pour un PoC (on n'audite pas le
        // modèle matériel de l'authentificateur). C'est aussi le format conseillé
        // pour préserver la vie privée de l'utilisateur.
        $this->attestationSupport = AttestationStatementSupportManager::create([
            new NoneAttestationStatementSupport,
        ]);

        $this->serializer = (new WebauthnSerializerFactory($this->attestationSupport))->create();
    }

    // =====================================================================
    //  Configuration du Relying Party (le site)
    // =====================================================================

    private function rpEntity(): PublicKeyCredentialRpEntity
    {
        return PublicKeyCredentialRpEntity::create(
            (string) config('passkey.rp_name'),
            (string) config('passkey.rp_id'),
        );
    }

    /**
     * Gestionnaire d'algorithmes restreint à ES256 (privilégié) + RS256 (fallback).
     *
     * On déclare explicitement ces deux algorithmes, et UNIQUEMENT eux :
     *  - à l'enregistrement, l'étape CheckAlgorithm rejette toute clé dont l'algo
     *    n'est pas dans {@see ALLOWED_ALGS} ;
     *  - à la connexion, CheckSignature ne sait vérifier que ES256/RS256 : une
     *    assertion signée autrement échoue (pas de repli muet vers « n'importe quoi »).
     */
    private function algorithmManager(): CoseAlgorithmManager
    {
        return CoseAlgorithmManager::create()->add(ES256::create(), RS256::create());
    }

    private function ceremonyFactory(): CeremonyStepManagerFactory
    {
        $factory = new CeremonyStepManagerFactory;
        $factory->setAlgorithmManager($this->algorithmManager());
        $factory->setAttestationStatementSupportManager($this->attestationSupport);

        $origins = (array) config('passkey.allowed_origins', []);
        if ($origins !== []) {
            $factory->setAllowedOrigins($origins);
        }

        return $factory;
    }

    // =====================================================================
    //  Enregistrement d'une passkey (navigator.credentials.create)
    // =====================================================================

    /**
     * Construit les options de création (dont le challenge) pour le navigateur.
     */
    public function registrationOptions(int $userId, string $username): PublicKeyCredentialCreationOptions
    {
        $userEntity = PublicKeyCredentialUserEntity::create(
            $username,
            $this->userHandle($userId),
            $username,
        );

        // On exclut les credentials déjà enregistrés pour empêcher un double
        // enregistrement du même authentificateur.
        $excludeCredentials = array_map(
            fn (array $row): PublicKeyCredentialDescriptor => PublicKeyCredentialDescriptor::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                Base64UrlSafe::decode((string) $row['credential_id']),
            ),
            $this->repository->findByUserId($userId),
        );

        return PublicKeyCredentialCreationOptions::create(
            $this->rpEntity(),
            $userEntity,
            random_bytes(32),
            // ES256 en PREMIER (privilégié), puis RS256 en fallback de
            // compatibilité (Windows Hello/Edge refusent une liste ES256 seule).
            // Liste explicite : le navigateur choisit le 1er algo qu'il supporte.
            [
                PublicKeyCredentialParameters::createPk(self::ALG_ES256),
                PublicKeyCredentialParameters::createPk(self::ALG_RS256),
            ],
            // residentKey explicitement "discouraged" (valeur valide de l'enum ;
            // on n'a pas besoin de credential découvrable, on passe par
            // allowCredentials). Évite le residentKey:null ignoré par le navigateur.
            AuthenticatorSelectionCriteria::create(
                null,
                AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
                AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_DISCOURAGED,
            ),
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            $excludeCredentials,
        );
    }

    /**
     * Vérifie la réponse d'attestation et persiste la passkey.
     *
     * @return array<string,mixed> la ligne user_passkeys créée (id, name, ...)
     */
    public function finishRegistration(
        PublicKeyCredentialCreationOptions $options,
        string $clientJson,
        string $host,
        int $userId,
        string $name,
    ): array {
        $response = $this->loadResponse($clientJson, AuthenticatorAttestationResponse::class);

        $validator = AuthenticatorAttestationResponseValidator::create(
            $this->ceremonyFactory()->creationCeremony(),
        );

        // En cas de réponse invalide (mauvais challenge, mauvaise origine,
        // algorithme non ES256...), check() lève une exception : pas de stockage.
        $record = $validator->check($response, $options, $host);

        $this->assertAllowedAlgorithm($record);

        $id = $this->repository->create([
            'user_id' => $userId,
            'credential_id' => Base64UrlSafe::encodeUnpadded($record->publicKeyCredentialId),
            'public_key' => base64_encode($record->credentialPublicKey),
            'sign_count' => $record->counter,
            'name' => $name,
            'user_handle' => $record->userHandle,
            'transports' => $record->transports === [] ? null : json_encode($record->transports),
            'aaguid' => $record->aaguid->__toString(),
        ]);

        return $this->repository->findOwnedById($id, $userId) ?? [];
    }

    // =====================================================================
    //  Connexion par passkey (navigator.credentials.get)
    // =====================================================================

    /**
     * Construit les options d'assertion (challenge + liste des credentials
     * autorisés). Pour un utilisateur inconnu, on renvoie une liste vide : le
     * navigateur ne trouvera aucune passkey, exactement comme s'il n'en avait
     * pas — afin de ne pas révéler l'existence du compte.
     *
     * @param  array<int,array<string,mixed>>  $passkeys
     */
    public function assertionOptions(array $passkeys): PublicKeyCredentialRequestOptions
    {
        $allowCredentials = array_map(
            fn (array $row): PublicKeyCredentialDescriptor => PublicKeyCredentialDescriptor::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                Base64UrlSafe::decode((string) $row['credential_id']),
            ),
            $passkeys,
        );

        return PublicKeyCredentialRequestOptions::create(
            random_bytes(32),
            (string) config('passkey.rp_id'),
            $allowCredentials,
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
        );
    }

    /**
     * Vérifie une assertion de connexion.
     *
     * @return array<string,mixed> la ligne user_passkeys correspondante
     *                             (contient user_id) en cas de succès
     *
     * @throws RuntimeException si la passkey est inconnue
     */
    public function finishAssertion(
        PublicKeyCredentialRequestOptions $options,
        string $clientJson,
        string $host,
    ): array {
        /** @var PublicKeyCredential $credential */
        $credential = $this->serializer->deserialize($clientJson, PublicKeyCredential::class, 'json');

        $response = $credential->response;
        if (! $response instanceof AuthenticatorAssertionResponse) {
            throw new RuntimeException('Réponse WebAuthn invalide (assertion attendue).');
        }

        $credentialIdB64 = Base64UrlSafe::encodeUnpadded($credential->rawId);
        $row = $this->repository->findByCredentialId($credentialIdB64);
        if ($row === null) {
            throw new RuntimeException('Passkey inconnue.');
        }

        $record = $this->recordFromRow($row);

        $validator = AuthenticatorAssertionResponseValidator::create(
            $this->ceremonyFactory()->requestCeremony(),
        );

        // Vérifie le challenge, l'origine, la signature ES256 et le compteur.
        // Lève une exception si quoi que ce soit cloche.
        $updated = $validator->check(
            $record,
            $response,
            $options,
            $host,
            $record->userHandle,
        );

        // Met à jour le compteur anti-rejeu et la date de dernière utilisation.
        $this->repository->touchAfterAssertion((int) $row['id'], $updated->counter);

        return $row;
    }

    // =====================================================================
    //  (Dé)sérialisation des options pour la session
    // =====================================================================

    public function serializeOptions(
        PublicKeyCredentialCreationOptions|PublicKeyCredentialRequestOptions $options,
    ): string {
        return $this->serializer->serialize($options, 'json');
    }

    public function deserializeCreationOptions(string $json): PublicKeyCredentialCreationOptions
    {
        return $this->serializer->deserialize($json, PublicKeyCredentialCreationOptions::class, 'json');
    }

    public function deserializeRequestOptions(string $json): PublicKeyCredentialRequestOptions
    {
        return $this->serializer->deserialize($json, PublicKeyCredentialRequestOptions::class, 'json');
    }

    // =====================================================================
    //  Helpers internes
    // =====================================================================

    /**
     * Identifiant opaque liant un credential à un utilisateur (userHandle).
     *
     * Pour ce PoC on dérive le handle de l'id utilisateur. En production on
     * préfèrerait un identifiant aléatoire et stable, non corrélé à l'id, pour
     * la vie privée — c'est documenté comme limite.
     */
    private function userHandle(int $userId): string
    {
        return (string) $userId;
    }

    private function loadResponse(string $clientJson, string $expectedResponseClass): AuthenticatorAttestationResponse
    {
        /** @var PublicKeyCredential $credential */
        $credential = $this->serializer->deserialize($clientJson, PublicKeyCredential::class, 'json');

        $response = $credential->response;
        if (! $response instanceof $expectedResponseClass) {
            throw new RuntimeException('Réponse WebAuthn invalide (attestation attendue).');
        }

        return $response;
    }

    /**
     * Garde-fou explicite : refuse toute clé dont l'algorithme n'est pas dans la
     * liste autorisée ({@see ALLOWED_ALGS} = ES256 privilégié, RS256 fallback).
     *
     * La cérémonie l'impose déjà (étape CheckAlgorithm), mais on décode la clé
     * COSE et on revérifie l'algorithme nous-mêmes pour pouvoir l'expliquer
     * clairement à l'oral : « voici où je rejette tout algo non déclaré ».
     */
    private function assertAllowedAlgorithm(CredentialRecord $record): void
    {
        $stream = new StringStream($record->credentialPublicKey);
        $decoded = Decoder::create()->decode($stream);

        if (! $decoded instanceof Normalizable) {
            throw new RuntimeException('Clé publique COSE invalide.');
        }

        $normalized = $decoded->normalize();
        if (! is_array($normalized)) {
            throw new RuntimeException('Clé publique COSE invalide.');
        }

        /** @var array<int|string, mixed> $normalized */
        $alg = CoseKey::create($normalized)->alg();
        if (! in_array($alg, self::ALLOWED_ALGS, true)) {
            throw new RuntimeException(sprintf(
                'Algorithme non autorisé : %d (seuls ES256 = %d et RS256 = %d sont acceptés).',
                $alg,
                self::ALG_ES256,
                self::ALG_RS256,
            ));
        }
    }

    /**
     * Reconstruit un CredentialRecord à partir d'une ligne user_passkeys pour la
     * vérification d'assertion. On ne stocke que des données publiques.
     *
     * @param  array<string,mixed>  $row
     */
    private function recordFromRow(array $row): CredentialRecord
    {
        $transports = [];
        if (! empty($row['transports'])) {
            $decoded = json_decode((string) $row['transports'], true);
            if (is_array($decoded)) {
                $transports = $decoded;
            }
        }

        $aaguid = ! empty($row['aaguid'])
            ? Uuid::fromString((string) $row['aaguid'])
            : Uuid::fromString('00000000-0000-0000-0000-000000000000');

        return new CredentialRecord(
            Base64UrlSafe::decode((string) $row['credential_id']),
            PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            $transports,
            'none',
            EmptyTrustPath::create(),
            $aaguid,
            (string) base64_decode((string) $row['public_key'], true),
            (string) $row['user_handle'],
            (int) $row['sign_count'],
        );
    }
}
