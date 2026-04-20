# Slice MVC — Rôles & Modération (`get_my_server_role` / `get_users_in_server` / `set_member_role` / `kick_member`)

_Date: 2026-04-18_

## Objectif du slice
Clarifier et migrer la couche rôles/modération dans des couches backend propres (**PermissionService + ModerationService + repositories**), en gardant les endpoints legacy comme **adaptateurs HTTP fins**, sans changer les règles métier existantes.

---

## 1) Règles actuelles conservées à l’identique

### `GET /api/get_my_server_role.php`
- Session requise (`requireAuthUserId()`), sinon `401`.
- Priorité admin globale:
  - si l’utilisateur est dans `global_permissions` → rôle retourné: `P1`.
- Sinon: lecture de `server_members.role` sur `(user_id, server_id)`.
- Contrat conservé:
  - réponse toujours `success:true` avec `role` (`P1|P2|P3|member|null`).
  - `server_id` non numérique/absent continue à aboutir à `role:null` (pas d’erreur HTTP).

### `GET /api/get_users_in_server.php`
- Session requise.
- `server_id` doit être `>0`, sinon `400 Requête invalide`.
- Accès: l’acteur doit être **membre du serveur** (`server_members`), sinon `403 Accès refusé`.
- Succès:
  - liste des membres du serveur,
  - rôle effectif calculé: `P1` si présent dans `global_permissions`, sinon `server_members.role`.

### `POST /api/set_member_role.php`
- Session requise.
- JSON attendu (`getJsonInput`), sinon `400 JSON invalide`.
- `new_role` autorisé uniquement: `P2|P3|member`.
- Autorisation: `P2` serveur ou `P1` global.
- Action: `UPDATE server_members` sur `(target_user_id, server_id)`.
- Contrat conservé:
  - pas de vérification explicite de l’existence de la cible,
  - réponse `success:true` même si aucune ligne n’est modifiée.

### `POST /api/kick_member.php`
- Session requise.
- JSON attendu.
- Autorisation: `P2` serveur ou `P1` global.
- Règle spécifique conservée:
  - si cible est `P2`, seul `P1` peut kick.
- Action: `DELETE FROM server_members`.
- Contrat conservé:
  - pas de vérification explicite de cible absente/hors serveur,
  - `success:true` possible même si aucune suppression effective.

---

## 2) Répartition MVC introduite

### Endpoint adapters
- `api/get_my_server_role.php`
- `api/get_users_in_server.php`
- `api/set_member_role.php`
- `api/kick_member.php`

Ils ne gardent que:
- adaptation HTTP (session, query/json parsing),
- délégation vers un contrôleur métier,
- sérialisation via `respondFromController()`.

### Controller
- `App\Controllers\RoleModerationController`
- Mapping des erreurs:
  - `InvalidArgumentException` → `400`
  - `DomainException` → `403`
  - `PDOException` → `500 Erreur serveur`

### Services
- `App\Services\PermissionService`
  - centralise `isP1`, `getServerRole`, `hasPermission`.
- `App\Services\ModerationService`
  - orchestre les cas d’usage:
    - lecture rôle courant,
    - liste utilisateurs serveur,
    - changement de rôle,
    - kick membre.

### Repositories
- `App\Repositories\GlobalPermissionRepository` (nouveau)
  - SQL `global_permissions`.
- `App\Repositories\ServerMemberRepository` (enrichi)
  - SQL `server_members` + listing utilisateurs/roles effectifs + mutations de rôle/modération.

---

## 3) Incohérences actuelles documentées (non corrigées volontairement)

1. **Incohérence admin UI vs API (`set_member_role`)**
   - Le front admin tente des promotions `P1` via `set_member_role`.
   - L’API n’accepte que `P2|P3|member` (schéma DB `server_members.role` identique).
   - Cette divergence reste explicitement inchangée.

2. **`get_users_in_server` n’accorde pas de bypass P1**
   - Un P1 non membre d’un serveur reste refusé (`403`).
   - D’autres endpoints traitent P1 comme bypass global.
   - Incohérence conservée pour éviter toute régression d’accès.

3. **Résultats “success:true” sur mutation no-op**
   - `set_member_role` / `kick_member` restent silencieux si la cible n’existe pas dans le serveur.

4. **Validation hétérogène des entrées**
   - `get_my_server_role` tolère `server_id` absent/invalide avec `role:null`.
   - `get_users_in_server` exige `server_id > 0`.

5. **Rôle effectif ambigu pendant la modération**
   - `get_users_in_server` affiche `P1` comme rôle effectif.
   - `set_member_role` / `kick_member` opèrent sur `server_members.role` et règles locales.

---

## 4) Préparation Laravel Policies / Gates

Le découpage actuel prépare une migration naturelle vers Policies/Gates:
- `PermissionService::hasPermission()` pourra devenir une Gate transversale (`before` avec bypass P1).
- `ModerationService` expose des actions proches de Policies:
  - `viewServerMembers`,
  - `updateMemberRole`,
  - `kickMember`.
- Les endpoints sont déjà des adaptateurs minces; une bascule vers contrôleurs Laravel + `authorize()` restera locale.
- La séparation SQL dans repositories facilite une migration vers Eloquent sans changer les règles de décision.

---

## 5) Implémentation réalisée dans ce slice

- Ajout `GlobalPermissionRepository`.
- Enrichissement `ServerMemberRepository` (list users + update role + remove member).
- Ajout `PermissionService`.
- Ajout `ModerationService`.
- Ajout `RoleModerationController`.
- Câblage dans `ApiKernel`.
- Migration des 4 endpoints ciblés vers ce flux MVC.
- Refactor de `api/permissions.php` pour déléguer à `PermissionService` tout en conservant l’API helper historique (`isP1`, `getServerRole`, `hasPermission`).
