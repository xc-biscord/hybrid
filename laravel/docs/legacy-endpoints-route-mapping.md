# Mapping des endpoints historiques vers Laravel

Ce document référence l'état final du mapping public après convergence : les URLs historiques restent compatibles, mais le runtime dynamique est Laravel.

## État runtime

- Les fichiers wrappers legacy `api/*.php` ne sont plus actifs.
- Le runtime legacy `app/*`, `ApiKernel` et `Autoload` ne sont plus actifs.
- `router.php` transfère `/api/<name>.php` vers `laravel/public/index.php`.
- `router.php` transfère aussi `/invite.php` vers Laravel.
- Le fichier racine `invite.php` existe uniquement comme façade front-controller Laravel-compatible pour les environnements qui servent directement ce fichier.

## Routes publiques conservées

| Endpoint public | Route Laravel | Paramètres conservés | Délégation |
|---|---|---|---|
| `/api/login.php` | `laravel/routes/api.php` | JSON: `username`, `password` | `AuthController::login()` |
| `/api/register.php` | `laravel/routes/api.php` | JSON: `username`, `email`, `password` | `AuthController::register()` |
| `/api/check_auth.php` | `laravel/routes/api.php` | session native | `AuthController::checkAuth()` |
| `/api/auth.php` | `laravel/routes/api.php` | session native | `AuthController::auth()` |
| `/api/logout.php` | `laravel/routes/api.php` | session native | `AuthController::logout()` |
| `/api/get_profile.php` | `laravel/routes/api.php` | session native | `Api\GetProfileController::handle()` |
| `/api/get_user_profile.php` | `laravel/routes/api.php` | query: `user_id` | `Api\GetUserProfileController::handle()` |
| `/api/update_profile.php` | `laravel/routes/api.php` | JSON: `bio`, `avatar_url`, `status` | `Api\UpdateProfileController::handle()` |
| `/api/update_account.php` | `laravel/routes/api.php` | JSON: `username`, `email`, `password`, `current_password` | `AccountController::update()` |
| `/api/get_servers.php` | `laravel/routes/api.php` | session native | `ServerController::index()` |
| `/api/create_server.php` | `laravel/routes/api.php` | JSON: `nom` / `name` | `ServerController::create()` |
| `/api/get_server_name.php` | `laravel/routes/api.php` | query: `id` | `ServerController::showName()` |
| `/api/get_channels.php` | `laravel/routes/api.php` | query: `server_id` | `ChannelController::index()` |
| `/api/create_channel.php` | `laravel/routes/api.php` | JSON: `server_id`, `name` | `ChannelController::create()` |
| `/api/get_messages.php` | `laravel/routes/api.php` | query: `channel_id` | `MessageController::index()` |
| `/api/send_message.php` | `laravel/routes/api.php` | JSON: `channel_id`, `content` | `MessageController::create()` |
| `/api/delete_message.php` | `laravel/routes/api.php` | JSON: `message_id` | `MessageController::delete()` |
| `/api/create_invite.php` | `laravel/routes/api.php` | form: `server_id` | `InvitationController::create()` |
| `/api/accept_invite.php` | `laravel/routes/api.php` | form: `code` | `InvitationController::accept()` |
| `/api/start_dm.php` | `laravel/routes/api.php` | JSON: `other_user_id` | `DmController::start()` |
| `/api/get_dm_messages.php` | `laravel/routes/api.php` | query: `conversation_id` | `DmController::messages()` |
| `/api/send_dm.php` | `laravel/routes/api.php` | JSON: `conversation_id`, `content` | `DmController::send()` |
| `/api/get_dm_notifications.php` | `laravel/routes/api.php` | session native | `DmController::notifications()` |
| `/api/get_my_server_role.php` | `laravel/routes/api.php` | query: `server_id` | `RoleModerationController::getMyServerRole()` |
| `/api/get_users_in_server.php` | `laravel/routes/api.php` | query: `server_id` | `RoleModerationController::listUsersInServer()` |
| `/api/set_member_role.php` | `laravel/routes/api.php` | JSON: `target_user_id`, `server_id`, `new_role` | `RoleModerationController::setMemberRole()` |
| `/api/kick_member.php` | `laravel/routes/api.php` | JSON: `target_user_id`, `server_id` | `RoleModerationController::kickMember()` |
| `/api/get_all_users.php` | `laravel/routes/api.php` | session native | `AdminUserController::listUsers()` |
| `/api/get_user_servers.php` | `laravel/routes/api.php` | query: `user_id` | `AdminUserController::listUserServers()` |
| `/api/ban_user.php` | `laravel/routes/api.php` | JSON: `user_id` | `Api\BanUserController::handle()` |
| `/api/xxx.php` | `laravel/routes/api.php` | request legacy-compatible | `Api\XxxController::handle()` |
| `/api/health.php` | `laravel/routes/api.php` | none | JSON health response |
| `/invite.php` | `router.php` -> internal `/api/invite.php` in `laravel/routes/api.php` | query: `code`, session native | `InvitationController::resolve()` |

## Notes de compatibilité

- Les endpoints historiquement non verrouillés en méthode HTTP restent exposés en `Route::any(...)` lorsque le contrat l'exige.
- Les statuts HTTP et payloads historiques restent la référence jusqu'à changement contractuel explicite.
- L'authentification publique existante reste basée sur la session PHP native `$_SESSION['user_id']` pour préserver les contrats.
- `LegacyBridgeController` a été retiré : aucun fallback vers des wrappers supprimés ne subsiste dans le runtime Laravel.
