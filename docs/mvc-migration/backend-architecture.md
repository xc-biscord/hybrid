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
- `api/get_user_servers.php` → `AdminUserController::listUserServers`
- `api/get_all_users.php` → `AdminUserController::listUsers`

## Mapping vers concepts Laravel (cible)

- `app/Controllers/*` → `app/Http/Controllers/*`
- `app/Services/*` → services applicatifs (inchangés ou via `app/Services`)
- `app/Repositories/*` → repositories + Query Builder/Eloquent
- `app/Models/*` → modèles Eloquent
- `app/Middleware/*` → `app/Http/Middleware/*`
- `app/Support/ApiKernel.php` → remplacé par le container IoC Laravel + Service Providers
- `/api/*.php` → routes Laravel (`routes/api.php`) pointant vers controllers

## Conventions de migration (à appliquer endpoint par endpoint)

1. **Ne pas changer l’URL** de l’endpoint legacy.
2. Réduire le code endpoint à validation d’entrée + délégation contrôleur.
3. Déplacer le SQL dans un repository dédié.
4. Déplacer les règles métiers dans un service.
5. Uniformiser réponses via `BaseApiController`.
6. Garder la compatibilité payload (`success`, `error`, clés métiers actuelles) tant que le front n’est pas migré.

## Zones prêtes pour la prochaine vague

- Domaine `Server` (création + listing) : base MVC en place.
- Domaine admin users/servers : points d’entrée branchés.
- Infrastructure de wiring (`ApiKernel`) : prête pour extension incrémentale.

## Ce qui reste legacy (non migré)

- La majorité des endpoints `/api/*.php` non branchés controllers.
- `config/config.php` global + variable `$pdo` partagée.
- Auth/session et permissions encore partiellement procédurales.
- Pas de routing centralisé ni vrai container DI.
- Documentation endpoint existante inchangée (hors architecture).
