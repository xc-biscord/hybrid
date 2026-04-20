# Slice MVC — Servers (`get_servers` / `create_server`)

_Date: 2026-04-08_

## Objectif du slice
Migrer la gestion des serveurs vers la structure MVC existante, en gardant **strictement** les contrats externes pour:
- `api/get_servers.php`
- `api/create_server.php`

## 1) Comportement actuel exact conservé

### `GET /api/get_servers.php`
- Auth requise via session (`requireAuthUserId()`), sinon:
  - `401 {"success":false,"error":"Non authentifié"}`.
- Retour succès:
  - `200 {"success":true,"servers":[{"id":int,"name":string}, ...]}`.
- Ordre des serveurs:
  - tri SQL alphabétique ascendant sur `servers.name`.
- Erreur technique (PDO):
  - `500 {"success":false,"error":"Erreur serveur"}`.

### `POST /api/create_server.php`
- Méthode imposée (`requireMethod('POST')`), sinon:
  - `405 {"success":false,"error":"Méthode non autorisée"}`.
- Auth requise, sinon:
  - `401 {"success":false,"error":"Non authentifié"}`.
- Input JSON uniquement (via `getJsonInput()`), JSON invalide:
  - `400 {"success":false,"error":"JSON invalide"}`.
- Compatibilité d’entrée préservée:
  - `nom` **ou** `name` sont acceptés.
- Validation métier:
  - nom vide (après `trim`) => `400 {"success":false,"error":"Nom de serveur requis"}`.
- Succès:
  - insertion `servers(name, owner_id)`
  - ajout membership owner en `P2`
  - `201 {"success":true,"server_id":<id>}`.
- Erreur technique (PDO):
  - rollback transactionnel + `500 {"success":false,"error":"Erreur serveur"}`.

## 2) Répartition MVC mise en place

### Endpoint adapters (fins)
- `api/get_servers.php`
  - Auth + délégation `ServerController::index()` + sérialisation de réponse.
- `api/create_server.php`
  - Vérification méthode + auth + parsing JSON + délégation `ServerController::create()`.

### Controller — `App\Controllers\ServerController`
- Responsabilité:
  - orchestration HTTP ↔ service.
  - mapping exceptions -> payload/codes HTTP historiques.
- `create(int $userId, array $data)`:
  - appelle désormais `ServerService::createServerFromPayload()`.
- `index(int $userId)`:
  - appelle `ServerService::listUserServers()`.

### Service — `App\Services\ServerService`
- Responsabilité:
  - validation métier,
  - compatibilité d’entrée,
  - orchestration transactionnelle.
- `createServerFromPayload(int $ownerId, array $data)`:
  - extrait le nom via alias `nom`/`name`.
- `createServer(int $ownerId, string $name)`:
  - validation nom,
  - transaction DB,
  - création serveur + membership owner.
- `listUserServers(int $userId)`:
  - expose la liste des serveurs d’un membre.

### Repository — `App\Repositories\ServerRepository`
- Responsabilité SQL pure.
- `create(string $name, int $ownerId): int`.
- `findByMemberUserId(int $userId): array{id,name}[]`.
- `find(int $serverId): ?Server`.

### Modèle — `App\Models\Server`
- Entité `Server` (`id`, `name`, `ownerId`) + mapping `fromArray()` / `toArray()`.

## 3) Zéro-régression visée

Garanties explicitement maintenues:
- routes inchangées;
- payloads inchangés;
- alias d’entrée `nom`/`name` conservé;
- codes HTTP conservés (200/201/400/401/405/500 selon cas);
- structure de la réponse `success/error` inchangée.

## 4) Base réutilisable (channels / memberships)

Points prêts à réutiliser:
1. **Pattern endpoint adapter fin** (`requireMethod` + `requireAuthUserId` + délégation contrôleur).
2. **Service orienté contrat** (validation métier + compatibilité payload legacy).
3. **Repository SQL dédié** (requêtes isolées, testables, remplaçables).
4. **Transactions centralisées dans le service** pour les cas multi-écritures.
5. **Gestion uniforme des erreurs** via `BaseApiController` (`success/error`).

## 5) Tests manuels recommandés

### `create_server.php`
1. POST JSON `{"nom":"Mon serveur"}` → `201`, `server_id` présent.
2. POST JSON `{"name":"Mon serveur EN"}` → `201`, `server_id` présent.
3. POST JSON `{"nom":"   "}` → `400`, `Nom de serveur requis`.
4. GET sur endpoint (sans POST) → `405`, `Méthode non autorisée`.
5. POST sans session → `401`, `Non authentifié`.
6. POST JSON invalide → `400`, `JSON invalide`.

### `get_servers.php`
1. GET avec session → `200`, `servers` tableau d’objets `{id,name}`.
2. Vérifier tri alphabétique ascendant (`name`).
3. GET sans session → `401`, `Non authentifié`.
