# Notes Phase 0 — points de reprise runtime contractuel

Ce document conserve les constats techniques déjà formulés sur les rouges Phase 0 afin de guider les corrections minimales, sans changer les payloads legacy.

## Décision de phase

- Le batch Laravel-ready déjà vert peut servir de périmètre de non-régression.
- Le GO Phase 1 officiel reste bloqué tant que les corrections Phase 0, un nouvel audit de sortie et un GO/NO-GO explicite ne sont pas réalisés.

## Bugs et écarts identifiés

1. `LoginContractTest` utilisait des mots de passe hardcodés différents de ceux de `TestAccounts`.
2. `RegisterService` écrit dans le Hub serveur/canal `1`, alors que le seeder contractuel ne créait que le serveur `1101` et le canal `1201`.
3. `start_dm` reçoit contractuellement `target_user_id`, alors que les services DM lisent `other_user_id`.
4. `BanUserRepository::isP1()` interroge une colonne `permission`, alors que le schéma utilise `permission_level`.
5. Plusieurs contrôleurs profile/account peuvent diverger entre session PHP legacy, session Laravel et middleware Laravel.
6. La matrice P1/P2/P3 reste ambiguë : certains tests attendent que `P3` puisse modérer, tandis que les services exigent actuellement `P2`.
7. Plusieurs endpoints legacy retournent volontairement HTTP 200 sur erreur, alors que les middlewares Laravel standardisent en 401/403.

## Ordre minimal de correction

1. Corriger les tests/fixtures évidents : login puis register.
2. Corriger l'adaptation DM sans changer le payload JSON legacy.
3. Stabiliser profile et user_profile autour d'une seule source de session compatible Phase 0.
4. Stabiliser update_profile puis update_account.
5. Corriger ban_user.
6. Traiter P1/P2/P3 en dernier, car l'impact est transversal.
