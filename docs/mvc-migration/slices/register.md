# Slice MVC: `api/register.php`

## Objectif de migration

Transformer `api/register.php` en façade HTTP fine qui délègue à un contrôleur et un service applicatif transactionnel, sans changer le contrat public (route, payloads JSON, codes HTTP, messages d'erreur).

## Contrat historique conservé

### Route et méthode
- Route inchangée: `POST /api/register.php`
- Méthode non-POST: `405 {"success":false,"error":"Méthode non autorisée"}`

### Validation d'entrée
- `username`, `email`, `password` requis (non vides après trim pour `username` et `email`):
  - `400 {"success":false,"error":"Champs requis manquants"}`
- email invalide (`FILTER_VALIDATE_EMAIL`):
  - `400 {"success":false,"error":"Email invalide"}`

### Succès
- Compte créé:
  - `201 {"success":true}`
- Session ouverte côté serveur:
  - `$_SESSION['user_id'] = <id nouvel utilisateur>`

### Erreurs SQL
- Conflit unicité (`duplicate key`, code SQL 1062):
  - `409 {"success":false,"error":"Nom d'utilisateur ou email déjà utilisé"}`
- Autre erreur SQL:
  - `500 {"success":false,"error":"Erreur SQL"}`

## Répartition MVC mise en place

### Endpoint adapter — `api/register.php`
- Garde uniquement:
  - vérification méthode HTTP,
  - parsing JSON,
  - délégation `apiKernel()->authController()->register($data)`,
  - sérialisation de réponse via `respondFromController`.

### Controller — `App\Controllers\AuthController`
- Orchestration du cas d'usage register:
  - validation des champs requis et de l'email,
  - appel du service applicatif,
  - positionnement de session selon le comportement legacy,
  - mapping des exceptions PDO vers le contrat API historique.

### Service applicatif — `App\Services\RegisterService`
- Porte la transaction métier de bout en bout.
- Ordonne les effets métier multi-entités du workflow d'inscription.

### Repositories métier
- `UserRepository`:
  - création de l'utilisateur (`users`).
- `ProfileRepository`:
  - création du profil par défaut (`profiles`).
- `ServerMemberRepository`:
  - inscription auto au Hub public via insertion `INSERT IGNORE` (`server_members`).
- `MessageRepository`:
  - création du message de bienvenue (`messages`).

## Étapes transactionnelles exactes (ordre d'exécution)

Dans `RegisterService::register(...)`:
1. `beginTransaction()`.
2. `INSERT users(username, email, password_hash)` avec hash bcrypt.
3. Écriture session immédiate: `$_SESSION['user_id'] = <id nouvel utilisateur>`.
4. `INSERT profiles(user_id, avatar_url, bio, status)` avec:
   - avatar par défaut: `https://biscord-api-stg.xcsoftworks.com/assets/default-user.png`
   - bio: `''`
   - status: `'En ligne'`
5. `INSERT IGNORE server_members(server_id=1, user_id=<newUser>)` (auto-adhésion Hub public).
6. `INSERT messages(channel_id=1, user_id=<newUser>, content="🎉 Bienvenue à @<username> sur le Hub Biscord !", created_at=NOW())`.
7. `commit()`.
8. Retour de l'id utilisateur au contrôleur.

En cas de `PDOException`:
- rollback si transaction active,
- exception propagée au contrôleur pour mapping contractuel.

## Risques de régression identifiés

1. **Couplage IDs fixes Hub/Channel (`1`)**
   - Le comportement legacy dépend de ces IDs; absent ou modifié en base => erreur SQL identique à avant.

2. **Règle `INSERT IGNORE` conservée**
   - Si l'utilisateur est déjà membre du Hub (cas rare), aucune erreur levée et le workflow continue (comportement historique maintenu).

3. **Ordre des écritures maintenu dans la transaction**
   - Toute rupture d'ordre future pourrait changer l'état final en cas d'échec partiel.

4. **Session positionnée avant `commit` (comportement legacy conservé)**
   - En cas d'échec SQL après création du user, la transaction DB est rollback mais la session peut rester positionnée.

## Tests manuels recommandés

1. `POST /api/register.php` avec payload valide:
   - attendu `201 {"success":true}`.
2. Vérifier session active:
   - endpoint protégé (ex: `api/check_auth.php`) doit indiquer utilisateur connecté.
3. Vérifier effets DB:
   - ligne `users` créée,
   - ligne `profiles` créée avec valeurs par défaut,
   - membership Hub `server_members(server_id=1)` présent,
   - message de bienvenue `messages(channel_id=1)` présent.
4. Champ manquant (ex: password vide):
   - `400 Champs requis manquants`.
5. Email invalide:
   - `400 Email invalide`.
6. Duplicate username/email:
   - `409 Nom d'utilisateur ou email déjà utilisé`.
7. Méthode GET sur la route:
   - `405 Méthode non autorisée`.
