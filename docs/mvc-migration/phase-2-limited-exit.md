# Phase 2 limitée — audit de sortie

Date : 2026-06-08.

## 1. État des phases

| Phase | État | Note |
|---|---|---|
| Phase 0 | **Validée** | Baseline Contract annoncée verte : **120 tests passed**. |
| Phase 1 limitée | **Validée sur serveur réel** | Périmètre Laravel-ready limité confirmé : serveurs, canaux, messages, invitations. |
| Phase 2.1 — servers | **Appliquée** | `ServerRepository` Laravel réaligné sur le PDO injecté. |
| Phase 2.2 — channels | **Appliquée** | `ChannelRepository` Laravel réaligné sur le PDO injecté. |
| Phase 2.3 — messages | **Appliquée** | `MessageRepository` Laravel réaligné sur le PDO injecté. |
| Phase 2.4 — invitations | **Appliquée** | `InvitationRepository` Laravel réaligné sur le PDO injecté. |

## 2. Fichiers runtime réellement modifiés en Phase 2 limitée

Les changements runtime de la Phase 2 limitée sont limités aux repositories Laravel du périmètre déjà couvert :

- `laravel/app/Repositories/ServerRepository.php`
- `laravel/app/Repositories/ChannelRepository.php`
- `laravel/app/Repositories/MessageRepository.php`
- `laravel/app/Repositories/InvitationRepository.php`

Aucun changement runtime supplémentaire n'est requis par cet audit de sortie.

## 3. Périmètre confirmé

Familles couvertes uniquement :

- servers ;
- channels ;
- messages ;
- invitations.

Familles sensibles explicitement hors périmètre :

- auth/session ;
- compte/profil ;
- administration/modération ;
- DM ;
- `delete_message` ;
- `/invite.php` racine.

## 4. Garde-fous vérifiés

- Wrappers `/api/*.php` conservés.
- Aucun rewrite global activé pour `/api/*.php`.
- Aucun `FormRequest` branché implicitement dans le périmètre Phase 2 limitée.
- Aucun payload JSON changé.
- Aucun statut HTTP changé.
- Aucun invariant legacy corrigé implicitement.
- Aucun fichier frontend modifié.
- Aucun endpoint ni route ajouté, supprimé ou déplacé pendant l'audit.
- Aucun répertoire `app/*` supprimé.

## 5. Résultat Contract serveur réel

Résultat attendu et critère de sortie : **120 tests passed** sur la suite Contract exécutée contre le serveur réel.

Commande de référence :

```bash
cd laravel && php artisan test --testsuite=Contract
```

Note d'audit locale : l'environnement de travail courant ne contient pas `laravel/vendor/autoload.php`; une tentative d'installation Composer a été bloquée par l'accès GitHub/proxy. La confirmation finale doit donc être relue depuis l'exécution serveur réel Phase 2.5 avant toute discussion de Phase 3.

## 6. Décision

- **GO conditionnel** pour préparer une discussion de Phase 3, uniquement après confirmation explicite de la suite Contract serveur réel à **120 tests passed**.
- **NO-GO** pour une convergence globale `app/*` vers `laravel/app/*`.
- **NO-GO** pour la suppression des wrappers `/api/*.php`.

## 7. Réserves restantes

1. Les familles auth/session, compte/profil, administration/modération, DM, `delete_message` et `/invite.php` racine restent non validées par cette Phase 2 limitée.
2. Les divergences legacy non REST doivent rester contractuellement protégées avant toute extension : statuts HTTP historiques, shapes JSON, messages exacts et méthodes acceptées.
3. Les wrappers `/api/*.php` restent la façade publique de compatibilité tant qu'une phase dédiée de retrait n'est pas décidée.
4. Toute Phase 3 doit être discutée et découpée avant exécution ; aucune bascule globale n'est autorisée par cet audit.
