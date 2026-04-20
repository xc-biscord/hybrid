# Phase 2 — Contrat endpoint `create_server.php` (legacy vs Laravel migré)

_Date du contrôle : 2026-04-20_

## Périmètre

Comparaison du contrat HTTP observable pour `POST /api/create_server.php` entre:

- **Legacy actuel**: `api/create_server.php` (façade PHP legacy).
- **Laravel migré**: route Laravel `POST /api/create_server.php` via `LegacyBridgeController`.

Objectif: détecter toute divergence de contrat (status code, JSON exact, présence des champs, erreurs).

---

## A) Équivalence globale

**Verdict: NON équivalent (risque de régression silencieuse présent).**

Le chemin succès métier est aligné (201 + `{success:true,server_id}`), mais des divergences existent sur la gestion de méthode HTTP et le parsing d'entrée.

---

## B) Divergences exactes

## 1) Rejet d'une méthode non autorisée (GET)

### Legacy
- Vérification explicite `requireMethod('POST')` dans `api/create_server.php`.
- Réponse normalisée par `requireMethod`: **405** + JSON exact `{"success":false,"error":"Méthode non autorisée"}`. 

### Laravel migré
- Route définie uniquement en `Route::post('/create_server.php', ...)`.
- Un GET est rejeté par le routeur Laravel (pas par `requireMethod`).
- **Divergence probable de JSON**: message Laravel par défaut (`message`) au lieu du contrat legacy `success/error`.

### Impact contrat
- Même code HTTP (405) possible, **mais shape JSON potentiellement différent**.
- Contrat front attendu `success/error` non garanti.

---

## 2) Format d'entrée accepté (JSON strict vs payload mixte)

### Legacy
- Utilise `getJsonInput()`.
- Si body vide: `[]`.
- Si JSON invalide: **400** + `{"success":false,"error":"JSON invalide"}`.
- Le payload utile est extrait de JSON seulement (`nom` ou `name`).

### Laravel migré
- `LegacyBridgeController::extractInput()` fusionne `request` (form-urlencoded) + JSON.
- Le contrôleur `ServerController::create()` reçoit donc des entrées non-JSON si envoyées en formulaire.

### Impact contrat
- **Contrat élargi non souhaité** côté Laravel migré (acceptation de form data). 
- Risque de différence sur erreurs d'entrée (`JSON invalide`) qui ne passent plus par la même logique.

---

## 3) Succès métier nominal

### Legacy
- Appelle `ServerController::create($userId, $data)` via `laravel_proxy`.

### Laravel migré
- `LegacyBridgeController` appelle le même `ServerController::create(...)`.

### Impact contrat
- Sur payload valide (`nom` ou `name`) et session valide, le comportement est aligné: **201** + `{success:true,server_id}`.

---

## C) Niveau de risque

- **Risque global: ÉLEVÉ** pour la phase "migration invisible".
- Justification:
  1. Divergence potentielle sur le JSON d'erreur en 405 (shape contractuel cassable sans bruit).
  2. Divergence sur parsing d'entrée (JSON strict legacy vs payload mixte Laravel), pouvant masquer des erreurs client existantes.
  3. Ces écarts touchent le contrat externe (pas seulement l'implémentation interne).

---

## Preuves (code source)

- Façade legacy `create_server.php` avec `requireMethod('POST')`, `requireAuthUserId()`, `getJsonInput()`, puis délégation contrôleur. 
- Implémentation de `requireMethod()` et `getJsonInput()` (405 personnalisé, 400 `JSON invalide`).
- Route Laravel en `POST` uniquement vers `LegacyBridgeController`.
- `LegacyBridgeController` pour `create_server` + `extractInput()` (fusion form + JSON).
- Contrôleur `ServerController::create()` renvoyant 201 en succès et 400/500 sur erreurs métier/DB.
- Invariant documenté historiquement: `create_server` doit accepter `nom` et `name`.

