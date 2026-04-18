# Migration PDO → Query Builder Laravel

> Phase 1 — migration progressive des repositories simples vers `DB::table()`
> Scope : `laravel/app/Repositories/` uniquement. Les fichiers `app/Repositories/` (stack PHP pure) restent inchangés.

---

## Périmètre de la migration

### Stratégie

- Utiliser `Illuminate\Support\Facades\DB` (Query Builder) — pas d'Eloquent.
- Conserver exactement les colonnes, alias, ordres et formats de retour.
- Ne pas modifier les payloads des réponses API.
- Ne pas toucher aux requêtes complexes ni aux blocs transactionnels.
- Migration partielle possible dans un même repository (certaines méthodes en PDO, d'autres en DB::).

### Note sur la compatibilité transactionnelle

`AppServiceProvider` lie `PDO::class` via `DB::connection()->getPdo()`. Ainsi, lorsque
`ServerService` ouvre une transaction PDO (`$this->pdo->beginTransaction()`), les appels
`DB::table()` dans `ServerRepository::create()` utilisent **la même connexion MySQL** et
participent à la transaction. La sécurité transactionnelle est préservée.

---

## Méthodes migrées (Phase 1)

### `ServerRepository` — migration complète

| Méthode | Avant | Après |
|---|---|---|
| `create()` | `PDO::prepare / execute / lastInsertId` | `DB::table('servers')->insertGetId([...])` |
| `findByMemberUserId()` | `PDO::prepare` + JOIN SQL | `DB::table('servers as s')->join(...)->where()->orderBy()->select()->get()` |
| `find()` | `PDO::prepare` + `Server::fromArray($row)` | `DB::table('servers')->where()->select()->first()` + cast stdClass→array |

PDO retiré du constructeur (aucune méthode restante ne l'utilise).

### `ChannelRepository` — migration complète

| Méthode | Avant | Après |
|---|---|---|
| `findByServerId()` | `PDO::prepare` + `fetchAll` | `DB::table('channels')->where()->orderBy()->select()->get()` |
| `create()` | `PDO::prepare / execute / lastInsertId` | `DB::table('channels')->insertGetId([...])` |

PDO retiré du constructeur.

### `MessageRepository` — migration partielle

| Méthode | Avant | Après |
|---|---|---|
| `create()` | `PDO::prepare / execute / lastInsertId` | `DB::table('messages')->insertGetId([...])` |
| `findByChannelId()` | `PDO::prepare` + double JOIN + `fetchAll` | `DB::table('messages as m')->join()->leftJoin()->where()->orderBy()->select()->get()` |

PDO **conservé** dans le constructeur (requis par les méthodes non migrées).

---

## Méthodes encore en PDO

### `MessageRepository`

| Méthode | Raison du maintien PDO |
|---|---|
| `createWithCurrentTimestamp()` | Utilise `NOW()` côté serveur SQL — migration possible en phase 2 avec `DB::raw('NOW()')` |
| `channelExists()` | Pattern `fetchColumn !== false` — migration possible en phase 2 |
| `userCanReadChannelMessages()` | Requête de contrôle d'accès aux permissions — à migrer prudemment |
| `findMessageChannelAndServer()` | JOIN messages+channels avec cast manuel des types — phase 2 |
| `deleteById()` | DELETE simple — phase 2 |

### `UserRepository` — non migré (hors périmètre phase 1)

| Méthode | Raison |
|---|---|
| `create()` | Simple mais hors liste cible initiale |
| `updateIdentityFields()` | Champs dynamiques (`implode` des colonnes) — requiert `DB::table()->update()` conditionnel |
| `findPasswordHashById()` | Simple, phase 2 |
| `updatePasswordHash()` | Simple, phase 2 |
| `listAllWithGlobalPermission()` | JOIN LEFT + ORDER BY — phase 2 |

### `ServerMemberRepository` — non migré (hors périmètre phase 1)

Toutes les méthodes restent en PDO. Plusieurs contiennent des JOIN complexes ou de la logique
de rôle hiérarchique (`P1/P2/P3`) qui nécessite une relecture attentive avant migration.

### `DmRepository` — non migré (hors périmètre phase 1)

Repository le plus complexe : GROUP BY, sous-requêtes de comptage non lus, multi-JOIN.
À traiter séparément avec tests de régression dédiés.

### `GlobalPermissionRepository` — non migré (hors périmètre phase 1)

`isGlobalAdmin()` utilise `fetchColumn !== false` sur une seule ligne. Simple mais hors liste cible.

### `ProfileRepository` — non migré

INSERT simple. Peut être migré en phase 2.

---

## Risques identifiés

| Risque | Niveau | Détail |
|---|---|---|
| Types de retour stdClass vs array | Faible | `DB::table()->get()` retourne une Collection de `stdClass`. Le cast `(array) $r` préserve les clés. Les valeurs numériques restent des strings (même comportement que PDO/MySQL). |
| Transaction PDO + DB facade | Faible | Vérifier que `AppServiceProvider` est chargé avant tout appel `DB::`. C'est garanti par le bootstrap Laravel. |
| `ServerRepository::find()` — cast `owner_id` | Faible | `Server::fromArray((array) $row)` reçoit un array avec les clés `id`, `name`, `owner_id` en string. Si `Server::fromArray` caste en `(int)`, aucun impact. Vérifier la méthode `fromArray`. |
| Alias `u.id as user_id` dans `findByChannelId` | Faible | Laravel Query Builder accepte les alias en string dans `select()`. Le cast `(array) $r` produit `['user_id' => ...]`, identique au PDO `fetchAll`. |
| Méthodes PDO dans transaction ouverte | Aucun | Le PDO injecté est `DB::connection()->getPdo()` — même handle de connexion. |

---

## Tests manuels recommandés

### ServerRepository

```
POST /api/create_server.php   { "nom": "Test Migration" }
→ Attendre : { "success": true, "server_id": <int> }

GET  /api/get_servers.php
→ Attendre : { "success": true, "servers": [{ "id": <int>, "name": "Test Migration" }] }
```

### ChannelRepository

```
POST /api/create_channel.php  { "server_id": <id>, "name": "général" }
→ Attendre : { "success": true, "channel_id": <int> }

GET  /api/get_channels.php?server_id=<id>
→ Attendre : { "success": true, "channels": [{ "id": <int>, "name": "général" }] }
```

### MessageRepository

```
POST /api/send_message.php    { "channel_id": <id>, "content": "hello" }
→ Attendre : { "success": true, "message_id": <int> }

GET  /api/get_messages.php?channel_id=<id>
→ Attendre : tableau de messages avec les clés id, content, created_at, username, user_id, avatar_url
→ Vérifier l'ordre ASC par created_at puis id
→ Vérifier que avatar_url est null si le profil est absent (LEFT JOIN)
```

### Vérification transactionnelle

```
POST /api/create_server.php avec un ownerId invalide (user inexistant en FK)
→ Attendre rollback propre, aucun serveur créé, aucun membre orphelin
```

---

## Fichiers modifiés

```
laravel/app/Repositories/ServerRepository.php   — migration complète (PDO retiré)
laravel/app/Repositories/ChannelRepository.php  — migration complète (PDO retiré)
laravel/app/Repositories/MessageRepository.php  — migration partielle (PDO conservé)
docs/mvc-migration/laravel-db-migration.md      — ce fichier
```

## Fichiers non modifiés

```
app/Repositories/ServerRepository.php          — stack PHP pure, PDO intact
app/Repositories/ChannelRepository.php         — stack PHP pure, PDO intact
app/Repositories/MessageRepository.php         — stack PHP pure, PDO intact
laravel/app/Repositories/UserRepository.php    — phase 2
laravel/app/Repositories/ServerMemberRepository.php — phase 2+
laravel/app/Repositories/DmRepository.php      — phase 3 (complexité élevée)
laravel/app/Repositories/GlobalPermissionRepository.php — phase 2
laravel/app/Repositories/ProfileRepository.php — phase 2
```
