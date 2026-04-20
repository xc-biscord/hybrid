# Migration front `serveur.html` → découpage incrémental sans big bang

## Contexte

`serveur.js` concentrait dans un seul fichier:
- les appels API (`fetch`)
- l'état local de page (canal courant, rôle, ctrl+b, utilisateur courant)
- le rendu DOM (channels, membres, messages, DM)
- la modération (kick, changement de rôle, suppression message)
- l'orchestration générale de la page

Objectif de ce slice: **extraire par responsabilités** en gardant `serveur.html` fonctionnel et sans changer l'UX.

## Découpage implémenté (itération 1)

### 1) Orchestration
- `frontend/server-page/controllers/ServerPageController.js`
  - point d'entrée de la page
  - enchaînement init + bindings événements
  - coordination API/store/views/modération/DM

### 2) État local
- `frontend/server-page/stores/ServerStore.js`
  - `serverId`, `currentChannelId`, `myRole`, `isP1`, `currentUserId`, `ctrlBActif`
  - helpers de permission (`canModerateMembers`, `canDeleteMessage`)

### 3) Transport API
- `frontend/server-page/api/ServerApiClient.js`
  - encapsule tous les endpoints utilisés par la page serveur
  - normalise `GET`, `POST JSON`, `POST FormData`

### 4) Rendu DOM par zone
- `frontend/server-page/views/ChannelListView.js`
- `frontend/server-page/views/MessageListView.js`
- `frontend/server-page/views/MemberListView.js`

Chaque vue rend sa zone et délègue les actions via callbacks.

### 5) Contrôleurs métier isolés
- `frontend/server-page/controllers/ModerationController.js`
  - kick membre
  - changement de rôle
  - suppression de message
- `frontend/server-page/controllers/DmNotifier.js`
  - badge DM non lus
  - avatars DM non lus
  - polling

### 6) Fichier d'entrée legacy conservé
- `serveur.js`
  - devient un bootstrap minimal
  - conserve la compatibilité avec les handlers inline HTML (`genererLienInvitation`, `toggleMenu`, `logout`, `fermerProfil`) via `window.*`

## Modifications HTML

- `serveur.html` garde son markup.
- changement minimal: script `serveur.js` chargé en `type="module"`.

## Mapping ancien → nouveau

| Responsabilité monolithe | Nouveau composant |
|---|---|
| init globale | `ServerPageController.init()` |
| état local global (`let ...`) | `ServerStore` |
| `fetch` directs | `ServerApiClient` |
| rendu channels | `ChannelListView` |
| rendu messages + bouton suppression | `MessageListView` + `ModerationController.deleteMessage()` |
| rendu membres + actions admin | `MemberListView` + `ModerationController` |
| DM badge + bulles | `DmNotifier` |
| fonctions globales appelées par HTML | bootstrap `serveur.js` |

## Ce qui reste volontairement legacy (pour itérations suivantes)

- appels `alert / confirm / prompt` conservés (pas de refonte UX)
- handlers inline dans `serveur.html` conservés
- absence de bus d'événements global (orchestration centralisée dans le contrôleur)
- pas de refactor CSS/markup

## Plan de migration recommandé (suite)

1. Extraire un `ProfileModalView` (rendu + bind bouton DM).
2. Factoriser les constantes d'assets par défaut dans un module partagé front.
3. Remplacer progressivement les `alert/confirm/prompt` par un composant de feedback non bloquant (si validé produit).
4. Ajouter des tests unitaires JS ciblés sur store + contrôleurs (mock API client).

