# Audit de sortie — Phase 0 (tests contractuels & fiabilisation du run)

_Date d'audit : 2026-04-20_

## 1) Résumé exécutif

- **État global Phase 0** : la base contractuelle est en place, stable côté architecture de test (seed/reset, auth/session, client HTTP, bridge hybride observé), avec une couverture active de **9 endpoints Laravel-ready sur 32 endpoints publics inventoriés**.
- **Ce qui est sécurisé** :
  - exécution des tests contre `biscord_db_tests` uniquement ;
  - source de vérité unique des comptes de test ;
  - scénarios clés d'auth de test via `PHPSESSID` ;
  - invariants legacy critiques déjà figés (notamment D2 `send_message` et D3 `get_server_name`).
- **Ce qui reste à surveiller** : couverture encore partielle hors lot Laravel-ready (auth/session legacy, moderation/admin, DM, profile), et prérequis DB Laravel D10 avant Phase 1.
- **Recommandation** : **GO conditionnel Phase 1** pour un périmètre strictement limité aux endpoints Laravel-ready déjà couverts, **NO-GO** pour toute extension Phase 1 hors ce périmètre tant que les préconditions ne sont pas explicitement validées.

---

## 2) Couverture (où on en est réellement)

## 2.1 Endpoints couverts par les tests contractuels actifs

Couverture actuelle constatée dans `laravel/tests/Contract/*ContractTest.php` :

1. `api/accept_invite.php`
2. `api/create_channel.php`
3. `api/create_invite.php`
4. `api/create_server.php`
5. `api/get_channels.php`
6. `api/get_messages.php`
7. `api/get_server_name.php`
8. `api/get_servers.php`
9. `api/send_message.php`

Soit **9/32 endpoints publics** de la matrice Phase 0.

## 2.2 Endpoints non couverts à date

### Auth/session
- `api/login.php`
- `api/logout.php`
- `api/check_auth.php`
- `api/auth.php`
- `api/register.php`
- `api/update_account.php`

### Profile
- `api/get_profile.php`
- `api/get_user_profile.php`
- `api/update_profile.php`

### Admin/modération
- `api/ban_user.php`
- `api/kick_member.php`
- `api/set_member_role.php`
- `api/get_all_users.php`
- `api/get_user_servers.php`
- `api/get_users_in_server.php`
- `api/delete_message.php`
- `api/get_my_server_role.php`

### DM
- `api/start_dm.php`
- `api/send_dm.php`
- `api/get_dm_messages.php`
- `api/get_dm_notifications.php`

### Divers
- `api/health.php`
- `/invite.php` (racine)

## 2.3 Lots/familles couverts

- **Couvert et stabilisé (première vague)** : lot **Laravel-ready**.
- **Partiellement/Non couvert** : auth/session, profile, moderation/admin, DM, endpoints procéduraux restants.

---

## 3) Invariants figés (encodés volontairement)

Invariants observés et explicitement figés par les tests actifs :

1. **`create_server` accepte `nom` ET `name`** (double contrat conservé).
2. **`send_message` conserve le gap d'autorisation D2** : un user authentifié non membre peut poster si le channel existe.
3. **`get_server_name` conserve le gap d'autorisation D3** : pas de vérification de membership serveur.
4. **`accept_invite` et `create_invite`** : en cas d'échec observé, les tests figent le shape de réponse legacy actuel (`success` + `error`) sans sur-spécifier au-delà de l'observé.
5. **Méthodes HTTP** : les endpoints POST strict (`create_channel`, `create_server`, `send_message`) gardent le `405` sur GET.

Ambiguïtés tranchées par observation réelle (et non par spéculation) :
- priorité à la **forme de réponse réellement observée** plutôt qu'à une norme JSON cible future ;
- conservation explicite des comportements permissifs du legacy tant que la phase de standardisation HTTP/JSON (Phase 4) n'est pas ouverte.

---

## 4) État d'exécution

## 4.1 État des lots

- **Lot Laravel-ready contractuel** : structure de tests complète et cohérente.
- **Autres lots** : non encore implémentés en tests contractuels.

## 4.2 Rouge/vert à date

- Dans cet environnement de travail, l'exécution `php artisan test --testsuite=Contract` n'a pas pu être rejouée car `laravel/vendor/autoload.php` est absent localement.
- Ce point est une **limitation d'environnement local**, pas un changement de runtime applicatif ni de contrat.

## 4.3 Points stabilisés

- reset DB déterministe ;
- comptes de test centralisés ;
- authentification de test via cookie session ;
- client HTTP contractuel indépendant du router Laravel ;
- couverture prioritaire du lot le plus sûr pour l'entrée en Phase 1.

## 4.4 Risques encore ouverts

1. **Couverture incomplète** hors Laravel-ready : risque de surprise lors de la bascule du point d'entrée si on élargit trop vite.
2. **D10 migration users Laravel vs schéma legacy** : prérequis bloquant à gérer explicitement avant toute action DB liée Phase 1/2.
3. **Comportements permissifs legacy** (D2/D3 etc.) : risque de régression involontaire si "correction" non encadrée pendant l'unification.
4. **Routes mutatrices non strictes** sur plusieurs endpoints non couverts : surface de comportement historique à figer avant standardisation.

---

## 5) Qualité de l'infra de test

## 5.1 Source de vérité des comptes

- **Unique** : `laravel/tests/Contract/Support/TestAccounts.php`.
- Seeder contractuel reconstruit les users à partir de cette source.

## 5.2 Stratégie seed/reset

- `resetAndSeed()` : truncate complet des tables de test, puis seed déterministe.
- garde-fou explicite : erreur immédiate si la DB courante n'est pas `biscord_db_tests`.

## 5.3 Auth/session de test

- login contractuel via `/api/login.php`, capture du cookie `PHPSESSID`, réutilisation automatique sur les requêtes suivantes.
- nettoyage de cookies avant chaque `actingAs()` pour éviter les contaminations inter-tests.

## 5.4 Base URL / host / cookies

- `BiscordHttpClient` normalise l'URL (notamment `localhost` -> `127.0.0.1`) et centralise les cookies.
- Les tests passent par le runtime HTTP legacy réel (`/api/*.php`) et non par un fake controller.

## 5.5 Bridges hybrides legacy -> Laravel

- Le lot couvert correspond précisément aux endpoints déjà branchés en bridge Laravel ;
- c'est le meilleur terrain pour sécuriser la future unification du point d'entrée (Phase 1) avec risque métier réduit.

## 5.6 Fragilités à ne pas ignorer

- dépendance à un serveur HTTP de test démarré ;
- dépendance à un schéma DB de test aligné ;
- nécessité de garder les invariants permissifs figés tant que Phase 4 n'est pas engagée.

---

## 6) GO / NO-GO Phase 1

## Décision proposée

- **GO conditionnel** pour Phase 1 **uniquement** sur le périmètre Laravel-ready déjà couvert contractuellement.
- **NO-GO** pour une Phase 1 élargie (auth/session, moderation, DM, profile) avant extension de couverture contractuelle.

## Préconditions explicites à valider avant démarrage effectif Phase 1

1. Run contractuel vert dans l'environnement cible de l'équipe.
2. Validation écrite du périmètre Phase 1 (uniquement les 9 endpoints Laravel-ready).
3. Neutralisation/plan validé du point D10 (migration users Laravel divergente).
4. Gel explicite des invariants legacy déjà observés (pas de "fix" implicite pendant la bascule de front-controller).

## Garde-fous à maintenir en continu

- exécution contre `biscord_db_tests` uniquement ;
- seed déterministe à chaque test ;
- aucune modification payload/route publique sans test contractuel associé ;
- revue explicite de toute différence de code HTTP ou shape JSON.

---

## 7) Recommandations opérationnelles pour Phase 1

## 7.1 Ordre conseillé (dans le périmètre autorisé)

1. `get_servers`
2. `get_channels`
3. `get_messages`
4. `get_server_name`
5. `create_server`
6. `create_channel`
7. `create_invite`
8. `accept_invite`
9. `send_message`

Raison : partir des endpoints lecture simples, puis mutateurs, et finir sur le cas permissif D2 (`send_message`) qui nécessite la plus grande vigilance contractuelle.

## 7.2 Ce qu'il faut éviter au début

- toucher au couple `login/logout/check_auth` en même temps que l'unification d'entrée ;
- corriger les incohérences legacy hors plan de phase (surtout autorisation permissive et codes HTTP atypiques) ;
- ouvrir plusieurs lots hétérogènes en parallèle.

## 7.3 "Laravel-ready" maintenant

- Les 9 endpoints déjà couverts contractuellement et déjà en stack bridge L.

## 7.4 Ce qui doit attendre

- admin/modération destructive ;
- DM ;
- profile ;
- standardisation des erreurs/codes HTTP (Phase 4) ;
- convergence DB/migrations (Phase 5).

---

## 8) Matrice synthétique (couverture, statut, confiance, remarque migration)

Légende statut : **C** = couvert contractuellement ; **NC** = non couvert.
Niveau de confiance : **Élevé / Moyen / Faible** (au sens "sécurité de migration immédiate").

| Endpoint | Statut | Confiance | Remarque migration |
|---|---|---|---|
| `api/accept_invite.php` | C | Élevé | Laravel-ready ; invariant shape legacy figé |
| `api/create_channel.php` | C | Élevé | Laravel-ready ; POST strict figé |
| `api/create_invite.php` | C | Élevé | Laravel-ready ; comportement d'échec permissif figé |
| `api/create_server.php` | C | Élevé | Laravel-ready ; double entrée `nom/name` figée |
| `api/get_channels.php` | C | Élevé | Laravel-ready |
| `api/get_messages.php` | C | Élevé | Laravel-ready |
| `api/get_server_name.php` | C | Élevé | Laravel-ready ; invariant D3 figé |
| `api/get_servers.php` | C | Élevé | Laravel-ready |
| `api/send_message.php` | C | Élevé | Laravel-ready ; invariant D2 figé |
| `api/auth.php` | NC | Faible | include legacy à figer avant migration auth |
| `api/ban_user.php` | NC | Faible | non transactionnel, à traiter prudemment |
| `api/check_auth.php` | NC | Faible | contrat spécifique `{logged_in}` |
| `api/delete_message.php` | NC | Moyen | stack K, mutateur |
| `api/get_all_users.php` | NC | Faible | P1 admin critique |
| `api/get_dm_messages.php` | NC | Faible | DM non couvert |
| `api/get_dm_notifications.php` | NC | Faible | DM non couvert |
| `api/get_my_server_role.php` | NC | Faible | invariant `role:null` à figer |
| `api/get_profile.php` | NC | Faible | procédural + auth include |
| `api/get_user_profile.php` | NC | Faible | erreurs en 200 à préserver |
| `api/get_user_servers.php` | NC | Faible | P1 admin critique |
| `api/get_users_in_server.php` | NC | Moyen | authorization membership |
| `api/health.php` | NC | Élevé | trivial, peut être couvert rapidement |
| `api/kick_member.php` | NC | Faible | modération destructive |
| `api/login.php` | NC | Faible | pivot session, à isoler |
| `api/logout.php` | NC | Faible | redirect HTML legacy |
| `api/register.php` | NC | Moyen | stack K, création session |
| `api/send_dm.php` | NC | Faible | DM mutateur |
| `api/set_member_role.php` | NC | Faible | bug/contrat `P1` côté front |
| `api/start_dm.php` | NC | Faible | DM |
| `api/update_account.php` | NC | Faible | réponses permissives legacy |
| `api/update_profile.php` | NC | Faible | succès implicite legacy |
| `/invite.php` | NC | Moyen | endpoint racine à couvrir avant phases tardives |

---

## 9) Conclusion opérationnelle

## Ce qu'on garde tel quel

- harness contractuel actuel ;
- seed/reset déterministe et compte de vérité unique ;
- invariants legacy déjà figés sur le lot Laravel-ready.

## Ce qu'on surveille dès maintenant

- extension de couverture hors Laravel-ready avant toute extension de périmètre Phase 1 ;
- stabilité de l'environnement d'exécution (serveur HTTP + dépendances PHP locales) ;
- prérequis D10 sur la convergence schéma/migrations.

## Premier périmètre recommandé pour Phase 1

- unification du point d'entrée **strictement** sur les 9 endpoints Laravel-ready couverts.

## Ce qu'il ne faut surtout pas faire ensuite

1. mélanger unification du point d'entrée et refonte auth/session ;
2. corriger des incohérences legacy sans test contractuel préalable ;
3. ouvrir simultanément des lots admin + DM + profile sans couverture dédiée.
