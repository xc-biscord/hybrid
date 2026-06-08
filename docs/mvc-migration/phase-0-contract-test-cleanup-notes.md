# Notes Phase 0 — dette des tests contractuels Laravel

Ce document conserve les constats déjà formulés sur `laravel/tests/Contract` afin de servir de point de reprise sans relancer un audit.

## Contraintes Phase 0

- Ne pas modifier les endpoints legacy pour les rendre plus propres.
- Ne pas modifier les payloads JSON legacy.
- Ne pas modifier le frontend.
- Ne pas supprimer d'invariant contractuel tant que la suite Phase 0 n'est pas verte.

## État du dossier `laravel/tests/Contract`

- Le dossier contient 34 fichiers PHP et environ 2 660 lignes.
- Le socle commun existe déjà : `ContractTestCase` réinitialise la base, instancie `BiscordHttpClient`, expose les helpers `actingAsAlice`, `actingAsBob`, `actingAsAdmin`, `actingAsMod` et centralise `assertHasKeys`.
- `SessionHelper` centralise déjà l'authentification via `/api/login.php`.
- `BiscordHttpClient` centralise déjà `postJson`, `postForm`, `get` et `request`.

## Redondances observées

- Les assertions de statuts HTTP sont très répétées : 200, 201, 400, 401, 403, 405.
- Les messages legacy sont répétés en dur : `Non authentifié`, `Méthode non autorisée`, `JSON invalide`, `Identifiants manquants`, `Identifiants invalides`, `Champs requis manquants`.
- Les chemins `/api/*.php` sont répétés en dur dans chaque classe de test.
- Les données invalides comme `999999`, `abc`, `0`, ainsi que les payloads utilisateurs temporaires, sont dispersées.
- Les comptes sont centralisés dans `TestAccounts`, mais certains tests login utilisaient encore des credentials hardcodés.

## Tests à conserver

- Les tests du batch Laravel-ready doivent rester comme non-régression Phase 0 : `accept_invite`, `create_channel`, `create_invite`, `create_server`, `get_channels`, `get_messages`, `get_server_name`, `get_servers`, `send_message`.
- Les tests rouges documentant des divergences legacy doivent rester jusqu'au vert complet : `BanUser`, `GetProfile`, `GetUserProfile`, `UpdateProfile`, `UpdateAccount`, `StartDm`, `GetDmMessages`, `GetMyServerRole`, `KickMember`, `SetMemberRole`.
- Les tests sur `is_p1`, exposition/non-exposition d'email, status par défaut profil et distinction DM 200/201 sont des invariants contractuels sensibles.

## Tests à fusionner après retour au vert

- Login : missing username / missing password.
- Register : missing username / missing email.
- Register : duplicate username / duplicate email.
- GetUserServers : missing `user_id` / non-numeric `user_id`.
- GetUserProfile : missing `user_id` / non-numeric `user_id`.
- UpdateProfile : payload vide / status par défaut / méthode GET, si un test combiné conserve tous les invariants.

## Helpers à factoriser après retour au vert

- Assertions de statut : `assertStatus`, `assertCreated`, `assertBadRequest`, `assertForbidden`, `assertMethodNotAllowed`.
- Assertions legacy : `assertLegacyUnauthenticated401`, `assertLegacyError200`, `assertJsonSuccess`, `assertJsonFailure`.
- Constantes de tests : endpoints, messages legacy, IDs invalides, payloads utilisateurs temporaires.

## Réduction estimée après stabilisation

- Réduction prudente : environ 250 à 350 lignes.
- Réduction agressive : environ 400 à 600 lignes, mais à éviter avant le vert Phase 0 complet.
