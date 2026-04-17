# Slice MVC — Messages (backend)

## Objectif de cette étape
Migrer le périmètre messages serveur vers les couches MVC backend **sans modifier le comportement public existant**.

Périmètre migré:
- `api/get_messages.php`
- `api/send_message.php`
- `api/delete_message.php`

## Contrats legacy reproduits

### `GET /api/get_messages.php`
- Auth: session requise (`user_id`).
- Entrée: query `channel_id` casté en entier.
- Règles effectives:
  - `channel_id <= 0` → `400` + `{ success:false, error:"Paramètre channel_id invalide" }`
  - refus d’accès si l’utilisateur n’est pas membre du serveur du channel → `403` + `Accès refusé`
  - sinon liste des messages triés `created_at ASC, id ASC`
- Sortie succès: `{ success:true, messages:[{id,content,created_at,username,user_id,avatar_url}] }`

### `POST /api/send_message.php`
- Auth: session requise (`user_id`).
- Méthode HTTP: strictement `POST`.
- Entrée JSON: `channel_id`, `content`.
- Règles effectives:
  - `channel_id <= 0` ou `content` vide après `trim` → `400` + `Message vide ou channel manquant`
  - channel inexistant → `404` + `Channel inexistant`
  - insertion message puis retour `201`.
- Sortie succès: `{ success:true, message_id:int }`
- Erreur SQL: `500` + `Erreur SQL`.

### `/api/delete_message.php`
- Auth: session requise (`user_id`).
- Méthode HTTP: **non verrouillée** (comportement legacy conservé).
- Entrée: JSON lu depuis `php://input`, `message_id` casté en entier (si absent/invalide => `0`).
- Règles effectives:
  - message introuvable → `{ success:false, error:"Message introuvable" }` avec HTTP `200`
  - suppression autorisée si rôle serveur `P2`/`P3` **ou** admin global `P1`
  - refus permission → `{ success:false, error:"Permission refusée" }` avec HTTP `200`
- Sortie succès: `{ success:true }`.

## Design MVC introduit

### Controller
- `app/Controllers/MessageController.php`
- Orchestration des trois cas d’usage:
  - `index(userId, channelId)`
  - `create(userId, data)`
  - `delete(userId, data)`
- Normalise les réponses `{statusCode, payload}` pour `respondFromController()`.

### Service
- `app/Services/MessageService.php`
- Porte les règles applicatives/validation:
  - validation `channel_id/content`
  - contrôle d’accès lecture channel
  - permission suppression (`P2/P3` ou `P1`)
  - conservation explicite des codes/messages legacy.

### Repository
- `app/Repositories/MessageRepository.php`
- Centralise le SQL messages:
  - existence channel
  - accès user->channel (membership)
  - listing messages
  - insertion message
  - lookup message/server pour suppression
  - suppression message
- La méthode existante `createWithCurrentTimestamp()` est conservée pour les flux déjà dépendants.

## Endpoints legacy conservés comme façades

- `api/get_messages.php`:
  - garde auth + extraction query
  - délègue au `MessageController::index`
- `api/send_message.php`:
  - garde contrôle méthode/auth/JSON
  - délègue au `MessageController::create`
- `api/delete_message.php`:
  - garde auth + parsing input permissif legacy
  - délègue au `MessageController::delete`

## Limites connues conservées (volontairement)

1. **Faiblesse d’autorisation sur `send_message`**: l’endpoint vérifie uniquement que le channel existe, pas l’appartenance utilisateur au serveur du channel.
2. `delete_message` renvoie des erreurs métier avec HTTP `200` (au lieu de `4xx`).
3. `delete_message` ne verrouille pas la méthode HTTP (pas de `requireMethod('POST')`).
4. Parsing JSON permissif sur `delete_message` (payload invalide assimilé à `message_id=0`).

Ces points sont explicitement documentés pour correction ultérieure, sans changement de comportement dans cette étape.
