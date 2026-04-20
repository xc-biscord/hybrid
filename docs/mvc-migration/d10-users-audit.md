# Audit D10 — Cohérence `users` legacy vs attentes Laravel

Date: 2026-04-20
Périmètre: audit lecture seule (aucune migration, aucune modification DB/runtime)

## 0) Méthode et limites

- Tentative d'inspection directe de `biscord_db_tests` via PDO CLI: **échec de connexion** (`Connection refused` puis `No such file or directory`), donc pas d'accès runtime DB dans cet environnement d'audit.
- Pour rester non-intrusif et ne rien modifier, l'analyse s'appuie sur:
  - le schéma SQL versionné (`biscord_db.sql`),
  - les usages applicatifs legacy/Laravel côté repositories/services,
  - le modèle `User` Laravel et la migration `create_users_table`.

> Impact: l'audit est **fortement fiable sur la structure attendue/provisionnée**, mais la mention "structure réelle" de l'instance live `biscord_db_tests` doit être confirmée en environnement où MySQL est accessible.

---

## A) Table `users` legacy — structure observée (source: `biscord_db.sql`)

### Colonnes

1. `id` — `int NOT NULL AUTO_INCREMENT` (PK)
2. `username` — `varchar(255) NOT NULL` (UNIQUE)
3. `email` — `varchar(255) NOT NULL` (UNIQUE)
4. `password_hash` — `text NOT NULL`
5. `created_at` — `timestamp NULL DEFAULT CURRENT_TIMESTAMP`

### Contraintes / index

- PK: `PRIMARY KEY (id)`
- Uniques: `UNIQUE(username)`, `UNIQUE(email)`
- `id` en auto-increment
- Pas de colonne `updated_at`
- Pas de colonne `remember_token`
- Pas de colonne `email_verified_at`

---

## B) Attentes Laravel — modèle + migration

### Modèle `App\Models\User`

- `fillable`: `name`, `email`, `password`
- `hidden`: `password`, `remember_token`
- casts:
  - `email_verified_at` => `datetime`
  - `password` => `hashed`

### Migration Laravel `0001_01_01_000000_create_users_table.php`

La migration standard attend une table `users` avec:

1. `id` (`$table->id()` => `BIGINT UNSIGNED` en pratique Laravel)
2. `name` (`string`, non-null)
3. `email` (`string`, unique)
4. `email_verified_at` (`timestamp`, nullable)
5. `password` (`string`, non-null)
6. `remember_token` (`string(100)`, nullable)
7. `created_at` + `updated_at` (`timestamps()`)

### Auth Laravel

- `config/auth.php` utilise provider `eloquent` avec `App\Models\User`.
- Donc, par défaut, Laravel suppose que les colonnes utilisées par `Authenticatable` existent (notamment `password`, `remember_token`).

---

## C) Différences détaillées (Legacy DB vs Laravel attendu)

## 1) Colonnes manquantes côté legacy pour le modèle/migration Laravel

- `name` (Laravel) absent, remplacé par `username` (legacy)
- `password` (Laravel) absent, remplacé par `password_hash` (legacy)
- `remember_token` absent
- `email_verified_at` absent
- `updated_at` absent

## 2) Colonnes en trop côté legacy vs modèle/migration Laravel

- `username` (absent de la migration/model `User` par défaut)
- `password_hash` (absent du modèle/migration par défaut)

## 3) Divergences de types et tailles

- `id`: legacy `int` vs Laravel `$table->id()` (`bigint unsigned`) → risque mismatch FK/casts/évolution future
- `password_hash`: legacy `text` vs Laravel `password` en `varchar(255)`
  - Sur la contrainte de sécurité demandée (>=60):
    - legacy `text` satisfait largement la capacité
    - Laravel `string(255)` satisfait aussi, mais **nom de colonne différent**
- `created_at`: legacy `timestamp NULL DEFAULT CURRENT_TIMESTAMP` vs Laravel `timestamps()` (`created_at` et `updated_at` nullable selon version/config)

## 4) Divergences de contraintes

- Uniques `email`: présent des deux côtés (OK conceptuellement)
- Unique `username`: présent côté legacy, pas prévu dans migration standard Laravel
- `remember_token` nullable string: **attendu Laravel mais absent legacy**
- `password` (nom exact) attendu Laravel auth: **absent legacy**

## 5) Champs critiques auth (point de contrôle demandé)

- `password` (>=60) :
  - Colonne `password` **absente** en legacy
  - Colonne `password_hash` en `text NOT NULL` existante (capacité OK)
  - Incompatibilité de nom = risque auth Eloquent native
- `remember_token` nullable string : **absente** en legacy

---

## D) Risques concrets pour Phase 1

Niveau global proposé: **ÉLEVÉ** (si on active/authentifie via Eloquent `User` standard sans adaptation explicite).

### Risques identifiés

1. **Auth Laravel incompatible par défaut**
   - `Authenticatable::getAuthPassword()` lit la colonne `password`.
   - La table legacy stocke `password_hash`.
   - Conséquence: login Eloquent natif cassé ou comportement silencieux incorrect.

2. **Hydratation/écriture Eloquent incohérente**
   - `fillable` contient `name`/`password` alors que la table a `username`/`password_hash`.
   - Risque d'échec SQL (`Unknown column`) ou données non persistées comme attendu.

3. **Timestamps partiels**
   - Absence de `updated_at` legacy vs comportement Eloquent par défaut (`$timestamps=true`).
   - Risque d'UPDATE/INSERT incluant `updated_at` inexistant.

4. **remember token non supporté**
   - Fonctionnalités "remember me" Laravel non compatibles sans adaptation.

5. **Drift futur migrations**
   - Lancer `php artisan migrate` sur ce contexte pourrait vouloir créer/altérer des structures non alignées (`name/password/...`).
   - Risque de collision schéma élevé.

### Nuance importante

Le code applicatif Biscord Laravel actuel utilise massivement des repositories SQL explicites (ex: `users.username`, `users.password_hash`), ce qui **réduit le risque immédiat** tant qu'on n'introduit pas l'auth Eloquent standard sur `User`.

---

## E) Verdict Phase 1

- Verdict: **À SURVEILLER (frontière DANGEREUSE)**.
- Interprétation:
  - **SAFE conditionnel** pour Phase 1 **uniquement** si l'on garde la stratégie actuelle (legacy schema + accès SQL explicite/repositories, sans basculer auth Eloquent standard).
  - **DANGEREUX** si quelqu'un active naïvement le flux Laravel auth/migrations users par défaut.

---

## Recommandations (sans implémentation)

## Ce qu'il faut figer maintenant (Phase 1)

1. **Source de vérité DB = schéma legacy actuel** (`users.username`, `users.password_hash`, `id int`).
2. **Interdiction opérationnelle** de `php artisan migrate` sur l'environnement aligné legacy.
3. **Conserver l'accès SQL explicite** pour les endpoints migrés, sans dépendre du modèle `User` standard tant qu'il n'est pas aligné.
4. **Ajouter un garde-fou CI**: test/healthcheck qui échoue si un code path attend `users.password` ou `users.name`.

## Ce qu'il ne faut PAS faire maintenant

- Ne pas renommer de colonnes en DB.
- Ne pas ajouter `remember_token`/`updated_at` en prod/test runtime métier dans cette phase.
- Ne pas activer Laravel Breeze/Fortify/Auth scaffolding par défaut sur cette table sans adaptation.

## Ce qu'on préparera en Phase 5 (convergence DB)

1. Décider d'un contrat cible unique pour `users` (legacy-compatible ou Laravel-native).
2. Plan de migration explicite et versionné (avec rollback) pour harmoniser:
   - `username` vs `name`
   - `password_hash` vs `password`
   - `int` vs `bigint`
   - `remember_token`, `updated_at`, `email_verified_at`
3. Introduire des tests de non-régression auth avant toute convergence.

---

## Annexes — preuves code

- Migration Laravel users incompatible legacy déjà signalée dans la matrice D10 du projet.
- Les repositories/services métiers lisent/écrivent bien `username` et `password_hash`, confirmant le contrat legacy actuel côté code.
