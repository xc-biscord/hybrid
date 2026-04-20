# Endpoint inventory (baseline actuel)

_Source d’analyse: fichiers `api/*.php` et `invite.php` du repo (lecture statique, sans changement de code)._ 

## Légende
- **Auth**: `Oui` si l’endpoint exige une session (`$_SESSION['user_id']`/`requireAuthUserId()`), sinon `Non`.
- **Méthode effective**: méthode réellement contrôlée dans le code (si non contrôlée, endpoint potentiellement accessible via plusieurs méthodes HTTP).

## Endpoints API

| Endpoint | Méthode effective | Paramètres d’entrée | Auth | Réponse actuelle (succès) | Erreurs observées |
|---|---|---|---|---|---|
| `/api/accept_invite.php` | Non verrouillée (utilisé en POST côté front) | Form URL-encoded: `code` | Oui | `{ success: true, server_id }` | `{ success:false,error:"Données manquantes." }`, `{ ..."Invitation invalide." }` |
| `/api/auth.php` | Non verrouillée | aucun | Oui | aucun body JSON (seulement 200 si connecté) | `401 { success:false,error:"Non authentifié" }` via `requireAuthUserId()` |
| `/api/ban_user.php` | Non verrouillée (attendu JSON) | JSON: `user_id` | Oui + rôle P1 | `{ success:true }` | non authentifié, non P1, `user_id` invalide, `Erreur DB : ...` |
| `/api/check_auth.php` | Non verrouillée | aucun | Non | `{ logged_in:true, username }` ou `{ logged_in:false }` | pas de code HTTP explicite sur erreurs métiers |
| `/api/create_channel.php` | **POST** | JSON: `server_id`, `name` | Oui + permission `P2`/`P3` (ou P1 via helper) | `201 { success:true, channel_id }` | 405, 400 requête invalide, 403 permission |
| `/api/create_invite.php` | Non verrouillée (utilisé en POST) | FormData: `server_id` | Oui + membre du serveur | `{ success:true, invite_url }` | données manquantes, non autorisé |
| `/api/create_server.php` | **POST** | JSON: `nom` (ou `name`) | Oui | `201 { success:true, server_id }` | 405, 400 nom requis, 500 erreur serveur |
| `/api/delete_message.php` | Non verrouillée (attendu JSON) | JSON: `message_id` | Oui + `P2/P3` ou P1 | `{ success:true }` | non authentifié, message introuvable, permission refusée |
| `/api/get_all_users.php` | Non verrouillée | aucun | Oui + P1 | `{ success:true, users:[{id,username,email,created_at,permission_level}] }` | non authentifié, accès P1, DB |
| `/api/get_channels.php` | Non verrouillée (GET côté front) | Query: `server_id` | Oui + membre serveur | `{ success:true, channels:[{id,name}] }` | 400 server_id invalide, 403 accès refusé |
| `/api/get_dm_messages.php` | Non verrouillée (GET côté front) | Query: `conversation_id` | Oui + participant conversation | `{ success:true, messages:[...], recipient:{id,username,avatar_url} }` | 400 conversation invalide, 403 accès, 404 destinataire, 500 DB |
| `/api/get_dm_notifications.php` | Non verrouillée (GET côté front) | aucun | Oui | `{ success:true, unread_conversations:[{conversation_id,sender_id,unread_count,username,avatar_url,last_message}] }` | 500 DB |
| `/api/get_messages.php` | Non verrouillée (GET côté front) | Query: `channel_id` | Oui + membre serveur du channel | `{ success:true, messages:[{id,content,created_at,username,user_id,avatar_url}] }` | 400 channel_id invalide, 403 accès refusé |
| `/api/get_my_server_role.php` | Non verrouillée (GET côté front) | Query: `server_id` | Oui | `{ success:true, role:"P1"\|"P2"\|"P3"\|"member"\|null }` | pas de validation stricte `server_id` |
| `/api/get_profile.php` | Non verrouillée (GET côté front) | aucun | Oui | `{ success:true, profile:{username,email,bio,avatar_url,status,is_p1} }` | 401 non connecté, profil introuvable, 500 avec `details` |
| `/api/get_server_name.php` | Non verrouillée (GET côté front) | Query: `id` | Oui | `{ success:true, name }` | 400 ID manquant, 404 introuvable |
| `/api/get_servers.php` | Non verrouillée (GET côté front) | aucun | Oui | `{ success:true, servers:[{id,name}] }` | pas d’erreur métier explicite |
| `/api/get_user_profile.php` | Non verrouillée (GET côté front) | Query: `user_id` | Oui | `{ success:true, user:{id,username,avatar_url,bio,status} }` | non connecté, `user_id` invalide, user non trouvé, DB |
| `/api/get_user_servers.php` | Non verrouillée (GET côté front admin) | Query: `user_id` | Oui + P1 | `{ success:true, servers:[{id,name}] }` | non authentifié, accès P1, user_id invalide, DB |
| `/api/get_users_in_server.php` | Non verrouillée (GET côté front) | Query: `server_id` | Oui + membre serveur | `{ success:true, users:[{id,username,role}] }` | 400, 403 |
| `/api/health.php` | Non verrouillée (GET côté front) | aucun | Non | `{ success:true, status:"ok" }` | aucune |
| `/api/kick_member.php` | Non verrouillée (attendu JSON) | JSON: `target_user_id`, `server_id` | Oui + `P2` ou P1 | `{ success:true }` | non authentifié, permission, tentative kick P2 par non-P1 |
| `/api/login.php` | **POST** | JSON: `username`, `password` | Non | `{ success:true, user_id }` (crée session) | 405, 400 identifiants manquants, 401 invalides |
| `/api/logout.php` | Non verrouillée | aucun | Non (agit sur session si présente) | **Redirection HTTP** vers `/index.html` (pas JSON) | n/a |
| `/api/register.php` | **POST** | JSON: `username`, `email`, `password` | Non | `201 { success:true }` (crée session) | 405, 400 champs/email, 409 doublon, 500 SQL |
| `/api/send_dm.php` | **POST** | JSON: `conversation_id`, `content` | Oui + participant conversation | `201 { success:true, message_id }` | 405, 400 contenu/conversation manquants, 403, 500 |
| `/api/send_message.php` | **POST** | JSON: `channel_id`, `content` | Oui | `201 { success:true, message_id }` | 405, 400, 404 channel, 500 SQL |
| `/api/set_member_role.php` | Non verrouillée (attendu JSON) | JSON: `target_user_id`, `server_id`, `new_role` (`P2`,`P3`,`member`) | Oui + `P2` ou P1 | `{ success:true }` | non authentifié, rôle invalide, permission refusée |
| `/api/start_dm.php` | **POST** | JSON: `other_user_id` | Oui | `{ success:true, conversation_id, status:"exists"\|"created" }` | 405, 400 identifiant invalide, 500 DB |
| `/api/update_account.php` | Non verrouillée (attendu JSON) | JSON optionnel: `username`, `email`, `password`, `current_password` | Oui | `{ success:true }` | non connecté, aucune donnée, mdp actuel requis/incorrect, 500 avec `debug` |
| `/api/update_profile.php` | Non verrouillée (attendu JSON) | JSON: `bio`, `avatar_url`, `status` | Oui (indirect via `auth.php`) | `{ success:true }` | erreur implicite si session absente |

## Endpoint hors `/api` utilisé par le front

| Endpoint | Méthode effective | Paramètres | Auth | Réponse |
|---|---|---|---|---|
| `/invite.php` | Non verrouillée (GET côté front) | Query: `code` | Oui | `{ success:true, server_id, server_name }` sinon `{ success:false, error }` |

