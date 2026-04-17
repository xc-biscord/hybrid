# Front migration — `accueil.html`

## Objectif

Alléger `accueil.html` en retirant le script inline, sans changement visible côté utilisateur, et préparer l'écran profil pour la future refonte backend de `update_account.php`.

## Structure mise en place

- `frontend/controllers/ProfilePageController.js`
  - Orchestration de la page.
  - Chargement initial du profil.
  - Soumission des 3 formulaires (profil, compte, mot de passe).
  - Règles de validation mot de passe (confirmation + mot de passe actuel requis).
- `frontend/views/ProfilePageView.js`
  - Gestion DOM (lecture des champs, binding des formulaires).
  - Rendu du profil (username, avatar, champs pré-remplis).
  - Affichage des feedbacks utilisateur (toast).
- `frontend/api/client.js` (réutilisé)
  - Appels HTTP GET/POST avec cookies (`credentials: include`) et JSON.

## Comportements conservés

- Chargement du profil au démarrage via `get_profile.php`.
- Mise à jour du profil via `update_profile.php`.
- Mise à jour compte et mot de passe via `update_account.php`.
- Textes de feedback identiques (succès/erreur) pour ne pas modifier l'UX perçue.
- Fallback avatar vers `assets/default-user.png` en cas d'URL vide/invalide.

## Modifications HTML

- `accueil.html` garde sa structure.
- Seul changement: suppression du script inline et ajout d'un import module:
  - `frontend/controllers/ProfilePageController.js`

## Intérêt pour la suite backend

La page profil dépend maintenant d'un contrôleur unique, ce qui simplifie:

- l'adaptation du contrat de `update_account.php` (ou de son remplaçant) à un endroit unique;
- l'introduction progressive d'un endpoint dédié au mot de passe;
- les tests ciblés sur la couche orchestration sans toucher au markup.
