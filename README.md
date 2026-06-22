# Biscord

[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=xc-biscord_hybrid&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=xc-biscord_hybrid)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=xc-biscord_hybrid&metric=bugs)](https://sonarcloud.io/summary/new_code?id=xc-biscord_hybrid)
[![Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=xc-biscord_hybrid&metric=vulnerabilities)](https://sonarcloud.io/summary/new_code?id=xc-biscord_hybrid)
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=xc-biscord_hybrid&metric=code_smells)](https://sonarcloud.io/summary/new_code?id=xc-biscord_hybrid)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=xc-biscord_hybrid&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=xc-biscord_hybrid)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=xc-biscord_hybrid&metric=reliability_rating)](https://sonarcloud.io/summary/new_code?id=xc-biscord_hybrid)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=xc-biscord_hybrid&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=xc-biscord_hybrid)
[![Duplicated Lines (%)](https://sonarcloud.io/api/project_badges/measure?project=xc-biscord_hybrid&metric=duplicated_lines_density)](https://sonarcloud.io/summary/new_code?id=xc-biscord_hybrid)
[![Lines of Code](https://sonarcloud.io/api/project_badges/measure?project=xc-biscord_hybrid&metric=ncloc)](https://sonarcloud.io/summary/new_code?id=xc-biscord_hybrid)
[![Technical Debt](https://sonarcloud.io/api/project_badges/measure?project=xc-biscord_hybrid&metric=sqale_index)](https://sonarcloud.io/summary/new_code?id=xc-biscord_hybrid)

Biscord est une application web de messagerie communautaire (type Discord) :
serveurs, salons, messages publics, messages privés (DM), invitations, rôles et
modération, et un PoC d'authentification par **passkeys (WebAuthn)**.

L'architecture est **hybride** : les pages publiques (HTML/JS) et les URLs
historiques `/api/*.php` sont servies depuis la racine du projet, mais tout le
backend dynamique est porté par une application **Laravel** (sous `laravel/`).
Le script `router.php` fait le pont : il démarre la session PHP native puis
transmet les requêtes `/api/*.php` et `/invite.php` au runtime Laravel.

- **Production :** <https://biscord-api-stg.xcsoftworks.com>
- **Démonstration / recette :** <https://biscord-api-stg.xcsoftworks.com> (même instance, jeu de données de démonstration)
- **Documentation de l'API :** `api/docs/index.html`
- **Modèle de données (ERD) :** [`docs/database/`](docs/database/)

> La langue principale de la documentation est le **français**. Seuls quelques
> termes techniques restent en anglais lorsqu'ils sont d'usage (endpoint,
> session, passkey, contract test…).

## Fonctionnalités

- Inscription, connexion par mot de passe et déconnexion (session PHP).
- Connexion forte additive par **passkey / WebAuthn** (PoC).
- Serveurs : création, appartenance, rôles (`P2` admin, `P3` modérateur, `member`).
- Salons textuels et messages publics avec historique.
- Messages privés (DM) avec suivi de lecture et notifications de non-lus.
- Invitations par code pour rejoindre un serveur.
- Profils utilisateurs (bio, avatar, statut).
- Administration globale (super administrateur `P1`).

## Prérequis

| Outil | Version | Remarque |
| --- | --- | --- |
| **PHP** | **8.3** (testé : 8.3.6) | extensions `pdo_mysql`, `mbstring`, `openssl`, `json` |
| **Composer** | **2.x** (testé : 2.7) | gestionnaire de dépendances PHP |
| **MySQL** | **8.0** | MariaDB ≥ 10.6 également compatible |
| **Node.js** | **≥ 20.19** (testé : 24.x) | uniquement pour reconstruire les assets Laravel (Vite) |
| **npm** | **≥ 10** (testé : 11.x) | idem |

Node/npm ne sont nécessaires **que** si l'on souhaite reconstruire les assets
front de Laravel ; ils ne sont pas requis pour faire tourner l'API.

## Installation

### 1. Récupérer le projet

```bash
git clone https://github.com/kudasaixc/biscord.git biscord
cd biscord
```

### 2. Installer les dépendances PHP

```bash
cd laravel
composer install
```

### 3. Créer le fichier d'environnement Laravel

```bash
cp .env.example .env
php artisan key:generate
```

Puis ouvrir `laravel/.env` et renseigner les identifiants de la base
(`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

### 4. Créer le fichier de configuration natif

Le pont legacy (`router.php`) charge `config/config.php` à la racine pour ouvrir
la connexion PDO et démarrer la session. Ce fichier contient des secrets et
n'est **pas** versionné ; on le crée à partir du modèle :

```bash
cd ..
cp config/config.example.php config/config.php
```

Renseigner les mêmes identifiants de base que dans `laravel/.env`.

## Procédure base de données

Le schéma métier est provisionné par **SQL** (et non par les migrations
Eloquent). On n'exécute donc **pas** `php artisan migrate` : cela créerait une
table `users` Laravel en conflit avec le schéma applicatif.

### 1. Créer la base et l'utilisateur

```sql
CREATE DATABASE biscord_db CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
CREATE USER 'biscord_app'@'localhost' IDENTIFIED BY 'votre_mot_de_passe';
GRANT SELECT, INSERT, UPDATE, DELETE ON biscord_db.* TO 'biscord_app'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Importer le schéma

```bash
mysql -u root -p biscord_db < biscord_db.sql
mysql -u root -p biscord_db < laravel/database/sql/user_passkeys.sql
```

`biscord_db.sql` crée les 12 tables métier (utilisateurs, serveurs, salons,
messages, invitations, DM…) avec leurs clés et index. `user_passkeys.sql` ajoute
la table des passkeys (PoC WebAuthn).

### 3. Créer les comptes de démonstration

```bash
cd laravel
php artisan biscord:seed-demo
```

Cette commande (idempotente) insère les comptes ci-dessous et un petit jeu de
données navigable : un serveur de démonstration, un salon `general`, une
invitation `DEMO-INVITE`, un message de bienvenue et une conversation privée.

Après ces étapes, le projet est fonctionnel.

## Comptes de démonstration

| Rôle | E-mail | Mot de passe |
| --- | --- | --- |
| **Super Administrateur** (global `P1`) | `admin@example.com` | `Admin123!` |
| **Administrateur** de serveur (`P2`) | `moderator@example.com` | `Moderator123!` |
| **Utilisateur** standard | `user@example.com` | `User123!` |

Ces comptes sont créés par `php artisan biscord:seed-demo`. Les mots de passe
sont hachés (bcrypt) comme via l'inscription réelle : ils fonctionnent
directement avec `login.php`.

## Exécution locale

Depuis la **racine** du projet, servir l'application avec le serveur intégré de
PHP. Le routeur `router.php` redirige les URLs historiques `/api/*.php` et
`/invite.php` vers Laravel :

```bash
php -S 127.0.0.1:8000 -t . router.php
```

L'application est alors disponible sur :

```text
http://127.0.0.1:8000/
```

## Tests

Les **tests de contrat** (`laravel/tests/Contract`) font foi sur le comportement
des endpoints `/api/*.php`. Ils interrogent le serveur en HTTP et une base MySQL
dédiée (`biscord_db_tests`).

```bash
cd laravel

# Suite de contrat (serveur local + base de test requis)
php artisan test --testsuite=Contract

# Un test ciblé
php artisan test --filter LoginContractTest

# Tests unitaires / Feature (SQLite en mémoire, sans dépendance externe)
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

## Structure du projet

| Chemin | Rôle |
| --- | --- |
| `laravel/` | Application Laravel (contrôleurs, services, repositories, routes, tests) |
| `api/docs/` | Documentation statique de l'API |
| `frontend/`, `styles/`, `*.html`, `*.js` | Pages et assets front publics |
| `config/config.php` | Configuration native (PDO + session), **non versionnée** |
| `router.php` | Routeur du serveur PHP intégré → Laravel |
| `invite.php` | Façade racine pour l'URL historique d'invitation |
| `biscord_db.sql` | Dump du schéma de la base |
| `docs/database/` | Modèle relationnel (ERD : Mermaid, PNG, Markdown) |

## Fonctionnalités futures

Évolutions envisagées pour les prochaines itérations :

- **Temps réel** : passage des messages et notifications en WebSocket (Laravel Reverb) plutôt qu'en polling.
- **Passkeys** : sortie du statut PoC, gestion multi-appareils et récupération de compte.
- **Médias** : envoi de fichiers et d'images dans les salons et les DM.
- **Modération avancée** : journal d'audit, sanctions temporaires, signalements.
- **Rôles personnalisés** : remplacement des rôles fixes (`P1/P2/P3/member`) par des rôles et permissions paramétrables par serveur.
- **API publique** : authentification par token et documentation OpenAPI.
- **Internationalisation** complète de l'interface (FR/EN).

## Notes d'architecture

- Le runtime dynamique appartient à Laravel ; les URLs `/api/*.php` sont conservées comme routes de compatibilité.
- L'authentification s'appuie sur la session PHP native partagée entre `router.php` et Laravel.
- Les tests de contrat constituent le garde-fou de compatibilité : préserver les payloads JSON, statuts HTTP et comportements de session lors des évolutions.
