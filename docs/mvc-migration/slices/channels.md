# Slice MVC — Channels (`get_channels` / `create_channel`)

_Date: 2026-04-17_

## Objectif du slice
Migrer le domaine **channels** vers la structure MVC backend tout en gardant les endpoints historiques (`api/get_channels.php`, `api/create_channel.php`) comme **adaptateurs fins** et en préservant strictement les permissions métier existantes (P2/P3/P1).

---

## 1) Comportement actuel exact conservé

### `GET /api/get_channels.php`
- Auth session requise (`requireAuthUserId()`), sinon `401`.
- Paramètre `server_id` requis (`int > 0`), sinon:
  - `400 {"success":false,"error":"Paramètre server_id invalide"}`.
- Contrôle d’accès:
  - l’utilisateur doit être **membre du serveur** (`server_members`), sinon:
  - `403 {"success":false,"error":"Accès refusé"}`.
- Succès:
  - `200 {"success":true,"channels":[{"id":int,"name":string}, ...]}`
  - tri SQL par `channels.id ASC`.

### `POST /api/create_channel.php`
- Méthode imposée POST (`requireMethod('POST')`), sinon `405`.
- Auth session requise, sinon `401`.
- Payload JSON via `getJsonInput()` (JSON invalide => `400 JSON invalide` côté bootstrap).
- Validation:
  - `server_id > 0` et `name` non vide après `trim`, sinon:
  - `400 {"success":false,"error":"Requête invalide"}`.
- Permissions conservées:
  - autorisé si rôle serveur `P2` ou `P3`;
  - ou autorisé si global admin `P1`.
- En cas de refus:
  - `403 {"success":false,"error":"Permission refusée"}`.
- Succès:
  - insertion dans `channels(server_id, name)`;
  - `201 {"success":true,"channel_id":int}`.

---

## 2) Répartition MVC introduite

### Endpoint adapters (legacy conservés)
- `api/get_channels.php`:
  - garde l’auth et la lecture de `server_id`, délègue à `ChannelController::index()`.
- `api/create_channel.php`:
  - garde `POST` + auth + parsing JSON, délègue à `ChannelController::create()`.

### Controller — `App\Controllers\ChannelController`
- Mappe les erreurs métier/validation vers les codes HTTP historiques:
  - `InvalidArgumentException` → 400
  - `DomainException` → 403
  - `PDOException` → 500
- Expose:
  - `index(int $userId, int $serverId)`
  - `create(int $userId, array $data)`

### Service — `App\Services\ChannelService`
- Centralise la logique hors endpoint:
  - validation (`server_id`, `name`),
  - contrôle membership pour lecture,
  - autorisation création `P2/P3/P1`.
- Expose:
  - `listChannelsForServer(int $userId, int $serverId)`
  - `createChannelFromPayload(int $userId, array $data)`

### Repository — `App\Repositories\ChannelRepository`
- Contient uniquement le SQL channels:
  - `findByServerId(int $serverId)`
  - `create(int $serverId, string $name)`

### Enrichissement transverse — `App\Repositories\ServerMemberRepository`
- Méthodes ajoutées pour éviter le SQL dans les endpoints/services:
  - `isMember(int $serverId, int $userId): bool`
  - `findRole(int $userId, int $serverId): ?string`

---

## 3) Place des permissions existantes (P2/P3/P1)

### Règles métier inchangées
- **Lecture channels (`get_channels`)**: membership serveur obligatoire.
- **Création channel (`create_channel`)**: autorisé pour `P2`/`P3`, ou bypass global admin `P1`.

### Où vit la logique maintenant
- Vérification `P1` via `AdminMiddleware::isGlobalAdmin()`.
- Vérification rôle serveur via `ServerMemberRepository::findRole()`.
- Décision d’autorisation de création centralisée dans `ChannelService::canCreateChannel()`.

### Vers Laravel Policies/Gates
Ce découpage prépare une migration directe:
- `canCreateChannel()` pourra devenir une **Policy/Gate** (`ChannelPolicy@create`).
- `isMember()` pourra devenir une règle d’accès de ressource (`viewAny/view`).
- Le contrôleur est déjà au bon niveau d’orchestration HTTP pour accueillir des appels de policy.

---

## 4) Duplications endpoint/helper identifiées

- **Dans le domaine channels migré**:
  - la duplication SQL/validation entre endpoint et helper a été supprimée.
  - les endpoints ne font plus que l’adaptation HTTP.

- **Contexte global API (hors ce slice)**:
  - certains endpoints legacy cumulent `hasPermission(...)` **et** `isP1(...)` alors que `hasPermission()` inclut déjà le bypass P1.
  - cette duplication est documentée pour nettoyage futur, mais non modifiée ici pour éviter tout risque de régression hors scope channels.

---

## 5) Dépendances déjà prêtes pour messages/modération

Ce slice laisse des briques directement réutilisables:
1. `ServerMemberRepository::isMember()` pour contrôler l’accès lecture aux messages/canaux.
2. `ServerMemberRepository::findRole()` pour les règles de modération par rôle (P2/P3).
3. `AdminMiddleware::isGlobalAdmin()` pour les bypass d’administration globale (P1).
4. Pattern stable **endpoint adapter → controller → service → repository**.
5. Mapping d’erreurs homogène via `BaseApiController` (`success/error`, codes constants).

---

## 6) Vérifications manuelles recommandées

1. `GET /api/get_channels.php?server_id=<id_valide_membre>` → `200` + liste triée par `id`.
2. `GET /api/get_channels.php?server_id=0` → `400 Paramètre server_id invalide`.
3. `GET /api/get_channels.php?server_id=<id_non_membre>` → `403 Accès refusé`.
4. `POST /api/create_channel.php` sans POST → `405`.
5. `POST` avec `{ "server_id":0, "name":"x" }` → `400 Requête invalide`.
6. `POST` avec `{ "server_id":X, "name":"   " }` → `400 Requête invalide`.
7. `POST` sur serveur avec rôle P2/P3 → `201 channel_id`.
8. `POST` sans droit (hors P2/P3/P1) → `403 Permission refusée`.
