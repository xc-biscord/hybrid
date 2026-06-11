# Phase 6.0 — Wrapper Inventory

**Date :** 2026-06-11
**Commit de référence :** 88f1bd5 (post phase 5.3)
**Suite Contract :** 120 passed / 659 assertions (vert)

État legacy : `app/*` supprimé, `ApiKernel` supprimé, `Autoload` supprimé, `api/permissions.php` supprimé. Plus aucun code legacy `app/`. Restent les wrappers `api/*.php` qui pontent les requêtes HTTP vers Laravel.

---

## 1. Vue d'ensemble

`api/` contient **34 fichiers `.php`** :
- **3 fichiers d'infrastructure** : `bootstrap.php`, `laravel_proxy.php`, `health.php`
- **31 wrappers fonctionnels**

**Aucun wrapper ne contient de SQL** (grep `SELECT/INSERT/UPDATE/DELETE/prepare/query/PDO/exec` → vide).
**Aucun wrapper ne contient de logique métier.** Tous délèguent à un contrôleur Laravel via `laravelMake()`.

La logique résiduelle présente dans certains wrappers est exclusivement du **glue de transport** (lecture des superglobales `$_GET`/`$_POST`/`$_SESSION`, casts de type, vérification de méthode HTTP) ou de rares **gardes de validation inline**.

---

## 2. Infrastructure (non-wrappers)

| Fichier | Lignes | Rôle | Supprimable ? |
|---|---|---|---|
| `bootstrap.php` | 51 | `require config/config.php` (→ `session_start()` + `$pdo`) ; pose le header `Content-Type: application/json` ; définit 5 helpers (`jsonResponse`, `respondFromController`, `requireMethod`, `getJsonInput`, `requireAuthUserId`) | Seulement avec les 30 wrappers qui en dépendent |
| `laravel_proxy.php` | 41 | Boot Laravel (`laravelApp`), `laravelMake`, `respondFromJsonResponse` | **Chokepoint** — requis par les 31 wrappers. Dernier à partir |
| `health.php` | 7 | Renvoie `{success:true,status:ok}` statique. Aucune dépendance, ne charge pas Laravel | Indépendant, supprimable à tout moment |

**Note `bootstrap.php` :** le `$pdo` fourni par `config/config.php` n'est **plus utilisé par aucun wrapper** (zéro SQL). Seul `session_start()` reste nécessaire (auth par session). `$pdo` est du poids mort — mais `config/config.php` est hors périmètre.

`respondFromController()` (helper de `bootstrap.php`) n'est **appelé par aucun wrapper** — vestige inerte, sans lien avec du legacy supprimé, laissé en place (hors périmètre Phase 6).

---

## 3. Classification des 31 wrappers

### 3.1 Pass-through pur (5) — natif Laravel `Request`

Construisent une `Request` depuis les globales et délèguent **tout** au contrôleur `handle($request)`. Aucune extraction manuelle, aucun helper `bootstrap.php` utilisé.

| Wrapper | Lignes | Contrôleur cible | Dépendances |
|---|---|---|---|
| `ban_user.php` | 11 | `Api\BanUserController::handle` | bootstrap (header+session) + proxy |
| `get_profile.php` | 11 | `Api\GetProfileController::handle` | bootstrap (header+session) + proxy |
| `get_user_profile.php` | 11 | `Api\GetUserProfileController::handle` | bootstrap (header+session) + proxy |
| `update_profile.php` | 11 | `Api\UpdateProfileController::handle` | bootstrap (header+session) + proxy |
| `xxx.php` | 9 | `Api\XxxController::handle` (placeholder) | bootstrap (header+session) + proxy |

> Ces 5 n'utilisent **aucun helper** de `bootstrap.php` : leur dépendance à `bootstrap.php` se limite aux effets de bord `session_start()` + header JSON. Migration la plus simple : une route Laravel `Route::match(...)` les remplace directement.

### 3.2 Proxy fin — glue de transport (23)

Extraient des paramètres des superglobales et les passent en arguments à une méthode de contrôleur. Pas de logique métier, pas de SQL, pas de garde émettant une réponse.

| Wrapper | Lignes | Contrôleur::méthode | Helpers bootstrap utilisés |
|---|---|---|---|
| `accept_invite.php` | 10 | `InvitationController::accept` | — (lit `$_SESSION`,`$_POST`) |
| `create_invite.php` | 10 | `InvitationController::create` | — (lit `$_SESSION`,`$_POST`) |
| `check_auth.php` | 7 | `AuthController::checkAuth` | — (aucun param) |
| `logout.php` | 6 | `AuthController::logout` | — (bypass bootstrap ; `config.php` direct ; pas de `respondFromJsonResponse`) |
| `create_channel.php` | 10 | `ChannelController::create` | requireMethod, requireAuthUserId, getJsonInput |
| `create_server.php` | 10 | `ServerController::create` | requireMethod, requireAuthUserId, getJsonInput |
| `delete_message.php` | 9 | `MessageController::delete` | requireAuthUserId (lit body JSON brut) |
| `get_all_users.php` | 8 | `AdminUserController::listUsers` | requireAuthUserId |
| `get_channels.php` | 9 | `ChannelController::index` | requireAuthUserId (lit `$_GET`) |
| `get_dm_messages.php` | 9 | `DmController::messages` | requireAuthUserId (lit `$_GET`) |
| `get_dm_notifications.php` | 8 | `DmController::notifications` | requireAuthUserId |
| `get_messages.php` | 9 | `MessageController::index` | requireAuthUserId (lit `$_GET`) |
| `get_my_server_role.php` | 11 | `RoleModerationController::getMyServerRole` | requireAuthUserId (lit `$_GET`) |
| `get_server_name.php` | 9 | `ServerController::showName` | requireAuthUserId (lit `$_GET`) |
| `get_servers.php` | 8 | `ServerController::index` | requireAuthUserId |
| `get_users_in_server.php` | 10 | `RoleModerationController::listUsersInServer` | requireAuthUserId (lit `$_GET`) |
| `kick_member.php` | 10 | `RoleModerationController::kickMember` | requireAuthUserId, getJsonInput |
| `login.php` | 9 | `AuthController::login` | requireMethod, getJsonInput |
| `register.php` | 10 | `AuthController::register` | requireMethod, getJsonInput |
| `send_dm.php` | 10 | `DmController::send` | requireMethod, requireAuthUserId, getJsonInput |
| `send_message.php` | 10 | `MessageController::create` | requireMethod, requireAuthUserId, getJsonInput |
| `set_member_role.php` | 10 | `RoleModerationController::setMemberRole` | requireAuthUserId, getJsonInput |
| `start_dm.php` | 10 | `DmController::start` | requireMethod, requireAuthUserId, getJsonInput |

### 3.3 Proxy fin avec garde/branche résiduelle (3)

Contiennent une branche conditionnelle qui émet une réponse ou contrôle le flux — seule vraie « logique résiduelle » au-delà du glue.

| Wrapper | Lignes | Logique résiduelle | Contrôleur cible |
|---|---|---|---|
| `get_user_servers.php` | 14 | Garde `400` inline si `$_GET['user_id']` manquant/non-numérique (`jsonResponse(... 400)`) | `AdminUserController::listUserServers` |
| `update_account.php` | 13 | Garde `200` "Non connecté" si pas de `$_SESSION['user_id']` (`jsonResponse(... 200)`) | `AccountController::update` |
| `auth.php` | 12 | Branche `if ($response instanceof JsonResponse)` + `exit` — le contrôleur peut ne pas renvoyer de `JsonResponse` | `AuthController::auth` |

---

## 4. Dépendances

### 4.1 Qui appelle `laravel_proxy.php`

**Les 31 wrappers fonctionnels** (tous). `laravel_proxy.php` est le **point de passage unique** vers Laravel : il ne peut être supprimé qu'une fois tous les wrappers retirés (ou remplacés par un rewrite global vers les routes Laravel).

### 4.2 Qui dépend de `bootstrap.php`

**30 wrappers** dépendent de `bootstrap.php`.
**Exceptions :**
- `logout.php` — charge `config/config.php` **directement** (obtient `session_start()` mais ni le header JSON ni les helpers)
- `health.php` — autonome, ne charge rien

**9 wrappers requièrent `bootstrap.php` sans utiliser aucun de ses helpers** (`ban_user`, `get_profile`, `get_user_profile`, `update_profile`, `xxx`, `check_auth`, `auth`, `accept_invite`, `create_invite`) : leur dépendance se réduit aux effets de bord `session_start()` + header JSON.

### 4.3 Chaîne réelle

```
api/<wrapper>.php
  ├─ require bootstrap.php  ──► require config/config.php ──► session_start() [+ $pdo INUTILISÉ]
  │                          └─► header('Content-Type: application/json')
  │                          └─► helpers (requireAuthUserId / requireMethod / getJsonInput / jsonResponse)
  └─ require laravel_proxy.php ──► laravelApp() / laravelMake() / respondFromJsonResponse()
                                    └─► \App\Http\Controllers\... (Laravel)
```

---

## 5. Candidats suppression (Phase future)

**Tous les wrappers sont supprimables à terme** — aucun ne porte de logique métier. La suppression dépend d'un **rewrite global `/api/*.php` → routes Laravel** (hors périmètre actuel, non activé). Une fois ce rewrite en place, les 31 wrappers + `bootstrap.php` + `laravel_proxy.php` disparaissent ensemble.

Effort de migration par catégorie :

| Catégorie | Effort | Détail |
|---|---|---|
| Pass-through pur (5) | **Trivial** | `Route::match()` direct, le contrôleur lit déjà la `Request` |
| Proxy fin glue (23) | **Faible** | Déplacer l'extraction `$_GET`/`$_POST`/`$_SESSION` → `Request`/`FormRequest` dans la route/contrôleur |
| Proxy avec garde (3) | **Moyen** | Reporter la garde inline (`get_user_servers` 400, `update_account` 200, `auth` instanceof) dans le contrôleur/FormRequest en **préservant les statuts HTTP exacts** (notamment le `200` de `update_account` et le `400` de `get_user_servers`) |

**Indépendant et supprimable immédiatement :** `health.php` (aucune dépendance) — à confirmer s'il sert à un health-check externe.

**Point d'attention :** plusieurs contrats reposent sur des **invariants de statut non-standard** (ex. `ban_user` / `update_account` renvoient `200` au lieu de `401`). Toute suppression de wrapper devra router vers un contrôleur Laravel qui **reproduit ces statuts à l'identique** — sinon régression Contract.

---

## 6. Synthèse

- 31 wrappers, **0 SQL, 0 logique métier**.
- 5 pass-through purs, 23 proxies de transport, 3 proxies avec garde résiduelle.
- `laravel_proxy.php` = chokepoint unique (31 dépendants).
- `bootstrap.php` = session + header + helpers (30 dépendants ; `$pdo` mort).
- Aucune action de suppression en Phase 6.0 — inventaire uniquement.
- Suppression future conditionnée à un rewrite global, en préservant les statuts HTTP non-standard.
