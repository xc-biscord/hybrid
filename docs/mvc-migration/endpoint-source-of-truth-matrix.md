# Endpoint Source-of-Truth Matrix (Phase 0)

_Date : 2026-04-18 — Branche : `claude/refactor-business-logic-Gk8WL`_

Cible stratégique validée : **Laravel (`laravel/app/*` + `laravel/routes/api.php`) devient la source of truth** une fois la Phase 1 exécutée.
Ce document fige l'**état réel actuel** des endpoints, sans modification de code. Il sert de référence pilotable pour les phases suivantes.

---

## 1. Conventions de lecture

### Stacks observées
- **P** — `procédural` : `session_start()` direct, SQL inline via `$pdo`, aucun passage par controller.
- **K** — `ApiKernel` : `require bootstrap.php` → `apiKernel()->xxxController()` → classes de `app/*` (MVC maison).
- **L** — `Laravel bridge` : `require bootstrap.php` + `require laravel_proxy.php` → `laravelMake(\App\Http\Controllers\*)` (Laravel bootstrapé manuellement depuis le front-controller PHP legacy).

### Auth
- `requireAuthUserId()` (helper de `bootstrap.php`) → renvoie `401 {success:false,error:"Non authentifié"}` si absent.
- `session_start()` + `isset($_SESSION['user_id'])` → comportement variable (certains renvoient `200` avec `success:false`).
- `P1-only` → exige `permission_level = 'P1'` dans `global_permissions`.

### Méthode HTTP
- `POST strict` → `requireMethod('POST')` présent → `405` si autre.
- `ANY` → aucune vérification → endpoint mutateur accessible via GET.

---

## 2. Matrice globale (32 endpoints)

| # | Endpoint | Stack | Cible Laravel (Phase 1) | Méthode | Auth | Perm. | Entrée | Succès | Erreurs principales | Consommateurs front |
|---|---|---|---|---|---|---|---|---|---|---|
| 1 | `api/accept_invite.php` | **L** | `InvitationController::accept` | ANY (POST en prod) | Oui (sinon `userId=null`) | aucune | form-urlencoded `code` | `{success:true, server_id}` | `{success:false,error:"Données manquantes."}`, `"Invitation invalide."` | `invitation.html` |
| 2 | `api/auth.php` | P/K hybride | n/a (sera supprimé) | ANY | Oui | — | aucune | *(body vide, 200)* | `401 {success:false,error:"Non authentifié"}` | **backend only** (inclus par `get_profile.php`, `update_profile.php`) |
| 3 | `api/ban_user.php` | **P** | `AdminUserController::banUser` | ANY | Oui | **P1** | JSON `user_id` | `{success:true}` | `{success:false,error:"Non authentifié"\|"Accès refusé : réservé aux P1"\|"user_id invalide"\|"Erreur DB : …"}` (tous en 200) | `admin.html` |
| 4 | `api/check_auth.php` | **P** | `AuthController::status` | ANY | Non | — | aucune | `{logged_in:true, username}` | `{logged_in:false}` | `index.html`, `auth_check.js` |
| 5 | `api/create_channel.php` | **L** | `ChannelController::create` | **POST strict** | Oui | P2/P3 (ou P1) | JSON `server_id,name` | `201 {success:true, channel_id}` | `400,403,405` | `serveur.js` |
| 6 | `api/create_invite.php` | **L** | `InvitationController::create` | ANY | Oui | membre serveur | form-urlencoded `server_id` | `{success:true, invite_url}` | `{success:false,error:…}` | `serveur.js` |
| 7 | `api/create_server.php` | **L** | `ServerController::create` | **POST strict** | Oui | — | JSON `nom` OU `name` | `201 {success:true, server_id}` | `400,405,500` | `serveurs.html` |
| 8 | `api/delete_message.php` | **K** | `MessageController::delete` | ANY | Oui | P2/P3 ou P1 | JSON `message_id` | `{success:true}` | non-auth, message introuvable, permission | `serveur.js` |
| 9 | `api/get_all_users.php` | **K** | `AdminUserController::listUsers` | ANY | Oui | **P1** | aucune | `{success:true, users:[{id,username,email,created_at,permission_level}]}` | non-auth, non-P1, DB | `admin.html` |
| 10 | `api/get_channels.php` | **L** | `ChannelController::index` | ANY (GET en prod) | Oui | membre serveur | query `server_id` | `{success:true, channels:[{id,name}]}` | `400,403` | `serveur.js` |
| 11 | `api/get_dm_messages.php` | **K** | `DmController::messages` | ANY (GET) | Oui | participant | query `conversation_id` | `{success:true, messages:[…], recipient:{id,username,avatar_url}}` | `400,403,404,500` | `dm.html` |
| 12 | `api/get_dm_notifications.php` | **K** | `DmController::notifications` | ANY (GET) | Oui | — | aucune | `{success:true, unread_conversations:[…]}` | `500` | `serveur.js` |
| 13 | `api/get_messages.php` | **L** | `MessageController::index` | ANY (GET) | Oui | membre serveur | query `channel_id` | `{success:true, messages:[{id,content,created_at,username,user_id,avatar_url}]}` | `400,403` | `serveur.js` |
| 14 | `api/get_my_server_role.php` | **K** | `RoleModerationController::getMyServerRole` | ANY (GET) | Oui | — | query `server_id` | `{success:true, role:"P1"\|"P2"\|"P3"\|"member"\|null}` | renvoie `role:null` sur `server_id` invalide (**pas d'erreur**) | `serveur.js` |
| 15 | `api/get_profile.php` | **P** | `AccountController::show` | ANY (GET) | Oui | — | aucune | `{success:true, profile:{username,email,bio,avatar_url,status,is_p1}}` | `401`, profil introuvable, `500` avec `details` | `accueil.html` |
| 16 | `api/get_server_name.php` | **L** | `ServerController::showName` | ANY (GET) | Oui | — (**pas** de check membre) | query `id` | `{success:true, name}` | `400,404` | `serveur.js` |
| 17 | `api/get_servers.php` | **L** | `ServerController::index` | ANY (GET) | Oui | — | aucune | `{success:true, servers:[{id,name}]}` | aucune métier | `serveurs.html`, `serveur.js` |
| 18 | `api/get_user_profile.php` | **P** | `AccountController::showUser` | ANY (GET) | Oui | — | query `user_id` | `{success:true, user:{id,username,avatar_url,bio,status}}` | `user_id invalide`, user introuvable, DB (tous en 200) | `serveur.js` |
| 19 | `api/get_user_servers.php` | **K** | `AdminUserController::listUserServers` | ANY (GET) | Oui | **P1** | query `user_id` | `{success:true, servers:[{id,name}]}` | `400`, non-P1, DB | `admin.html` |
| 20 | `api/get_users_in_server.php` | **K** | `RoleModerationController::listUsersInServer` | ANY (GET) | Oui | membre serveur | query `server_id` | `{success:true, users:[{id,username,role}]}` | `400,403` | `serveur.js` |
| 21 | `api/health.php` | **P** triviale | n/a (peut rester) | ANY | Non | — | aucune | `{success:true, status:"ok"}` | aucune | `status.html`, `index.html` |
| 22 | `api/kick_member.php` | **K** | `RoleModerationController::kickMember` | ANY | Oui | P2 ou P1 | JSON `target_user_id, server_id` | `{success:true}` | non-auth, permission, tentative kick P2 par non-P1 | `serveur.js` |
| 23 | `api/login.php` | **P** | `AuthController::login` | **POST strict** | Non | — | JSON `username, password` | `{success:true, user_id}` (crée session) | `400,401,405` | `index.js` |
| 24 | `api/logout.php` | **P** | `AuthController::logout` | ANY | Non | — | aucune | **`302 Location: /index.html`** (pas de JSON) | n/a | `index.html`, `serveur.js` |
| 25 | `api/register.php` | **K** | `AuthController::register` | **POST strict** | Non | — | JSON `username, email, password` | `201 {success:true}` (crée session) | `400,405,409,500` | `index.js` |
| 26 | `api/send_dm.php` | **K** | `DmController::send` | **POST strict** | Oui | participant | JSON `conversation_id, content` | `201 {success:true, message_id}` | `400,403,405,500` | `dm.html` |
| 27 | `api/send_message.php` | **L** | `MessageController::create` | **POST strict** | Oui | (⚠ pas de check membre) | JSON `channel_id, content` | `201 {success:true, message_id}` | `400,404,405,500` | `serveur.js` |
| 28 | `api/set_member_role.php` | **K** | `RoleModerationController::setMemberRole` | ANY | Oui | P2 ou P1 | JSON `target_user_id, server_id, new_role` (`P2\|P3\|member`) | `{success:true}` | `"Rôle invalide"`, permission | `serveur.js`, `admin.html` |
| 29 | `api/start_dm.php` | **K** | `DmController::start` | **POST strict** | Oui | — | JSON `other_user_id` | `{success:true, conversation_id, status:"exists"\|"created"}` | `400,405,500` | `serveur.js` |
| 30 | `api/update_account.php` | **K** | `AccountController::update` | ANY | Oui | — | JSON `username?, email?, password?, current_password?` | `{success:true}` | `200 success:false` sur non-auth, mdp actuel requis, etc. (`500` avec `debug` exposé) | `accueil.html` |
| 31 | `api/update_profile.php` | **P** | `AccountController::updateProfile` | ANY | Oui (via include `auth.php`) | — | JSON `bio, avatar_url, status` | `{success:true}` *(implicite, pas de retour explicite)* | silencieux si session absente | `accueil.html` |
| 32 | `/invite.php` (**racine**) | **P** | `InvitationController::resolveCode` | ANY (GET) | Oui | — | query `code` | `{success:true, server_id, server_name}` | `{success:false, error:"Utilisateur non connecté ou lien invalide."\|"Lien invalide."}` | `invitation.html` |

---

## 3. Répartition des stacks

| Stack | Nombre | Endpoints |
|---|---|---|
| **L** — Laravel bridge | 9 | accept_invite, create_channel, create_invite, create_server, get_channels, get_messages, get_server_name, get_servers, send_message |
| **K** — ApiKernel (MVC `app/*`) | 13 | delete_message, get_all_users, get_dm_messages, get_dm_notifications, get_my_server_role, get_user_servers, get_users_in_server, kick_member, register, send_dm, set_member_role, start_dm, update_account |
| **P** — Procédural | 10 (dont `invite.php` racine) | auth, ban_user, check_auth, get_profile, get_user_profile, health, login, logout, update_profile, invite |

Note : `auth.php` est compté dans P mais c'est un **include partagé** (pas un endpoint frontend direct), et `health.php` est une P triviale sans SQL.

---

## 4. Endpoints déjà candidats "Laravel-first"

Critères : stack **L** actuelle ET controller Laravel fonctionnel ET test contractuel facilement scriptable.

- `api/accept_invite.php`
- `api/create_channel.php`
- `api/create_invite.php`
- `api/create_server.php`
- `api/get_channels.php`
- `api/get_messages.php`
- `api/get_server_name.php`
- `api/get_servers.php`
- `api/send_message.php`

Sur ce périmètre, la Phase 1 (unification du point d'entrée via routing Laravel) ne présente aucun risque de régression métier : le code exécuté est déjà celui de Laravel, seul le chemin de bootstrap changera.

---

## 5. Endpoints à NE PAS toucher maintenant (Phase 0 → Phase 2 plus tard)

### 5.1 Zone auth/session (R2 — perte de session)
- `api/login.php` — crée `$_SESSION['user_id']` utilisé par **tous** les autres endpoints.
- `api/logout.php` — `session_destroy()` + redirection HTML. Toucher avant stabilisation = déconnexion massive.
- `api/check_auth.php` — consommé par `auth_check.js` chargé sur quasi toutes les pages.
- `api/auth.php` — include partagé par `get_profile.php` et `update_profile.php` (un renommage casse tout en cascade).

### 5.2 Zone destructive non transactionnelle (R3)
- `api/ban_user.php` — 5 `DELETE` séquentiels (`server_members`, `messages`, `profiles`, `global_permissions`, `users`) **sans transaction**. Le jumeau dans `laravel/routes/api.php:170-187` est aussi non transactionnel. **Toute intervention doit être précédée par l'encapsulation en `DB::transaction`**, hors périmètre Phase 0.

### 5.3 Zone à contrat ambigu (fige-avant-bouge)
- `api/get_my_server_role.php` — renvoie `role:null` sans erreur sur `server_id` invalide. Comportement exploité côté front, à figer tel quel.
- `api/create_server.php` — accepte `nom` OU `name`. Variante de nommage à documenter mais **pas** à uniformiser.
- `api/set_member_role.php` — `admin.html` envoie `new_role:'P1'` qui est rejeté par le backend (`"Rôle invalide"`). C'est un bug latent, mais **le front en dépend**. À figer, pas à corriger.
- `api/update_profile.php` — réponse implicite (pas de `success:true` explicite retourné). Contrat à préserver.

---

## 6. Points dangereux / incohérences critiques relevées

| # | Gravité | Endpoint | Incohérence | Action Phase 0 |
|---|---|---|---|---|
| D1 | **Haute** | `ban_user.php` + route `/ban_user.php` dans `laravel/routes/api.php` | 5 DELETE non-transactionnels → état incohérent possible sur échec partiel | Documenter (fait), **ne pas corriger** |
| D2 | **Haute** | `send_message.php` | Vérifie l'existence du channel mais **pas l'appartenance** utilisateur au serveur → n'importe quel user authentifié peut poster dans n'importe quel channel s'il connaît l'ID | Documenter dans contract test comme invariant actuel |
| D3 | **Haute** | `get_server_name.php` | Exige session mais ne vérifie pas l'appartenance au serveur demandé | Documenter comme invariant actuel |
| D4 | Moyenne | `update_account.php` | Expose `debug` dans les réponses d'erreur `500` | Ne pas corriger ; figer |
| D5 | Moyenne | `ban_user.php` | Expose le message SQL brut (`Erreur DB : …`) | Ne pas corriger ; figer |
| D6 | Moyenne | `Route::any` sur `delete_message`, `set_member_role`, `kick_member`, `ban_user` (côté Laravel) + équivalent legacy côté `api/*.php` | Mutateurs accessibles en GET → CSRF possible | Phase 4 ; documenter |
| D7 | Moyenne | Divergence réelle entre `app/Repositories/ServerRepository.php` (PDO) et `laravel/app/Repositories/ServerRepository.php` (Query Builder) | Deux implémentations pour le même nom logique → risque de divergence comportementale | Documenter dans `contract-test-plan.md` |
| D8 | Basse | `logout.php` renvoie un `Location:` HTML pendant que tous les autres endpoints sont JSON | Rupture de contrat côté consommateurs scriptés | À préserver en l'état jusqu'à Phase 4 |
| D9 | Basse | `check_auth.php` n'utilise pas la clé `success` (utilise `logged_in`) | Shape non homogène | À préserver |
| D10 | Critique | Migration Laravel `0001_01_01_000000_create_users_table.php` incompatible avec `biscord_db.sql` (colonnes `name,password` vs `username,password_hash`) | **Bloquant Phase 1** | Déjà isolé par `biscord_db_tests` ; documenter comme prérequis Phase 1 |

---

## 7. Prérequis avant Phase 1

1. **[Tooling]** Suite de tests contractuels exécutable contre la DB de test `biscord_db_tests` (cf. `contract-test-plan.md`).
2. **[Neutralisation D10]** Remplacer la migration Laravel `create_users_table` par une baseline alignée sur `biscord_db.sql` (voir plan de migration DB §4 du document de stratégie). **N'exécuter aucun `php artisan migrate` en prod** tant que cela n'est pas fait.
3. **[Inventaire des consommateurs]** Aucun `fetch()` frontend ne doit référencer un endpoint absent de la matrice ci-dessus. Grep effectué ; la liste est exhaustive.
4. **[Snapshot répertoires métier dupliqués]** `app/*` et `laravel/app/*` : un diff à plat est à archiver avant toute action Phase 2.
5. **[Doc]** Ce fichier + `contract-test-plan.md` commités sur `claude/refactor-business-logic-Gk8WL` — **aucun code runtime modifié**.

---

## 8. Légende finale de cadre d'exécution Phase 0

- Aucune route publique modifiée.
- Aucun payload JSON modifié.
- Aucune bascule frontend vers Laravel.
- Aucune rewrite serveur.
- Aucune modification de la DB réelle (`biscord_db_tests` isolée).
- Les seuls fichiers touchés pour Phase 0 sont les documents sous `docs/mvc-migration/` (dont ce fichier).
