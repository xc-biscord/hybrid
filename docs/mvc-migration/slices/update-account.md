# Slice MVC: `api/update_account.php`

## Objectif de migration

Transformer `api/update_account.php` en adaptateur HTTP fin, tout en conservant le contrat public existant (messages JSON, route, format global des erreurs).

## Use-cases métier identifiés (comportement historique)

1. **Refus si non connecté**
   - Condition: `$_SESSION['user_id']` absent.
   - Réponse: `{ "success": false, "error": "Non connecté" }`.

2. **Refus si aucune donnée updatable**
   - Condition: `username`, `email`, `password` absents ou vides.
   - Réponse: `{ "success": false, "error": "Aucune donnée à mettre à jour" }`.

3. **Mise à jour username/email**
   - Condition: `username` et/ou `email` non vides.
   - Action: `UPDATE users SET ... WHERE id = ?`.

4. **Changement de mot de passe avec vérification du mot de passe courant**
   - Condition: `password` non vide.
   - Sous-cas:
     - `current_password` manquant/vide → erreur `Mot de passe actuel requis`.
     - hash introuvable ou `password_verify` en échec → erreur `Mot de passe actuel incorrect`.
     - sinon hash bcrypt + persistance du nouveau hash.

5. **Erreur SQL**
   - Condition: exception PDO.
   - Réponse HTTP 500: `success=false`, `error="Erreur SQL"`, `debug=<message technique>`.

## Répartition par couches

### Adaptateur HTTP (`api/update_account.php`)
- Initialise l’environnement (`config` + autoload).
- Conserve le contrôle de session et le message `Non connecté`.
- Parse JSON de manière tolérante puis délègue au controller.
- Ecrit le status code + payload JSON retournés par la couche applicative.

### Controller (`App\Controllers\AccountController`)
- Orchestration endpoint.
- Appelle le validator pour normaliser la payload.
- Applique la règle "aucune donnée à mettre à jour".
- Traduit les exceptions métier/infra en contrat API historique.

### Service (`App\Services\AccountService`)
- Exécute les use-cases:
  - mise à jour identité (username/email)
  - changement mot de passe
- Applique les vérifications de sécurité liées au mot de passe courant.

### Repository (`App\Repositories\UserRepository`)
- Isole les opérations SQL:
  - update username/email
  - lecture hash mot de passe
  - update hash mot de passe

### Validator (`App\Validators\AccountUpdateValidator`)
- Normalise les entrées (trim / null).
- Détermine si la requête contient au moins un champ updatable.

## Sécurité: points vérifiés

- Vérification explicite d’authentification via session conservée au niveau adaptateur.
- Vérification obligatoire du `current_password` pour tout changement de mot de passe.
- Vérification `password_verify` avant remplacement du hash.
- Hachage du nouveau mot de passe via `PASSWORD_BCRYPT`.
- SQL paramétré (prepared statements) dans le repository.

## Compatibilité du contrat public

Conservé:
- route `/api/update_account.php`
- structure JSON de réponse `success` + `error`
- messages métiers existants (`Non connecté`, `Aucune donnée ...`, `Mot de passe actuel ...`)
- exposition temporaire de `debug` en cas d’erreur SQL (HTTP 500)

> Note: l’exposition de `debug` est techniquement risquée en production, mais maintenue ici volontairement pour éviter une rupture de contrat. Elle devra être traitée dans une étape contractuelle dédiée.
