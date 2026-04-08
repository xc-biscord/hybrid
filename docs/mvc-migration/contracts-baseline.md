# Contracts baseline & incohérences (sans correction)

Objectif: figer le contrat **tel qu’il existe aujourd’hui** avant migration MVC.

## Baseline global

1. Deux styles de contrôleurs coexistent:
   - style "bootstrap" (`jsonResponse`, `requireMethod`, `requireAuthUserId`) ;
   - style legacy (echo JSON direct + `session_start()` local).
2. Les payloads de succès sont majoritairement `{ success: true, ... }`, **mais pas tous** (`check_auth`, `logout`).
3. La validation HTTP method est partielle (seulement quelques endpoints imposent `POST`).
4. L’auth est parfois explicite (`requireAuthUserId`), parfois implicite via inclusion (`auth.php`).

## Incohérences de contrat relevées

### 1) Forme de réponse non homogène
- `/api/check_auth.php` renvoie `{ logged_in: boolean, username? }` sans clé `success`.
- `/api/logout.php` renvoie une redirection HTML/HTTP vers `/index.html`, pas JSON.
- `/api/auth.php` peut renvoyer un body vide en succès (utile comme garde, pas comme API REST classique).

### 2) Méthodes HTTP non verrouillées
- Plusieurs endpoints mutateurs n’imposent pas `POST` (`set_member_role`, `kick_member`, `delete_message`, `ban_user`, `update_profile`, `update_account`, `create_invite`, `accept_invite`).
- Risque: comportement dépendant du client actuel plus que du contrat backend.

### 3) Formats d’entrée hétérogènes
- JSON body: `login`, `register`, `send_message`, etc.
- FormData / x-www-form-urlencoded: `create_invite`, `accept_invite`.
- Query string: nombreux GET.
- Cette mixité doit être conservée pendant migration incrémentale pour éviter cassure front.

### 4) Incohérences d’autorisation
- `send_message.php` vérifie l’existence du channel mais **pas** l’appartenance utilisateur au serveur/channel.
- `get_server_name.php` exige une session mais ne vérifie pas l’appartenance au serveur demandé.
- `get_my_server_role.php` retourne `success:true` même si `server_id` absent/invalide (role potentiellement `null`).

### 5) Incohérences d’erreurs / statut HTTP
- Endpoints bootstrap: codes HTTP explicites (400/401/403/404/405/500).
- Endpoints legacy: souvent `200` même en erreur métier avec `{success:false,error:...}`.
- `update_account.php` et certains endpoints exposent des détails techniques (`debug`, `Erreur DB : ...`).

### 6) Contrat front ↔ backend fragile sur l’admin
- `admin.html` essaie de changer le rôle P1 via `set_member_role.php` avec `new_role:'P1'`.
- Mais `set_member_role.php` n’accepte que `P2`, `P3`, `member`.
- Résultat attendu actuel: erreur `Rôle invalide` (comportement à documenter, ne pas corriger ici).

### 7) Variantes de nommage de champs
- `create_server.php` accepte `nom` **ou** `name`.
- Contrat utile pour compatibilité mais ambigu pour future normalisation MVC.

## Règle migration recommandée (phase baseline)

Conserver strictement:
- routes publiques identiques,
- schémas JSON existants,
- codes HTTP existants,
- paramètres acceptés (y compris alias `nom`/`name`),
- comportements de permission actuels même imparfaits.

