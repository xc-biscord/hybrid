# Biscord — Architecture backend MVC minimale (migration incrémentale)

## Objectif
Mettre en place une base MVC **backend** compatible avec une migration progressive vers Laravel, sans casser les endpoints existants (`/api/*.php`) ni changer les URLs.

## Arborescence introduite

```txt
app/
  Controllers/
    BaseApiController.php
    AdminUserController.php
    ServerController.php
  Services/
    ServerService.php
    UserServerService.php
  Repositories/
    ServerRepository.php
    ServerMemberRepository.php
  Models/
    Server.php
    ServerMember.php
  Middleware/
    AdminMiddleware.php
  Support/
    Autoload.php
    ApiKernel.php
```

## Rôle des couches (convention Biscord)

### Controllers (`app/Controllers`)
- Point d’entrée applicatif pour les cas d’usage.
- Reçoivent les données déjà validées basiquement par l’endpoint (`$_GET`, JSON, session).
- Orchestrent les services et normalisent le format de réponse API (`success/error`, status HTTP).
- **Pas de SQL direct** attendu à terme (toléré temporairement pendant migration).

### Services (`app/Services`)
- Encapsulent la logique métier / règles applicatives.
- Peuvent composer plusieurs repositories dans une transaction.
- Ne dépendent ni de `$_SERVER` ni de la couche HTTP.

### Repositories (`app/Repositories`)
- Contiennent les requêtes SQL et l’accès PDO.
- Fournissent des méthodes explicites orientées domaine (ex: `findByMemberUserId`).

### Models (`app/Models`)
- Objets domaine simples (DTO/entités légères) représentant les concepts Biscord.
- Préparent le terrain pour une future convergence vers des `Models` Eloquent.

### Middleware (`app/Middleware`)
- Règles transverses (authz/authn, checks réutilisables) hors endpoints.
- Exemple actuel: vérification admin global (`P1`).

### Support (`app/Support`)
- Infrastructure légère de transition:
  - `Autoload.php`: autoload PSR-4-like pour namespace `App\`.
  - `ApiKernel.php`: assemblage manuel des dépendances (préfigure un container DI Laravel).

## Squelettes implémentés

### Controllers
- `BaseApiController`: helpers `success()` / `error()` pour homogénéiser les retours.
- `ServerController`: orchestration `create` + `index` pour la gestion des serveurs.
- `AdminUserController`: lecture admin des utilisateurs et des serveurs d’un utilisateur.

### Services
- `ServerService`: cas d’usage serveur (`createServer`, `listUserServers`) avec transaction.
- `UserServerService`: politique d’accès admin + lecture des serveurs d’un utilisateur.

### Repositories
- `ServerRepository`: persistance serveur (`create`, `findByMemberUserId`, `find`).
- `ServerMemberRepository`: persistance membre (`addMember`, `listServersForUser`).

### Models
- `Server`: modèle domaine minimal (id, name, ownerId).
- `ServerMember`: modèle domaine minimal (serverId, userId, role).

## Stratégie d’intégration progressive avec les endpoints legacy

Les fichiers `/api/*.php` restent les points d’entrée publics, mais deviennent des **adaptateurs HTTP**:
1. vérifier méthode/session/params
2. instancier un controller via `apiKernel()`
3. déléguer le cas d’usage
4. convertir le résultat controller vers JSON (`respondFromController`)

Cela permet de migrer endpoint par endpoint sans big-bang.

## Endpoints déjà branchés sur la nouvelle structure

- `api/create_server.php` → `ServerController::create`
- `api/get_servers.php` → `ServerController::index`
- `api/get_server_name.php` → `ServerController::showName`
- `api/get_user_servers.php` → `AdminUserController::listUserServers`
- `api/get_all_users.php` → `AdminUserController::listUsers`

## Conventions techniques posées

- **Namespaces backend**: toute nouvelle classe backend passe par `App\*` (autoload `app/Support/Autoload.php`).
- **Controllers = contrat HTTP interne**: les controllers retournent `['statusCode' => int, 'payload' => array]`.
- **Endpoints legacy = adaptateurs**: ils appellent `apiKernel()` + `respondFromController()` et ne font plus de SQL direct.
- **Services sans contexte HTTP**: aucun `$_SESSION`, `$_GET`, `$_POST` dans les services.
- **Repositories orientés cas d’usage**: méthodes explicites (`findByMemberUserId`, `listServersForUser`, `find`), pas d’API SQL générique.
- **Compatibilité Laravel**: séparation Controller/Service/Repository/Model et middleware dédiée conservées à l’identique lors du passage vers `app/Http/*`.

## Conventions de migration endpoint (Laravel-ready)

### Convention A — endpoint legacy
- L’URL `api/*.php` reste inchangée.
- L’endpoint ne contient que: garde-fous HTTP + adaptation entrée/sortie.
- Toute règle métier va dans Service/Repository.

### Convention B — payload API
- Réponse normalisée côté controller:
  - succès: `{ success: true, ... }`
  - erreur: `{ success: false, error: "..." }`
- Les clés métiers historiques sont conservées pour ne pas casser le front.

### Convention C — dépendances
- `ApiKernel` centralise le wiring temporaire.
- Éviter l’instanciation directe de PDO/services dans les endpoints.

## Mapping vers concepts Laravel (cible)

| Structure Biscord actuelle | Cible Laravel | Notes migration |
|---|---|---|
| `app/Controllers/*` | `app/Http/Controllers/*` | Méthodes de controllers réutilisables quasi à l’identique |
| `app/Services/*` | `app/Services/*` | Conserve la logique métier hors contrôleur |
| `app/Repositories/*` | Repositories + Eloquent/Query Builder | SQL brut migrable progressivement |
| `app/Models/*` | `app/Models/*` (Eloquent) | DTO actuels deviennent entités Eloquent |
| `app/Middleware/*` | `app/Http/Middleware/*` | Contrôles d’accès migrables en middleware Laravel |
| `app/Support/ApiKernel.php` | Container IoC + Service Providers | Remplacé quand le bootstrap Laravel prend le relais |
| `/api/*.php` | `routes/api.php` | Même contrats d’API, pointés vers controllers Laravel |

## Zones prêtes pour la prochaine vague

- Domaine `Server` (création + listing) : base MVC en place.
- Domaine admin users/servers : points d’entrée branchés.
- Infrastructure de wiring (`ApiKernel`) : prête pour extension incrémentale.
- Contrat JSON de base (`BaseApiController`) : socle commun pour nouveaux endpoints.

## Ce qui reste legacy (non migré)

- La majorité des endpoints `/api/*.php` non branchés controllers.
- `config/config.php` global + variable `$pdo` partagée.
- Auth/session et permissions encore partiellement procédurales.
- Pas de routing centralisé ni vrai container DI.
- Documentation endpoint existante inchangée (hors architecture).
