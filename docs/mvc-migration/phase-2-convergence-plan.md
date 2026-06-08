# Phase 2.0 — plan de convergence `app/*` vers `laravel/app/*`

Date : 2026-06-08.

## 1. Cadre et périmètre

Phase 2.0 prépare uniquement la convergence des doublons `app/*` vers `laravel/app/*` pour le périmètre déjà validé en Phase 1 limitée.

Rappels de validation :

- Phase 0 : **120 tests passed** ;
- Phase 1 limitée : validée sur serveur réel ;
- aucun runtime ne doit être modifié par ce document ;
- aucun endpoint sensible ne doit entrer dans le périmètre Phase 2.0 ;
- aucun rewrite global `/api/*.php` ne doit être activé.

Endpoints Phase 1 validés et couverts :

1. `/api/accept_invite.php`
2. `/api/create_channel.php`
3. `/api/create_invite.php`
4. `/api/create_server.php`
5. `/api/get_channels.php`
6. `/api/get_messages.php`
7. `/api/get_server_name.php`
8. `/api/get_servers.php`
9. `/api/send_message.php`

Familles couvertes : **servers**, **channels**, **messages**, **invitations**.

Familles explicitement hors périmètre : auth/session, compte/profil, administration/modération, DM, frontend, rewrite global.

## 2. Classes Laravel réellement utilisées par les 9 endpoints

### 2.1 Routage et bridge

| Endpoint | Entrée Laravel actuelle | Contrôleur appelé | Notes bridge / helpers |
|---|---|---|---|
| `/api/get_servers.php` | `Route::get('/get_servers.php', ...)` | `ServerController::index()` | Session legacy native via helper local `$requireLegacySession`. |
| `/api/get_server_name.php` | `Route::get('/get_server_name.php', ...)` | `ServerController::showName()` | Lecture query `id`, pas de contrôle membership dans le contrat validé. |
| `/api/get_channels.php` | `Route::get('/get_channels.php', ...)` | `ChannelController::index()` | Lecture query `server_id`, session legacy native. |
| `/api/create_server.php` | `LegacyBridgeController::handle()` | `ServerController::create()` | Validation méthode POST et JSON invalide dans `LegacyBridgeController`. |
| `/api/get_messages.php` | `LegacyBridgeController::handle()` | `MessageController::index()` | Auth native obligatoire dans `LegacyBridgeController`. |
| `/api/send_message.php` | `LegacyBridgeController::handle()` | `MessageController::create()` | Validation méthode POST et JSON invalide dans `LegacyBridgeController`. |
| `/api/create_invite.php` | `LegacyBridgeController::handle()` | `InvitationController::create()` | User/session optionnel pour conserver les invariants legacy. |
| `/api/accept_invite.php` | `LegacyBridgeController::handle()` | `InvitationController::accept()` | User/session optionnel pour conserver les invariants legacy. |
| `/api/create_channel.php` | `LegacyBridgeController::handle()` | `ChannelController::create()` | Validation méthode POST et JSON invalide dans `LegacyBridgeController`. |

Wrappers conservés côté `api/` : les 9 fichiers `api/*.php` existent toujours et appellent Laravel via `api/laravel_proxy.php` pour le périmètre validé.

### 2.2 Controllers Laravel

| Famille | Fichier Laravel | Méthodes utilisées | Statut |
|---|---|---|---|
| servers | `laravel/app/Http/Controllers/ServerController.php` | `index`, `showName`, `create` | Utilisé. |
| channels | `laravel/app/Http/Controllers/ChannelController.php` | `index`, `create` | Utilisé. |
| messages | `laravel/app/Http/Controllers/MessageController.php` | `index`, `create` | Utilisé. |
| invitations | `laravel/app/Http/Controllers/InvitationController.php` | `create`, `accept` | Utilisé. |
| bridge | `laravel/app/Http/Controllers/LegacyBridgeController.php` | `handle` + helpers privés | Utilisé par 6 endpoints du périmètre. |
| response helper | `laravel/app/Http/Controllers/BaseApiController.php` | `success`, `error` | Utilisé par les controllers ci-dessus. |

### 2.3 Services Laravel

| Famille | Fichier Laravel | Méthodes utilisées | Statut |
|---|---|---|---|
| servers | `laravel/app/Services/ServerService.php` | `createServerFromPayload`, `listUserServers`, `getServerById` | Utilisé. |
| channels | `laravel/app/Services/ChannelService.php` | `listChannelsForServer`, `createChannelFromPayload` | Utilisé. |
| messages | `laravel/app/Services/MessageService.php` | `listMessages`, `sendMessageFromPayload` | Utilisé. |
| invitations | `laravel/app/Services/InvitationService.php` | `createInvite`, `acceptInvite` | Utilisé. |
| permissions indirectes | `laravel/app/Middleware/AdminMiddleware.php` | `isGlobalAdmin` | Utilisé indirectement par channels/messages. |

### 2.4 Repositories Laravel

| Famille | Fichier Laravel | Méthodes utilisées | Statut |
|---|---|---|---|
| servers | `laravel/app/Repositories/ServerRepository.php` | `create`, `findByMemberUserId`, `find` | Utilisé. |
| servers/channels/messages | `laravel/app/Repositories/ServerMemberRepository.php` | `addMember`, `isMember`, `findRole` | Utilisé. |
| channels | `laravel/app/Repositories/ChannelRepository.php` | `findByServerId`, `create` | Utilisé. |
| messages | `laravel/app/Repositories/MessageRepository.php` | `userCanReadChannelMessages`, `findByChannelId`, `channelExists`, `create` | Utilisé. |
| invitations | `laravel/app/Repositories/InvitationRepository.php` | `findServerIdByCode`, `isUserMemberOfServer`, `addUserToServer`, `createInvitation` | Utilisé. |
| permissions indirectes | `laravel/app/Repositories/GlobalPermissionRepository.php` | `isGlobalAdmin` | Utilisé indirectement par `AdminMiddleware`. |

### 2.5 Models / validators / support

| Type | Fichier | Usage réel Phase 1 limitée | Note |
|---|---|---|---|
| model | `laravel/app/Models/Server.php` | Utilisé par `ServerRepository::find()` / `ServerService::getServerById()` / `ServerController::showName()` | Dans le périmètre. |
| validators Laravel | `laravel/app/Http/Requests/CreateServerRequest.php` | Non utilisé par les routes Phase 1 actuelles | À ne pas brancher implicitement. |
| validators Laravel | `laravel/app/Http/Requests/CreateChannelRequest.php` | Non utilisé par les routes Phase 1 actuelles | À ne pas brancher implicitement. |
| validators Laravel | `laravel/app/Http/Requests/SendMessageRequest.php` | Non utilisé par les routes Phase 1 actuelles | À ne pas brancher implicitement. |
| bridge PHP | `api/laravel_proxy.php` | Utilisé par les wrappers legacy des 9 endpoints | À conserver temporairement. |
| support legacy | `app/Support/ApiKernel.php`, `app/Support/Autoload.php` | Non appelé directement par les wrappers Laravel-ready actuels | À ne pas supprimer en Phase 2.0. |

## 3. Doublons correspondants côté `app/*`

### 3.1 Doublons directs trouvés

| Fichier legacy `app/*` | Correspondant Laravel | Famille |
|---|---|---|
| `app/Controllers/BaseApiController.php` | `laravel/app/Http/Controllers/BaseApiController.php` | response helper |
| `app/Controllers/ServerController.php` | `laravel/app/Http/Controllers/ServerController.php` | servers |
| `app/Controllers/ChannelController.php` | `laravel/app/Http/Controllers/ChannelController.php` | channels |
| `app/Controllers/MessageController.php` | `laravel/app/Http/Controllers/MessageController.php` | messages |
| `app/Services/ServerService.php` | `laravel/app/Services/ServerService.php` | servers |
| `app/Services/ChannelService.php` | `laravel/app/Services/ChannelService.php` | channels |
| `app/Services/MessageService.php` | `laravel/app/Services/MessageService.php` | messages |
| `app/Services/PermissionService.php` | `laravel/app/Services/PermissionService.php` | permissions indirectes |
| `app/Repositories/ServerRepository.php` | `laravel/app/Repositories/ServerRepository.php` | servers |
| `app/Repositories/ServerMemberRepository.php` | `laravel/app/Repositories/ServerMemberRepository.php` | servers/channels/messages |
| `app/Repositories/ChannelRepository.php` | `laravel/app/Repositories/ChannelRepository.php` | channels |
| `app/Repositories/MessageRepository.php` | `laravel/app/Repositories/MessageRepository.php` | messages |
| `app/Repositories/GlobalPermissionRepository.php` | `laravel/app/Repositories/GlobalPermissionRepository.php` | permissions indirectes |
| `app/Models/Server.php` | `laravel/app/Models/Server.php` | servers |
| `app/Middleware/AdminMiddleware.php` | `laravel/app/Middleware/AdminMiddleware.php` | permissions indirectes |

### 3.2 Classes uniquement Laravel dans le périmètre

| Fichier Laravel | Famille | Classement |
|---|---|---|
| `laravel/app/Http/Controllers/InvitationController.php` | invitations | uniquement Laravel |
| `laravel/app/Services/InvitationService.php` | invitations | uniquement Laravel |
| `laravel/app/Repositories/InvitationRepository.php` | invitations | uniquement Laravel |
| `laravel/app/Http/Controllers/LegacyBridgeController.php` | bridge | uniquement Laravel |
| `laravel/app/Http/Requests/CreateServerRequest.php` | validator potentiel | uniquement Laravel, non branché actuellement |
| `laravel/app/Http/Requests/CreateChannelRequest.php` | validator potentiel | uniquement Laravel, non branché actuellement |
| `laravel/app/Http/Requests/SendMessageRequest.php` | validator potentiel | uniquement Laravel, non branché actuellement |

### 3.3 Classes uniquement legacy autour du périmètre

| Fichier legacy | Usage / recommandation |
|---|---|
| `api/laravel_proxy.php` | Bridge PHP nécessaire aux wrappers `/api/*.php` conservés. À garder temporairement. |
| `app/Support/ApiKernel.php` | Support legacy général. Ne pas toucher en Phase 2 limitée. |
| `app/Support/Autoload.php` | Support legacy général. Ne pas toucher en Phase 2 limitée. |
| `app/Models/ServerMember.php` | Modèle legacy sans correspondant Laravel direct dans le périmètre validé. Ne pas toucher. |

## 4. Comparaison fichier par fichier et source of truth recommandée

| Classe / fichier | Comparaison `app/*` vs `laravel/app/*` | Classement | Source of truth recommandée | Décision Phase 2 |
|---|---|---|---|---|
| `BaseApiController` | Même intention fonctionnelle, mais legacy retourne un tableau `{statusCode,payload}` alors que Laravel retourne `JsonResponse`. | divergent mais compatible | conserver Laravel | Ne pas fusionner tant que des chemins legacy peuvent attendre le format tableau. |
| `ServerController` | Logique identique ; différences de namespace, type de retour et `JsonResponse`. | quasi identique | conserver Laravel | Fusion/suppression legacy plus tard, après preuve que plus aucun chemin n'utilise le contrôleur legacy. |
| `ChannelController` | Logique identique ; différences de namespace, type de retour et `JsonResponse`. | quasi identique | conserver Laravel | Fusion/suppression legacy plus tard. |
| `MessageController` | Logique identique sur `index`/`create`; `delete` existe aussi mais hors périmètre validé. Différences de namespace/type retour. | quasi identique pour le périmètre, divergent à surveiller hors périmètre | conserver Laravel pour `index`/`create`, conserver legacy temporairement | Ne pas toucher `delete_message.php` en Phase 2 limitée. |
| `ServerService` | Fichiers identiques. | identique | conserver Laravel | Legacy supprimable plus tard après audit d'utilisation. |
| `ChannelService` | Fichiers identiques. | identique | conserver Laravel | Legacy supprimable plus tard après audit d'utilisation. |
| `MessageService` | Fichiers identiques, mais contient aussi `deleteMessageFromPayload` hors périmètre. | identique, avec méthode hors périmètre | conserver Laravel pour Phase 1, conserver legacy temporairement | Ne pas modifier les branches delete. |
| `PermissionService` | Fichiers identiques. | identique | conserver Laravel | Ne pas migrer de nouveaux endpoints de permission en Phase 2 limitée. |
| `AdminMiddleware` | Fichiers identiques. | identique | conserver Laravel | Utilisation indirecte OK ; ne pas toucher aux middlewares Laravel sensibles. |
| `ServerRepository` | Même API publique ; legacy utilise PDO, Laravel utilise `DB` facade. Résultat attendu compatible. | divergent mais compatible | conserver Laravel | Valider par contrats servers avant tout retrait legacy. |
| `ServerMemberRepository` | Fichiers identiques. | identique | conserver Laravel | Legacy supprimable plus tard après audit d'utilisation. |
| `ChannelRepository` | Même API publique ; legacy utilise PDO, Laravel utilise `DB` facade. Résultat attendu compatible. | divergent mais compatible | conserver Laravel | Valider par contrats channels avant tout retrait legacy. |
| `MessageRepository` | Mixte : certaines méthodes Laravel utilisent `DB`, d'autres conservent PDO ; legacy est tout PDO. Méthodes Phase 1 `findByChannelId`/`create` compatibles, contrôles d'accès conservés en PDO. | divergent mais compatible, à risque moyen | conserver Laravel pour `get_messages`/`send_message`, conserver legacy temporairement | Ne pas migrer `delete` ni changer timestamps/statuts. |
| `GlobalPermissionRepository` | Fichiers identiques. | identique | conserver Laravel | Ne pas élargir aux endpoints admin/modération. |
| `Server` model | Fichiers identiques. | identique | conserver Laravel | Legacy supprimable plus tard après audit d'utilisation. |
| `InvitationController` | Aucun équivalent `app/Controllers`. | uniquement Laravel | conserver Laravel | Source of truth actuelle. |
| `InvitationService` | Aucun équivalent `app/Services`. | uniquement Laravel | conserver Laravel | Source of truth actuelle. |
| `InvitationRepository` | Aucun équivalent `app/Repositories`. | uniquement Laravel | conserver Laravel | Source of truth actuelle. |
| `LegacyBridgeController` | Aucun équivalent legacy direct. | uniquement Laravel | conserver Laravel | À garder jusqu'à fin de convergence endpoint par endpoint. |
| `CreateServerRequest` | Aucun équivalent actif dans `app/*`; non utilisé par la route validée. | uniquement Laravel | fusionner plus tard | Ne pas brancher sans contrats dédiés, risque de changer payload/statuts. |
| `CreateChannelRequest` | Aucun équivalent actif dans `app/*`; non utilisé par la route validée. | uniquement Laravel | fusionner plus tard | Ne pas brancher sans contrats dédiés. |
| `SendMessageRequest` | Aucun équivalent actif dans `app/*`; non utilisé par la route validée. | uniquement Laravel | fusionner plus tard | Ne pas brancher sans contrats dédiés. |
| `api/laravel_proxy.php` | Pas d'équivalent Laravel ; bridge procédural des wrappers conservés. | uniquement legacy | conserver legacy temporairement | Suppression uniquement dans une phase dédiée de retrait des wrappers. |
| `app/Support/ApiKernel.php` / `Autoload.php` | Support legacy général non spécifique au périmètre Phase 1. | uniquement legacy | conserver legacy temporairement | Ne pas toucher en Phase 2 limitée. |

Aucun doublon du périmètre validé n'est classé **divergent dangereux** à ce stade. Les divergences les plus sensibles sont `BaseApiController` et les repositories PDO vs `DB`, car une suppression prématurée du legacy pourrait casser un chemin non encore inventorié.

## 5. Plan de convergence Phase 2 par mini-lots

### Lot 2.1 — servers

Endpoints concernés :

- `/api/create_server.php`
- `/api/get_servers.php`
- `/api/get_server_name.php`

Fichiers à toucher plus tard, si le lot est ouvert :

- `laravel/app/Http/Controllers/ServerController.php`
- `laravel/app/Services/ServerService.php`
- `laravel/app/Repositories/ServerRepository.php`
- `laravel/app/Repositories/ServerMemberRepository.php`
- `laravel/app/Models/Server.php`
- documentation de convergence associée

Fichiers à ne surtout pas toucher dans ce lot :

- `api/create_server.php`, `api/get_servers.php`, `api/get_server_name.php` tant que les wrappers sont conservés ;
- `laravel/routes/api.php` hors correction explicitement couverte par contrat ;
- auth/session : `api/login.php`, `api/logout.php`, `api/check_auth.php`, `api/auth.php`, `api/register.php` ;
- compte/profil, DM, admin/modération ;
- frontend.

Tests contractuels à lancer :

- `php artisan test --filter=CreateServerContractTest`
- `php artisan test --filter=GetServersContractTest`
- `php artisan test --filter=GetServerNameContractTest`
- `php artisan test --testsuite=Contract`

Risque : **faible à moyen**.

- Faible sur services/model identiques.
- Moyen sur `ServerRepository` car la version Laravel utilise `DB` facade au lieu de PDO.

Rollback possible : oui.

- Revert du mini-lot.
- Conservation des wrappers permettant de revenir au chemin précédent sans rewrite global.

### Lot 2.2 — channels

Endpoints concernés :

- `/api/create_channel.php`
- `/api/get_channels.php`

Fichiers à toucher plus tard, si le lot est ouvert :

- `laravel/app/Http/Controllers/ChannelController.php`
- `laravel/app/Services/ChannelService.php`
- `laravel/app/Repositories/ChannelRepository.php`
- `laravel/app/Repositories/ServerMemberRepository.php`
- `laravel/app/Middleware/AdminMiddleware.php` uniquement si un audit prouve que c'est nécessaire
- documentation de convergence associée

Fichiers à ne surtout pas toucher dans ce lot :

- `api/create_channel.php`, `api/get_channels.php` tant que les wrappers sont conservés ;
- `laravel/app/Http/Requests/CreateChannelRequest.php` sans contrat prouvant que les statuts/messages restent inchangés ;
- endpoints admin/modération même si les rôles P2/P3 sont proches fonctionnellement ;
- frontend et routes sensibles.

Tests contractuels à lancer :

- `php artisan test --filter=CreateChannelContractTest`
- `php artisan test --filter=GetChannelsContractTest`
- `php artisan test --testsuite=Contract`

Risque : **moyen**.

- Le contrôle de rôle P2/P3 et l'admin global passent par `AdminMiddleware` / `ServerMemberRepository`.
- Toute correction implicite des permissions peut changer un invariant legacy.

Rollback possible : oui.

- Revert du mini-lot.
- Maintien des wrappers et absence de rewrite global.

### Lot 2.3 — messages

Endpoints concernés :

- `/api/get_messages.php`
- `/api/send_message.php`

Fichiers à toucher plus tard, si le lot est ouvert :

- `laravel/app/Http/Controllers/MessageController.php` uniquement pour `index` et `create` ;
- `laravel/app/Services/MessageService.php` uniquement pour `listMessages` et `sendMessageFromPayload` ;
- `laravel/app/Repositories/MessageRepository.php` uniquement pour les méthodes utilisées par `get_messages` et `send_message` ;
- `laravel/app/Repositories/ServerMemberRepository.php` et `laravel/app/Middleware/AdminMiddleware.php` uniquement si nécessaire et prouvé par contrats ;
- documentation de convergence associée.

Fichiers à ne surtout pas toucher dans ce lot :

- `api/get_messages.php`, `api/send_message.php` tant que les wrappers sont conservés ;
- `api/delete_message.php` et toute branche `delete` hors périmètre ;
- `laravel/app/Http/Requests/SendMessageRequest.php` sans preuve contractuelle ;
- DM (`start_dm`, `send_dm`, `get_dm_messages`, notifications) ;
- frontend.

Tests contractuels à lancer :

- `php artisan test --filter=GetMessagesContractTest`
- `php artisan test --filter=SendMessageContractTest`
- `php artisan test --testsuite=Contract`

Risque : **moyen à élevé**.

- `send_message` conserve un gap legacy : un utilisateur authentifié non membre peut poster si le channel existe.
- `MessageRepository` mélange encore PDO et `DB`; changer l'une des requêtes peut modifier timestamps, tri, clés JSON ou codes d'erreur.

Rollback possible : oui, mais à tester immédiatement.

- Revert du mini-lot.
- Vérification immédiate de `SendMessageContractTest`, notamment l'invariant non-member.

### Lot 2.4 — invitations

Endpoints concernés :

- `/api/create_invite.php`
- `/api/accept_invite.php`

Fichiers à toucher plus tard, si le lot est ouvert :

- `laravel/app/Http/Controllers/InvitationController.php`
- `laravel/app/Services/InvitationService.php`
- `laravel/app/Repositories/InvitationRepository.php`
- `laravel/app/Http/Controllers/LegacyBridgeController.php` uniquement si le bridge d'entrée doit être simplifié sans changer les contrats
- documentation de convergence associée

Fichiers à ne surtout pas toucher dans ce lot :

- `api/create_invite.php`, `api/accept_invite.php` tant que les wrappers sont conservés ;
- auth/session globale ;
- routes d'invitation frontend ou `invitation.html` ;
- endpoints serveurs/canaux/messages hors régression ciblée.

Tests contractuels à lancer :

- `php artisan test --filter=CreateInviteContractTest`
- `php artisan test --filter=AcceptInviteContractTest`
- `php artisan test --testsuite=Contract`

Risque : **faible à moyen**.

- Les classes invitation sont uniquement Laravel, donc il n'y a pas de doublon `app/*` à fusionner.
- Le risque principal est la conservation des invariants legacy `success=false` avec HTTP 200 et des messages exacts.

Rollback possible : oui.

- Revert du mini-lot.
- Maintien des wrappers et absence de rewrite global.

### Lot 2.5 — audit de sortie Phase 2 limitée

Objectif : valider que la convergence limitée n'a pas débordé du périmètre Phase 1.

Fichiers à toucher :

- uniquement un document d'audit de sortie, par exemple `docs/mvc-migration/phase-2-limited-exit.md` ;
- aucun runtime pendant l'audit final.

Fichiers à ne surtout pas toucher :

- tous les endpoints sensibles ;
- tous les fichiers frontend ;
- tous les wrappers `/api/*.php` sauf décision explicite dans une phase dédiée ultérieure ;
- rewrite global / configuration serveur.

Tests contractuels à lancer :

- `php artisan test --filter=CreateServerContractTest`
- `php artisan test --filter=GetServersContractTest`
- `php artisan test --filter=GetServerNameContractTest`
- `php artisan test --filter=CreateChannelContractTest`
- `php artisan test --filter=GetChannelsContractTest`
- `php artisan test --filter=GetMessagesContractTest`
- `php artisan test --filter=SendMessageContractTest`
- `php artisan test --filter=CreateInviteContractTest`
- `php artisan test --filter=AcceptInviteContractTest`
- `php artisan test --testsuite=Contract`

Risque : **faible** si l'audit reste documentaire.

Rollback possible : oui, simple revert documentaire.

## 6. Garde-fous de convergence

1. Ne jamais supprimer `app/*` dans le même lot qui change le chemin Laravel : suppression uniquement après audit d'usage et suite Contract verte.
2. Ne jamais brancher les `FormRequest` Laravel du périmètre sans prouver que les payloads, messages d'erreur et statuts HTTP restent identiques.
3. Ne jamais corriger implicitement les gaps legacy pendant une convergence technique.
4. Ne jamais inclure `delete_message`, DM, auth/session, profil/compte ou admin/modération dans les lots 2.1 à 2.4.
5. Ne jamais activer de rewrite global `/api/*.php`.
6. Garder `api/laravel_proxy.php` et les wrappers `/api/*.php` jusqu'à une phase dédiée de retrait.
7. Après chaque mini-lot : lancer les tests ciblés du lot puis la suite Contract complète sur serveur réel.

## 7. Décision Phase 2.0

Décision : **GO préparation Phase 2 limitée**.

Le démarrage des mini-lots 2.1 à 2.4 est acceptable uniquement sous ces conditions :

- périmètre limité aux 9 endpoints validés ;
- source of truth recommandée : `laravel/app/*` pour les classes déjà utilisées par les routes Phase 1 ;
- conservation temporaire des doublons `app/*` tant qu'un audit d'usage n'a pas prouvé leur retrait sûr ;
- aucun changement de runtime, route, endpoint, payload JSON, statut HTTP, frontend ou rewrite global sans contrat dédié.

Décision complémentaire : **NO-GO pour une convergence globale `app/*` vers `laravel/app/*`** tant que les endpoints sensibles et familles hors périmètre ne sont pas contractuellement stabilisés.
