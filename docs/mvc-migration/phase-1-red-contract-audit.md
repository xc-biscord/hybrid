# Audit ciblé des 32 tests contractuels rouges restants

Date d'audit : 2026-06-08.

## 1. Résumé exécutif

Le batch Laravel-ready déjà vert couvre le chemin serveur/canaux/messages/invitations nécessaire au démarrage limité de la Phase 1 : `accept_invite`, `create_channel`, `create_invite`, `create_server`, `get_channels`, `get_messages`, `get_server_name`, `get_servers` et `send_message`.

Les 32 rouges restants ne forment pas un seul bloc métier cassé. Ils se répartissent en cinq familles :

1. **Contrats legacy intentionnellement non REST** : plusieurs endpoints historiques retournent HTTP 200 pour des erreurs d'authentification, de permission ou de validation. Les tests les documentent, mais le routage/middleware Laravel tend à normaliser en 401/403/404/405.
2. **Fixtures incohérentes avec les tests** : mots de passe du seeder différents de ceux de `LoginContractTest`, serveur/canal Hub absents pour le register, rôles serveur ambigus (`P3` utilisé comme rôle puissant par certains tests alors que le service exige `P2`).
3. **Endpoints partiellement migrés** : `start_dm` attend `other_user_id` côté service alors que le contrat utilise `target_user_id`; plusieurs endpoints profil/compte existent en contrôleurs API dédiés mais ne sont pas exposés de manière homogène par les routes Laravel publiques.
4. **Invariants mal documentés ou contradictoires** : rôle global `P1` dans `get_my_server_role`, distinction 403/404 des DM inconnus, forme exacte du corps de `/api/auth.php`.
5. **Bugs runtime potentiels** : `BanUserRepository::isP1()` cible une colonne `permission` inexistante dans le schéma historique qui utilise `permission_level`.

Conclusion : **GO limité Phase 1 possible** si la Phase 1 ne couvre que le batch Laravel-ready déjà vert. En revanche, **NO-GO pour basculer globalement `/api/*.php` vers Laravel** tant que les familles auth/profile/account/admin/DM/modération ci-dessous ne sont pas stabilisées.

## 2. Tableau des 32 fails classés

| # | Groupe | Test rouge | Cause probable | Classement | Coût | Traitement recommandé |
|---:|---|---|---|---|---|---|
| 1 | AuthContractTest | `test_auth_authenticated_returns_200_with_empty_body_invariant` | Le client contractuel expose `body`, pas `raw`, et l'endpoint succès est un invariant de garde vide difficile à exprimer côté Laravel. | test faux / invariant mal documenté | faible | Corriger le test pour lire `body`, puis décider si Laravel doit vraiment exposer `/api/auth.php` vide. |
| 2 | BanUserContractTest | `test_ban_user_unauthenticated_returns_200_not_401_invariant` | Le contrat legacy demande 200, alors que le middleware Laravel d'auth tend à produire 401 si la route passe par `auth.session`. | divergence legacy réelle | faible | Sortir `ban_user` du middleware standard ou router vers le contrôleur legacy-compatible. |
| 3 | BanUserContractTest | `test_ban_user_non_p1_returns_200_access_denied_invariant` | Même divergence : legacy 200 + JSON d'erreur, Laravel standardise en 403 via `p1.only`. | divergence legacy réelle | faible | Court-circuiter le middleware P1 pour conserver l'invariant legacy. |
| 4 | BanUserContractTest | `test_ban_user_zero_user_id_returns_200_invalid_invariant` | Si l'exécution passe par `BanUserController`, la vérification P1 peut échouer avant validation à cause du repository P1. | bug runtime potentiel | faible | Corriger la vérification P1 après validation du contrat exact. |
| 5 | BanUserContractTest | `test_ban_user_missing_user_id_returns_200_invalid_invariant` | Même famille que #4 : validation du payload masquée par auth/P1 ou par routage incomplet. | bug runtime potentiel | faible | Prioriser après avoir choisi un seul chemin Laravel pour `ban_user`. |
| 6 | BanUserContractTest | `test_ban_user_p1_can_ban_target_user` | `BanUserRepository::isP1()` cherche `permission` alors que les fixtures insèrent `permission_level`; risque de refus ou SQL error. | bug runtime réel | faible | Harmoniser repository/schéma sans changer le payload JSON. |
| 7 | GetDmMessagesContractTest | `test_get_dm_messages_unknown_conversation_returns_404` | Le repository vérifie existence et participation dans une même requête; conversation inconnue et non accessible deviennent indistinguables et remontent souvent 403. | invariant mal documenté | moyen | Décider si on veut distinguer 404 inconnu vs 403 non participant; sinon corriger le test. |
| 8 | GetMyServerRoleContractTest | `test_get_my_server_role_returns_null_for_non_member_invariant` | Le service retourne `P1` dès que l'utilisateur est admin global, même s'il n'est pas membre du serveur ciblé. | invariant mal documenté | faible | Documenter rôle effectif global vs rôle serveur strict; ajuster test ou service selon décision. |
| 9 | GetProfileContractTest | `test_get_profile_success_shape_for_non_p1_user` | Endpoint profil partiellement migré/exposé; risque de route absente ou de session lue différemment entre `$_SESSION` et session Laravel. | endpoint partiellement migré | moyen | Brancher officiellement le contrôleur profil sur les routes publiques legacy. |
| 10 | GetProfileContractTest | `test_get_profile_p1_flag_is_true_for_admin_user` | Même famille; dépend aussi de la cohérence `global_permissions.permission_level`. | endpoint partiellement migré | moyen | Vérifier route + repository P1. |
| 11 | GetProfileContractTest | `test_get_profile_requires_authentication` | Message legacy attendu : `Utilisateur non connecté`, pas le message standard `Non authentifié`. | divergence legacy réelle | faible | Garder un guard spécifique à cet endpoint. |
| 12 | GetProfileContractTest | `test_get_profile_includes_email_in_profile_invariant` | Invariant legacy sensible : le profil propre expose `email`; il faut le préserver si le frontend en dépend. | divergence legacy réelle | faible | Confirmer que la route pointe vers `GetProfileController`. |
| 13 | GetUserProfileContractTest | `test_get_user_profile_unauthenticated_returns_200_invariant` | Legacy retourne HTTP 200 `Non connecté`; Laravel standard tend à 401 si middleware appliqué. | divergence legacy réelle | faible | Utiliser un guard endpoint-spécifique. |
| 14 | GetUserProfileContractTest | `test_get_user_profile_success_shape` | Endpoint public utilisateur partiellement migré; attendu root key `user`. | endpoint partiellement migré | moyen | Exposer le contrôleur dédié et verrouiller la shape. |
| 15 | GetUserProfileContractTest | `test_get_user_profile_missing_user_id_returns_200_with_error_invariant` | Validation legacy non REST : 200 au lieu de 400. | divergence legacy réelle | faible | Préserver validation contrôleur sans FormRequest standard. |
| 16 | GetUserProfileContractTest | `test_get_user_profile_non_numeric_user_id_returns_200_with_error_invariant` | Même famille : 200 pour paramètre non numérique. | divergence legacy réelle | faible | Même traitement que #15. |
| 17 | GetUserProfileContractTest | `test_get_user_profile_unknown_user_returns_200_not_found_invariant` | Legacy retourne 200 `Utilisateur non trouvé`, pas 404. | divergence legacy réelle | faible | Ne pas remplacer par `abort(404)` Laravel. |
| 18 | GetUserProfileContractTest | `test_get_user_profile_does_not_expose_email_invariant` | Invariant de confidentialité : contrairement à `get_profile`, aucun email. | invariant mal documenté | faible | Ajouter une assertion de non-régression après routage. |
| 19 | KickMemberContractTest | `test_kick_member_p3_can_kick_regular_member` | Les tests traitent `P3` comme rôle autorisé; `ModerationService` exige actuellement `P2`. | fixture/seeder incorrect ou invariant rôle ambigu | moyen | Trancher hiérarchie P2/P3; aligner fixture ou permission service. |
| 20 | LoginContractTest | `test_login_success_with_username_returns_200_and_user_id` | Le test utilise `password_alice`, mais le seeder définit `alice-pass-123`. | test faux / fixture incohérente | faible | Remplacer les littéraux de test par `TestAccounts::password('alice')` ou aligner fixture. |
| 21 | LoginContractTest | `test_login_accepts_email_in_username_field_invariant` | Même incohérence de mot de passe que #20. | test faux / fixture incohérente | faible | Même correction. |
| 22 | RegisterContractTest | `test_register_success_returns_201` | `RegisterService` ajoute le nouvel utilisateur au Hub serveur/canal IDs 1, absents du seeder contractuel actuel. | fixture/seeder incorrect | faible | Ajouter fixtures Hub minimales côté tests, sans toucher `.env` ni `.env.example`. |
| 23 | SetMemberRoleContractTest | `test_set_member_role_p3_can_update_member_role` | Même ambiguïté de hiérarchie que #19 : `P3` attendu puissant par le test, service exige `P2`. | fixture/seeder incorrect ou invariant rôle ambigu | moyen | Corriger une seule fois la matrice de permissions. |
| 24 | StartDmContractTest | `test_start_dm_existing_conversation_returns_200_invariant` | Contrat envoie `target_user_id`; service lit `other_user_id`. | endpoint partiellement migré | faible | Accepter l'alias legacy `target_user_id` côté adaptateur/contrôleur. |
| 25 | StartDmContractTest | `test_start_dm_new_conversation_returns_201_invariant` | Même divergence de champ que #24. | endpoint partiellement migré | faible | Même correction. |
| 26 | UpdateAccountContractTest | `test_update_account_unauthenticated_returns_200_not_401_invariant` | Guard legacy retourne 200 `Non connecté`, incompatible avec middleware auth standard. | divergence legacy réelle | faible | Ne pas appliquer `auth.session` standard à cet endpoint. |
| 27 | UpdateAccountContractTest | `test_update_account_empty_payload_returns_error_message` | Validation legacy : payload vide retourne 200 avec message, pas 400. | divergence legacy réelle | faible | Conserver `AccountController`/validator legacy-compatible. |
| 28 | UpdateAccountContractTest | `test_update_account_duplicate_username_returns_200_with_error` | Legacy retourne 200 sur conflit domaine; Laravel moderne pourrait produire 409/500 selon chemin. | divergence legacy réelle | moyen | Mapper les erreurs SQL/domaines vers l'invariant contractuel. |
| 29 | UpdateProfileContractTest | `test_update_profile_success_returns_200_success_true` | Endpoint profil update partiellement migré/exposé; lecture session `$_SESSION` vs Laravel session à harmoniser. | endpoint partiellement migré | moyen | Exposer un chemin unique et tester session. |
| 30 | UpdateProfileContractTest | `test_update_profile_accepts_empty_payload_invariant` | Legacy accepte payload vide avec defaults. | divergence legacy réelle | faible | Préserver defaults `bio=''`, `avatar_url=''`, `status='disponible'`. |
| 31 | UpdateProfileContractTest | `test_update_profile_default_status_is_disponible_invariant` | Invariant de default status dépend du succès de `update_profile` puis de `get_profile`. | endpoint partiellement migré | moyen | Traiter après `get_profile`. |
| 32 | UpdateProfileContractTest | `test_update_profile_accepts_get_method_invariant` | Legacy accepte GET; une route Laravel REST `POST` seulement échouerait en 405. | divergence legacy réelle | faible | Utiliser `Route::any` ou wrapper legacy-compatible si invariant conservé. |

## 3. Causes racines probables

### A. Routage/middleware Laravel trop standard pour des endpoints legacy

Les routes Laravel déjà prêtes utilisent des groupes `auth.session` et `p1.only`, qui retournent naturellement 401/403. Or plusieurs contrats demandent explicitement HTTP 200 avec `success=false` pour des cas d'erreur historiques. Cela concerne surtout `ban_user`, `get_user_profile`, `update_account` et certains profils.

Coût estimé : **faible à moyen**. La correction est principalement un choix de routage/adaptateur, pas une refonte métier.

### B. Incohérences de fixtures contractuelles

Le seeder crée les comptes `alice`, `bob`, `admin`, `mod` avec des mots de passe `*-pass-123`, alors que `LoginContractTest` utilise `password_alice`. Le même seeder ne crée pas le serveur/canal Hub ID 1 requis par `RegisterService`.

Coût estimé : **faible**. Ces corrections appartiennent aux tests/fixtures, pas au runtime.

### C. Matrice de rôles P1/P2/P3 ambiguë

`ModerationService` accorde les actions de modération aux rôles qui passent `hasPermission(..., ['P2'])`, tandis que les tests attendent qu'Alice en `P3` puisse kick et modifier les rôles. Il faut trancher si `P3` est supérieur à `P2`, inférieur, ou un rôle legacy spécial.

Coût estimé : **moyen**, car la décision impacte `kick_member`, `set_member_role`, potentiellement `create_channel` et d'autres checks de permission.

### D. Adaptation de payload partielle

`StartDmContractTest` envoie `target_user_id`, mais `DmService::startConversationFromPayload()` lit `other_user_id`. Le service retourne bien 200/201 selon conversation existante/nouvelle si l'ID cible est compris.

Coût estimé : **faible**. Un alias d'entrée suffit côté adaptateur Laravel.

### E. Bugs runtime réels ou très probables

`BanUserRepository::isP1()` interroge `global_permissions.permission`, alors que le schéma et les fixtures utilisent `permission_level`. C'est un bug concret dès que le chemin d'exécution passe par ce repository.

Coût estimé : **faible**.

## 4. Tests probablement faux

1. `AuthContractTest::test_auth_authenticated_returns_200_with_empty_body_invariant` : le test lit `raw`, alors que le client HTTP contractuel retourne `body`.
2. `LoginContractTest::test_login_success_with_username_returns_200_and_user_id` : mot de passe littéral incohérent avec `TestAccounts`.
3. `LoginContractTest::test_login_accepts_email_in_username_field_invariant` : même incohérence.
4. `GetDmMessagesContractTest::test_get_dm_messages_unknown_conversation_returns_404` : à revalider contre le legacy exact; le code actuel ne distingue pas conversation inconnue et conversation inaccessible.
5. `GetMyServerRoleContractTest::test_get_my_server_role_returns_null_for_non_member_invariant` : si `P1` est un rôle effectif global, le test attend un rôle serveur strict et doit le dire explicitement.

## 5. Fixtures probablement à corriger

1. Mots de passe de login : remplacer les littéraux `password_alice` par les mots de passe centralisés de `TestAccounts`, ou aligner `TestAccounts` sur les tests.
2. Hub register : créer dans le seeder contractuel un serveur ID 1 et un canal ID 1 si `RegisterService` doit rester inchangé.
3. Rôles serveur : clarifier Alice `P3` vs permission requise `P2`; soit la fixture doit donner `P2` à Alice pour les tests de modération, soit le service doit reconnaître `P3` comme autorisé.
4. DM messages : si le contrat 404 est conservé, ajouter une fixture permettant de distinguer une conversation inexistante d'une conversation existante non accessible.

## 6. Bugs runtime potentiels

1. `BanUserRepository::isP1()` utilise une colonne absente (`permission`) au lieu de `permission_level`.
2. `start_dm` ne comprend pas le champ legacy `target_user_id`.
3. Les endpoints profil/compte peuvent être exposés par des routes Laravel incomplètes ou via des chemins session hétérogènes (`$_SESSION` vs `$request->session()`).
4. `get_dm_messages` confond possiblement 403 et 404.
5. `update_account` doit mapper les erreurs de domaine/SQL vers les codes legacy 200 attendus.

## 7. Ordre recommandé de correction

1. **Nettoyer les tests/fixtures évidents** : `LoginContractTest`, Hub ID 1 pour `RegisterContractTest`, correction `raw`/`body` dans `AuthContractTest`.
2. **Choisir la stratégie de routage legacy** : pour chaque endpoint non REST, décider `Route::any`/contrôleur dédié sans middleware standard vs middleware Laravel standard.
3. **Corriger les bugs à faible coût** : `BanUserRepository::isP1()` et alias `target_user_id` pour `start_dm`.
4. **Stabiliser profil/compte** : `get_profile`, `get_user_profile`, `update_profile`, `update_account`, en conservant les shapes et codes legacy.
5. **Trancher la matrice de rôles** : résoudre ensemble `kick_member`, `set_member_role`, `get_my_server_role`.
6. **Traiter les invariants restants** : 403/404 DM, rôle effectif P1, messages exacts d'auth.

## 8. GO / NO-GO Phase 1

**GO limité Phase 1** si le périmètre Phase 1 est strictement limité au batch Laravel-ready déjà vert : serveurs, canaux, messages et invitations listés dans le résumé exécutif.

**NO-GO global** pour remplacer tout `/api/*.php` ou pour annoncer une compatibilité Laravel complète. Les familles auth, profil, compte, DM start/messages, admin ban et modération contiennent encore des divergences legacy et des fixtures ambiguës.
