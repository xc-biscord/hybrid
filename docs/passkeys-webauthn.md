# Passkeys / WebAuthn — PoC (dossier oral E6 / CDA)

> Évolution **expérimentale** : connexion à deux étapes permettant d'utiliser un
> **mot de passe** *ou* une **passkey**, sans jamais supprimer ni casser
> l'authentification par mot de passe existante.

---

## 0. Résumé express

- Le login devient en **2 étapes** : (1) on saisit l'identifiant, (2) le serveur
  annonce les méthodes disponibles et l'utilisateur choisit *mot de passe* ou *passkey*.
- Le **mot de passe reste le fallback principal** ; `/api/login.php` n'a pas changé.
- Une nouvelle table **`user_passkeys`** stocke uniquement des **données publiques**.
- Toute la cryptographie est isolée dans **`PasskeyService`** (lib
  `web-auth/webauthn-lib`), **imposant ES256 (ECDSA P-256 + SHA-256)**.
- Implémenté sur la branche `feat/passkeys-webauthn-poc`, **non mergé**.

---

## 1. Workflow complet

### Étape 1 — Identification
```
Utilisateur : saisit username/email -> "Continuer"
Front       : POST /api/login_methods.php { identifier }
Serveur     : répond { methods: { password: true, passkey: bool } }
```

### Étape 2 — Choix de la méthode
- **Mot de passe** (toujours proposé) → `POST /api/login.php` *(inchangé)*.
- **Passkey** (si le compte en a une) :
  ```
  Front   : POST /api/passkey_login_options.php { identifier }
  Serveur : renvoie un challenge + la liste des credentials autorisés
  Front   : navigator.credentials.get(...)  -> l'authentificateur SIGNE le challenge
  Front   : POST /api/passkey_login_verify.php { réponse signée }
  Serveur : vérifie la signature ES256, le challenge et l'origine -> ouvre la session
  ```

### Gestion depuis le profil (utilisateur connecté)
```
Ajouter   : POST /api/passkey_register_options.php  -> challenge de création
            navigator.credentials.create(...)        -> génère la paire de clés
            POST /api/passkey_register_verify.php     -> vérifie + stocke la clé PUBLIQUE
Lister    : GET  /api/passkey_list.php
Supprimer : POST /api/passkey_delete.php { id }       -> contrôle de droits + garde
```

### Schéma (création de passkey)
```
 Navigateur / Authentificateur                   Serveur (PasskeyService)
 ─────────────────────────────                   ────────────────────────
        │  register_options.php  ───────────────▶ génère challenge (32 octets)
        │                                          stocke en session (TTL court)
        │  ◀───────────── options (challenge) ─────┤
 create() : génère clé privée+publique             │
   (clé privée reste DANS l'authentificateur)       │
        │  register_verify.php (clé publique) ────▶ vérifie attestation + ES256
        │                                          stocke clé PUBLIQUE en base
        │  ◀───────────────── success ─────────────┤
```

---

## 2. Pourquoi WebAuthn améliore la sécurité

- **Rien de réutilisable n'est stocké côté serveur** : on garde une clé *publique*,
  inutile pour usurper l'utilisateur. Une fuite de base ne donne pas les comptes.
- **Anti-hameçonnage natif** : la signature est liée à l'**origine** (le domaine).
  Un faux site ne peut pas réutiliser une authentification — le navigateur refuse.
- **Pas de secret partagé** : contrairement au mot de passe, il n'y a pas de
  « secret » transmis qui pourrait être intercepté ou rejoué.
- **Résistance au rejeu** grâce au **challenge** à usage unique (voir §5).

---

## 3. Clé publique vs clé privée (cryptographie asymétrique)

Une passkey, c'est une **paire de clés** ES256 (ECDSA) :

| Clé | Où elle vit | Rôle |
|-----|-------------|------|
| **Privée** | **Dans l'authentificateur** (téléphone, Touch ID, clé FIDO2) — jamais exportée | **Signe** le challenge |
| **Publique** | Envoyée au serveur, stockée en base | **Vérifie** la signature |

Ce qui est signé avec la clé privée ne peut être vérifié qu'avec la clé publique
correspondante, et **on ne peut pas déduire la privée de la publique**.

---

## 4. Pourquoi le serveur ne stocke jamais la clé privée

- Techniquement, **il ne la reçoit jamais** : la clé privée est générée et
  conservée dans l'élément sécurisé de l'authentificateur.
- C'est tout l'intérêt : même un serveur compromis ne peut pas se faire passer
  pour l'utilisateur, car il **ne possède que de quoi vérifier**, pas de quoi signer.
- Dans notre base `user_passkeys`, la colonne `public_key` contient la clé
  **publique COSE** ; il n'existe **aucune** colonne pour une clé privée.

---

## 5. Comment le challenge évite la réutilisation d'une ancienne authentification

- À chaque connexion/enregistrement, le serveur génère un **challenge aléatoire**
  (32 octets) **à usage unique**, stocké temporairement en session (TTL court).
- L'authentificateur **signe ce challenge précis**. La réponse n'est donc valable
  que pour **cette** tentative.
- Le serveur **consomme** le challenge (le supprime) dès la vérification : une
  réponse rejouée plus tard échoue (challenge absent/expiré).
- Un **compteur de signature** (`sign_count`) renforce la détection de clonage :
  il doit progresser, sinon l'assertion est suspecte.

---

## 6. Pourquoi cette évolution reste expérimentale

- **Attestation `none`** : on ne vérifie pas le modèle matériel de
  l'authentificateur (suffisant pour un PoC, et meilleur pour la vie privée).
- **`user_handle` dérivé de l'id utilisateur** : en production on utiliserait un
  identifiant aléatoire non corrélé (vie privée).
- **Anti-énumération partielle** : un compte *possédant* une passkey est
  distinguable (`passkey:true`). Compromis assumé pour le PoC.
- **CSRF** : les endpoints s'appuient sur la session + cookie `SameSite=Lax`
  (comme le reste de l'app) + le challenge anti-rejeu ; un token CSRF dédié
  serait un durcissement.
- **ES256 uniquement** : volontaire (voir ci-dessous), mais certains
  authentificateurs anciens ne proposant que RS256 seraient refusés.
- **HTTPS obligatoire** : WebAuthn n'est utilisable qu'en contexte sécurisé.

### Note ES256 / ECDSA (+ fallback RS256 documenté)
- **ES256 (ECDSA P-256 + SHA-256) est l'algorithme PRIVILÉGIÉ** : il est proposé
  **en premier** dans `pubKeyCredParams`, donc préféré par le navigateur.
- **RS256 (RSASSA-PKCS1-v1_5 + SHA-256) est un fallback de compatibilité**,
  proposé **en second**, **uniquement** parce que certains authentificateurs
  (notamment **Windows Hello / Edge**) refusent une liste ES256 seule (warning
  navigateur : « missing at least one of the default algorithm identifiers:
  ES256 and RS256 »).
- **Aucun fallback silencieux non documenté** : seuls ES256 et RS256 sont
  déclarés, et **seuls** eux sont acceptés à la vérification :
  - `algorithmManager()` ne contient qu'ES256 + RS256 ;
  - l'étape `CheckAlgorithm` (enregistrement) et `CheckSignature` (connexion) s'y
    réfèrent ;
  - un garde-fou explicite `PasskeyService::assertAllowedAlgorithm()` revérifie
    que l'algorithme de la clé ∈ {ES256, RS256}, et **rejette tout le reste**.
- Tests automatisés : `PasskeyServiceTest` vérifie le flux complet **en ES256**
  *et* **en RS256**, plus le **rejet d'une signature falsifiée**.

> Compatibilité : `authenticatorSelection` est nettoyé côté front
> (`webauthn.js`) pour ne pas envoyer de champs `null` (que le navigateur ignore
> en émettant un warning), et `residentKey` est fixé à une valeur valide
> (`"discouraged"`). Le message « authnticatorAttachment » dans la console est un
> warning **émis par le navigateur lui-même** (faute d'orthographe d'Edge), pas
> par notre code.

#### Outil de debug
```bash
php artisan passkey:debug-options 1001 --username=alice
```
Affiche le JSON `publicKey` exact envoyé au navigateur (challenge,
`pubKeyCredParams`, `authenticatorSelection`, etc.).

---

## 7. Tests à faire avant une mise en production

**Déjà automatisé :**
- `Tests\Feature\PasskeyServiceTest` — authentificateur logiciel ES256 :
  enregistrement + connexion avec **vraie signature ECDSA**, et **rejet d'une
  signature falsifiée**.
- `Tests\Contract\PasskeyContractTest` — surface HTTP : anti-énumération,
  auth requise, contrôle de droits à la suppression.

**À tester manuellement / avant prod :**
1. **Vrais authentificateurs** : Touch ID/Face ID, Windows Hello, Android,
   clé FIDO2 (YubiKey), gestionnaires (1Password…).
2. **HTTPS + `rpId`** : vérifier que `PASSKEY_RP_ID` = domaine réel et que
   `PASSKEY_ALLOWED_ORIGINS` couvre l'origine servie (voir §8).
3. **Multi-navigateurs** : Chrome, Firefox, Safari, Edge + mobiles.
4. **Parcours de secours** : WebAuthn indisponible / annulé → le mot de passe
   doit toujours fonctionner.
5. **Compteur de signature** : tester des authentificateurs qui renvoient 0.
6. **Suppression** : confirmer la garde « dernière méthode » sur un compte
   passkey-only (sans mot de passe).
7. **Charge / sessions** : expiration du challenge, sessions concurrentes.
8. **Sécurité** : revue de l'anti-énumération et du CSRF selon le niveau visé.

---

## 8. Configuration & déploiement

Variables d'environnement (`config/passkey.php`) :

| Variable | Rôle | Défaut |
|----------|------|--------|
| `PASSKEY_RP_ID` | domaine du Relying Party | `localhost` |
| `PASSKEY_RP_NAME` | nom affiché par le navigateur | `Biscord` |
| `PASSKEY_ALLOWED_ORIGINS` | origines autorisées (CSV) | *(vide)* |
| `PASSKEY_CHALLENGE_TTL` | durée de vie du challenge (s) | `120` |

> **Dev local en HTTP** : par défaut la lib **exige HTTPS**. Pour tester sur
> `http://localhost:8001`, définir
> `PASSKEY_ALLOWED_ORIGINS=http://localhost:8001` (et `PASSKEY_RP_ID=localhost`).
> En staging/prod (HTTPS), renseigner le domaine réel.

### Schéma de base
La DDL de `user_passkeys` est dans `laravel/database/sql/user_passkeys.sql`
(provisioning par SQL brut, comme le reste du schéma). L'utilisateur applicatif
n'a que les droits CRUD ; la création de table nécessite un compte privilégié :
```sql
mysql <db> < laravel/database/sql/user_passkeys.sql
GRANT SELECT, INSERT, UPDATE, DELETE ON <db>.user_passkeys TO '<app_user>'@'localhost';
```

---

## 9. Fichiers de l'évolution

**Backend**
- `app/Services/Passkey/PasskeyService.php` — cœur WebAuthn (ES256).
- `app/Services/Passkey/PdoPasskeyRepository.php` — accès table `user_passkeys`.
- `app/Http/Controllers/Api/Passkey/*.php` — endpoints (login_methods, auth,
  register, management) + base commune (session + challenge).
- `routes/api.php` — routes additives (login.php **inchangé**).
- `config/passkey.php` — configuration RP.
- `laravel/database/sql/user_passkeys.sql`, `tests/Contract/Support/TestDatabaseSeeder.php` — schéma + seed.

**Frontend**
- `frontend/auth/webauthn.js` — helpers binaires (base64url ↔ ArrayBuffer).
- `index.html` / `index.js` — login en 2 étapes (fallback mot de passe préservé).
- `accueil.html` / `frontend/controllers/PasskeyController.js` — section profil.
- `styles/accueil.css` — styles de la section Passkeys.

**Tests**
- `tests/Feature/PasskeyServiceTest.php` — E2E crypto (authentificateur logiciel).
- `tests/Contract/PasskeyContractTest.php` — surface HTTP.
