# Slice MVC — DM (backend + front)

## Objectif de cette étape
Migrer le périmètre messagerie privée vers une structure MVC plus propre côté backend et vers des modules dédiés côté front, **sans modifier les comportements existants**.

Périmètre migré:
- `api/start_dm.php`
- `api/get_dm_messages.php`
- `api/send_dm.php`
- `api/get_dm_notifications.php`
- `dm.html`

## 1) Analyse du domaine DM existant

Le domaine DM actuel repose sur 4 cas d'usage:
1. **Démarrer / retrouver une conversation privée** entre deux utilisateurs (`start_dm`).
2. **Lire les messages d'une conversation** avec contrôle d'accès strict au participant (`get_dm_messages`).
3. **Envoyer un message DM** dans une conversation (`send_dm`).
4. **Lister les notifications DM non lues** pour l'utilisateur courant (`get_dm_notifications`).

### Règles métier observées (legacy conservé)
- Une conversation DM est unique par paire d'utilisateurs normalisée (`min(user_id)`, `max(user_id)`).
- Un utilisateur ne peut pas démarrer une conversation avec lui-même.
- La lecture des messages marque la conversation comme lue (`dm_reads.last_read_at = NOW()`).
- Les notifications ne comptent que:
  - les messages envoyés par l'autre participant,
  - et postérieurs au dernier `last_read_at` connu.
- Le front DM:
  - refuse la page sans `cid` et affiche l'erreur `BISCORD-800`,
  - poll les messages toutes les 4 secondes,
  - groupe visuellement les messages consécutifs du même expéditeur,
  - garde l'avatar par défaut historique quand `avatar_url` est absent.

## 2) Design MVC backend introduit

### Controller
- `app/Controllers/DmController.php`
- Orchestration des endpoints:
  - `start(currentUserId, input)`
  - `messages(userId, conversationId)`
  - `send(senderId, data)`
  - `notifications(userId)`
- Mapping des erreurs/status HTTP legacy conservé:
  - `Identifiant utilisateur invalide` en `400`
  - `Accès refusé` en `403`
  - `Destinataire introuvable` en `404`
  - erreurs SQL en `500` avec messages historiques (`Erreur DB`, `Erreur de base de données`).

### Service
- `app/Services/DmService.php`
- Porte les règles métier DM:
  - validation des entrées (`other_user_id`, `conversation_id`, `content`)
  - normalisation de paire utilisateur pour création/recherche conversation
  - contrôle d'accès conversation
  - composition de la réponse `messages + recipient`
  - marquage lu lors de la lecture
  - récupération des notifications non lues.

### Repository
- `app/Repositories/DmRepository.php`
- Centralisation de tout le SQL DM:
  - recherche/création conversation
  - vérification accès conversation
  - lecture profil destinataire
  - lecture des messages triés
  - upsert de lecture (`dm_reads`)
  - insertion de message
  - agrégation des notifications non lues.

### Wiring kernel
- `app/Support/ApiKernel.php`
  - ajout de `dmController()`
  - injection `DmRepository` -> `DmService` -> `DmController`.

## 3) Façades API legacy conservées

Les 4 fichiers API restent des points d'entrée HTTP, mais délèguent désormais à `DmController`.

- `api/start_dm.php`
  - conserve `POST`, auth, parsing JSON.
- `api/get_dm_messages.php`
  - conserve auth + lecture `conversation_id` en query.
- `api/send_dm.php`
  - conserve `POST`, auth, parsing JSON.
- `api/get_dm_notifications.php`
  - conserve auth.

## 4) Migration front `dm.html` vers modules dédiés

### Nouveau découpage
- `dm.js` : bootstrap de page.
- `frontend/dm-page/api/DmApiClient.js` : transport API DM.
- `frontend/dm-page/controllers/DmPageController.js` : orchestration (init, submit, polling).
- `frontend/dm-page/views/DmView.js` : rendu DOM + bindings UI.
- `frontend/dm-page/stores/DmStore.js` : état local (`conversationId`, `messages`, `recipient`).

### `dm.html`
- script inline supprimé.
- chargement via `<script type="module" src="dm.js?v=1"></script>`.

### Comportements explicitement conservés
- message d'erreur plein écran et exception `BISCORD-800` si `cid` manquant.
- polling toutes les 4 secondes.
- envoi message via `POST /api/send_dm.php` puis rechargement immédiat.
- mêmes logs console (`Destinataire DM : ...`, warning si destinataire absent).
- même avatar par défaut historique.
- même stratégie de grouping visuel par expéditeur consécutif.

## 5) Risques de régression identifiés

1. **Couplage format payload front/back**: la vue dépend de la présence de `messages` et `recipient`; tout changement du contrat API cassera le rendu.
2. **Polling sans gestion d'erreur avancée**: en cas de panne API, comportement reste similaire (promesses rejetées visibles console), mais UX inchangée.
3. **Rendu HTML des messages via `innerHTML`**: risque XSS déjà présent dans le legacy, volontairement conservé.
4. **Compatibilité navigateurs modules ES**: `dm.html` dépend maintenant de `type="module"` (aligné avec le pattern déjà utilisé sur `serveur.html`).

## 6) Tests manuels prioritaires

1. **Ouverture DM sans `cid`**
   - Aller sur `dm.html` sans query.
   - Vérifier affichage erreur `BISCORD-800`.

2. **Chargement conversation valide**
   - Aller sur `dm.html?cid=<id valide>`.
   - Vérifier affichage destinataire + historique trié ascendant.

3. **Envoi DM**
   - Envoyer un message non vide.
   - Vérifier insertion immédiate dans la liste (après refresh post-submit).

4. **Validation contenu vide**
   - Soumettre vide/espaces.
   - Vérifier qu'aucun envoi n'est déclenché.

5. **Contrôle d'accès conversation**
   - Forcer un `cid` non autorisé.
   - Vérifier réponse API `403 Accès refusé` et absence de rendu de messages.

6. **Notifications non lues**
   - Envoyer des messages depuis un autre compte.
   - Vérifier `get_dm_notifications.php` puis disparition après ouverture de conversation (mise à jour `last_read_at`).
