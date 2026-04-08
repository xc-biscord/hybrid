# Migration log (MVC) — Biscord

## 2026-04-08 — Baseline documentaire initiale

### Objectif de l’itération
Créer une base documentaire fiable pour une migration incrémentale vers MVC, sans toucher aux comportements existants.

### Actions réalisées
- Création du dossier `docs/mvc-migration/`.
- Ajout d’un inventaire exhaustif des endpoints (`endpoint-inventory.md`).
- Ajout d’un inventaire front↔API (`frontend-inventory.md`).
- Ajout d’un baseline des contrats et incohérences (`contracts-baseline.md`).

### Changements code runtime
- **Aucun** changement runtime (pas de refactor backend/frontend).
- **Aucune** route publique modifiée.
- **Aucun** contrat JSON modifié.

### Risques identifiés pour la suite MVC
1. Divergence des conventions (bootstrap vs legacy) compliquant une extraction propre en contrôleurs.
2. Endpoints mutateurs sans verrou de méthode HTTP.
3. Contrats d’erreur non homogènes (statut HTTP et payload).
4. Cas admin fragile (`set_member_role` vs besoin P1).
5. Contrôles d’accès incomplets sur certains endpoints (`send_message`, `get_server_name`).

### Prochain pas recommandé (itération suivante)
- Introduire une couche MVC "façade" endpoint par endpoint (adapter/controller minimal),
- en commençant par les endpoints les plus sollicités côté front,
- sans modifier les contrats externes.

