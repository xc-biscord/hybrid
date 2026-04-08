# Frontend inventory (pages ↔ API)

## Pages publiques / d’entrée

| Page | Fichier source | Endpoints appelés | Notes contrat |
|---|---|---|---|
| Landing / login / register | `index.html` + `index.js` | `GET /api/check_auth.php`, `POST /api/register.php`, `POST /api/login.php`, `GET /api/health.php`, lien `GET /api/logout.php` | Les forms attendent JSON; `logout.php` est une redirection HTTP. |
| Redirect auth guard | `auth_check.js` (chargé par plusieurs pages) | `GET /api/check_auth.php` | Redirige vers `/index.html` si `logged_in=false`. |

## Pages applicatives

| Page | Fichier source | Endpoints appelés | Paramètres envoyés côté front |
|---|---|---|---|
| Accueil profil | `accueil.html` | `GET api/get_profile.php`, `POST api/update_profile.php`, `POST api/update_account.php` | `update_profile`: `{bio,avatar_url,status}` ; `update_account`: `{username,email}` ou `{password,current_password}` |
| Liste serveurs | `serveurs.html` | `GET /api/get_servers.php`, `POST /api/create_server.php` | `create_server`: `{nom}` |
| Vue serveur / chat | `serveur.html` + `serveur.js` | `GET /api/get_profile.php`, `GET /api/get_my_server_role.php?server_id`, `GET /api/get_server_name.php?id`, `GET /api/get_servers.php`, `POST /api/create_invite.php`, `GET /api/get_channels.php?server_id`, `GET /api/get_users_in_server.php?server_id`, `POST /api/kick_member.php`, `POST /api/set_member_role.php`, `GET /api/get_user_profile.php?user_id`, `POST /api/start_dm.php`, `GET /api/get_dm_notifications.php`, `GET /api/get_messages.php?channel_id`, `POST /api/delete_message.php`, `POST /api/send_message.php`, `POST /api/create_channel.php`, `GET /api/logout.php` | Mélange `FormData`, query string et JSON. |
| Vue DM | `dm.html` | `GET /api/get_dm_messages.php?conversation_id`, `POST /api/send_dm.php` | `send_dm`: `{conversation_id,content}` |
| Invitation | `invitation.html` | `GET /invite.php?code`, `POST /api/accept_invite.php` | `accept_invite` en `application/x-www-form-urlencoded`: `code` |
| Admin | `admin.html` | `GET /api/get_all_users.php`, `POST /api/set_member_role.php`, `GET /api/get_user_servers.php?user_id`, `POST /api/ban_user.php` | Tentative de promotion P1 via `set_member_role` avec `{server_id:0,new_role:'P1'}` |
| Statut API | `status.html` | plusieurs `GET /api/*` (configurable via tableau local) | Teste disponibilité JSON des endpoints. |

## Couverture des endpoints par le front

- Endpoints **utilisés** par le front actuel: tous sauf `/api/auth.php` (helper interne surtout backend/docs).
- Endpoint non-`/api` mais consommé: `/invite.php`.

