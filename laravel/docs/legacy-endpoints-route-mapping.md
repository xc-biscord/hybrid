# Mapping des endpoints legacy vers les routes Laravel

Ce document référence le mapping des endpoints historiques `/api/*.php` vers `laravel/routes/api.php`, en conservant les paramètres et les contraintes de middleware.

## Middlewares utilisés

- `auth.session` : vérifie `$_SESSION['user_id']`.
- `p1.only` : vérifie l'accès admin global (P1).

## Groupes de routes

## Phase 1 — LegacyBridge (`/api/*.php` via front controller Laravel)

Les endpoints ci-dessous sont désormais capturés par `LegacyBridgeController::handle()` dans `routes/api.php` (URLs publiques inchangées).

| Endpoint legacy | Route Laravel | Middleware | Dispatch bridge |
|---|---|---|---|
| `/api/get_servers.php` | `GET /api/get_servers.php` | `auth.session` | Laravel (`ServerController::index`) |
| `/api/get_server_name.php` | `GET /api/get_server_name.php` | `auth.session` | Laravel (`ServerController::showName`) |
| `/api/get_channels.php` | `GET /api/get_channels.php` | `auth.session` | Laravel (`ChannelController::index`) |
| `/api/get_messages.php` | `GET /api/get_messages.php` | `auth.session` | Laravel (`MessageController::index`) |
| `/api/create_server.php` | `POST /api/create_server.php` | `auth.session` | Legacy fallback (`require ../api/create_server.php`) |
| `/api/create_channel.php` | `POST /api/create_channel.php` | `auth.session` | Legacy fallback (`require ../api/create_channel.php`) |
| `/api/send_message.php` | `POST /api/send_message.php` | `auth.session` | Legacy fallback (`require ../api/send_message.php`) |
| `/api/create_invite.php` | `POST /api/create_invite.php` | `auth.session` | Legacy fallback (`require ../api/create_invite.php`) |
| `/api/accept_invite.php` | `POST /api/accept_invite.php` | `auth.session` | Legacy fallback (`require ../api/accept_invite.php`) |

### 1) Register

| Endpoint legacy | Méthode | Middleware | Paramètres conservés | Délégation |
|---|---|---|---|---|
| `/api/register.php` | `POST` | — | JSON: `username`, `email`, `password` | `AuthController::register()` |

### 2) Servers

| Endpoint legacy | Méthode | Middleware | Paramètres conservés | Délégation |
|---|---|---|---|---|
| `/api/get_servers.php` | `GET` | `auth.session` | session `user_id` | `ServerController::index()` |
| `/api/create_server.php` | `POST` | `auth.session` | JSON: `nom` / `name` | `ServerController::create()` |
| `/api/get_server_name.php` | `GET` | `auth.session` | query: `id` | `ServerController::showName()` |

### 3) Channels

| Endpoint legacy | Méthode | Middleware | Paramètres conservés | Délégation |
|---|---|---|---|---|
| `/api/get_channels.php` | `GET` | `auth.session` | query: `server_id` | `ChannelController::index()` |
| `/api/create_channel.php` | `POST` | `auth.session` | JSON: `server_id`, `name` | `ChannelController::create()` |

### 4) Messages

| Endpoint legacy | Méthode | Middleware | Paramètres conservés | Délégation |
|---|---|---|---|---|
| `/api/get_messages.php` | `GET` | `auth.session` | query: `channel_id` | `MessageController::index()` |
| `/api/send_message.php` | `POST` | `auth.session` | JSON: `channel_id`, `content` | `MessageController::create()` |
| `/api/delete_message.php` | `ANY` (legacy non verrouillé) | `auth.session` | JSON: `message_id` | `MessageController::delete()` |

### 5) DM

| Endpoint legacy | Méthode | Middleware | Paramètres conservés | Délégation |
|---|---|---|---|---|
| `/api/start_dm.php` | `POST` | `auth.session` | JSON: `other_user_id` | `DmController::start()` |
| `/api/get_dm_messages.php` | `GET` | `auth.session` | query: `conversation_id` | `DmController::messages()` |
| `/api/send_dm.php` | `POST` | `auth.session` | JSON: `conversation_id`, `content` | `DmController::send()` |
| `/api/get_dm_notifications.php` | `GET` | `auth.session` | session `user_id` | `DmController::notifications()` |

### 6) Moderation

| Endpoint legacy | Méthode | Middleware | Paramètres conservés | Délégation |
|---|---|---|---|---|
| `/api/get_my_server_role.php` | `GET` | `auth.session` | query: `server_id` (nullable) | `RoleModerationController::getMyServerRole()` |
| `/api/get_users_in_server.php` | `GET` | `auth.session` | query: `server_id` | `RoleModerationController::listUsersInServer()` |
| `/api/set_member_role.php` | `ANY` (legacy non verrouillé) | `auth.session` | JSON: `target_user_id`, `server_id`, `new_role` | `RoleModerationController::setMemberRole()` |
| `/api/kick_member.php` | `ANY` (legacy non verrouillé) | `auth.session` | JSON: `target_user_id`, `server_id` | `RoleModerationController::kickMember()` |

### 7) Admin

| Endpoint legacy | Méthode | Middleware | Paramètres conservés | Délégation |
|---|---|---|---|---|
| `/api/get_all_users.php` | `GET` | `auth.session` + `p1.only` | session `user_id` | `AdminUserController::listUsers()` |
| `/api/get_user_servers.php` | `GET` | `auth.session` + `p1.only` | query: `user_id` | `AdminUserController::listUserServers()` |
| `/api/ban_user.php` | `ANY` (legacy non verrouillé) | `auth.session` + `p1.only` | JSON: `user_id` | suppression SQL directe dans la route |

## Notes de compatibilité

- Aucun fichier legacy `/api/*.php` n'est supprimé; l'ancien backend continue de fonctionner en parallèle.
- Les endpoints historiquement non verrouillés en méthode HTTP restent exposés en `Route::any(...)` pour préserver le contrat.
- Le mapping route/controller dans Laravel est centralisé dans `laravel/routes/api.php`.
