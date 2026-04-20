# Rapport de compatibilité — lot 1 (2026-04-18)

## 1) Classification des endpoints restants par familles

### Profile / account léger
- `/api/get_profile.php`
- `/api/get_user_profile.php`
- `/api/update_profile.php`
- `/api/update_account.php` (déjà MVC hors Laravel route, encore à basculer sur façade Laravel homogène)

### Invitations
- `/api/create_invite.php`
- `/api/accept_invite.php`
- (`/invite.php` hors `/api`, conservé legacy)

### Admin complémentaires
- `/api/ban_user.php` (admin P1 + suppression multi-tables)

### Auth legacy
- `/api/login.php`
- `/api/logout.php`
- `/api/check_auth.php`
- `/api/auth.php` (check session brut)

### Moderation complexe
- Famille déjà migrée partiellement mais encore sensible contractuellement:
  - `/api/set_member_role.php`
  - `/api/kick_member.php`
  - `/api/get_users_in_server.php`
  - `/api/get_my_server_role.php`

### DM
- Famille déjà migrée vers MVC non-Laravel, non prioritaire pour ce lot:
  - `/api/start_dm.php`
  - `/api/get_dm_messages.php`
  - `/api/send_dm.php`
  - `/api/get_dm_notifications.php`

## 2) Première famille choisie (faible risque)

**Invitations** (`create_invite`, `accept_invite`).

Raisons:
- surface fonctionnelle réduite,
- contraintes permission simples (membre serveur),
- absence de statuts HTTP hétérogènes à préserver (200 legacy),
- impact front concentré.

## 3) Endpoints migrés dans ce lot

- `/api/create_invite.php` → façade PHP conservée + délégation `InvitationController::create()`.
- `/api/accept_invite.php` → façade PHP conservée + délégation `InvitationController::accept()`.

## 4) Endpoints laissés en legacy (et raisons)

- **Profile / account léger**: hétérogénéité historique des messages d'erreur (`Non connecté` vs `Non authentifié`, champs `details/debug`) à cadrer avant bascule Laravel complète.
- **Auth legacy**: endpoints de session sensibles (`login/logout/check_auth`) avec effets de bord (redirection HTTP, session lifecycle) — risque régression élevé.
- **Admin complémentaires (`ban_user`)**: endpoint destructif multi-tables, nécessite sécurisation et tests ciblés avant migration.
- **Moderation complexe**: règles de permissions P1/P2/P3 déjà stables en MVC actuel; priorité moindre pour une bascule immédiate.
- **DM**: déjà migré en architecture contrôlée (controller/service/repository), pas bloquant pour l'objectif incrémental.

## 5) Recommandation famille suivante

**Prochaine famille recommandée: Profile / account léger**

Ordre proposé:
1. `get_user_profile.php` (lecture simple),
2. `get_profile.php` (lecture enrichie + `is_p1`),
3. `update_profile.php` (écriture unique table),
4. `update_account.php` (en dernier, car contrat d'erreur legacy plus atypique).

Ce séquencement minimise le risque de régression tout en augmentant la cohérence de la zone "compte" côté Laravel.
