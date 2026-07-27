# Documentation d'analyse des modules

> **Date** : 27/07/2026 · **Branche** : `refactor/stock-rebuild` · Analyse générée par Claude Code
> Un document par module actif, structure commune en 9 sections : vue d'ensemble, fonctionnalités, pages et écrans (boutons + modales), spécifications UX, permissions et rôles, modèle de données (ERD mermaid), workflows métier, routes et endpoints, points d'attention.

## Documents

| Document | Module | Contenu clé |
|---|---|---|
| [ANALYSE_Core.md](ANALYSE_Core.md) | Administration | 12 pages, 8 modales, 26 permissions, 11 commandes console, gestion utilisateurs/rôles/permissions/modules, journal d'activité |
| [ANALYSE_Organisation.md](ANALYSE_Organisation.md) | Structure organisationnelle | 8 pages, 8 modales, 78 routes, hiérarchies site→bâtiment→étage→local et direction→service→unité, postes de travail |
| [ANALYSE_ParcInfo.md](ANALYSE_ParcInfo.md) | Parc informatique | ~40 écrans, ~35 modales, 18 catégories d'équipements + catégories dynamiques, licences/logiciels, consommables, bons de répartition, 44 rapports |
| [ANALYSE_Grh.md](ANALYSE_Grh.md) | Ressources humaines | 3 pages, 1 modale, dossiers employés — module embryonnaire |

## Architecture transverse

- **Socle** : Laravel 12 + `nwidart/laravel-modules` 12, `spatie/laravel-permission` (droits), `spatie/laravel-activitylog` (journal), `barryvdh/laravel-dompdf` (PDF), `rap2hpoutre/fast-excel` (exports), Ziggy (`route()` en JS).
- **Patterns UI communs** (détail dans `PATTERNS.md`) : listes Bootstrap Table en pagination serveur (`getData` + `queryParams`), modales Bootstrap en création/édition duale, confirmations et toasts SweetAlert2, Select2 dans les modales, quick-adds (création de référentiel à la volée), JS statique servi depuis `public/js/modules/<module>/`.
- **Navigation inter-modules** : sidebar générique `core::partials.sidebar-modules` pilotée par la clé `navigation` du config de chaque module, filtrée par permission et `Route::has()`.
- **Dépendances entre modules** : Grh ↔ Organisation (rattachement des dossiers employés / responsables), ParcInfo → Organisation (localisations, affectations), ParcInfo → Grh (employés), tous → Core (users, layouts, permissions).
- **Historique** : les modules Achat et Stock ont été supprimés le 27/07/2026 (commits `547ddef`→`ea44c33`). Leurs spécifications `CDC_Achat.md` / `SFD_Achat.md` sont conservées à la racine comme trace fonctionnelle. L'acquisition du matériel se fait par saisie manuelle ParcInfo en attendant un futur mécanisme.

## Synthèse des constats à traiter (relevés en §9 des documents)

### Sécurité — prioritaire

| Constat | Module | Détail |
|---|---|---|
| Aucune permission appliquée côté serveur | Core, Grh | Seul le middleware `auth` protège les routes ; le chantier P8 n'a couvert que ParcInfo (et partiellement Organisation) |
| `PosteTravailController` sans middleware de permission | Organisation | Seul contrôleur du module sans `HasMiddleware` |
| Routes `toggle-status` (×8) et `locaux/api` non protégées | Organisation | Aucune permission exigée |
| Permissions incomplètes / doublons | ParcInfo | 12 catégories couvertes par le seul `.index` (repli du trait), `licences.create` sans permission, doublons `parcinfo.*` vs `parc-info.referentiels.*` |

### Bugs fonctionnels

| Constat | Module | Détail |
|---|---|---|
| `type_service` : enum `medico-technique` vs validation `medico_technique` | Organisation | Insertion impossible pour ce type |
| Bouton « Sync Permissions » appelle `core:sync-permissions` (nom réel : `cores:sync-permissions`) | Core | Bouton cassé |
| `cores:user-permissions` et `activities:cleanup-expired` plantent | Core | Méthode `findByIdOrUsername` et scope `expired()` inexistants |
| Avatars uploadés jamais résolus ; écran config module inopérant ; boutons Exporter/Nettoyer du journal sans handler | Core | Détail en §9 du document |
| Bug de parenthésage du filtre site dans `getData` équipements ; doublon `telephonie`/`terminaux-ip` | ParcInfo | Détail en §9 du document |
| `Site::magasins()` référence une classe inexistante ; `generateCode()` sujet aux collisions | Organisation | Code mort post-suppression Stock / fragilité |
| `<x-grh::layouts.master>` casse `php artisan view:cache` | Grh | Stub sans classe ni vue anonyme (les 3 autres modules ont `components/layouts/master.blade.php`) |
| Tri non whitelisté (500 sur colonnes calculées), `LIKE` sensible à la casse | Grh | Détail en §9 du document |

### Dette technique

| Constat | Module | Détail |
|---|---|---|
| ~6 000 lignes de JS mort par catégorie d'équipement | ParcInfo | Seuls `dynamic-index.js` et `selection_modals.js` sont chargés |
| 18 blocs de routes répétitifs par catégorie | ParcInfo | Factorisation en boucle possible |
| Modèles Eloquent pointant des tables supprimées | ParcInfo | Résidus post-suppression Stock à purger |
| Suppressions définitives présentées comme « désactivation » ; gardes limitées aux enfants actifs | Organisation | Risque d'erreur FK 500 |
| `ilike` PostgreSQL-only dans plusieurs `getData` | Organisation, Grh | Non portable |
| Partial `sidebar-modules` sans consommateur restant | Core | Orphelin depuis la suppression d'Achat |
| Couverture de tests très inégale | Tous | 67 ParcInfo, 17 racine, 1 Grh, 0 Organisation |
