# Contract tests — exécution (batch Laravel-ready)

Ce document décrit l'exécution locale / serveur de test de l'ossature de tests contractuels introduite pour le batch **Laravel-ready** de la Phase 0.

## Pré-requis

- Base de test MySQL disponible : `biscord_db_tests` (schéma aligné sur `biscord_db.sql`).
- Aucune exécution contre la base réelle.
- Dépendances PHP/Laravel installées dans `laravel/`.
- Serveur PHP legacy lancé depuis la racine repo :

```bash
php -S localhost:8000 -t .
```

## Résolution de la configuration DB de test

L'helper `TestDatabaseSeeder` résout la connexion DB dans cet ordre :

1. Variables `CONTRACT_TEST_DB_*` (override explicite pour les tests Contract).
2. Variables `DB_*` de l'environnement shell courant.
3. Valeurs présentes dans `laravel/.env` (fichier existant sur l'environnement d'exécution).
4. Valeurs par défaut de repli.

Ce comportement permet d'exécuter la suite contre `biscord_db_tests` sans modifier le runtime applicatif.

## Variables d'environnement optionnelles

Par défaut, la suite utilise :

- `CONTRACT_TEST_BASE_URL=http://localhost:8000`
- `CONTRACT_TEST_DB_HOST=localhost`
- `CONTRACT_TEST_DB_PORT=3306`
- `CONTRACT_TEST_DB_DATABASE=biscord_db_tests`
- `CONTRACT_TEST_DB_USERNAME=adminweb`
- `CONTRACT_TEST_DB_PASSWORD=MazdeoAchaqui`

Vous pouvez surcharger ces variables avant exécution si nécessaire.

## Lancer la suite contractuelle

Depuis `laravel/` :

```bash
php artisan test --testsuite=Contract
```

Ou en ciblant uniquement les tests contractuels du batch :

```bash
./vendor/bin/phpunit --testsuite Contract
```

### Note sur l'installation des dépendances

Si `vendor/` n'est pas installé, exécuter d'abord :

```bash
composer install --no-interaction
```

En environnement CI/reseau restreint, l'installation Composer peut échouer (accès GitHub bloqué). Dans ce cas, documenter explicitement le blocage réseau, car il empêche l'exécution PHPUnit complète.

## Endpoints couverts dans cette première vague (Laravel-ready)

- `api/accept_invite.php`
- `api/create_channel.php`
- `api/create_invite.php`
- `api/create_server.php`
- `api/get_channels.php`
- `api/get_messages.php`
- `api/get_server_name.php`
- `api/get_servers.php`
- `api/send_message.php`

## Endpoints volontairement laissés pour plus tard

Conformément au plan Phase 0, cette vague **n'inclut pas encore** :

- Auth/session : `api/login.php`, `api/logout.php`, `api/check_auth.php`, `api/auth.php`, `api/register.php`, `api/update_account.php`
- Profile : `api/get_profile.php`, `api/get_user_profile.php`, `api/update_profile.php`
- Admin destructif / moderation complexe : `api/ban_user.php`, `api/kick_member.php`, `api/set_member_role.php`, `api/get_all_users.php`, `api/get_user_servers.php`, `api/get_users_in_server.php`
- DM : `api/start_dm.php`, `api/send_dm.php`, `api/get_dm_messages.php`, `api/get_dm_notifications.php`
- Divers hors batch : `api/delete_message.php`, `api/get_my_server_role.php`, `api/health.php`, `/invite.php`

## Ambiguïtés documentées et périmètre minimal sécurisé

- `accept_invite` et `create_invite` présentent une ambiguïté documentaire sur le code HTTP non-auth (200 dans la matrice, 401 possible dans le plan). Les tests figent donc le **shape JSON d'échec** et acceptent `200|401` pour éviter de sur-spécifier au-delà du minimum sûr.
- Les invariants D2 et D3 sont explicitement figés dans les tests :
  - D2 : `send_message` n'impose pas la membership serveur pour un user authentifié.
  - D3 : `get_server_name` ne vérifie pas la membership serveur.

## Notes d'implémentation

- Seeder déterministe : `Tests\Contract\Support\TestDatabaseSeeder`.
- Session de test via cookie `PHPSESSID` généré localement : `Tests\Contract\Support\SessionHelper`.
- Client HTTP contractuel vers le runtime legacy réel : `Tests\Contract\Support\BiscordHttpClient`.
