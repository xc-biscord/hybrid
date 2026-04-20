# Migration front `serveurs.html` → architecture légère (transport / orchestration / rendu)

## Objectif

Extraire la logique inline de `serveurs.html` sans changer les comportements visibles ni les contrats API.

## Nouveau découpage

- **Transport API**: `frontend/api/client.js`
  - centralise `fetch` (`GET` / `POST`), `credentials: include`, sérialisation JSON.
- **Orchestration page serveurs**: `frontend/controllers/ServersPageController.js`
  - initialise la page, coordonne chargement + création, gère navigation.
- **Rendu DOM / interactions UI**: `frontend/views/ServersPageView.js`
  - écoute du formulaire, rendu de la liste, empty state, alertes utilisateur, redirection.

`serveurs.html` ne garde plus de logique métier inline: il charge juste le contrôleur en module ES.

## Mapping ancien → nouveau

| Avant (`serveurs.html` inline) | Après | Détails |
|---|---|---|
| `const API_BASE = "/api"` | `ApiClient` dans `frontend/api/client.js` | Base API centralisée, réutilisable par d'autres pages. |
| `chargerServeurs()` | `ServersPageController.loadServers()` + `ServersPageView.renderServers()` | Le contrôleur récupère les données; la vue gère le DOM. |
| `fetch(get_servers.php)` | `apiClient.get('/get_servers.php')` | Même endpoint et même credentials. |
| Création des `<div class="server-entry">` | `ServersPageView.renderServers()` | Même structure DOM et même click vers `serveur?id=<id>`. |
| Empty state `Aucun serveur...` | `ServersPageView.renderEmptyState()` | Texte inchangé. |
| `submit` form inline | `ServersPageView.bindCreateServer()` + `ServersPageController.createServer()` | Événement géré par la vue, logique API dans le contrôleur. |
| `fetch(create_server.php)` avec `{ nom }` | `apiClient.post('/create_server.php', { nom })` | Contrat payload conservé. |
| `alert("Serveur créé !")` / `alert("Erreur : ...")` | `ServersPageView.showCreateSuccess/Error()` | Messages conservés. |
| `chargerServeurs()` au chargement | `ServersPageController.init()` | Point d'entrée unique. |

## Impacts fonctionnels

- Aucun endpoint modifié:
  - `GET /api/get_servers.php`
  - `POST /api/create_server.php`
- Aucun changement UI attendu (mêmes classes CSS, mêmes textes, mêmes alertes).
- Architecture prête à répliquer sur `accueil.html`, `dm.html`, puis `serveur.html`.
