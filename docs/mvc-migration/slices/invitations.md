# Slice Invitations — migration façade `/api/*.php` vers controllers Laravel

_Date: 2026-04-18_

## Endpoints couverts
- `/api/create_invite.php`
- `/api/accept_invite.php`

## Contrat legacy conservé
- Route inchangée (`/api/*.php` conservé).
- Payload d'entrée conservé en `POST` form-data (`server_id` pour `create_invite`, `code` pour `accept_invite`).
- Payload JSON de sortie conservé:
  - `create_invite`: `{ success: true, invite_url }` ou `{ success: false, error }`
  - `accept_invite`: `{ success: true, server_id }` ou `{ success: false, error }`
- Statuts HTTP conservés (200 sur les réponses métier de cette famille).
- Permissions conservées:
  - `create_invite`: utilisateur membre du serveur requis.
  - `accept_invite`: code invitation valide; ajout membre si absent.

## Implémentation
- Façade endpoint:
  - `api/create_invite.php` et `api/accept_invite.php` délèguent désormais à `InvitationController` via `laravel_proxy.php`.
- Laravel:
  - `InvitationController` (orchestration réponse JSON)
  - `InvitationService` (règles métier legacy)
  - `InvitationRepository` (accès DB via Query Builder)

## Risque et périmètre
- Slice choisie car faible risque:
  - peu d'endpoints,
  - flux métier court,
  - pas de dépendance front complexe,
  - pas de permission hiérarchique P1/P2/P3.
- Aucun endpoint legacy supprimé.
