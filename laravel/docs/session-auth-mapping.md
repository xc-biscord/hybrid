# Mapping système actuel -> Laravel (auth/admin)

## Objectif de compatibilité

Ce mapping reproduit le comportement historique basé sur `$_SESSION['user_id']` et la vérification P1 sans introduire Sanctum/Auth Laravel.

## Authentification session

- **Avant (legacy PHP)** : `requireAuthUserId()` dans `api/bootstrap.php` refuse si `$_SESSION['user_id']` absent et retourne `{"success":false,"error":"Non authentifié"}` avec HTTP `401`.
- **Maintenant (Laravel)** : `AuthSessionMiddleware` vérifie `$_SESSION['user_id']` et retourne la même structure JSON avec HTTP `401`.

## Vérification admin global (P1)

- **Avant (legacy PHP)** : `isP1($userId, $pdo)` via `PermissionService`/`GlobalPermissionRepository`.
- **Maintenant (Laravel)** : `EnsureGlobalAdmin` délègue à `App\Services\PermissionService::isP1()` qui vérifie l'existence de `user_id` dans `global_permissions`.
- **Réponse refus** : `{"success":false,"error":"Accès refusé : réservé aux P1"}` avec HTTP `403`.

## Enregistrement middleware

- Alias Laravel configurés :
  - `auth.session` -> `AuthSessionMiddleware`
  - `ensure.global.admin` -> `EnsureGlobalAdmin`

## Usage routes API

- Fichier `routes/api.php` activé dans le bootstrap Laravel.
- Utilisation possible :
  - Route authentifiée session : `Route::middleware(['auth.session'])`
  - Route admin P1 : `Route::middleware(['auth.session', 'ensure.global.admin'])`
