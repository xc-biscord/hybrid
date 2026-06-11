# Phase 4 — Legacy Inventory

**Date :** 2026-06-11  
**Statut phase 3 :** tous les endpoints fonctionnels routés vers Laravel  
**Objectif phase 4 :** inventaire exact du legacy restant avant suppression progressive (Phase 5)

---

## 1. État des wrappers `/api/*.php`

### 1.1 Wrappers — bridge Laravel pur (30 fichiers)

Tous ces fichiers suivent le même pattern :
```
require_once bootstrap.php
require_once laravel_proxy.php
laravelMake(\App\Http\Controllers\...) → respondFromJsonResponse()
```
Aucun n'appelle `apiKernel()`, aucun SQL, aucune logique procédurale propre.

| Fichier | Contrôleur Laravel cible |
|---|---|
| `accept_invite.php` | `InvitationController::accept` |
| `auth.php` | `AuthController::auth` |
| `ban_user.php` | `Api\BanUserController::handle` + `laravelApp()` |
| `check_auth.php` | `AuthController::checkAuth` |
| `create_channel.php` | `ChannelController::create` |
| `create_invite.php` | `InvitationController::create` |
| `create_server.php` | `ServerController::create` |
| `delete_message.php` | `MessageController::delete` |
| `get_all_users.php` | `AdminUserController::listUsers` |
| `get_channels.php` | `ChannelController::index` |
| `get_dm_messages.php` | `DmController::messages` |
| `get_dm_notifications.php` | `DmController::notifications` |
| `get_messages.php` | `MessageController::index` |
| `get_my_server_role.php` | `RoleModerationController::getMyServerRole` |
| `get_profile.php` | `Api\GetProfileController::handle` + `laravelApp()` |
| `get_server_name.php` | `ServerController::showName` |
| `get_servers.php` | `ServerController::index` |
| `get_user_profile.php` | `Api\GetUserProfileController::handle` + `laravelApp()` |
| `get_user_servers.php` | `AdminUserController::listUserServers` |
| `get_users_in_server.php` | `RoleModerationController::listUsersInServer` |
| `kick_member.php` | `RoleModerationController::kickMember` |
| `login.php` | `AuthController::login` |
| `register.php` | `AuthController::register` |
| `send_dm.php` | `DmController::send` |
| `send_message.php` | `MessageController::create` |
| `set_member_role.php` | `RoleModerationController::setMemberRole` |
| `start_dm.php` | `DmController::start` |
| `update_account.php` | `AccountController::update` (lit `$_SESSION` dans le wrapper) |
| `update_profile.php` | `Api\UpdateProfileController::handle` + `laravelApp()` |
| `xxx.php` | `Api\XxxController::handle` (placeholder) |

### 1.2 Wrappers — anomalies

| Fichier | Problème |
|---|---|
| `logout.php` | Charge `config/config.php` **directement** (bypass de `bootstrap.php` / `Autoload.php`), puis `laravel_proxy.php`. Bridge Laravel sinon propre. |
| `permissions.php` | **Legacy pur.** Charge `bootstrap.php`, instancie directement `GlobalPermissionRepository`, `ServerMemberRepository`, `PermissionService` (namespace `App\` legacy) avec `PDO`. Définit 3 fonctions globales : `permissionService()`, `isP1()`, `getServerRole()`, `hasPermission()`. N'est `require_once`-é par aucun autre fichier détecté. |

### 1.3 Fichiers infrastructure (non-wrappers)

| Fichier | Rôle | État |
|---|---|---|
| `bootstrap.php` | Charge `config/config.php` + `app/Support/Autoload.php`. Définit : `jsonResponse()`, `respondFromController()`, `requireMethod()`, `getJsonInput()`, `requireAuthUserId()`, `apiKernel()` | Requis par tous les wrappers sauf `logout.php` et `health.php`. `apiKernel()` est défini mais **jamais appelé** dans aucun wrapper. |
| `laravel_proxy.php` | Bootstrap Laravel, définit `laravelApp()`, `laravelMake()`, `respondFromJsonResponse()` | Requis par 30 wrappers. Indispensable phase 5. |
| `health.php` | Retourne `{success:true,status:ok}` statique | Standalone, aucune dépendance. |

---

## 2. État de `app/*`

### 2.1 Vue d'ensemble

Tout `app/` utilise le namespace `App\` (identique à Laravel). Les classes sont rendues disponibles via `spl_autoload_register` dans `Autoload.php`, chargé par `bootstrap.php`. Elles **ne sont pas** dans l'autoload Composer (`composer.json` pointe `"App\\"` vers `laravel/app/`, pas vers `app/`).

### 2.2 Controllers (9 fichiers) — **aucune référence active**

| Fichier | SQL/PDO | Référencé par |
|---|---|---|
| `AccountController.php` | Oui | `ApiKernel.php` uniquement |
| `AdminUserController.php` | Non (délègue service) | `ApiKernel.php` uniquement |
| `AuthController.php` | Non | `ApiKernel.php` uniquement |
| `BaseApiController.php` | Non | `ApiKernel.php` uniquement |
| `ChannelController.php` | Non | `ApiKernel.php` uniquement |
| `DmController.php` | Non | `ApiKernel.php` uniquement |
| `MessageController.php` | Non | `ApiKernel.php` uniquement |
| `RoleModerationController.php` | Non | `ApiKernel.php` uniquement |
| `ServerController.php` | Non | `ApiKernel.php` uniquement |

### 2.3 Middleware (1 fichier)

| Fichier | SQL/PDO | Référencé par |
|---|---|---|
| `AdminMiddleware.php` | Oui — `SELECT 1 FROM global_permissions` | `ApiKernel.php` uniquement |

### 2.4 Models (2 fichiers)

| Fichier | SQL/PDO | Référencé par |
|---|---|---|
| `Server.php` | Non (value object pur) | Repositories legacy |
| `ServerMember.php` | Non (value object pur) | Repositories legacy |

### 2.5 Repositories (8 fichiers) — **tous avec SQL direct**

| Fichier | SQL/PDO | Référencé par |
|---|---|---|
| `ChannelRepository.php` | Oui | `ApiKernel.php` |
| `DmRepository.php` | Oui | `ApiKernel.php` |
| `GlobalPermissionRepository.php` | Oui | `ApiKernel.php`, `permissions.php` |
| `MessageRepository.php` | Oui | `ApiKernel.php` |
| `ProfileRepository.php` | Oui | `ApiKernel.php` |
| `ServerMemberRepository.php` | Oui | `ApiKernel.php`, `permissions.php` |
| `ServerRepository.php` | Oui | `ApiKernel.php` |
| `UserRepository.php` | Oui | `ApiKernel.php` |

### 2.6 Services (9 fichiers)

| Fichier | SQL/PDO direct | Référencé par |
|---|---|---|
| `AccountService.php` | Non | `ApiKernel.php` |
| `ChannelService.php` | Non | `ApiKernel.php` |
| `DmService.php` | Non | `ApiKernel.php` |
| `MessageService.php` | Non | `ApiKernel.php` |
| `ModerationService.php` | Non | `ApiKernel.php` |
| `PermissionService.php` | Non | `ApiKernel.php`, `permissions.php` |
| `RegisterService.php` | Oui | `ApiKernel.php` |
| `ServerService.php` | Oui | `ApiKernel.php` |
| `UserServerService.php` | Non | `ApiKernel.php` |

### 2.7 Support (2 fichiers)

| Fichier | Rôle | Référencé par |
|---|---|---|
| `ApiKernel.php` | DI factory complète — construit tous les controllers legacy avec leurs dépendances | `bootstrap.php` (pour la fonction `apiKernel()` uniquement — jamais appelée) |
| `Autoload.php` | `spl_autoload_register` pour namespace `App\` → `app/` | `bootstrap.php` |

### 2.8 Validators (1 fichier)

| Fichier | Référencé par |
|---|---|
| `AccountUpdateValidator.php` | `ApiKernel.php` uniquement |

---

## 3. Carte des dépendances restantes

```
api/*.php (30 wrappers)
  └─ require_once bootstrap.php
        ├─ require_once config/config.php   ← PDO créé ici, $pdo global
        └─ require_once app/Support/Autoload.php  ← spl_autoload app/*
              └─ rend disponibles : Controllers, Repositories, Services, Models, Validators

api/bootstrap.php
  └─ define apiKernel() → new ApiKernel($pdo)
        └─ ApiKernel.php  ← jamais instancié en pratique

api/permissions.php
  ├─ require_once bootstrap.php
  ├─ use App\Repositories\GlobalPermissionRepository  ← legacy
  ├─ use App\Repositories\ServerMemberRepository      ← legacy
  └─ use App\Services\PermissionService               ← legacy

api/logout.php
  ├─ require_once config/config.php  ← directement, sans bootstrap
  └─ require_once laravel_proxy.php

api/laravel_proxy.php
  └─ require laravel/vendor/autoload.php
  └─ require laravel/bootstrap/app.php
```

**Ce qui charge encore `app/` :**
- `bootstrap.php` via `Autoload.php` — touché par 30 wrappers à chaque requête
- `permissions.php` via instanciation directe — fichier orphelin (aucun include actif détecté)

---

## 4. Résultat des tests Contract

```
php artisan test --testsuite=Contract
120 failed (0 assertions)
```

**Cause :** `tests/Contract/Support/TestDatabaseSeeder.php` est en permissions `600 root:root` — illisible pour l'utilisateur courant (`admin`). Tous les tests échouent sur `Class "Tests\Contract\Support\TestDatabaseSeeder" not found`.

**Cause secondaire (root) :** `phpunit.xml` injecte `DB_DATABASE=:memory:` dans l'env de test. La méthode `resolveConfigValue()` du seeder lit `DB_DATABASE` en fallback et tente une connexion MySQL vers `:memory:`, ce qui échoue avec `Access denied for user ... to database ':memory:'`.

**Ce n'est pas une régression Phase 4.** Les tests Contract passaient en Phase 3 (120 passed / 659 assertions) via le runner dédié. L'infrastructure de test nécessite un fix de permissions et/ou une variable `CONTRACT_TEST_DB_DATABASE` isolée de l'env phpunit.xml.

---

## 5. Candidats suppression Phase 5

### 5.1 Supprimables sans risque apparent

> **Prérequis unique :** retirer `require_once app/Support/Autoload.php` de `bootstrap.php`

| Fichier / Dossier | Raison |
|---|---|
| `app/Controllers/` (9 fichiers) | Aucun wrapper ne les appelle. `ApiKernel.php` seul point de référence — lui-même mort. |
| `app/Models/` (2 fichiers) | Uniquement utilisés par les Repositories legacy. |
| `app/Repositories/` (8 fichiers) | Appelés uniquement par `ApiKernel.php` et `permissions.php`. |
| `app/Services/` (9 fichiers) | Idem. |
| `app/Middleware/AdminMiddleware.php` | Uniquement dans `ApiKernel.php`. |
| `app/Validators/AccountUpdateValidator.php` | Uniquement dans `ApiKernel.php`. |
| `app/Support/ApiKernel.php` | Défini en bootstrap, jamais instancié. |
| `app/Support/Autoload.php` | Sera retiré de `bootstrap.php`. |
| `api/permissions.php` | N'est inclus par aucun autre fichier. Fonctions `isP1/getServerRole/hasPermission` non appelées dans les wrappers actifs. Vérifier les logs HTTP avant suppression. |
| `api/xxx.php` | Placeholder. À confirmer avec l'équipe. |

### 5.2 À conserver temporairement (Phase 5 dépend d'eux)

| Fichier | Pourquoi |
|---|---|
| `api/bootstrap.php` | Requis par 30 wrappers. Retirer `Autoload.php` dedans suffit pour déconnecter `app/*`. Ne peut être supprimé qu'avec les wrappers eux-mêmes. |
| `api/laravel_proxy.php` | Cœur du bridge. Indispensable tant que les wrappers existent. |
| `config/config.php` | Utilisé par `bootstrap.php` et `logout.php`. Contient credentials DB et session. |

---

## 6. Risques

| Risque | Niveau | Détail |
|---|---|---|
| `permissions.php` orphelin mais accessible HTTP | Faible | Pas inclus activement. Si appelé directement via HTTP, il instancierait du legacy PDO. Vérifier les logs avant suppression. |
| Namespace collision `App\` | Faible | Legacy `app/` et Laravel `laravel/app/` partagent le namespace `App\`. L'`Autoload.php` legacy prend la main sur les classes legacy tant qu'il est chargé. Une fois retiré, seul l'autoload Composer reste. |
| `apiKernel()` dans bootstrap | Nul | Définie, jamais appelée. |
| `TestDatabaseSeeder.php` 600 root:root | Moyen | Bloque tous les Contract tests pour les non-root. Fix : `sudo chmod 644 tests/Contract/Support/TestDatabaseSeeder.php`. Indépendant de Phase 5 mais bloque la validation régression. |
| `logout.php` bypass bootstrap | Nul | N'appelle pas `Autoload.php`, ne charge pas `app/*`. |

---

## 7. Recommandation Phase 5

**Ordre de suppression proposé :**

1. **Fix test infrastructure** — `sudo chmod 644 laravel/tests/Contract/Support/TestDatabaseSeeder.php` et définir `CONTRACT_TEST_DB_DATABASE=biscord_db_tests` dans l'env CI pour isoler les Contract tests de l'env phpunit.xml. Valider que les 120 Contract tests repassent avant toute suppression.

2. **Supprimer `api/permissions.php`** — fichier orphelin, aucune référence active. Vérifier les logs d'accès HTTP au préalable.

3. **Retirer `require_once app/Support/Autoload.php`** de `bootstrap.php`, puis **supprimer `app/Support/ApiKernel.php`** — déconnecte l'intégralité de `app/*` sans toucher aucun wrapper. Les 30 wrappers continuent de fonctionner.

4. **Supprimer `app/`** en entier — une fois l'étape 3 validée en test, tout `app/` devient dead code.

5. **Nettoyer `bootstrap.php`** — retirer la fonction `apiKernel()` devenue inutile.

6. **Supprimer les wrappers `/api/*.php`** — dernière étape, après activation du rewrite global vers Laravel (hors scope Phase 5).
