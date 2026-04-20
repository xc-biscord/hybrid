## 2026-04-18 — Lot 1 familles restantes: Invitations (faible risque)

### Objectif de l’itération
Migrer une première famille d’endpoints encore legacy vers le pattern façade `/api/*.php` → controllers Laravel, sans big bang.

### Actions réalisées
- Migration de `/api/create_invite.php` vers `InvitationController::create()` via `laravel_proxy.php`.
- Migration de `/api/accept_invite.php` vers `InvitationController::accept()` via `laravel_proxy.php`.
- Ajout du triplet Laravel `InvitationController` / `InvitationService` / `InvitationRepository`.
- Conservation stricte des contrats legacy (routes, payloads, statuts HTTP 200 sur réponses métier, permissions).
- Ajout d’une documentation de slice (`docs/mvc-migration/slices/invitations.md`) et d’un rapport de compatibilité lot 1.

### Changements code runtime
- Aucun endpoint supprimé sous `/api/*.php`.
- Aucun changement de route publique.
- Endpoints invitations désormais orchestrés par Laravel tout en gardant la façade historique.

### Pourquoi cette famille en premier
- Faible complexité métier et faible couplage.
- Risque de régression limité comparé aux familles auth/admin/modération complexe.

# Migration log (MVC) — Biscord

## 2026-04-17 — Consolidation post-prompt-9 (sans changement de contrat)

### Objectif de l’itération
Corriger les écarts de conventions identifiés après les prompts 1→9, en restant strictement sur un patch incrémental et sans refonte.

### Actions réalisées
- Harmonisation de `api/update_account.php` sur le pipeline bootstrap commun (`jsonResponse`, `getJsonInput`, `apiKernel`, `respondFromController`) tout en conservant explicitement le message legacy `Non connecté` et le code HTTP historique `200` pour ce cas.
- Retrait du SQL direct de `AdminUserController::listUsers` au profit de la couche service/repository (`UserServerService` + `UserRepository`).
- Aucune modification de routes publiques, permissions, payloads ou statuts visibles côté client.

### Conventions consolidées (stabilisées)
- Adaptateur endpoint `/api/*.php` minimal: validation HTTP/session/entrée puis délégation contrôleur.
- Contrat legacy préservé par endpoint même si des conventions globales existent (ex. `Non connecté` vs `Non authentifié`).
- SQL centralisé dans les repositories pour les slices migrés.
- `ApiKernel` comme point de câblage unique contrôleurs/services/repositories.

### Écarts volontairement laissés en legacy avant prompts 10→14
- Endpoints non migrés restent hors bootstrap commun tant que leur slice n’est pas traitée.
- Hétérogénéité historique de certains messages/codes d’erreur conservée tant qu’aucune évolution contractuelle dédiée n’est validée.
- Exposition du champ `debug` sur `update_account` en erreur SQL conservée par compatibilité contractuelle.

## 2026-04-17 — Bilan explicite des étapes 1 → 9

### Prompt 1 — Baseline documentaire
- Inventaires API/front et baseline des contrats legacy publiés sous `docs/mvc-migration/`.

### Prompt 2 — Architecture backend cible incrémentale
- Formalisation du socle MVC minimal, principes d’adaptateurs fins et trajectoire Laravel-ready.

### Prompt 3 — Front profile: cadrage migration
- Documentation dédiée de la page profil (`accueil.html`) et de ses interactions API.

### Prompt 4 — Front servers: cadrage migration
- Documentation dédiée de la page serveurs et des conventions front↔API associées.

### Prompt 5 — Slice servers migré
- Migration `get_servers` / `create_server` vers chaîne adapter → controller → service → repository, avec compat `nom`/`name`.

### Prompt 6 — Slice register migré
- Migration de `register` avec conservation stricte des effets transactionnels legacy (session, hub auto-join, message de bienvenue).

### Prompt 7 — Slice channels migré
- Migration `get_channels` / `create_channel` avec préservation stricte des permissions `P2/P3/P1`.

### Prompt 8 — Stabilisation du socle API
- Consolidation du bootstrap partagé (`requireMethod`, `getJsonInput`, `requireAuthUserId`, `respondFromController`) et adoption progressive sur endpoints migrés.

### Prompt 9 — Extension migration endpoints de lecture/écriture ciblés
- Alignement d’un lot d’endpoints supplémentaires sur le socle commun sans changement de contrat public.

## 2026-04-08 — Slice Servers (`get_servers` / `create_server`)

### Objectif de l’itération
Extraire proprement le slice "servers" vers MVC en conservant strictement les contrats externes.

### Actions réalisées
- Endpoint adapters conservés fins (`api/get_servers.php`, `api/create_server.php`).
- Extraction explicite de la compatibilité payload `nom` / `name` côté `ServerService`.
- Consolidation de la logique métier/transactionnelle dans `ServerService`.
- SQL maintenu dans `ServerRepository`.
- Documentation de slice ajoutée: `docs/mvc-migration/slices/servers.md`.

### Changements code runtime
- Pas de changement de routes publiques.
- Pas de changement de schéma de payload de succès/erreur.
- Pas de changement de codes HTTP visibles.

### Résultat
- Slice servers aligné MVC, réutilisable comme patron pour channels/memberships, sans régression fonctionnelle attendue.

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
