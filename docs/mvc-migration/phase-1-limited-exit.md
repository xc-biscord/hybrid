# Phase 1 limitée — document de sortie

Date : 2026-06-08.

## 1. Statut de validation

| Phase | Statut | Preuve / note |
|---|---|---|
| Phase 0 | **Validée** | **120 tests passed**. |
| Phase 1.1 | **Validée** | Validation effectuée sur serveur réel. |
| Phase 1.2 | **Validée** | Tests ciblés validés sur serveur réel après application via `LegacyBridgeController` / routes Laravel. |
| Phase 1.3 | **Validée** | Tests ciblés validés sur serveur réel après application via `LegacyBridgeController` / routes Laravel. |
| Phase 1.4 | **Validée** | Tests ciblés et suite Contract complète validés sur serveur réel après application via `LegacyBridgeController` / routes Laravel. |

## 2. Périmètre Laravel-ready couvert

La Phase 1 limitée couvre uniquement le lot Laravel-ready déjà stabilisé. Les 9 endpoints couverts sont :

1. `/api/accept_invite.php`
2. `/api/create_channel.php`
3. `/api/create_invite.php`
4. `/api/create_server.php`
5. `/api/get_channels.php`
6. `/api/get_messages.php`
7. `/api/get_server_name.php`
8. `/api/get_servers.php`
9. `/api/send_message.php`

Ce périmètre reste volontairement limité aux familles serveurs, canaux, messages et invitations.

## 3. Invariants de sortie

### 3.1 Wrappers legacy conservés

Les wrappers historiques `/api/*.php` existent toujours pour les 9 endpoints Laravel-ready couverts :

- `api/accept_invite.php`
- `api/create_channel.php`
- `api/create_invite.php`
- `api/create_server.php`
- `api/get_channels.php`
- `api/get_messages.php`
- `api/get_server_name.php`
- `api/get_servers.php`
- `api/send_message.php`

La Phase 1 limitée ne supprime donc pas les façades publiques historiques.

### 3.2 Aucun rewrite global activé

Aucun rewrite global de tout `/api/*.php` vers Laravel n'est activé dans le cadre de cette sortie limitée.

La bascule reste explicite, endpoint par endpoint, via les routes Laravel prévues pour le lot Laravel-ready. Il n'y a pas de remplacement global du répertoire `api/` ni de front-controller global imposé à tous les endpoints historiques.

### 3.3 Aucun endpoint sensible migré dans le périmètre limité

Aucun endpoint sensible n'est intégré au périmètre de sortie de la Phase 1 limitée.

Restent hors périmètre Phase 1 limitée :

- auth/session : `/api/login.php`, `/api/logout.php`, `/api/check_auth.php`, `/api/auth.php`, `/api/register.php` ;
- compte/profil : `/api/update_account.php`, `/api/get_profile.php`, `/api/get_user_profile.php`, `/api/update_profile.php` ;
- administration/modération : `/api/ban_user.php`, `/api/get_all_users.php`, `/api/get_user_servers.php`, `/api/get_users_in_server.php`, `/api/get_my_server_role.php`, `/api/set_member_role.php`, `/api/kick_member.php`, `/api/delete_message.php` ;
- DM : `/api/start_dm.php`, `/api/get_dm_messages.php`, `/api/send_dm.php`, `/api/get_dm_notifications.php`.

Ces familles ne doivent pas être considérées comme validées pour une bascule globale tant que leurs contrats, fixtures et invariants legacy ne sont pas stabilisés explicitement.

## 4. GO / NO-GO Phase 2

Décision : **GO conditionnel Phase 2**.

Le GO est accordé uniquement si la Phase 2 démarre avec les garde-fous suivants :

1. conserver le périmètre réellement validé comme base de départ ;
2. ne pas activer de rewrite global `/api/*.php` ;
3. ne pas élargir implicitement aux endpoints sensibles ;
4. ajouter ou confirmer les contrats avant toute migration endpoint par endpoint ;
5. préserver les wrappers legacy tant que le frontend et les contrats n'ont pas validé leur retrait.

Décision complémentaire : **NO-GO pour une Phase 2 globale** qui remplacerait l'ensemble des endpoints `/api/*.php` ou annoncerait une compatibilité Laravel complète.

## 5. Réserves restantes

1. Les familles auth/session, compte/profil, administration/modération et DM restent à traiter hors du périmètre Phase 1 limitée.
2. Les divergences legacy non REST doivent rester documentées et couvertes avant migration : codes HTTP historiques, shapes JSON, messages exacts, méthodes HTTP acceptées.
3. Les fixtures contractuelles et les rôles doivent rester cohérents avant extension du périmètre.
4. Les invariants permissifs observés ne doivent pas être corrigés implicitement pendant une migration technique.
5. Le retrait éventuel des wrappers `/api/*.php` devra faire l'objet d'une phase dédiée, avec validation frontend et contractuelle explicite.
6. Toute extension Phase 2 doit rester incrémentale, testée sur serveur réel, et réversible endpoint par endpoint.
