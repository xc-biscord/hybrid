# Contract Test Plan — Phase 0

_Objectif : figer le comportement HTTP **observable** de chaque endpoint `/api/*.php` et `/invite.php` avant toute bascule d'architecture, sans modifier le runtime._

---

## 1. Principes directeurs

1. **Test de contrat, pas de test unitaire.** On vérifie la *forme* et le *code HTTP* observés en boîte noire, pas l'implémentation.
2. **Fige l'existant, même imparfait.** Les incohérences (shape non homogène, 200 sur erreur métier, absence de `success`, redirect HTML de `logout`) sont des *invariants à préserver*.
3. **Isolé de la prod.** Tous les tests tapent uniquement sur `biscord_db_tests` (déjà configuré dans `laravel/.env`).
4. **Reproductible.** Seed dédié par test ou fixture partagée ; jamais de dépendance à des IDs inventés.
5. **Non destructif.** Les tests nettoient après eux (transactions rollback OU truncate après suite).
6. **Aucune modification de code applicatif** n'est requise pour mettre en place cette suite.

---

## 2. Stack de tests proposée

### Choix retenu : **PHPUnit Laravel (`laravel/tests/Feature`)**

Raisons :
- `laravel/phpunit.xml` existe déjà.
- Permet d'utiliser `$this->postJson(...)`, `$this->get(...)` via un client HTTP de test Laravel.
- **MAIS** : les endpoints actuels ne passent pas par le routeur Laravel HTTP → le client Laravel ne les atteindra pas.
  → Solution : les tests contractuels ciblent le **serveur PHP-FPM local** hébergeant `api/*.php` via un `HttpClient` (pas le test runner Laravel interne).

### Harness concret

```
laravel/tests/
  Contract/
    Support/
      BiscordHttpClient.php        // cURL wrapper autour de http://localhost:8000
      TestDatabaseSeeder.php       // seed idempotent dans biscord_db_tests
      SessionHelper.php            // gère le cookie PHPSESSID entre requêtes
    AcceptInviteContractTest.php
    AuthContractTest.php
    BanUserContractTest.php
    ...                            // 1 fichier par endpoint
```

### Pré-requis d'exécution
- `php -S localhost:8000 -t .` lancé depuis la racine du repo (sert `api/*.php` **et** `invite.php`).
- `biscord_db_tests` provisionnée avec le schéma `biscord_db.sql`.
- Variable `CONTRACT_TEST_BASE_URL=http://localhost:8000` pour injection dans le client.
- `biscord_db_tests` vidée + reseedée avant chaque classe de test (hook `setUpBeforeClass`).

### Alternative légère (si PHPUnit écarté)
Un répertoire `tests/contract/` avec des scripts `bruno`/`httpie`/`k6` en mode *record-replay*. Acceptable mais moins intégré.

---

## 3. Fixtures minimales

Le seeder doit provisionner **un état déterministe** pour la suite :

| Fixture | Contenu | Justification |
|---|---|---|
| `user_alice` | `id=1001`, username/email/password connus, pas de P1 | User standard |
| `user_bob` | `id=1002`, user standard | Cible DM / kick / ban |
| `user_admin` | `id=1003`, `global_permissions.permission_level='P1'` | Tests admin |
| `user_mod` | `id=1004`, role `P2` sur `server_1` | Tests moderation |
| `server_1` | owner = `user_alice`, contient `channel_1` | Tests serveurs/channels/messages |
| `channel_1` | dans `server_1` | Tests messages |
| `server_members` | alice P3, bob member, mod P2, admin non-membre | Couvre matrice permissions |
| `invitation_valid` | `code='INV-OK'`, `server_id=server_1` | accept_invite / invite.php OK |
| `dm_conv_alice_bob` | `id=2001`, user1=alice, user2=bob | DM tests |
| `message_seed` | id connus dans `channel_1` par alice et bob | delete_message |

Les IDs au-dessus de 1000 évitent toute collision avec d'éventuels auto-incréments en cours.

---

## 4. Matrice des cas de test par endpoint

Légende cases : `✓ requis` · `—` non applicable

| # | Endpoint | Succès 200/201 | 401 non-auth | 403 permission | 400 payload invalide | 404 ressource | 405 méthode | Shape JSON verrouillée | Header particulier |
|---|---|---|---|---|---|---|---|---|---|
| 1 | accept_invite | ✓ `{success,server_id}` | ✓ (`code` + pas de session) | — | ✓ code vide | ✓ code inconnu | — | ✓ | — |
| 2 | auth | ✓ body vide 200 | ✓ 401 JSON | — | — | — | — | ✓ (body vide) | `Content-Type: application/json` |
| 3 | **ban_user** | ✓ `{success:true}` | ✓ (200 `{success:false}`) | ✓ non-P1 (200) | ✓ `user_id` invalide | — | — | ✓ | figer 200 sur toutes les erreurs |
| 4 | check_auth | ✓ `{logged_in:true,username}` | ✓ `{logged_in:false}` | — | — | — | — | ✓ **pas de `success`** | — |
| 5 | create_channel | ✓ 201 `{success,channel_id}` | ✓ 401 | ✓ 403 | ✓ 400 name vide | — | ✓ 405 GET | ✓ | — |
| 6 | create_invite | ✓ `{success,invite_url}` | ✓ 200/401 selon auth | ✓ non-membre | ✓ `server_id` invalide | — | — | ✓ | — |
| 7 | create_server | ✓ 201 `{success,server_id}` ×2 (`nom` ET `name`) | ✓ 401 | — | ✓ 400 nom vide | — | ✓ 405 GET | ✓ | — |
| 8 | delete_message | ✓ `{success:true}` | ✓ 401 | ✓ ni propriétaire ni P2/P3 | ✓ `message_id` absent | ✓ message inconnu | — | ✓ | — |
| 9 | get_all_users | ✓ `{success,users:[…]}` | ✓ 401 | ✓ non-P1 | — | — | — | ✓ colonnes : `id,username,email,created_at,permission_level` | — |
| 10 | get_channels | ✓ `{success,channels}` | ✓ 401 | ✓ non-membre | ✓ server_id=0 | — | — | ✓ | — |
| 11 | get_dm_messages | ✓ `{success,messages,recipient}` | ✓ 401 | ✓ non-participant | ✓ conv_id=0 | ✓ recipient inconnu | — | ✓ shape `recipient:{id,username,avatar_url}` | — |
| 12 | get_dm_notifications | ✓ `{success,unread_conversations}` | ✓ 401 | — | — | — | — | ✓ | — |
| 13 | get_messages | ✓ `{success,messages[…avatar_url…]}` | ✓ 401 | ✓ non-membre | ✓ channel_id=0 | — | — | ✓ | — |
| 14 | get_my_server_role | ✓ `{success,role:"P3"}` et `{success,role:null}` sur invalide | ✓ 401 | — | **ne pas** tester comme erreur : `server_id` invalide → `success:true,role:null` | — | — | ✓ invariant documenté | — |
| 15 | get_profile | ✓ `{success,profile:{…,is_p1}}` | ✓ 401 | — | — | — | — | ✓ | — |
| 16 | get_server_name | ✓ `{success,name}` | ✓ 401 | — | ✓ 400 id absent | ✓ 404 id inconnu | — | ✓ | **ne pas** vérifier membre-ship (invariant D3) |
| 17 | get_servers | ✓ `{success,servers}` | ✓ 401 | — | — | — | — | ✓ | — |
| 18 | get_user_profile | ✓ `{success,user:{…}}` | ✓ 200 `success:false` | — | ✓ 200 `success:false` sur user_id invalide | ✓ 200 `success:false` | — | ✓ invariant : **toujours 200** | — |
| 19 | get_user_servers | ✓ `{success,servers}` | ✓ 401 | ✓ non-P1 | ✓ 400 user_id manquant | — | — | ✓ | — |
| 20 | get_users_in_server | ✓ `{success,users:[{id,username,role}]}` | ✓ 401 | ✓ non-membre | ✓ 400 | — | — | ✓ | — |
| 21 | health | ✓ `{success:true,status:"ok"}` | — | — | — | — | — | ✓ | — |
| 22 | kick_member | ✓ `{success:true}` | ✓ 401 | ✓ tentative kick P2 par non-P1 | — | — | — | ✓ | — |
| 23 | **login** | ✓ `{success,user_id}` + crée session (cookie) | — | — | ✓ 400 identifiants vides | — | ✓ 405 GET | ✓ | ✓ cookie `PHPSESSID` set |
| 24 | **logout** | ✓ **302 `Location:/index.html`** | — | — | — | — | — | ✓ **pas de JSON** | ✓ cookie session invalidée |
| 25 | register | ✓ 201 `{success:true}` + session | — | — | ✓ 400/409 | — | ✓ 405 | ✓ | ✓ cookie |
| 26 | send_dm | ✓ 201 `{success,message_id}` | ✓ 401 | ✓ non-participant | ✓ 400 | — | ✓ 405 | ✓ | — |
| 27 | send_message | ✓ 201 `{success,message_id}` | ✓ 401 | **invariant D2** : pas de 403 si user authentifié quel que soit le channel | ✓ 400 | ✓ 404 channel inconnu | ✓ 405 | ✓ | — |
| 28 | set_member_role | ✓ `{success:true}` pour P2/P3/member | ✓ 401 | ✓ non-P2/P1 | ✓ `"Rôle invalide"` pour `new_role:"P1"` (**invariant du bug admin.html**) | — | — | ✓ | — |
| 29 | start_dm | ✓ `{success,conversation_id,status:"created"}` 1ère fois, `"exists"` 2e | ✓ 401 | — | ✓ 400 other_user_id invalide | — | ✓ 405 | ✓ | — |
| 30 | update_account | ✓ `{success:true}` | ✓ 200 `{success:false}` (invariant) | — | ✓ mdp actuel requis/faux (200) | — | — | ✓ ; vérifier **absence** de `debug` dans un cas standard de succès | — |
| 31 | update_profile | ✓ 200 (body implicite) | — (silencieux) | — | — | — | — | ✓ invariant : réponse minimale | — |
| 32 | invite.php (racine) | ✓ `{success,server_id,server_name}` | ✓ 200 `{success:false}` | — | ✓ code absent | ✓ code inconnu | — | ✓ | — |

### Nombre approximatif de tests
≈ **150 cas** (5 par endpoint en moyenne) pour la première passe ; extensible par la suite.

---

## 5. Invariants à préserver explicitement (anti-régression)

Ces tests **doivent échouer** si un contributeur "corrige" le comportement sans discussion préalable :

- **I1** `check_auth.php` : shape `{logged_in:bool}` **sans** clé `success`.
- **I2** `logout.php` : `302 Location: /index.html`, **pas** de body JSON.
- **I3** `ban_user.php` : renvoie `200` avec `success:false` sur toutes les erreurs (pas de 401/403/500).
- **I4** `get_my_server_role.php` : `{success:true, role:null}` sur `server_id` invalide (pas d'erreur).
- **I5** `get_user_profile.php` : toujours `200`, erreurs dans le body.
- **I6** `create_server.php` : accepte `nom` **et** `name` — les deux doivent rester fonctionnels.
- **I7** `set_member_role.php` : `new_role:'P1'` est rejeté avec `"Rôle invalide"` — ce n'est PAS à corriger.
- **I8** `update_profile.php` : pas de shape JSON stricte attendue en succès.
- **I9** `send_message.php` : ne vérifie pas l'appartenance au serveur — figer ce trou (D2) pour pouvoir détecter une *vraie* régression ultérieure.
- **I10** `get_server_name.php` : pas de check membership (D3) — figer.

---

## 6. Ce qui N'EST PAS couvert par cette suite

- Tests de performance / latence.
- Tests de concurrence (lock, race conditions sur DM, sur `ban_user`).
- Validation de l'intégrité post-`ban_user` (documentée dans D1 comme non transactionnelle).
- Tests du schéma DB Laravel (cf. prérequis D10 — relève du plan migration DB).
- Tests des deux implémentations dupliquées `app/*` vs `laravel/app/*` **séparément** — on ne teste que l'endpoint réel tel qu'exposé. La couverture de la divergence D7 sera obtenue indirectement : si on bascule le routing en Phase 1 et que la suite échoue, c'est que la duplication avait divergé.

---

## 7. Ordre d'écriture des tests (priorité)

1. **Batch "Laravel-ready"** (9 endpoints L) — production la plus rapide, valide directement la Phase 1.
2. **Batch "Admin & moderation"** (ban_user, set_member_role, kick_member, get_all_users, get_user_servers) — haute sensibilité.
3. **Batch "Auth & session"** (login, logout, check_auth, auth, register, update_account) — doit être stable avant de toucher la phase 2 session.
4. **Batch "Profile"** (get_profile, get_user_profile, update_profile).
5. **Batch "DM"** (start_dm, send_dm, get_dm_messages, get_dm_notifications).
6. **Batch "Trivial"** (health, invite.php racine).

---

## 8. Critères de sortie Phase 0

La Phase 0 est considérée terminée quand :

1. Ce document + `endpoint-source-of-truth-matrix.md` sont mergés sur `claude/refactor-business-logic-Gk8WL`.
2. Tous les invariants §5 sont encodés dans au moins un test automatisé.
3. La suite s'exécute entièrement sur `biscord_db_tests` **sans toucher** la base réelle.
4. Au moins un run CI (ou manuel documenté) montre `all green` sur la baseline.
5. La migration Laravel `create_users_table` divergente (D10) a été **explicitement identifiée** comme bloquante pour Phase 1 et son remplacement est planifié.

Tant que ces 5 critères ne sont pas réunis : **no-go sur Phase 1**.
