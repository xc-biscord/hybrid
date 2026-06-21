window.BISCORD_API_DOCS = {
  basePath: "/api/",
  groups: [
    { id: "auth", fr: "Authentification & session", en: "Authentication & session" },
    { id: "passkeys", fr: "Passkeys / WebAuthn", en: "Passkeys / WebAuthn" },
    { id: "profiles", fr: "Utilisateurs & profils", en: "Users & profiles" },
    { id: "servers", fr: "Serveurs", en: "Servers" },
    { id: "channels", fr: "Salons", en: "Channels" },
    { id: "messages", fr: "Messages publics", en: "Public messages" },
    { id: "invites", fr: "Invitations", en: "Invitations" },
    { id: "dm", fr: "Messages privés", en: "Direct messages" },
    { id: "moderation", fr: "Modération & rôles", en: "Moderation & roles" },
    { id: "admin", fr: "Administration", en: "Administration" },
    { id: "misc", fr: "Divers", en: "Miscellaneous" }
  ],
  endpoints: [
    {
      group: "auth", method: "POST", path: "login.php", auth: "public",
      fr: "Connecte un utilisateur par nom d'utilisateur ou email et crée la session.",
      en: "Logs a user in with username or email and creates the session.",
      request: { username: "alice", password: "secret-password" },
      responses: [{ code: 200, body: { success: true, user_id: 1001 } }, { code: 400, body: { success: false, error: "Identifiants manquants" } }, { code: 401, body: { success: false, error: "Identifiants invalides" } }, { code: 429, body: { success: false, error: "Trop de tentatives. Reessaie dans quelques minutes." } }],
      notes: { fr: "Le serveur régénère l'ID de session à la connexion.", en: "The server regenerates the session ID on successful login." }
    },
    {
      group: "auth", method: "POST", path: "register.php", auth: "public",
      fr: "Crée un compte avec profil associé.",
      en: "Creates an account with its associated profile.",
      request: { username: "alice", email: "alice@example.com", password: "secret-password" },
      responses: [{ code: 201, body: { success: true } }, { code: 400, body: { success: false, error: "Champs requis manquants" } }, { code: 409, body: { success: false, error: "Nom d'utilisateur ou email déjà utilisé" } }]
    },
    {
      group: "auth", method: "GET", path: "check_auth.php", auth: "optional",
      fr: "Indique si la session courante est connectée.",
      en: "Reports whether the current session is logged in.",
      responses: [{ code: 200, body: { logged_in: true, username: "alice" } }, { code: 200, body: { logged_in: false } }]
    },
    {
      group: "auth", method: "GET", path: "auth.php", auth: "session",
      fr: "Vérifie la session pour les gardes legacy; succès avec corps vide.",
      en: "Checks the session for legacy guards; success returns an empty body.",
      responses: [{ code: 200, body: "(empty body, application/json)" }, { code: 401, body: { success: false, error: "Non authentifie" } }]
    },
    {
      group: "auth", method: "GET", path: "logout.php", auth: "session",
      fr: "Détruit la session et redirige vers /index.html.",
      en: "Destroys the session and redirects to /index.html.",
      responses: [{ code: 302, body: "Location: /index.html" }]
    },
    {
      group: "passkeys", method: "POST", path: "login_methods.php", auth: "public",
      fr: "Retourne les méthodes de connexion disponibles pour un identifiant.",
      en: "Returns available login methods for an identifier.",
      request: { identifier: "alice" },
      responses: [{ code: 200, body: { success: true, methods: { password: true, passkey: false } } }, { code: 400, body: { success: false, error: "Identifiant manquant" } }],
      notes: { fr: "password vaut toujours true pour limiter l'énumération des comptes.", en: "password is always true to reduce account enumeration." }
    },
    {
      group: "passkeys", method: "POST", path: "passkey_login_options.php", auth: "public",
      fr: "Génère les options WebAuthn d'assertion pour une connexion passkey.",
      en: "Generates WebAuthn assertion options for passkey login.",
      request: { identifier: "alice" },
      responses: [{ code: 200, body: { success: true, options: { challenge: "base64url", allowCredentials: [] } } }, { code: 400, body: { success: false, error: "Identifiant manquant" } }]
    },
    {
      group: "passkeys", method: "POST", path: "passkey_login_verify.php", auth: "public",
      fr: "Vérifie l'assertion signée WebAuthn et ouvre la session.",
      en: "Verifies the signed WebAuthn assertion and opens the session.",
      request: { id: "credential-id", rawId: "base64url", type: "public-key", response: { authenticatorData: "...", clientDataJSON: "...", signature: "..." } },
      responses: [{ code: 200, body: { success: true, user_id: 1001 } }, { code: 400, body: { success: false, error: "Challenge expire ou absent. Recommence." } }, { code: 401, body: { success: false, error: "Connexion par passkey impossible." } }]
    },
    {
      group: "passkeys", method: "POST", path: "passkey_register_options.php", auth: "session",
      fr: "Génère les options WebAuthn de création pour l'utilisateur connecté.",
      en: "Generates WebAuthn creation options for the logged-in user.",
      responses: [{ code: 200, body: { success: true, options: { challenge: "base64url", user: { name: "alice" } } } }, { code: 401, body: { success: false, error: "Non authentifie" } }]
    },
    {
      group: "passkeys", method: "POST", path: "passkey_register_verify.php", auth: "session",
      fr: "Vérifie l'attestation WebAuthn et enregistre la passkey.",
      en: "Verifies the WebAuthn attestation and stores the passkey.",
      request: { name: "Laptop", credential: { id: "credential-id", type: "public-key", response: {} } },
      responses: [{ code: 201, body: { success: true, passkey: { id: 3001, name: "Laptop", created_at: "2026-06-21 10:00:00", last_used_at: null } } }, { code: 400, body: { success: false, error: "Reponse de credential manquante" } }, { code: 422, body: { success: false, error: "Enregistrement de la passkey impossible : ..." } }]
    },
    {
      group: "passkeys", method: "GET", path: "passkey_list.php", auth: "session",
      fr: "Liste les passkeys de l'utilisateur connecté.",
      en: "Lists passkeys owned by the logged-in user.",
      responses: [{ code: 200, body: { success: true, passkeys: [{ id: 3001, name: "Laptop", created_at: "2026-06-21 10:00:00", last_used_at: null }] } }, { code: 401, body: { success: false, error: "Non authentifie" } }]
    },
    {
      group: "passkeys", method: "POST", path: "passkey_delete.php", auth: "session",
      fr: "Supprime une passkey appartenant à l'utilisateur connecté.",
      en: "Deletes a passkey owned by the logged-in user.",
      request: { id: 3001 },
      responses: [{ code: 200, body: { success: true } }, { code: 400, body: { success: false, error: "Identifiant de passkey manquant" } }, { code: 404, body: { success: false, error: "Passkey introuvable" } }, { code: 409, body: { success: false, error: "Impossible de supprimer la dernière méthode de connexion du compte." } }]
    },
    {
      group: "profiles", method: "GET", path: "get_profile.php", auth: "session",
      fr: "Retourne le profil de l'utilisateur connecté.",
      en: "Returns the logged-in user's profile.",
      responses: [{ code: 200, body: { success: true, profile: { bio: "Hello", avatar_url: "/assets/default-user.png", status: "disponible" } } }, { code: 401, body: { success: false, error: "Utilisateur non connecté" } }]
    },
    {
      group: "profiles", method: "GET", path: "get_user_profile.php?user_id=1002", auth: "session",
      fr: "Retourne le profil public d'un utilisateur ciblé.",
      en: "Returns a target user's public profile.",
      query: [{ name: "user_id", type: "integer", required: true }],
      responses: [{ code: 200, body: { success: true, user: { id: 1002, username: "bob", bio: "", avatar_url: null } } }, { code: 200, body: { success: false, error: "Paramètre user_id invalide" } }],
      notes: { fr: "Plusieurs erreurs legacy retournent HTTP 200 avec success:false.", en: "Several legacy errors return HTTP 200 with success:false." }
    },
    {
      group: "profiles", method: "POST", path: "update_profile.php", auth: "session",
      fr: "Met à jour le profil; les champs absents prennent les valeurs legacy par défaut.",
      en: "Updates the profile; missing fields use legacy defaults.",
      request: { bio: "Hello", avatar_url: "/assets/default-user.png", status: "disponible" },
      responses: [{ code: 200, body: { success: true } }]
    },
    {
      group: "profiles", method: "POST", path: "update_account.php", auth: "session",
      fr: "Met à jour username, email et/ou mot de passe du compte courant.",
      en: "Updates username, email and/or password for the current account.",
      request: { username: "alice2", email: "alice2@example.com", password: "new-secret", current_password: "old-secret" },
      responses: [{ code: 200, body: { success: true } }, { code: 200, body: { success: false, error: "Mot de passe actuel requis" } }, { code: 500, body: { success: false, error: "Erreur SQL" } }]
    },
    {
      group: "servers", method: "GET", path: "get_servers.php", auth: "session",
      fr: "Liste les serveurs dont l'utilisateur connecté est membre.",
      en: "Lists servers where the logged-in user is a member.",
      responses: [{ code: 200, body: { success: true, servers: [{ id: 1101, name: "General" }] } }, { code: 401, body: { success: false, error: "Non authentifie" } }]
    },
    {
      group: "servers", method: "GET", path: "get_server_name.php?id=1101", auth: "session",
      fr: "Retourne le nom d'un serveur.",
      en: "Returns a server name.",
      query: [{ name: "id", type: "integer", required: true }],
      responses: [{ code: 200, body: { success: true, name: "General" } }, { code: 400, body: { success: false, error: "ID manquant" } }, { code: 404, body: { success: false, error: "Serveur introuvable" } }]
    },
    {
      group: "servers", method: "POST", path: "create_server.php", auth: "session",
      fr: "Crée un serveur et ajoute le créateur comme P2.",
      en: "Creates a server and adds the creator as P2.",
      request: { name: "General" },
      responses: [{ code: 201, body: { success: true, server_id: 1101 } }, { code: 400, body: { success: false, error: "Nom de serveur requis" } }]
    },
    {
      group: "channels", method: "GET", path: "get_channels.php?server_id=1101", auth: "session",
      fr: "Liste les salons d'un serveur accessible.",
      en: "Lists channels for an accessible server.",
      query: [{ name: "server_id", type: "integer", required: true }],
      responses: [{ code: 200, body: { success: true, channels: [{ id: 1201, name: "general" }] } }, { code: 400, body: { success: false, error: "Paramètre server_id invalide" } }, { code: 403, body: { success: false, error: "Accès refusé" } }]
    },
    {
      group: "channels", method: "POST", path: "create_channel.php", auth: "session",
      fr: "Crée un salon dans un serveur. Rôle P2/P3 ou admin global requis.",
      en: "Creates a channel in a server. Requires P2/P3 or global admin.",
      request: { server_id: 1101, name: "general" },
      responses: [{ code: 201, body: { success: true, channel_id: 1201 } }, { code: 400, body: { success: false, error: "Requête invalide" } }, { code: 403, body: { success: false, error: "Permission refusée" } }]
    },
    {
      group: "messages", method: "GET", path: "get_messages.php?channel_id=1201", auth: "session",
      fr: "Retourne l'historique d'un salon lisible par l'utilisateur.",
      en: "Returns history for a channel readable by the user.",
      query: [{ name: "channel_id", type: "integer", required: true }],
      responses: [{ code: 200, body: { success: true, messages: [{ id: 1401, content: "Bonjour", username: "alice", user_id: 1001, avatar_url: null }] } }, { code: 400, body: { success: false, error: "Paramètre channel_id invalide" } }, { code: 403, body: { success: false, error: "Accès refusé" } }]
    },
    {
      group: "messages", method: "POST", path: "send_message.php", auth: "session",
      fr: "Publie un message dans un salon existant.",
      en: "Publishes a message to an existing channel.",
      request: { channel_id: 1201, content: "Bonjour" },
      responses: [{ code: 201, body: { success: true, message_id: 1401 } }, { code: 400, body: { success: false, error: "Message vide ou channel manquant" } }, { code: 404, body: { success: false, error: "Channel inexistant" } }]
    },
    {
      group: "messages", method: "POST", path: "delete_message.php", auth: "session",
      fr: "Supprime un message. Admin global ou rôle P2/P3 du serveur requis.",
      en: "Deletes a message. Requires global admin or P2/P3 role on the server.",
      request: { message_id: 1401 },
      responses: [{ code: 200, body: { success: true } }, { code: 200, body: { success: false, error: "Permission refusée" } }],
      notes: { fr: "Les erreurs métier de cet endpoint restent en HTTP 200 pour compatibilité legacy.", en: "Business errors on this endpoint stay HTTP 200 for legacy compatibility." }
    },
    {
      group: "invites", method: "POST", path: "create_invite.php", auth: "session",
      fr: "Crée une invitation pour un serveur dont l'utilisateur est membre.",
      en: "Creates an invitation for a server where the user is a member.",
      requestType: "form", request: { server_id: 1101 },
      responses: [{ code: 200, body: { success: true, invite_url: "https://biscord-api-stg.xcsoftworks.com/invitation.html?code=abc123" } }, { code: 200, body: { success: false, error: "Donnees manquantes." } }]
    },
    {
      group: "invites", method: "POST", path: "accept_invite.php", auth: "session",
      fr: "Ajoute l'utilisateur connecté au serveur lié au code d'invitation.",
      en: "Adds the logged-in user to the server linked to the invitation code.",
      requestType: "form", request: { code: "abc123" },
      responses: [{ code: 200, body: { success: true, server_id: 1101 } }, { code: 200, body: { success: false, error: "Invitation invalide." } }]
    },
    {
      group: "invites", method: "GET", path: "invite.php?code=abc123", auth: "session",
      fr: "Résout un lien d'invitation et retourne le résumé du serveur.",
      en: "Resolves an invitation link and returns the server summary.",
      query: [{ name: "code", type: "string", required: true }],
      responses: [{ code: 200, body: { success: true, server_id: 1101, server_name: "General" } }, { code: 200, body: { success: false, error: "Utilisateur non connecté ou lien invalide." } }]
    },
    {
      group: "dm", method: "POST", path: "start_dm.php", auth: "session",
      fr: "Ouvre ou retrouve une conversation privée avec un autre utilisateur.",
      en: "Creates or finds a direct-message conversation with another user.",
      request: { target_user_id: 1002 },
      responses: [{ code: 201, body: { success: true, conversation_id: 2001, status: "created" } }, { code: 200, body: { success: true, conversation_id: 2001, status: "exists" } }, { code: 400, body: { success: false, error: "Identifiant utilisateur invalide" } }]
    },
    {
      group: "dm", method: "GET", path: "get_dm_messages.php?conversation_id=2001", auth: "session",
      fr: "Retourne les messages d'une conversation et marque la conversation comme lue.",
      en: "Returns conversation messages and marks the conversation as read.",
      query: [{ name: "conversation_id", type: "integer", required: true }],
      responses: [{ code: 200, body: { success: true, messages: [{ id: 2101, conversation_id: 2001, sender_id: 1001, content: "Salut" }], recipient: { id: 1002, username: "bob", avatar_url: null } } }, { code: 403, body: { success: false, error: "Accès refusé" } }, { code: 404, body: { success: false, error: "Conversation introuvable" } }]
    },
    {
      group: "dm", method: "POST", path: "send_dm.php", auth: "session",
      fr: "Envoie un message privé dans une conversation accessible.",
      en: "Sends a direct message in an accessible conversation.",
      request: { conversation_id: 2001, content: "Salut" },
      responses: [{ code: 201, body: { success: true, message_id: 2101 } }, { code: 400, body: { success: false, error: "Conversation ou contenu manquant" } }, { code: 403, body: { success: false, error: "Accès refusé" } }]
    },
    {
      group: "dm", method: "GET", path: "get_dm_notifications.php", auth: "session",
      fr: "Liste les conversations avec messages non lus.",
      en: "Lists conversations with unread messages.",
      responses: [{ code: 200, body: { success: true, unread_conversations: [{ conversation_id: 2001, sender_id: 1002, unread_count: 3, username: "bob", avatar_url: null, last_message: "Salut" }] } }]
    },
    {
      group: "moderation", method: "GET", path: "get_my_server_role.php?server_id=1101", auth: "session",
      fr: "Retourne le rôle de l'utilisateur connecté dans un serveur.",
      en: "Returns the logged-in user's role in a server.",
      query: [{ name: "server_id", type: "integer", required: false }],
      responses: [{ code: 200, body: { success: true, role: "P2" } }, { code: 200, body: { success: true, role: null } }]
    },
    {
      group: "moderation", method: "GET", path: "get_users_in_server.php?server_id=1101", auth: "session",
      fr: "Liste les membres et rôles effectifs d'un serveur.",
      en: "Lists members and effective roles for a server.",
      query: [{ name: "server_id", type: "integer", required: true }],
      responses: [{ code: 200, body: { success: true, users: [{ id: 1001, username: "alice", role: "P2" }] } }, { code: 400, body: { success: false, error: "Requête invalide" } }, { code: 403, body: { success: false, error: "Accès refusé" } }]
    },
    {
      group: "moderation", method: "POST", path: "set_member_role.php", auth: "session",
      fr: "Change le rôle d'un membre. Rôles valides : P2, P3, member.",
      en: "Changes a member role. Valid roles: P2, P3, member.",
      request: { server_id: 1101, target_user_id: 1002, new_role: "P3" },
      responses: [{ code: 200, body: { success: true } }, { code: 400, body: { success: false, error: "Rôle invalide" } }, { code: 403, body: { success: false, error: "Permission refusée" } }]
    },
    {
      group: "moderation", method: "POST", path: "kick_member.php", auth: "session",
      fr: "Retire un membre d'un serveur.",
      en: "Removes a member from a server.",
      request: { server_id: 1101, target_user_id: 1002 },
      responses: [{ code: 200, body: { success: true } }, { code: 403, body: { success: false, error: "Impossible de kick un P2 sans etre P1" } }]
    },
    {
      group: "admin", method: "GET", path: "get_all_users.php", auth: "P1",
      fr: "Liste tous les utilisateurs. Réservé à l'administration globale P1.",
      en: "Lists all users. Restricted to global P1 administrators.",
      responses: [{ code: 200, body: { success: true, users: [{ id: 1001, username: "alice", email: "alice@example.com", permission_level: "P1" }] } }, { code: 403, body: { success: false, error: "Accès réservé aux P1" } }]
    },
    {
      group: "admin", method: "GET", path: "get_user_servers.php?user_id=1002", auth: "P1",
      fr: "Liste les serveurs d'un utilisateur ciblé. Réservé P1.",
      en: "Lists servers for a target user. P1 only.",
      query: [{ name: "user_id", type: "integer", required: true }],
      responses: [{ code: 200, body: { success: true, servers: [{ id: 1101, name: "General" }] } }, { code: 400, body: { success: false, error: "Paramètre user_id manquant ou invalide" } }, { code: 403, body: { success: false, error: "Accès refusé : réservé aux P1" } }]
    },
    {
      group: "admin", method: "POST", path: "ban_user.php", auth: "P1",
      fr: "Bannit un utilisateur. Les erreurs legacy restent en HTTP 200.",
      en: "Bans a user. Legacy errors remain HTTP 200.",
      request: { user_id: 1002 },
      responses: [{ code: 200, body: { success: true } }, { code: 200, body: { success: false, error: "Accès refusé : réservé aux P1" } }, { code: 200, body: { success: false, error: "user_id invalide" } }]
    },
    {
      group: "misc", method: "POST", path: "xxx.php", auth: "public",
      fr: "Endpoint technique legacy validé par Laravel; requiert un id entier.",
      en: "Legacy technical endpoint validated by Laravel; requires an integer id.",
      request: { id: 1 },
      responses: [{ code: 200, body: { result: "repository-defined payload" } }, { code: 422, body: { message: "The id field is required.", errors: { id: ["The id field is required."] } } }]
    },
    {
      group: "misc", method: "GET", path: "health.php", auth: "public",
      fr: "Healthcheck JSON simple.",
      en: "Simple JSON healthcheck.",
      responses: [{ code: 200, body: { success: true, status: "ok" } }]
    }
  ]
};
