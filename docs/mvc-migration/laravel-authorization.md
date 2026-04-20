# Laravel Authorization — Gates & Policies

_Date: 2026-04-18_

## Objectif

Introduire les primitives Laravel d'autorisation (Gates & Policies) **en
couche d'intégration** au-dessus de la logique métier déjà encapsulée
dans `PermissionService` et `ModerationService`. Aucun comportement
métier n'est déplacé ni réécrit : les services restent la source unique
de vérité, les policies et gates ne sont que des adaptateurs.

### Contraintes respectées

- `PermissionService` et `ModerationService` ne sont **pas modifiés**.
- Les règles de permission (`P1 | P2 | P3 | member`, bypass P1,
  « seul P1 peut kick un P2 »…) sont **inchangées**.
- Aucun contrat d'endpoint ni aucun payload n'est modifié.
- Les contrôleurs existants continuent d'appeler les services
  directement. L'adoption des policies se fait **progressivement**,
  endpoint par endpoint, lors d'une refacto ultérieure.

---

## 1) Domaines identifiés

| Domaine                 | Source de vérité                                                    | Adaptateur Laravel       |
| ----------------------- | ------------------------------------------------------------------- | ------------------------ |
| Admin global (P1)       | `PermissionService::isP1()`                                         | Gate `is-p1`             |
| Serveur (vue / rôle)    | `PermissionService::hasPermission()` + `ServerMemberRepository`     | `ServerPolicy`           |
| Channels                | `ChannelService::canCreateChannel()` (P2/P3 ou P1)                  | `ChannelPolicy`          |
| Messages                | `MessageService::canDeleteMessage()` + `userCanReadChannelMessages` | `MessagePolicy`          |
| Server members / modération | `ModerationService` (`listUsers`, `setMemberRole`, `kickMember`) | `ServerMemberPolicy`     |

---

## 2) Gates enregistrées

Toutes dans `app/Providers/AuthServiceProvider.php`.

### `is-p1`
```php
Gate::allows('is-p1'); // user authentifié
Gate::forUser($user)->allows('is-p1');
```
Délègue à `PermissionService::isP1((int) $user->id)`.

Remplace progressivement :
- le middleware `EnsureGlobalAdmin`
- les appels directs à `PermissionService::isP1()` dans les contrôleurs
  admin (ex. `AdminUserController`)
- les appels à `AdminMiddleware::isGlobalAdmin()` dans les services.

### `server.has-role`
```php
Gate::allows('server.has-role', [$serverId, ['P2', 'P3']]);
```
Délègue à `PermissionService::hasPermission()`. C'est l'équivalent
direct de l'API service ; à utiliser uniquement quand un besoin ad-hoc
ne rentre pas dans une policy dédiée (`ChannelPolicy`, `MessagePolicy`,
`ServerMemberPolicy`).

---

## 3) Policies enregistrées

Enregistrées explicitement via `Gate::policy(Model::class,
Policy::class)` dans `AuthServiceProvider::boot()` (pas d'auto-discovery
car `Server` et `ServerMember` ne sont pas Eloquent).

### `ServerPolicy` (mappée à `App\Models\Server`)

| Méthode                                      | Délégation / règle wrappée                                      |
| -------------------------------------------- | --------------------------------------------------------------- |
| `before($user, $ability)`                    | `PermissionService::isP1()` → bypass global P1                  |
| `view(User $user, Server $server)`           | `ServerMemberRepository::isMember($server->id, $user->id)`      |
| `hasRole(User $user, int $serverId, array)`  | `PermissionService::hasPermission()` (échappatoire générique)   |

### `ChannelPolicy`

| Méthode                                | Règle wrappée                                                      |
| -------------------------------------- | ------------------------------------------------------------------ |
| `viewAny(User $user, int $serverId)`   | `ServerMemberRepository::isMember()` (= `listChannelsForServer`)   |
| `create(User $user, int $serverId)`    | `PermissionService::hasPermission($uid, $sid, ['P2','P3'])` (= `ChannelService::canCreateChannel`) |

### `MessagePolicy`

| Méthode                                      | Règle wrappée                                                                 |
| -------------------------------------------- | ----------------------------------------------------------------------------- |
| `viewInChannel(User $user, int $channelId)`  | `MessageRepository::userCanReadChannelMessages()` (= `MessageService::listMessages`) |
| `deleteInServer(User $user, int $serverId)`  | `PermissionService::hasPermission($uid, $sid, ['P2','P3'])` (= `MessageService::canDeleteMessage`) |

### `ServerMemberPolicy`

| Méthode                                                      | Règle wrappée (service)                                                       |
| ------------------------------------------------------------ | ----------------------------------------------------------------------------- |
| `viewAny(User $user, int $serverId)`                         | `ServerMemberRepository::isMember()` — **pas de bypass P1**, miroir exact de `ModerationService::listUsersInServer` |
| `updateRole(User $user, int $serverId)`                      | `PermissionService::hasPermission($uid, $sid, ['P2'])` (= `ModerationService::setMemberRole` auth) |
| `kick(User $user, int $serverId, ?int $targetUserId = null)` | `hasPermission(['P2'])` **+** invariant « target P2 ⇒ acteur P1 » (= `ModerationService::kickMember` auth) |

#### Note sur `kick`
L'invariant « seul un P1 peut kick un P2 » est **conservé dans le
service**. La policy le ré-évalue uniquement si le caller fournit
`$targetUserId` — c'est une optimisation de court-circuit, jamais une
source de vérité. `ModerationService::kickMember` reste seul juge final
au moment de l'action.

---

## 4) Mapping avec l'ancien système

| Vérification legacy                                                      | Nouveau point d'entrée Laravel                                     |
| ------------------------------------------------------------------------ | ------------------------------------------------------------------ |
| `PermissionService::isP1($uid)`                                          | `Gate::allows('is-p1')`                                            |
| `PermissionService::hasPermission($uid, $sid, $roles)`                   | `Gate::allows('server.has-role', [$sid, $roles])` ou `ServerPolicy::hasRole` |
| `AdminMiddleware::isGlobalAdmin($uid)` (dans `ChannelService`, `MessageService`, `UserServerService`) | `Gate::allows('is-p1')`                      |
| `ChannelService::canCreateChannel()` (privée)                            | `ChannelPolicy::create`                                            |
| `MessageService::canDeleteMessage()` (privée)                            | `MessagePolicy::deleteInServer`                                    |
| `MessageRepository::userCanReadChannelMessages()`                        | `MessagePolicy::viewInChannel`                                     |
| `ModerationService::listUsersInServer` (précondition membership)          | `ServerMemberPolicy::viewAny`                                      |
| `ModerationService::setMemberRole` (précondition auth)                   | `ServerMemberPolicy::updateRole`                                   |
| `ModerationService::kickMember` (précondition auth + P2-target)          | `ServerMemberPolicy::kick`                                         |
| `EnsureGlobalAdmin` middleware                                           | `Gate::allows('is-p1')` + middleware `can:is-p1`                   |
| `ServerMemberRepository::isMember()` utilisé inline dans les services    | `ServerPolicy::view` / `ChannelPolicy::viewAny`                    |

---

## 5) Adoption côté contrôleur (pattern)

Les contrôleurs continuent de fonctionner à l'identique. Quand on veut
migrer un endpoint, on remplace un check inline par une policy :

```php
// Avant
if (!$this->permissionService->hasPermission($uid, $sid, ['P2', 'P3'])) {
    return $this->error('Permission refusée', 403);
}

// Après
if (!Gate::forUser(User::find($uid))->allows('create', [$serverPolicyArg, $sid])) {
    return $this->error('Permission refusée', 403);
}
```

La session courante utilisant `$_SESSION['user_id']` et non le guard
Laravel, tant que l'auth n'est pas câblée sur `Auth::`, les policies
s'invoquent via `Gate::forUser($user)`. Une fois l'auth session-Laravel
branchée, `$user->can('create', …)` et le middleware `can:` deviennent
disponibles automatiquement.

---

## 6) Checks encore 100 % legacy (non wrappés)

Ces vérifications ne sont **pas** couvertes par une policy/gate pour
l'instant. Elles restent internes aux services, volontairement :

- **DM (messages privés)** — `DmService::listConversationMessages` et
  `DmService::sendMessageFromPayload` utilisent
  `DmRepository::userHasConversationAccess` (appartenance à une
  conversation 1-1). Pas de rôles serveur, pas de bypass P1 ; à
  wrapper plus tard dans une éventuelle `DmConversationPolicy`.
- **Création de serveur** — `ServerService` ne fait aucun contrôle au-delà
  de « user authentifié ». Rien à wrapper aujourd'hui.
- **Comptes** — `AccountService` / `RegisterService` : logique
  d'identification, hors scope autorisation.
- **Invariants internes à `ModerationService`** — validation
  `VALID_MEMBER_ROLES` (`P2|P3|member`), détection du rôle cible pour
  le kick : restent dans le service. Les policies ne font que
  pré-filtrer.
- **Existence de ressource** — `MessageRepository::findMessageChannelAndServer`,
  `MessageRepository::channelExists`, etc. restent des vérifications
  d'existence, pas d'autorisation.

---

## 7) Stratégie de migration progressive

1. **Phase actuelle (ce commit).** Les policies/gates existent et sont
   enregistrées. Aucun contrôleur ne les utilise encore.
2. **Phase 1.** Les nouveaux endpoints Laravel utilisent `Gate::allows`
   / `$this->authorize()` directement. Les contrôleurs legacy sont
   inchangés.
3. **Phase 2.** Lors de la conversion des contrôleurs legacy vers des
   contrôleurs Laravel, les appels inline à `PermissionService` /
   `ModerationService` (côté auth uniquement) sont remplacés par des
   appels de policy. Les services restent inchangés.
4. **Phase 3.** Une fois l'auth session branchée sur le guard Laravel,
   utilisation idiomatique via `$request->user()->can(...)` et
   middleware `can:` dans les définitions de route.
5. **Phase 4 (optionnelle).** Les méthodes privées
   `canCreateChannel` / `canDeleteMessage` peuvent être supprimées au
   profit d'appels de policy depuis les services eux-mêmes, si l'on
   décide d'unifier. Aujourd'hui **elles sont laissées en place**
   (contrainte « ne pas réécrire »).

---

## 8) Fichiers créés

- `laravel/app/Policies/ServerPolicy.php`
- `laravel/app/Policies/ChannelPolicy.php`
- `laravel/app/Policies/MessagePolicy.php`
- `laravel/app/Policies/ServerMemberPolicy.php`
- `laravel/app/Providers/AuthServiceProvider.php`
- `laravel/bootstrap/providers.php` (ajout d'`AuthServiceProvider`)
