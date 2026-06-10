# Bootstrap Laravel isolé (Biscord)

_Date: 2026-04-18_

## Objectif
Initialiser un projet Laravel **isolé** dans ce repo, sans impacter l’existant (`/api/*.php`) ni déplacer les fichiers existants.

## Décision d’isolation
- Laravel doit vivre dans `laravel/` (sous-répertoire dédié) pour éviter tout conflit avec le backend PHP actuel.
- Les endpoints legacy `/api/*.php` restent inchangés et continuent à fonctionner en parallèle.

## Tentative d’initialisation dans cet environnement
Commande exécutée depuis la racine du repo:

```bash
composer create-project laravel/laravel laravel
```

Résultat:
- Échec réseau (accès sortant bloqué vers Packagist, `CONNECT tunnel failed, response 403`).
- Conclusion: le squelette Laravel n’a pas pu être téléchargé dans cet environnement de travail.

## Configuration cible à appliquer dès que le téléchargement est possible
Après création de `laravel/`, appliquer la configuration suivante:

### 1) `.env`
Copier `.env.example` vers `.env`, puis définir:

```dotenv
APP_NAME=Biscord
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=biscord_db
DB_USERNAME=biscord_test_app
DB_PASSWORD=<rotated-secret>

SESSION_DRIVER=file
SESSION_LIFETIME=120
```

> Credentials repris des paramètres existants (`config/config.php`) et base cible ajustée sur `biscord_db`.

### 2) Génération de clé
```bash
cd laravel
php artisan key:generate
```

### 3) Vérification connexion DB (biscord_db)
```bash
php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';"
```

Attendu: `DB OK`.

## Vérification faite dans l’environnement actuel (hors Laravel)
Un test direct PDO a été tenté vers `biscord_db`:

```bash
php -r '$pdo=new PDO("mysql:host=127.0.0.1;port=3306;dbname=biscord_db;charset=utf8mb4","biscord_test_app","<rotated-secret>"); echo "ok\n";'
```

Résultat:
- `SQLSTATE[HY000] [2002] Connection refused`
- Le serveur MySQL n’est pas joignable depuis cet environnement, donc la validation DB ne peut pas être confirmée ici.

## Garanties de non-régression
- Aucun fichier sous `/api/*.php` n’a été modifié.
- Aucun déplacement de fichiers existants.
- Le plan d’intégration Laravel reste strictement isolé dans `laravel/`.

## Checklist d’exécution (en environnement avec réseau + MySQL)
1. `composer create-project laravel/laravel laravel`
2. `cp laravel/.env.example laravel/.env`
3. Renseigner les variables DB ci-dessus (`biscord_db`).
4. `cd laravel && php artisan key:generate`
5. `php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';"`
6. Lancer Laravel en local si besoin: `php artisan serve`

