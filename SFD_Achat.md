# Spécifications Fonctionnelles Détaillées — Module Achat

**Application** : CHU-YO — Gestion du Parc Informatique
**Module** : `Modules/Achat` (Laravel Modules — `nwidart/laravel-modules`)
**Version du document** : 1.0
**Date** : 26 juillet 2026
**Statut** : Description de l'existant (rétro-spécification à partir du code source)

---

## Avertissement méthodologique

Ce document décrit **l'application telle qu'elle est réellement implémentée**, écran par écran, action par action. Chaque assertion est traçable vers un fichier source. Il ne décrit pas une cible souhaitée : lorsqu'un comportement observé est incohérent ou incomplet, il est **décrit tel quel**, puis référencé dans le chapitre 12 « Anomalies et dette fonctionnelle ».

Il complète — sans les remplacer — les deux documents existants à la racine du projet :

| Document | Niveau | Objet |
|---|---|---|
| `opencode-specifications-fonctionnelles-achat.md` | Audit technique | Routes, tables, cas d'usage résumés |
| `opencode-specifications-fonctionnelles-module-achat.md` | SFD métier | Acteurs, RACI, règles, workflows |
| **`SFD_Achat.md` (ce document)** | **SFD applicative / IHM** | **Pages, onglets, modales, boutons, colonnes, messages** |

---

## Table des matières

1. [Présentation générale](#1-présentation-générale)
2. [Architecture applicative](#2-architecture-applicative)
3. [Modèle de données](#3-modèle-de-données)
4. [Cartographie fonctionnelle](#4-cartographie-fonctionnelle)
5. [Catalogue des écrans](#5-catalogue-des-écrans)
6. [Catalogue des modales et composants transverses](#6-catalogue-des-modales-et-composants-transverses)
7. [Règles de gestion](#7-règles-de-gestion)
8. [Workflows de bout en bout](#8-workflows-de-bout-en-bout)
9. [Sécurité et matrice des permissions](#9-sécurité-et-matrice-des-permissions)
10. [Messages, erreurs et états d'écran](#10-messages-erreurs-et-états-décran)
11. [Intégrations inter-modules](#11-intégrations-inter-modules)
12. [Anomalies et dette fonctionnelle](#12-anomalies-et-dette-fonctionnelle)
13. [Annexes](#13-annexes)

---

## 1. Présentation générale

### 1.1 Objet du module

Le module **Achat** couvre la chaîne d'approvisionnement du CHU-YO, depuis le référencement d'un article jusqu'à son intégration physique dans le parc informatique :

> **Catalogue** → **Bon de Commande (BC)** → **Bordereau de Livraison (BL)** → **Assistant d'intégration** → **Fiches Équipements / Licences / Stock**

Sa particularité fonctionnelle est le **couplage automatique avec les modules ParcInfo et Stock** : la validation d'un bordereau de livraison ne se contente pas de clôturer un document — elle **crée les enregistrements métier** correspondants (fiches équipements avec code inventaire, licences logicielles, mouvements de stock).

### 1.2 Périmètre couvert

**Inclus dans le module :**

- Référencement des articles achetables (équipement, consommable, licence, prestation)
- Émission et cycle de vie des bons de commande fournisseurs
- Réception des livraisons, gestion des livraisons partielles et des reliquats
- Assistant de saisie d'inventaire (numéros de série, codes inventaire, clés de licence)
- Intégration automatique dans ParcInfo et Stock
- Consultation du stock des consommables issus du catalogue Achat
- Rapports, états et exports PDF
- Gestion documentaire (pièces jointes sur BC et BL)

**Hors périmètre (non implémenté malgré des permissions déclarées) :**

- Demandes d'achat / expression de besoin en amont du BC
- Circuit d'approbation multi-niveaux
- Gestion des factures et du règlement fournisseur
- Mouvements de stock manuels depuis le module Achat (entrée/sortie/inventaire)
- Écran dédié à la gestion des licences depuis le module Achat

### 1.3 Acteurs

Trois rôles métier sont définis dans `Modules/Achat/config/permissions.php` :

| Rôle | Libellé | Responsabilité fonctionnelle |
|---|---|---|
| `acheteur` | Acheteur | Gère le catalogue, crée et modifie les bons de commande en brouillon |
| `validateur_achat` | Validateur Achat | Valide et annule les bons de commande, exporte les rapports |
| `magasinier` | Magasinier | Réceptionne les livraisons, exécute l'assistant d'intégration, consulte les stocks |

Un rôle `Admin` reçoit automatiquement l'intégralité des permissions du module (`PermissionsAchatSeeder`).

### 1.4 Glossaire

| Terme | Définition |
|---|---|
| **Article** | Référence du catalogue achetable. N'est pas un objet physique. |
| **BC** | Bon de Commande. Document d'engagement vers un fournisseur. Numéroté `BC-AAAA-NNNN`. |
| **BL** | Bordereau de Livraison. Constate une réception physique. Numéroté `BL-AAAA-NNNN`. |
| **Réf. bordereau physique** | Numéro figurant sur le document papier du fournisseur. Unique dans l'application. |
| **Ligne de commande** | Couple (article, quantité, prix unitaire) d'un BC. Porte le compteur `quantite_livree`. |
| **Reste à livrer** | `quantite − quantite_livree` d'une ligne de commande, plancher à 0. |
| **Reliquat** | Ensemble des restes à livrer d'un BC en statut `valide` ou `partiel`. |
| **Wizard** | Assistant pas-à-pas de saisie d'inventaire, préalable obligatoire à la validation d'un BL contenant des équipements ou des licences. |
| **Unité** | Exemplaire physique individuel d'un article livré. Un article livré en 3 exemplaires génère 3 unités à saisir. |
| **Code inventaire** | Identifiant unique d'un équipement dans le parc. Format paramétrable `INV-{YYYY}-{SEQUENCE:4}`. |
| **Intégration** | Opération de création des fiches ParcInfo/Stock lors de la validation d'un BL. |

### 1.5 Dépendances inter-modules

```
                      ┌─────────────┐
                      │   ParcInfo  │
                      │             │
   lecture ──────────▶│ Marques     │
   (référentiels)     │ Catégories  │
                      │ Fournisseurs│
                      └──────┬──────┘
┌──────────┐                 │
│  Achat   │  écriture       │  Équipements
│          │────────────────▶│  Licences / Logiciels
│          │  (validation BL)│  Consommables + Mouvements
│          │                 │  Historique changements
│          │                 └─────────────┘
│          │                 ┌─────────────┐
│          │  écriture       │    Stock    │
│          │────────────────▶│  Mouvements │
│          │  (validation BL)│  Lots FIFO  │
└──────────┘                 └─────────────┘
```

- **ParcInfo** — dépendance **forte et bidirectionnelle**. Les tables `achat_articles`, `achat_bons_commande` référencent par clé étrangère `parc_info_marques`, `parc_info_categories_equipements`, `parc_info_fournisseurs`. Le module Achat est donc **inopérant** sans ParcInfo.
- **Stock** — dépendance **forte** depuis l'ajout de `Modules\Stock\Services\EntreeStockService` dans le constructeur de `WizardValidationService`. La validation d'un BL échoue si le module Stock est absent.
- **Core** — `App\Models\User` pour les champs d'audit et les permissions Spatie.

---

## 2. Architecture applicative

### 2.1 Arborescence du module

```
Modules/Achat/
├── app/
│   ├── Events/              4 événements (dont 1 jamais dispatché — cf. AN-08)
│   ├── Http/
│   │   ├── Controllers/     7 contrôleurs
│   │   └── Requests/        6 FormRequests
│   ├── Listeners/           5 listeners (corps vides — cf. AN-08)
│   ├── Models/              8 modèles Eloquent
│   ├── Providers/           3 providers
│   ├── Services/            6 services métier
│   └── Traits/              HasAuditFields
├── config/
│   ├── config.php           Types, statuts, seuils, préfixes
│   └── permissions.php      22 permissions + 3 rôles
├── database/
│   ├── migrations/          8 tables `achat_*`
│   └── seeders/             Permissions, paramètres, articles de démo
├── resources/views/         21 vues Blade
├── routes/web.php           Préfixe `/achat`, noms `achat.*`
└── tests/Feature/           9 tests fonctionnels
```

Les scripts JavaScript ne résident **pas** dans le module mais dans `public/js/modules/achat/` (8 fichiers, ~2 000 lignes).

### 2.2 Patterns applicatifs

Le module suit les conventions décrites dans `PATTERNS.md` :

| Couche | Convention |
|---|---|
| **Contrôleur** | Détecte `$request->ajax()` : renvoie du JSON pour Bootstrap Table, sinon la vue Blade. |
| **Service** | Toute règle métier et toute transaction sont dans un service, jamais dans le contrôleur. |
| **FormRequest** | Autorisation (`authorize()`) + validation syntaxique. Les règles sémantiques sont dans le service. |
| **Réponse JSON** | `{ success: bool, message: string, redirect?: string }`. Erreur métier → HTTP 422. |
| **Front** | jQuery + Bootstrap 5 + Bootstrap Table (pagination serveur) + SweetAlert2 + Chart.js. |
| **Audit** | Trait `HasAuditFields` : renseigne `created_by` / `updated_by` automatiquement. |
| **Suppression** | `SoftDeletes` sur toutes les entités principales. |

### 2.3 Stack front-end

| Bibliothèque | Usage |
|---|---|
| **Bootstrap Table** | Toutes les listes. Pagination serveur, tri, recherche, sélection radio, export colonnes. |
| **SweetAlert2** | Toutes les confirmations et notifications. Aucune `alert()` native. |
| **Chart.js** | Graphiques du tableau de bord et des statistiques. |
| **DomPDF** (`barryvdh/laravel-dompdf`) | Génération des PDF (BC, BL, rapports). |
| **AdminLTE 4** | Layout, sidebar, navbar. |
| **Helper `route()`** | Résolution des URLs nommées côté JavaScript (fourni globalement par l'application). |

### 2.4 Points d'entrée

Toutes les routes sont préfixées `/achat`, nommées `achat.*`, et protégées par le middleware `['auth', 'verified']`.

---

## 3. Modèle de données

### 3.1 Schéma relationnel

```
parc_info_marques ─────┐
parc_info_categories ──┼──▶ achat_articles ◀──┐
parc_info_fournisseurs─┘         │            │
        │                        │            │
        │                        ▼            │
        └──▶ achat_bons_commande ─▶ achat_lignes_commande
                     │                        │
                     ▼                        │
        achat_bordereaux_livraison ─▶ achat_lignes_livraison
                     │
                     ├──▶ achat_wizard_data (temporaire, purgé après validation)
                     │
                     └──▶ achat_documents (polymorphe, partagé avec les BC)

achat_parametres  (clé/valeur, hors relations)
```

### 3.2 `achat_articles` — Catalogue

| Colonne | Type | Contrainte | Description |
|---|---|---|---|
| `code_article` | string(50) | **unique** | Code de référencement interne |
| `designation` | string | requis, 3–255 | Libellé commercial |
| `description` | text | nullable | Spécifications libres |
| `type_article` | enum | `equipement` \| `consommable` \| `licence` \| `prestation` | Pilote le comportement métier |
| `reference_constructeur` | string(100) | **unique par marque** (`unique_ref_marque`) | Référence fabricant |
| `marque_id` | FK | requis → `parc_info_marques` | |
| `categorie_equipement_id` | FK | → `parc_info_categories_equipements` | Requis si type = équipement, interdit sinon |
| `fournisseur_prefere_id` | FK | nullable → `parc_info_fournisseurs` | Fournisseur habituel |
| `prix_indicatif` | decimal(12,2) | défaut 0 | Prix de référence, pré-remplit les lignes de BC |
| `unite_mesure` | string(20) | défaut `unite` | Unité, boîte, rouleau… |
| `taux_tva` | decimal(5,2) | défaut **20.00** | *Non exploité dans les calculs — cf. AN-03* |
| `compte_comptable` | string(20) | nullable | Imputation comptable |
| `seuil_alerte` | int | défaut 0 | Seuil de réapprovisionnement (consommables) |
| `stock_actuel` | int | défaut 0 | Incrémenté à la validation d'un BL |
| `duree_validite_mois` | int | nullable | Durée de licence |
| `url_fiche_technique` | string(500) | URL valide | Lien documentaire externe |
| `image` | string(500) | nullable | Photo, stockée dans `storage/app/public/articles` |
| `actif` | bool | défaut `true` | Un article inactif n'est plus sélectionnable dans un BC |

Index : `(type_article, categorie_equipement_id)`, `actif`. SoftDeletes + audit.

### 3.3 `achat_bons_commande` — Bon de commande

| Colonne | Type | Description |
|---|---|---|
| `numero_commande` | string(50) unique | Généré `BC-AAAA-NNNN` |
| `fournisseur_id` | FK requis | → `parc_info_fournisseurs` |
| `date_commande` | date | |
| `statut` | enum | `brouillon` \| `valide` \| `partiel` \| `livre` \| `annule` |
| `montant_total` | decimal(14,2) | Somme `quantite × prix_unitaire`, **hors taxes** |
| `commentaire` | text | Observations, max 1 000 caractères |
| `valide_par` | FK users | Renseigné à la validation |
| `date_validation` | timestamp | Renseigné à la validation |

Index : `numero_commande`, `statut`. SoftDeletes + audit.

### 3.4 `achat_lignes_commande`

| Colonne | Type | Description |
|---|---|---|
| `bon_de_commande_id` | FK **cascade delete** | Suppression du BC → suppression des lignes |
| `article_id` | FK | |
| `quantite` | int | Contrainte SQL `CHECK (quantite > 0)` (hors SQLite) |
| `prix_unitaire` | decimal(12,2) | Prix négocié, peut différer du prix indicatif |
| `quantite_livree` | int, défaut 0 | Incrémenté à chaque validation de BL |

Accesseurs : `reste_a_livrer` = `max(0, quantite − quantite_livree)`, `montant_ligne` = `quantite × prix_unitaire`.

### 3.5 `achat_bordereaux_livraison`

| Colonne | Type | Description |
|---|---|---|
| `numero_livraison` | string(50) unique | Généré `BL-AAAA-NNNN` |
| `bon_de_commande_id` | FK requis | Rattachement au BC |
| `date_livraison` | date | |
| `ref_bordereau_physique` | string(100) **unique** | Référence du document papier |
| `statut` | enum | `brouillon` \| `wizard` \| `valide` |
| `commentaire` | text | Observations de réception |

Index : `numero_livraison`, `ref_bordereau_physique`, `statut`. SoftDeletes + audit.

### 3.6 `achat_lignes_livraison`

| Colonne | Type | Description |
|---|---|---|
| `bordereau_livraison_id` | FK **cascade delete** | |
| `article_id` | FK | |
| `quantite_livree` | int | Contrainte SQL `CHECK (quantite_livree > 0)` (hors SQLite) |

### 3.7 `achat_wizard_data` — Données temporaires de l'assistant

| Colonne | Type | Description |
|---|---|---|
| `bordereau_livraison_id` | FK cascade | |
| `article_id` | FK | Une ligne par article de l'assistant |
| `unites_data` | JSON | Tableau des unités saisies : `[{numero_serie, code_inventaire, champs_valeurs{}}]` ou `[{cle_licence, date_activation, date_expiration}]` |
| `attributs_communs` | JSON nullable | Prévu, non alimenté par l'IHM actuelle |
| `completed` | bool | Étape validée par l'utilisateur |

**Ces enregistrements sont purgés** (`WizardData::where(...)->delete()`) à la fin d'une validation réussie.

### 3.8 `achat_documents` — Pièces jointes polymorphes

| Colonne | Type | Description |
|---|---|---|
| `documentable_type` / `documentable_id` | morphs | Cible : `BonCommande` ou `BordereauLivraison` |
| `nom` | string | Nom d'affichage, à défaut le nom original du fichier |
| `fichier_path` | string | Chemin dans `storage/app/public/achat_documents` |
| `taille` | int | Octets |
| `type_mime` | string | Pilote l'icône affichée |
| `notes` | text | Description libre |

### 3.9 `achat_parametres` — Paramétrage dynamique

Table clé/valeur consultée via `Parametre::getVal($cle, $defaut)`.

| Clé | Valeur par défaut | Usage |
|---|---|---|
| `pattern_code_inventaire` | `INV-{YYYY}-{SEQUENCE:4}` | Génération des codes inventaire |
| `prefix_bon_commande` | `BC` | Préfixe de numérotation des BC |
| `prefix_bordereau_livraison` | `BL` | Préfixe de numérotation des BL |
| `compteur_inventaire_annee` | `0` | Compteur séquentiel, verrouillé en base (`lockForUpdate`) |

---

## 4. Cartographie fonctionnelle

```
MODULE ACHAT
│
├── F1 · Pilotage
│   ├── F1.1 Tableau de bord ............................. E-01
│   └── F1.2 États & statistiques ........................ E-15, E-16
│
├── F2 · Catalogue des articles
│   ├── F2.1 Consulter et filtrer le catalogue ........... E-02
│   ├── F2.2 Créer un article ........................... M-01
│   ├── F2.3 Modifier un article ........................ M-01
│   ├── F2.4 Dupliquer un article ....................... E-02 / action
│   ├── F2.5 Activer / désactiver un article ............ E-02 / action
│   └── F2.6 Supprimer un article ....................... E-02 / action
│
├── F3 · Bons de commande
│   ├── F3.1 Consulter et filtrer les BC ................ E-03
│   ├── F3.2 Créer un BC ............................... E-04 (+ M-03, M-04)
│   ├── F3.3 Modifier un BC brouillon .................. E-05 (+ M-03, M-04)
│   ├── F3.4 Consulter le détail d'un BC ............... E-06 (7 onglets)
│   ├── F3.5 Valider un BC ............................. E-06 / action
│   ├── F3.6 Annuler un BC ............................. E-03, E-06 / action
│   ├── F3.7 Supprimer un BC brouillon ................. E-03 / action
│   ├── F3.8 Imprimer un BC ............................ E-07, E-08, M-06
│   └── F3.9 Joindre des documents à un BC ............. E-06 / onglet Documents
│
├── F4 · Bordereaux de livraison
│   ├── F4.1 Consulter et filtrer les BL ............... E-09
│   ├── F4.2 Créer un BL ............................... E-10 (+ M-05)
│   ├── F4.3 Consulter / modifier un BL brouillon ...... E-11 (+ M-05)
│   ├── F4.4 Supprimer un BL brouillon ................. E-09 / action
│   ├── F4.5 Imprimer un BL ............................ E-13, M-07
│   ├── F4.6 Joindre des documents à un BL ............. E-11 / onglet Documents
│   └── F4.7 Assistant d'intégration ................... E-12
│       ├── F4.7.1 Saisir les unités d'un équipement
│       ├── F4.7.2 Saisir les clés de licence
│       ├── F4.7.3 Sauvegarder une étape
│       └── F4.7.4 Finaliser et intégrer dans le parc
│
└── F5 · Stocks
    └── F5.1 Consulter le stock des consommables ....... E-14
```

**Récapitulatif : 16 écrans, 8 modales, 4 fonctionnalités majeures.**

---

## 5. Catalogue des écrans

> **Lecture des fiches** — Chaque fiche suit le même gabarit : identification, objectif, zones de l'écran, actions (déclencheur → effet → retour utilisateur), modales appelées, règles applicables, états particuliers.

---

### E-01 · Tableau de bord Achat

| | |
|---|---|
| **Route** | `GET /achat` → `achat.dashboard.index` |
| **Contrôleur** | `AchatController@index` |
| **Vue** | `achat::index` (`resources/views/index.blade.php`) |
| **Permission** | `achat.dashboard.view` |
| **Menu** | Sidebar › Tableau de bord |

**Objectif** — Donner en une page l'état de l'activité achat : volumétrie, alertes, tendance de dépense et derniers mouvements.

**Zone 1 — Cartes d'indicateurs (4 cartes)**

| Carte | Valeur principale | Valeur secondaire | Source |
|---|---|---|---|
| Catalogue Articles | `Article::count()` | *n* en alerte de stock | Tous types confondus |
| Bons de Commande | `BonCommande::count()` | *n* validé(s), *n* brouillon(s) | |
| Bordereaux de Livraison | `BordereauLivraison::count()` | *n* validé(s), *n* en cours | « en cours » = `brouillon` + `wizard` |
| Alertes Stock | Nombre de consommables sous seuil | « Consommables sous le seuil » | `stock_actuel <= seuil_alerte` |

**Zone 2 — Panneaux d'action rapide (4 tuiles cliquables)**

| Tuile | Destination |
|---|---|
| Nouveau Bon de Commande | E-04 |
| Enregistrer une Livraison | E-10 |
| Catalogue des Articles | E-02 |
| Assistant d'Intégration | E-09 (liste des BL) |

**Zone 3 — Graphiques**

| Graphique | Type | Données |
|---|---|---|
| Évolution Mensuelle des Dépenses (FCFA) | Courbe remplie | 6 mois, BC en statut `valide`/`partiel`/`livre`, montants formatés en XOF |
| Répartition par Fournisseur (Top 5) | Anneau | Somme des montants par fournisseur, ordre décroissant |

**Zone 4 — Tables de dernière activité**

| Table | Colonnes | Volume | Lien |
|---|---|---|---|
| Dernières Commandes | N° Commande *(lien vers E-06)*, Fournisseur, Montant, Statut *(badge coloré)* | 5 dernières | « Tout voir » → E-03 |
| Dernières Livraisons | N° Livraison *(lien vers E-11)*, BC associé, Bordereau physique, Statut | 5 dernières | « Tout voir » → E-09 |

**États particuliers**
- Table vide → « Aucune commande enregistrée. » / « Aucune livraison enregistrée. »
- Graphique sans données → canevas vide, aucun message d'accompagnement.

**Règles applicables** : RG-BC-08, RG-ART-08.

---

### E-02 · Catalogue des articles — Liste

| | |
|---|---|
| **Route** | `GET /achat/articles` → `achat.articles.index` |
| **Contrôleur** | `ArticleController@index` (double mode : HTML / JSON) |
| **Vue** | `achat::articles.index` |
| **Script** | `public/js/modules/achat/articles/index.js` |
| **Permission** | `achat.articles.view` |
| **Menu** | Sidebar › Catalogue Articles |

**Objectif** — Référencer et maintenir la liste des articles achetables.

**Zone 1 — Carte de filtres** (4 listes déroulantes, rechargement automatique de la table sur `change`)

| Filtre | Valeurs | Paramètre envoyé |
|---|---|---|
| Type d'article | Tous / Équipement / Consommable / Licence / Prestation | `type_article` |
| Marque | Toutes + liste `parc_info_marques` | `marque_id` |
| Catégorie d'équipement | Toutes + liste `parc_info_categories_equipements` | `categorie_equipement_id` |
| Statut | Tous / Actif / Inactif | `actif` |

La recherche plein texte native de Bootstrap Table (`search`) porte sur `code_article`, `designation`, `description`, `reference_constructeur`.

**Zone 2 — Barre d'outils** (boutons icône seule, tooltip au survol, désactivés tant qu'aucune ligne n'est sélectionnée)

| Bouton | Icône | Permission | Actif quand | Action |
|---|---|---|---|---|
| Créer | `fa-plus` (primary) | `articles.create` | toujours | Ouvre M-01 en mode création |
| Modifier | `fa-edit` (info) | `articles.edit` | 1 ligne sélectionnée | Charge l'article puis ouvre M-01 en mode édition |
| Dupliquer | `fa-clone` (secondary) | `articles.create` | 1 ligne sélectionnée | Duplique immédiatement, sans confirmation |
| Activer/Désactiver | `fa-toggle-on` (warning) | `articles.edit` | 1 ligne sélectionnée | Bascule `actif`, sans confirmation |
| Supprimer | `fa-trash` (danger) | `articles.delete` | 1 ligne sélectionnée | Confirmation SweetAlert puis suppression |

**Zone 3 — Table** (`#items-table`, pagination serveur, 25 lignes par défaut, choix 10/25/50/100)

| Colonne | Champ | Triable | Format |
|---|---|---|---|
| *(sélection)* | radio | non | Sélection unique |
| Code | `code_article` | oui | Gras |
| Désignation | `designation` | oui | Texte |
| Type | `type_article` | oui | Badge gris avec le libellé configuré |
| Réf. Constructeur | `reference_constructeur` | oui | `-` si vide |
| Marque | `marque` | oui | Libellé de la marque |
| Catégorie | `categorie` | oui | `-` si non applicable |
| Prix Indicatif | `prix_indicatif` | oui | Devise XOF, aligné à droite |
| Stock | `stock_actuel` | oui | Centré |
| Seuil Alerte | `seuil_alerte` | oui | Centré |
| Statut | `actif` | oui | Badge vert « Actif » / rouge « Inactif » |

**Interactions complémentaires**
- **Double-clic sur une ligne** → ouvre M-01 en mode édition (raccourci du bouton Modifier).
- Bouton **rafraîchir** et **sélecteur de colonnes** fournis par Bootstrap Table.

**Modales appelées** : M-01 (formulaire article), SW-01 (confirmation de suppression).

**Retours utilisateur**

| Action | Message |
|---|---|
| Création | « L'article '*désignation*' a été créé avec succès. » — toast 1,5 s |
| Modification | « L'article '*désignation*' a été mis à jour avec succès. » |
| Duplication | « L'article a été dupliqué sous le code '*code*-COPY'. » |
| Bascule d'état | « L'article a été activé / désactivé avec succès. » |
| Suppression possible | « L'article a été supprimé avec succès. » |
| Suppression impossible | « L'article est référencé dans des bons de commande. Il a été désactivé au lieu d'être supprimé. » *(affiché en succès — cf. AN-11)* |

**Règles applicables** : RG-ART-01 à RG-ART-10.

---

### E-03 · Bons de commande — Liste

| | |
|---|---|
| **Route** | `GET /achat/bons-commande` → `achat.bons-commande.index` |
| **Contrôleur** | `BonCommandeController@index` |
| **Vue** | `achat::bons_commande.index` |
| **Script** | `public/js/modules/achat/bons-commande/index.js` |
| **Permission** | `achat.bons_commande.view` |
| **Menu** | Sidebar › Bons de Commande |

**Objectif** — Piloter le portefeuille de commandes et accéder aux actions de cycle de vie.

**Zone 1 — Filtres**

| Filtre | Valeurs | Paramètre |
|---|---|---|
| Fournisseur | Tous + fournisseurs actifs | `fournisseur_id` |
| Statut | Tous / Brouillon / Validé / Livré Partiel / Livré Complet / Annulé | `statut` |

Recherche plein texte : `numero_commande`, `commentaire`, nom du fournisseur.

**Zone 2 — Barre d'outils**

| Bouton | Permission | Condition d'activation | Action |
|---|---|---|---|
| Créer (`fa-plus`) | `bons_commande.create` | toujours | Navigation vers E-04 |
| Voir / Modifier (`fa-eye`) | — | 1 ligne | Navigation vers E-06 |
| Imprimer (`fa-print`) | — | 1 ligne | Ouvre M-06 avec le PDF en iframe |
| Annuler (`fa-ban`) | `bons_commande.edit` | statut ∈ {`brouillon`, `valide`} | Confirmation puis annulation |
| Supprimer (`fa-trash`) | `bons_commande.delete` *(cf. AN-01)* | statut = `brouillon` | Confirmation puis suppression |

**Zone 3 — Table** (`#bons-commande-table`, tri par date de commande décroissante)

| Colonne | Format |
|---|---|
| N° Commande | Gras |
| Fournisseur | Nom |
| Date Commande | `JJ/MM/AAAA` |
| Montant Total | Devise XOF, aligné à droite |
| Statut | Badge : Brouillon *(gris)*, Validé *(bleu)*, Partiel *(cyan)*, Livré *(vert)*, Annulé *(rouge)* |
| Créé le | `JJ/MM/AAAA HH:MM` |

**Interaction** : double-clic sur une ligne → E-06.

**Modales appelées** : M-06 (aperçu PDF), SW-02 (confirmation annulation), SW-03 (confirmation suppression).

**Règles applicables** : RG-BC-04, RG-BC-05, RG-BC-06.

---

### E-04 · Bon de commande — Création

| | |
|---|---|
| **Route** | `GET /achat/bons-commande/create` → `achat.bons-commande.create` |
| **Soumission** | `POST /achat/bons-commande` → `achat.bons-commande.store` |
| **Contrôleur** | `BonCommandeController@create` / `@store` |
| **Vue** | `achat::bons_commande.create` |
| **Script** | `public/js/modules/achat/bons-commande/create.js` (partagé avec E-05) |
| **Permission** | `achat.bons_commande.create` |

**Objectif** — Saisir un bon de commande complet (en-tête + lignes) en une page, sans rechargement.

**Zone 1 — En-tête du bon de commande**

| Champ | Type | Obligatoire | Comportement |
|---|---|---|---|
| Fournisseur | Champ lecture seule + bouton « Choisir » | ✔ | Ouvre M-03. Après sélection : affiche le nom, le code fournisseur en dessous, et fait apparaître un bouton × de réinitialisation |
| Date de commande | Date | ✔ | Pré-remplie à la date du jour |
| Observations / Notes | Zone de texte 2 lignes | | Max 1 000 caractères |

**Zone 2 — Lignes de commande**

Tableau dynamique alimenté en JavaScript. **Une ligne vide est créée automatiquement au chargement.**

| Colonne | Largeur | Saisie |
|---|---|---|
| Article | 50 % | Bouton « Choisir un article » → M-04. Après sélection le bouton devient « Changer » et la désignation + le code s'affichent à côté |
| Quantité | 15 % | Numérique, minimum 1, centré |
| Prix Unitaire (FCFA) | 15 % | Numérique, minimum 0. **Pré-rempli avec le prix indicatif de l'article sélectionné** |
| Montant Ligne | 15 % | Calculé, lecture seule, devise XOF |
| Action | 5 % | Bouton corbeille |

**Pied de tableau — Totaux recalculés à chaque saisie**

| Ligne | Calcul |
|---|---|
| Total HT | Σ (quantité × prix unitaire) |
| TVA (18 %) | Total HT × 0,18 — **taux codé en dur, cf. AN-03** |
| Montant Total TTC | Total HT × 1,18 |

**Actions**

| Bouton | Effet |
|---|---|
| Ajouter une ligne | Ajoute une ligne vide en fin de tableau |
| Corbeille (par ligne) | Supprime la ligne. **Refuse si c'est la dernière** : « Un bon de commande doit contenir au moins une ligne. » |
| Annuler | Retour à E-03, sans confirmation |
| Enregistrer le Bon de Commande | Validation client puis soumission AJAX |

**Validation côté client (avant envoi)**

| Contrôle | Message affiché sous le champ |
|---|---|
| Fournisseur non sélectionné | « Veuillez sélectionner un fournisseur. » |
| Date vide | « La date de commande est requise. » |
| Article non choisi sur une ligne | « Veuillez choisir un article. » |
| Quantité < 1 ou non numérique | « Qté >= 1. » |
| Prix < 0 ou non numérique | « Prix >= 0. » |

Si un contrôle échoue : les champs fautifs passent en `is-invalid` (bordure rouge) et une alerte globale s'affiche — « Formulaire incomplet — Veuillez corriger les erreurs indiquées en rouge. »

**Validation côté serveur** (`StoreBonCommandeRequest`) — les erreurs 422 sont **re-ventilées sur les champs concernés**, y compris les erreurs indexées `lignes.N.champ`.

**Comportement au succès** — Toast « Enregistré avec succès ! » (1,5 s) puis redirection automatique vers E-06 du BC créé. Le bouton passe en état de chargement (« Enregistrement… » avec spinner) pendant la requête.

**Modales appelées** : M-03, M-04.

**Règles applicables** : RG-BC-01, RG-BC-02, RG-NUM-01.

---

### E-05 · Bon de commande — Modification

| | |
|---|---|
| **Route** | `GET /achat/bons-commande/{id}/edit` → `achat.bons-commande.edit` |
| **Soumission** | `PUT /achat/bons-commande/{id}` → `achat.bons-commande.update` |
| **Vue** | `achat::bons_commande.edit` |
| **Permission** | `achat.bons_commande.edit` |

**Objectif** — Corriger un bon de commande encore en brouillon.

**Différences avec E-04** (mêmes zones, même script) :

| Élément | Comportement en modification |
|---|---|
| Accès | **Redirection immédiate vers E-06** avec le message d'erreur « Ce bon de commande ne peut pas être modifié car il n'est plus en statut brouillon. » si `statut ≠ brouillon` |
| Champ caché `bc-id` | Présent, bascule le formulaire en mode `PUT` |
| Fournisseur | Pré-rempli, code fournisseur visible d'emblée, bouton × toujours affiché |
| Lignes | Pré-chargées depuis `existingLines` (article, quantité, prix négocié) |
| Bouton principal | « Enregistrer les modifications » |
| Bouton Annuler | Retour vers E-06 (et non vers la liste) |

**Effet de bord important** — La modification **supprime toutes les lignes existantes et les recrée** (`BonCommandeService::modifier`). Les identifiants de lignes changent et `quantite_livree` est remis à 0. Sans conséquence en pratique puisque seul un brouillon est modifiable, donc jamais livré.

**Règles applicables** : RG-BC-03.

---

### E-06 · Bon de commande — Fiche détail

| | |
|---|---|
| **Route** | `GET /achat/bons-commande/{id}` → `achat.bons-commande.show` |
| **Contrôleur** | `BonCommandeController@show` |
| **Vue** | `achat::bons_commande.show` (811 lignes — écran le plus riche du module) |
| **Permission** | `achat.bons_commande.view` |

**Objectif** — Vue à 360° d'une commande : état, contenu, réceptions, matériel généré, pièces jointes, traçabilité.

#### Zone 1 — Bandeau d'en-tête

Affiche le numéro de commande en grand, le badge de statut, puis en ligne : fournisseur, date, montant total.

**Barre d'actions contextuelles — la composition dépend du statut :**

| Statut | Boutons affichés |
|---|---|
| `brouillon` | Retour · **Modifier** (→E-05) · **Valider la commande** · **Annuler** · Imprimer/PDF |
| `valide` | Retour · **Créer Bordereau de Livraison** (→E-10 pré-filtré sur ce BC) · Imprimer/PDF |
| `partiel` | Retour · Imprimer/PDF |
| `livre` | Retour · Imprimer/PDF |
| `annule` | Retour uniquement |

Les boutons Modifier / Valider / Annuler sont conditionnés à la permission `achat.bons_commande.edit`, le bouton Créer BL à `achat.bordereaux.create`.

#### Zone 2 — Onglets (7)

**Onglet 1 · Fiche BC** — Deux colonnes.

*Colonne gauche — Informations générales* : N° Commande, Fournisseur (nom + code), Date Commande, Statut, Validé par, Date de validation, bloc Observations.

*Colonne droite — Lignes du bon de commande* :

| Colonne | Contenu |
|---|---|
| Article | Désignation en gras + code en petit |
| Catégorie | Badge du type d'article |
| Qté Comm. | Quantité commandée |
| Prix Unit. HT | Formaté |
| Montant HT | quantité × prix |
| Qté Livrée | En bleu |
| Reste | En rouge |
| Statut Ligne | **En attente** *(gris, qté livrée = 0)* · **Partiel** *(jaune)* · **Livré** *(vert)* |

*Pied de tableau* : Sous-total HT, TVA (18 %), Montant TTC.

**Onglet 2 · Lignes de commande** — Vue analytique complémentaire : Code, Désignation, Type, Mesure/Unité, Quantité, **Prix Indicatif** (référence catalogue), **Prix Négocié** (prix réellement commandé), Taux TVA. Permet de mesurer l'écart de négociation.

**Onglet 3 · Bordereaux associés** — Une carte par BL : numéro, badge de statut, référence physique, date de livraison, total d'unités livrées, bouton « Voir les détails » → E-11.
*État vide* : « Aucun bordereau de livraison associé à ce bon de commande. »

**Onglet 4 · Équipements intégrés** — Équipements ParcInfo dont `ref_bordereau` correspond à l'un des BL du BC.

| Colonne | Actions par ligne |
|---|---|
| Code Inventaire, Catégorie *(avec icône)*, Marque & Modèle, N° Série, Statut, État | **Voir la fiche** (nouvel onglet, route ParcInfo) · **Imprimer l'étiquette** (`parc-info.equipements.imprimer-etiquette`) |

*État vide* : « Aucun équipement n'a été généré pour ce bon de commande. »

**Onglet 5 · Documents joints** — badge du nombre de documents dans le libellé de l'onglet.

*Colonne gauche — formulaire d'ajout* : Nom du document *(facultatif)*, Fichier *(obligatoire, max 10 Mo, PDF/images/Word/Excel)*, Notes. Bouton « Téléverser le document ».

*Colonne droite — liste* : icône par type MIME *(PDF rouge, image verte, autre bleu)*, nom + date d'ajout, notes, taille en Ko, auteur, et deux actions : **Télécharger** et **Supprimer** *(avec confirmation)*.

L'ajout et la suppression se font en AJAX : la ligne est insérée/retirée du tableau et **le compteur de l'onglet est mis à jour** sans rechargement.

**Onglet 6 · Historique** — Frise chronologique reconstituée à l'affichage (aucune table d'historique dédiée) :

1. **Validation** — si `date_validation` renseignée : « Le bon de commande a été validé et verrouillé par *utilisateur*. »
2. **Réceptions** — une entrée par BL au statut `valide` : « Réception des articles enregistrée sous le bordereau physique *réf* par *utilisateur*. »
3. **Création** — « Bon de commande créé à l'état initial brouillon par *utilisateur*. »

**Onglet 7 · Journal système** — Tableau d'audit technique : date/heure, utilisateur, action *(badge CREATION / UPDATE / VALIDATION)*, détails. Reconstitué depuis `created_at`, `updated_at` et `date_validation` — **il ne s'agit pas d'un vrai journal d'événements** (cf. AN-09).

#### Zone 3 — Pied de fiche

Bandeau gris : Créé par · Le *(date/heure)* · Dernière modification.

**Actions et confirmations**

| Action | Confirmation SweetAlert | Après succès |
|---|---|---|
| Valider la commande | « Valider cette commande ? — Cette action verrouille la commande et permet de créer des bordereaux de livraison. » | Message « Validé ! » puis **rechargement de la page** |
| Annuler | « Annuler cette commande ? — Voulez-vous vraiment annuler ce bon de commande ? » | Message « Annulé ! » puis rechargement |
| Supprimer un document | « Supprimer ce document ? » | Ligne retirée, compteur décrémenté |

**Règles applicables** : RG-BC-04, RG-BC-05, RG-DOC-01 à RG-DOC-03.

---

### E-07 · Bon de commande — Aperçu avant impression (HTML)

| | |
|---|---|
| **Route** | `GET /achat/bons-commande/{id}/imprimer` → `achat.bons-commande.imprimer` |
| **Vue** | `achat::bons_commande.imprimer` |
| **Permission** | `achat.bons_commande.view` |

**Objectif** — Prévisualiser à l'écran le bon de commande au format A4 avant impression papier.

**Particularités**
- Page **autonome** : n'utilise pas le layout du module (pas de sidebar ni de navbar).
- Simule une feuille A4 (210 × 297 mm) sur fond gris, avec ombre portée.
- Barre d'actions sombre en haut, masquée à l'impression (`no-print-bar`) :

| Bouton | Action |
|---|---|
| Télécharger PDF | Rouvre la même route avec `?pdf=1` → E-08 |
| Imprimer | `window.print()` |
| Fermer | `window.close()` |

- Charte : en-tête institutionnel avec logo, encadrés bleus (`#1a73e8`), tableau des lignes à en-tête bleu.

---

### E-08 · Bon de commande — PDF

| | |
|---|---|
| **Route** | `GET /achat/bons-commande/{id}/imprimer?pdf=1` |
| **Vue** | `achat::bons_commande.print_pdf` |
| **Moteur** | DomPDF, format A4 **portrait** |
| **Nom du fichier** | `bon_de_commande_{numero_commande}.pdf` |
| **Mode** | `stream()` — affichage dans le navigateur, pas de téléchargement forcé |

Consommé directement (nouvel onglet depuis E-06) ou dans une iframe (M-06 depuis E-03).

---

### E-09 · Bordereaux de livraison — Liste

| | |
|---|---|
| **Route** | `GET /achat/bordereaux` → `achat.bordereaux.index` |
| **Contrôleur** | `BordereauLivraisonController@index` |
| **Vue** | `achat::bordereaux.index` |
| **Script** | `public/js/modules/achat/bordereaux/index.js` |
| **Permission** | `achat.bordereaux.view` |
| **Menu** | Sidebar › Bordereaux Livraison |

**Zone 1 — Filtres**

| Filtre | Valeurs | Paramètre |
|---|---|---|
| Bon de Commande | Tous + **BC en statut `valide` ou `partiel` uniquement** | `bon_de_commande_id` |
| Statut | Tous / Brouillon / En cours d'intégration / Validé & Intégré | `statut` |

Recherche plein texte : `numero_livraison`, `ref_bordereau_physique`, numéro du BC rattaché.

**Zone 2 — Barre d'outils**

| Bouton | Permission | Condition d'activation | Action |
|---|---|---|---|
| Créer (`fa-plus`) | `bordereaux.create` | toujours | → E-10 |
| Voir / Modifier (`fa-eye`) | — | 1 ligne | → E-11 |
| Imprimer (`fa-print`) | — | 1 ligne | Ouvre M-07 |
| **Assistant d'intégration** (`fa-magic`) | `bordereaux.edit` | statut ∈ {`brouillon`, `wizard`} | → E-12 |
| Supprimer (`fa-trash`) | `bordereaux.delete` *(cf. AN-01)* | statut = `brouillon` | Confirmation puis suppression |

**Zone 3 — Table** (tri par date de livraison décroissante)

| Colonne | Format |
|---|---|
| N° Livraison | Gras |
| Réf. Commande BC | Numéro du BC |
| Réf. Bordereau Physique | Texte |
| Date Livraison | `JJ/MM/AAAA` |
| Statut | Badge : Brouillon *(gris)*, **Wizard en cours** *(jaune)*, **Intégré** *(vert)* |
| Créé le | `JJ/MM/AAAA HH:MM` |

**Interaction** : double-clic → E-11.

**Modales appelées** : M-07, SW-04.

---

### E-10 · Bordereau de livraison — Création

| | |
|---|---|
| **Route** | `GET /achat/bordereaux/create` → `achat.bordereaux.create` |
| **Soumission** | `POST /achat/bordereaux` → `achat.bordereaux.store` |
| **Vue** | `achat::bordereaux.create` |
| **Script** | `public/js/modules/achat/bordereaux/create.js` |
| **Permission** | `achat.bordereaux.create` |
| **Paramètre d'URL** | `?bon_de_commande_id={id}` — pré-sélectionne le BC (utilisé depuis E-06) |

**Objectif** — Constater une réception physique et saisir les quantités effectivement reçues.

**Zone 1 — Informations de livraison**

| Champ | Type | Obligatoire | Comportement |
|---|---|---|---|
| Bon de Commande | Lecture seule + bouton loupe | ✔ | Ouvre M-05. **La sélection déclenche le chargement AJAX des lignes du BC.** Bouton × pour réinitialiser |
| Date de livraison | Date | ✔ | Pré-remplie à la date du jour |
| Réf. Bordereau Physique | Texte, forcé en majuscules | ✔ | Doit être unique. Ex. `BL-PHY-99882` |
| Observations / Remarques | Zone de texte | | « produits abîmés, manquants… » |

**Zone 2 — Articles reçus**

Le tableau est **entièrement piloté par le BC sélectionné** — l'utilisateur ne choisit pas d'articles.

| Colonne | Contenu |
|---|---|
| Article | Code en gras + désignation + badge de type |
| Qté Commandée | Lecture seule |
| Déjà Livrée | Lecture seule, grisé |
| Reste à Livrer | Lecture seule, bleu |
| **Qté Reçue** | Saisie numérique. **`min=0`, `max=reste_a_livrer`, pré-remplie avec le reste à livrer** |
| Action | Bouton corbeille (retire la ligne du bordereau) |

**Lignes totalement livrées** : le champ de saisie est **désactivé** et la valeur forcée à 0.

**Séquence de chargement des lignes**

1. Sélection d'un BC dans M-05
2. Affichage d'un spinner « Chargement des lignes de commande… »
3. Appel `GET /achat/bons-commande/{id}/lignes-a-livrer`
4. Rendu du tableau, activation du bouton Enregistrer

**États du tableau**

| Situation | Affichage | Bouton Enregistrer |
|---|---|---|
| Aucun BC sélectionné | « Veuillez sélectionner un bon de commande ci-dessus pour charger ses lignes. » | Désactivé |
| BC entièrement livré | Alerte « Ce bon de commande est déjà entièrement livré. » | Désactivé |
| BC sans lignes | « Aucune ligne de commande trouvée pour ce BC. » *(rouge)* | Désactivé |
| Erreur réseau | « Erreur lors du chargement des lignes. » | Désactivé |
| Lignes chargées | Tableau | **Activé** |
| Toutes les lignes supprimées | « Aucune ligne à livrer. » | Désactivé |

**Validations côté client avant soumission**

| Contrôle | Message |
|---|---|
| Aucune ligne présente | « Veuillez sélectionner un bon de commande valide avec au moins une ligne. » |
| Somme des quantités reçues = 0 | « Veuillez saisir une quantité reçue supérieure à 0 pour au moins un article. » |

**Suppression d'une ligne** — confirmation : « Supprimer la ligne ? — Cette ligne ne sera pas enregistrée dans le bordereau de livraison. » Les index des champs `lignes[N]` sont **réindexés automatiquement** après suppression.

**Comportement au succès** — Toast « Enregistré ! » puis redirection vers E-11.

**Modales appelées** : M-05, SW-05.

**Règles applicables** : RG-BL-01 à RG-BL-05, RG-NUM-02.

---

### E-11 · Bordereau de livraison — Fiche détail et édition in-place

| | |
|---|---|
| **Route** | `GET /achat/bordereaux/{id}` → `achat.bordereaux.show` |
| **Mise à jour** | `PUT /achat/bordereaux/{id}` → `achat.bordereaux.update` |
| **Vue** | `achat::bordereaux.show` |
| **Script** | `public/js/modules/achat/bordereaux/show.js` |
| **Permission** | `achat.bordereaux.view` (consultation), `achat.bordereaux.edit` (modification) |

**Objectif** — Consulter un bordereau, le corriger tant qu'il est en brouillon, et lancer l'intégration.

**Particularité — édition in-place** : la fiche est en lecture seule par défaut. Le bouton « Modifier » **transforme la page en formulaire** sans changer d'écran.

#### Zone 1 — Barre d'actions

À gauche : « Statut actuel : » + badge.

À droite, selon le statut :

| Statut | Boutons |
|---|---|
| `brouillon` | Retour · Imprimer · **Modifier** · **Lancer l'intégration** *(vert)* |
| `wizard` | Retour · Imprimer · **Continuer l'intégration** *(jaune)* |
| `valide` | Retour · Imprimer |

Deux boutons supplémentaires, **masqués par défaut**, apparaissent en mode édition : **Enregistrer** et **Annuler**.

#### Zone 2 — Onglets (3)

**Onglet 1 · Fiche BL**

*Informations de livraison* :

| Champ | Lecture seule | Mode édition |
|---|---|---|
| Bon de Commande | Champ grisé : « BC-2026-0001 - Nom fournisseur » | Devient un sélecteur avec loupe → M-05 et bouton × |
| Date de livraison | Désactivé | Activé |
| Réf. Bordereau Physique | Désactivé | Activé, majuscules forcées |
| Observations | Désactivé | Activé |

*Articles reçus* :

| Colonne | Lecture seule | Mode édition |
|---|---|---|
| Article | Code + désignation + badge de type | idem |
| Qté Commandée (BC) | Affichée | idem |
| Qté Reçue sur ce BL | Texte en gras | **Champ numérique** `min=1`, `max=max_qty` |
| Action | *(colonne masquée)* | **Colonne affichée** avec bouton corbeille |

Le plafond `max_qty` est calculé côté serveur : `reste_a_livrer du BC + quantité déjà saisie sur ce BL` — il autorise donc la ré-augmentation de la quantité déjà enregistrée sur ce bordereau.

**Onglet 2 · Équipements intégrés** — Équipements ParcInfo dont `ref_bordereau = numero_livraison`. Mêmes colonnes et mêmes actions que l'onglet 4 de E-06.
*État vide* : « Aucun équipement n'a encore été intégré pour ce bordereau de livraison. »

**Onglet 3 · Documents joints** — Identique à l'onglet Documents de E-06, avec `documentable_type = bordereau`.

#### Zone 3 — Pied de fiche

Créé par · Le · Dernière modification.

#### Cycle d'édition in-place

| Étape | Comportement |
|---|---|
| Clic sur **Modifier** | Sauvegarde des valeurs originales en mémoire · masque Modifier / Lancer l'intégration / Imprimer · affiche Enregistrer / Annuler · active les champs · re-génère les lignes en mode saisie |
| Changement de BC dans M-05 | **Recharge intégralement les lignes** depuis le nouveau BC. Si aucune ligne livrable : « Aucune ligne livrable trouvée pour ce bon de commande. » |
| Clic sur **Annuler** | Restauration des valeurs originales *(BC et lignes)*, retour en lecture seule, aucun appel serveur |
| Clic sur **Enregistrer** | Mêmes contrôles qu'en création, puis `PUT`, puis **rechargement de la page** |

**Modales appelées** : M-05, M-07, SW-05.

**Règles applicables** : RG-BL-03, RG-BL-04, RG-BL-06.

---

### E-12 · Assistant d'intégration (Wizard)

| | |
|---|---|
| **Route d'accès** | `GET /achat/bordereaux/{id}/wizard` → `achat.bordereaux.wizard` |
| **Sauvegarde d'étape** | `POST /achat/bordereaux/{bordereau}/wizard/{article}/sauvegarder` |
| **Finalisation** | `POST /achat/bordereaux/{bordereau}/wizard/valider` |
| **Vue** | `achat::bordereaux.wizard` |
| **Script** | `public/js/modules/achat/bordereaux/wizard.js` |
| **Services** | `WizardValidationService`, `EquipementIntegrationService`, `CodeInventaireGeneratorService` |
| **Permission** | `achat.bordereaux.edit` |

**Objectif** — Écran le plus critique du module. Il collecte les informations d'inventaire **unité par unité** puis déclenche la création irréversible des enregistrements dans ParcInfo et Stock.

#### Contrôles à l'ouverture

| Situation | Comportement |
|---|---|
| Statut ∉ {`brouillon`, `wizard`} | Redirection vers E-11 avec l'erreur : « L'assistant ne peut pas être lancé pour ce bordereau. » |
| Statut = `brouillon` | **Bascule automatique en statut `wizard`** avant affichage |
| Aucune ligne de type équipement ou licence | **Validation immédiate sans passer par l'assistant** puis redirection vers E-11 : « Le bordereau ne contenant que des consommables, il a été validé et intégré directement. » |
| Erreur pendant cette validation directe | Redirection vers E-11 : « Erreur lors de la validation : *message*. » |

#### Structure de l'écran — 2 colonnes

**Colonne gauche (25 %) — Navigation par étapes (`stepper`)**

Une étape par **article** de type équipement ou licence, plus une étape finale.

| Élément | Rendu |
|---|---|
| Étape en cours | Bordure gauche bleue, texte bleu, fond bleuté |
| Étape complétée | Bordure gauche verte, texte vert, icône ✔ verte |
| Étape non commencée | Bordure grise, icône ○ |
| Contenu de l'étape | « Étape *N* » en petites capitales + désignation de l'article *(tronquée à 180 px)* |
| Étape finale | « Validation Finale » avec icône drapeau à damier |

**Colonne droite (75 %) — Formulaire de l'étape active**

En-tête : désignation de l'article + « Saisie d'inventaire pour *N* unité(s) livrée(s) (Type : Équipement/Licence) ».

Puis **une carte par unité** (`Unité #1`, `Unité #2`, …), autant que la quantité livrée.

*Cas d'un article de type **équipement*** :

| Champ | Obligatoire | Comportement |
|---|---|---|
| Numéro de Série | ✔ | Majuscules forcées. Placeholder « Saisir le S/N physique » |
| Code Inventaire | | Majuscules forcées. Mention « (auto-généré si vide) » — le code est produit par `CodeInventaireGeneratorService` à la validation |
| **Caractéristiques Techniques** | | **Bloc dynamique** : un champ par champ personnalisé de la catégorie d'équipement de l'article (`categorie->champs`) |

Le type de contrôle des caractéristiques suit la définition du champ dans ParcInfo :

| `type_champ` | Contrôle rendu |
|---|---|
| `select` | Liste déroulante alimentée par `options_resolved` |
| `number` | Champ numérique |
| autre | Champ texte |

*Cas d'un article de type **licence*** :

| Champ | Obligatoire | Comportement |
|---|---|---|
| Clé de Licence | ✔ | Majuscules forcées, placeholder `XXXXX-XXXXX-XXXXX-XXXXX-XXXXX` |
| Date d'activation | ✔ | Pré-remplie à la date du jour |
| Date d'expiration | | Facultative |

*Pied de carte* : bouton **Précédent** *(désactivé sur la première étape)* et bouton **« Enregistrer cette étape & Continuer »**.

#### Étape finale — Validation et intégration

1. **Encart d'information** rappelant l'effet de l'opération : « les équipements physiques et les licences seront automatiquement créés et configurés dans votre parc informatique (statut "En stock", état "Bon"). Les consommables verront également leur stock incrémenté. »
2. **Récapitulatif** : une ligne par article avec la quantité livrée et un badge **Complété** *(vert)* ou **Non complété** *(rouge)*.
3. **Bouton « Finaliser et valider l'intégration »** — vert, large, **désactivé tant que toutes les étapes ne sont pas complétées**.
4. **Avertissement conditionnel** sous le bouton : « Veuillez compléter toutes les étapes avant de pouvoir finaliser. »

#### Comportement à la sauvegarde d'une étape

| Étape | Effet |
|---|---|
| 1 | Le bouton passe en « Sauvegarde… » avec spinner |
| 2 | `POST` du formulaire sérialisé + `completed=1` |
| 3 | Toast « Étape enregistrée ! » (1 s, sans bouton) |
| 4 | L'étape du stepper passe en vert avec icône ✔ |
| 5 | Le badge du récapitulatif final passe de rouge « Non complété » à vert « Complété » |
| 6 | Recalcul de l'éligibilité à la finalisation |
| 7 | **Navigation automatique vers l'étape suivante** |

En cas d'erreur : « Erreur de validation » avec le détail des erreurs de champs.

**Persistance** — Les données sont stockées dans `achat_wizard_data` en `updateOrCreate` par couple (bordereau, article). L'assistant peut donc être quitté et **repris ultérieurement** : les valeurs déjà saisies sont rechargées dans les champs et les étapes conservent leur état « complété ».

#### Finalisation

**Confirmation** : « Finaliser l'intégration ? — Les équipements et licences saisis seront créés de manière définitive dans le parc informatique ! » *(icône question, bouton vert « Oui, finaliser et intégrer »)*.

Pendant le traitement, le bouton affiche « Intégration en cours… ».

**Message de succès** : « Bordereau validé avec succès ! Intégration effectuée : *N* équipement(s) créé(s) et *M* licence(s) créée(s). » puis redirection vers E-11.

**Traitement serveur** — voir le workflow détaillé au [chapitre 8.3](#83-workflow-de-validation-dun-bordereau).

**Règles applicables** : RG-WZ-01 à RG-WZ-05, RG-INT-01 à RG-INT-07.

---

### E-13 · Bordereau de livraison — PDF

| | |
|---|---|
| **Route** | `GET /achat/bordereaux/{id}/imprimer` → `achat.bordereaux.imprimer` |
| **Vue** | `achat::bordereaux.print_pdf` |
| **Moteur** | DomPDF, A4 portrait |
| **Nom du fichier** | `bordereau_livraison_{numero_livraison}.pdf` |
| **Permission** | `achat.bordereaux.view` |

Contrairement au bon de commande, **il n'existe pas de page d'aperçu HTML** pour le bordereau : la route produit directement le PDF, consommé dans une iframe (M-07).

---

### E-14 · Stock des consommables

| | |
|---|---|
| **Route** | `GET /achat/stocks` → `achat.stocks.index` |
| **Contrôleur** | `StockController@index` |
| **Vue** | `achat::stocks.index` |
| **Permission** | `achat.stocks.view` |
| **Menu** | Sidebar › Suivi des Stocks |

**Objectif** — Surveiller le niveau de stock des articles du catalogue de type consommable.

**Zone 1 — Filtre unique**

| Filtre | Valeurs | Traduction SQL |
|---|---|---|
| Statut Stock | Tous les niveaux / **En alerte (≤ Seuil)** / **Correct (> Seuil)** | `stock_actuel <= seuil_alerte` / `stock_actuel > seuil_alerte` |

Recherche plein texte : `code_article`, `designation`.

**Zone 2 — Table** (lecture seule, **aucune barre d'outils, aucune action**)

| Colonne | Format |
|---|---|
| Code Article | Gras |
| Désignation | Texte |
| Marque | Libellé |
| Stock Actuel | Centré |
| Seuil Alerte | Centré |
| Alerte / Statut | Badge **rouge « Alerte stock »** *(avec icône triangle)* ou **vert « Stock Correct »** |

**Limites fonctionnelles** — Cet écran est purement consultatif. Les permissions `achat.stocks.entree`, `achat.stocks.sortie` et `achat.stocks.inventaire` sont déclarées mais **aucune fonctionnalité correspondante n'existe** dans le module (cf. AN-14). Les mouvements réels sont gérés par le module Stock.

---

### E-15 · États & Statistiques

| | |
|---|---|
| **Route** | `GET /achat/statistiques` → `achat.statistiques.index` |
| **Données** | `GET /achat/statistiques/data` → `achat.statistiques.data` |
| **Export** | `GET /achat/statistiques/pdf` → `achat.statistiques.pdf` |
| **Contrôleur** | `StatistiquesController` |
| **Vue** | `achat::statistiques.index` |
| **Permission** | `achat.dashboard.view` *(et non `achat.rapports.view` — cf. AN-13)* |
| **Menu** | Sidebar › États & Statistiques |

**Objectif** — Produire les états de synthèse et les rapports imprimables du domaine achat.

**Zone 1 — Cartes d'indicateurs (4 cartes à dégradé)**

| Carte | Couleur | Valeur | Calcul |
|---|---|---|---|
| Total Commandes | Bleu | Nombre de BC | Tous statuts |
| Montant Cumulé | Vert | Somme en FCFA | BC `valide` + `partiel` + `livre` |
| Complétion Livraisons | Violet | Pourcentage | Lignes avec `quantite_livree >= quantite` ÷ total des lignes de BC engagés. **100 % si aucune ligne** |
| Fournisseurs Actifs | Orange | Nombre | Tous les fournisseurs référencés |

**Zone 2 — Graphiques (3)**

| Graphique | Type | Contenu |
|---|---|---|
| Dépenses Mensuelles | Courbe | 6 périodes mensuelles |
| Dépenses par Fournisseur (Top 5) | Anneau | Montants cumulés par fournisseur |
| Articles par Type | Camembert | Nombre d'articles par type |

**Zone 3 — Sélection et paramètres du rapport**

| Champ | Valeurs |
|---|---|
| **Type de rapport** | Rapport Global des Bons de Commande · Rapport de Dépenses par Fournisseur · Rapport des Reliquats de Livraison · Articles les plus commandés |
| Fournisseur | Tous + liste |
| Statut | Tous / Brouillon / Validé / Partiel / Livré / Annulé |
| Date Début / Date Fin | Dates |

**Filtres applicables selon le rapport** — les champs non pertinents sont **masqués dynamiquement** :

| Rapport | Fournisseur | Statut | Dates |
|---|:---:|:---:|:---:|
| Rapport Global des BC | ✔ | ✔ | ✔ |
| Dépenses par Fournisseur | ✔ | — | — |
| Reliquats de Livraison | ✔ | — | — |
| Articles les plus commandés | — | — | — |

**Boutons** : **Rechercher** *(charge le tableau en AJAX)* et **Afficher / Imprimer PDF** *(ouvre M-08)*.

**Zone 4 — Tableau de résultats**

Masqué au chargement, il apparaît en fondu après la première recherche. Le titre et **les colonnes sont générés dynamiquement** à partir de la réponse serveur ; un badge indique le nombre de lignes.

> Le rapport par défaut (« Rapport Global des Bons de Commande ») est **déclenché automatiquement au chargement de la page**.

**Définition des quatre rapports**

| Rapport | Colonnes | Périmètre des données |
|---|---|---|
| **Rapport Global des Bons de Commande** | N° Commande, Date Commande, Fournisseur, Montant Total, Statut | Tous les BC, tri par date décroissante |
| **Rapport de Dépenses par Fournisseur** | Fournisseur, Nbre Commandes, Montant Total Commandé | Jointure fournisseurs × BC non supprimés, tri par montant décroissant |
| **Rapport des Reliquats de Livraison** | N° Commande, Article, Qté Commandée, Qté Livrée, Reliquat Restant | Lignes de BC `valide`/`partiel` avec `quantite_livree < quantite` |
| **Articles les plus commandés** | Désignation, Type, Quantité Totale Commandée, Montant Total Cumulé | Agrégation de toutes les lignes de commande, tri par quantité décroissante |

Les montants sont formatés `1 234 567 FCFA`.

**État d'erreur** — « Impossible de récupérer les données du rapport. » *(SweetAlert erreur)*.

**Modales appelées** : M-08.

---

### E-16 · Rapport statistique — PDF

| | |
|---|---|
| **Route** | `GET /achat/statistiques/pdf?report_type=…&filtres…` |
| **Vue** | `achat::statistiques.pdf` |
| **Moteur** | DomPDF, format A4 **paysage** |
| **Nom du fichier** | Titre du rapport en minuscules avec `_` (ex. `rapport_global_des_bons_de_commande.pdf`) |

Le PDF reprend le titre, les colonnes et les lignes du rapport, et **rappelle en en-tête les filtres appliqués** (Fournisseur, Statut, Date début, Date fin) sous forme lisible.

---

## 6. Catalogue des modales et composants transverses

### 6.1 Modales de formulaire et de sélection

---

#### M-01 · Formulaire Article (création / modification)

| | |
|---|---|
| **Écran hôte** | E-02 |
| **Identifiant** | `#item-modal` |
| **Taille** | `modal-lg`, centrée, **fond non cliquable** (`data-bs-backdrop="static"`) |
| **Titre dynamique** | « **Nouveau** Article » / « **Modifier** Article » |

Le formulaire est organisé en **2 onglets**.

**Onglet « Général »**

| Champ | Type | Obligatoire | Remarque |
|---|---|---|---|
| Type d'article | Liste | ✔ | **Pilote l'affichage conditionnel des autres champs** |
| Code Article | Texte majuscules | | Libellé « (facultatif) », placeholder « Génération auto si vide » — **mais le serveur l'exige, cf. AN-06** |
| Désignation | Texte | ✔ | 3 à 255 caractères |
| Marque | Liste | ✔ | Référentiel ParcInfo |
| Référence Constructeur | Texte | | Unique par marque |
| **Catégorie d'équipement** | Liste | ✔ *(si équipement)* | **Affiché uniquement si type = équipement** |
| Fournisseur Préféré | Liste | | Fournisseurs actifs |
| Prix Indicatif (FCFA) | Numérique ≥ 0 | ✔ *(côté client)* | |

**Onglet « Caractéristiques & Stocks »**

| Champ | Type | Valeur par défaut | Affichage conditionnel |
|---|---|---|---|
| Unité de mesure | Texte | `Unité` | toujours |
| Taux TVA (%) | Numérique 0–100 | `18` | toujours |
| Compte Comptable | Texte | | toujours |
| **Seuil d'alerte stock** | Numérique ≥ 0 | | **si type = consommable** |
| **Durée de validité (mois)** | Numérique ≥ 1 | | **si type = licence** |
| Lien Fiche Technique | URL | | toujours |
| Photo de l'article | Fichier image ≤ 2 Mo | | toujours |
| Description & Spécifications | Zone de texte 3 lignes | | toujours |

**Logique d'affichage conditionnel** (`ajusterChampsParType`) : les trois blocs conditionnels sont d'abord masqués, puis un seul est réaffiché selon le type choisi.

**Pied de modale** : « Annuler » *(ferme sans confirmation)* et « Enregistrer » *(spinner pendant l'envoi)*.

**Soumission** — `FormData` en `POST` *(avec `_method=PUT` en modification)* pour supporter l'upload d'image. Au succès : fermeture de la modale, toast, rafraîchissement de la table.

**Comportement à l'ouverture en création** : réinitialisation du formulaire, retour à l'onglet « Général », type forcé à `equipement`.

---

#### M-02 · *(réservé)*

---

#### M-03 · Sélection d'un Fournisseur

| | |
|---|---|
| **Écrans hôtes** | E-04, E-05 |
| **Identifiant** | `#modal-select-fournisseur` |
| **Taille** | `modal-lg`, centrée |
| **Source** | Fournisseurs **actifs** (`est_actif = true`), triés par nom |

| Élément | Description |
|---|---|
| Champ de recherche | Filtrage **instantané côté client** sur code + nom + ville |
| Table | Code *(badge monospace)* · Nom *(gras)* · Ville |
| Zone de défilement | Hauteur max 350 px, en-tête figé |
| Sélection | **Clic sur la ligne** — pas de bouton de validation |
| État vide | « Aucun fournisseur actif trouvé. » |

**Effet de la sélection** : renseigne l'identifiant caché, affiche le nom, affiche le code fournisseur sous le champ, fait apparaître le bouton de réinitialisation, ferme la modale.

---

#### M-04 · Sélection d'un Article

| | |
|---|---|
| **Écrans hôtes** | E-04, E-05 |
| **Identifiant** | `#modal-select-article` |
| **Source** | `window.articlesCatalogue` — articles **actifs** injectés dans la page |

| Élément | Description |
|---|---|
| Champ de recherche | Filtrage instantané sur désignation + code article |
| Filtre catégorie | « Toutes les catégories » / Équipement / Consommable |
| Table | Code Article · Désignation *(gras)* · Catégorie *(badge)* · Prix Indicatif *(devise)* |
| Sélection | Clic sur la ligne |
| État vide | « Aucun article trouvé. » |

**Contexte de ligne** — La modale mémorise **quelle ligne de commande** a déclenché son ouverture (`activeRowIndex`) et applique la sélection à cette ligne uniquement.

**Effet de la sélection** : renseigne l'article de la ligne, affiche désignation + code, **pré-remplit le prix unitaire avec le prix indicatif**, transforme le bouton en « Changer », recalcule les totaux, ferme la modale.

---

#### M-05 · Sélection d'un Bon de Commande

| | |
|---|---|
| **Écrans hôtes** | E-10, E-11 *(en mode édition)* |
| **Identifiant** | `#modal-select-bc` |
| **Source** | BC en statut `valide` ou `partiel` *(plus le BC courant en E-11)* |

| Élément | Description |
|---|---|
| Champ de recherche | Filtrage instantané sur numéro + nom du fournisseur |
| Table | N° Commande *(badge)* · Fournisseur *(gras)* · Date · Montant Total *(aligné à droite)* |
| Sélection | Clic sur la ligne |
| État vide | « Aucun bon de commande en cours trouvé. » |

**Effet de la sélection** : renseigne le BC, affiche « *numéro* - *fournisseur* », **déclenche le chargement AJAX des lignes livrables**, ferme la modale.

> **Variante d'apparence** : l'en-tête de cette modale est sombre (`bg-dark`) en E-10 et bleu (`bg-primary`) en E-11 — écart de charte, cf. AN-15.

---

### 6.2 Modales d'aperçu PDF

Ces trois modales partagent la même structure : `modal-xl` centrée, en-tête bleu avec icône PDF rouge, corps constitué d'une **iframe de 70 vh sans bordure**, pied avec un unique bouton « Fermer ».

| Réf. | Identifiant | Écran hôte | Titre | Source de l'iframe |
|---|---|---|---|---|
| **M-06** | `#printBcModal` | E-03 | Impression du Bon de Commande | `achat.bons-commande.imprimer` + `?pdf=1` |
| **M-07** | `#printBlModal` | E-09, E-11 | Impression du Bordereau de Livraison | `achat.bordereaux.imprimer` |
| **M-08** | `#printPdfModal` | E-15 | Visualisation et Impression du Rapport | `achat.statistiques.pdf` + filtres du formulaire |

L'impression et le téléchargement sont assurés par la **barre d'outils native du lecteur PDF du navigateur** — l'application ne fournit pas de bouton dédié dans ces modales.

---

### 6.3 Boîtes de dialogue SweetAlert (confirmations)

| Réf. | Écran | Titre | Texte | Bouton de confirmation |
|---|---|---|---|---|
| **SW-01** | E-02 | Supprimer cet article ? | Cette action peut désactiver l'article s'il est déjà lié à des commandes ! | « Oui, supprimer » *(rouge)* |
| **SW-02** | E-03, E-06 | Annuler cette commande ? | Êtes-vous sûr de vouloir annuler le bon de commande *numéro* ? | « Oui, annuler » *(jaune)* |
| **SW-03** | E-03 | Supprimer ce bon de commande ? | Cette action est irréversible ! | « Oui, supprimer » *(rouge)* |
| **SW-04** | E-09 | Supprimer ce bordereau ? | Cette action est irréversible ! | « Oui, supprimer » *(rouge)* |
| **SW-05** | E-10, E-11 | Supprimer la ligne ? | Cette ligne ne sera pas enregistrée dans le bordereau de livraison. | « Oui, supprimer » *(rouge)* |
| **SW-06** | E-06 | Valider cette commande ? | Cette action verrouille la commande et permet de créer des bordereaux de livraison. | « Oui, valider » *(vert)* |
| **SW-07** | E-12 | Finaliser l'intégration ? | Les équipements et licences saisis seront créés de manière définitive dans le parc informatique ! | « Oui, finaliser et intégrer » *(vert)* |
| **SW-08** | E-06, E-11 | Supprimer ce document ? | Voulez-vous vraiment supprimer ce document joint ? | « Oui, supprimer » *(rouge)* |

**Actions sans confirmation** *(par conception)* : duplication d'article, bascule actif/inactif, ajout de ligne, upload de document.

---

### 6.4 Composants de mise en page

| Composant | Fichier | Contenu |
|---|---|---|
| **Layout principal** | `layouts/master.blade.php` | En-tête HTML, jeton CSRF, chargement des plugins, `@stack('css')` / `@stack('js')`, navbar, sidebar |
| **Sidebar** | `layouts/partials/sidebar.blade.php` | Marque « CHU-YO \| Achats », 3 sections : *Gestion des Achats* (Catalogue, BC, BL, Stocks), *Analyse & Rapports* (États & Statistiques), *Navigation Portail* (Accueil général). Élément actif détecté par `request()->routeIs()` |
| **Navbar** | `layouts/partials/navbar.blade.php` | Barre supérieure |
| **Fil d'Ariane** | Section `@section('breadcrumb')` de chaque vue | Systématiquement : Achats › *rubrique* › *élément* |

---

## 7. Règles de gestion

> Chaque règle indique son point d'application dans le code, ce qui permet de vérifier son implémentation effective.

### 7.1 Catalogue des articles

| Réf. | Règle | Implémentation |
|---|---|---|
| **RG-ART-01** | Le code article est unique dans tout le catalogue. | `StoreArticleRequest` + contrainte SQL |
| **RG-ART-02** | La désignation comporte entre 3 et 255 caractères. | FormRequests |
| **RG-ART-03** | La référence constructeur est unique **pour une marque donnée**. Deux marques peuvent partager la même référence. | Contrainte composite `unique_ref_marque` + `Rule::unique()->where('marque_id')` |
| **RG-ART-04** | Si le type est « équipement », la catégorie d'équipement est **obligatoire**. | `ArticleService::validerReglesMetier` + `required_if` |
| **RG-ART-05** | Si le type n'est pas « équipement », la catégorie d'équipement doit être **nulle**. | `ArticleService::validerReglesMetier` |
| **RG-ART-06** | Le seuil d'alerte est obligatoire pour les consommables. | `required_if:type_article,consommable` |
| **RG-ART-07** | La durée de validité est obligatoire pour les licences. | `required_if:type_article,licence` |
| **RG-ART-08** | Un consommable est « en alerte » lorsque `stock_actuel <= seuil_alerte`. | `AchatController`, `StockController` |
| **RG-ART-09** | Seuls les articles **actifs** sont proposés à la sélection dans un bon de commande. | `BonCommandeController@create` / `@edit` |
| **RG-ART-10** | Un article **référencé dans au moins une ligne de commande ne peut pas être supprimé** : il est automatiquement désactivé à la place. | `ArticleService::supprimer` |
| **RG-ART-11** | La duplication crée un article de code `{code}-COPY` et de désignation `{désignation} (Copie)`. | `ArticleService::dupliquer` |
| **RG-ART-12** | L'image d'un article ne dépasse pas 2 Mo et doit être un fichier image. | FormRequests |

### 7.2 Bons de commande

| Réf. | Règle | Implémentation |
|---|---|---|
| **RG-BC-01** | Un bon de commande est **toujours créé en statut `brouillon`**, montant initial 0. | `BonCommandeService::creer` |
| **RG-BC-02** | Un bon de commande comporte **au moins une ligne**. | `StoreBonCommandeRequest` (`lignes.min:1`) + contrôle client |
| **RG-BC-03** | **Seul un bon de commande en brouillon est modifiable ou supprimable.** | `BonCommande::estModifiable()` |
| **RG-BC-04** | Un bon de commande n'est validable que s'il est **en brouillon et possède au moins une ligne**. | `BonCommande::estValidable()` |
| **RG-BC-05** | Un bon de commande est annulable s'il est en statut `brouillon` **ou** `valide`. Un BC partiellement ou totalement livré n'est plus annulable. | `BonCommande::estAnnulable()` |
| **RG-BC-06** | La validation enregistre l'utilisateur validateur et l'horodatage. | `BonCommandeService::valider` |
| **RG-BC-07** | Le montant total est **recalculé automatiquement** après création ou modification des lignes : `Σ (quantité × prix unitaire)`. Il s'agit d'un **montant hors taxes**. | `BonCommande::recalculerMontantTotal` |
| **RG-BC-08** | Les statistiques de dépense ne prennent en compte que les BC en statut `valide`, `partiel` ou `livre`. | Contrôleurs Dashboard et Statistiques |
| **RG-BC-09** | La quantité d'une ligne est un entier strictement positif. | Contrainte SQL `CHECK` + validation |
| **RG-BC-10** | Le prix unitaire est libre (≥ 0) et **peut différer du prix indicatif** du catalogue. | Validation |
| **RG-BC-11** | Le statut d'un BC évolue automatiquement à la validation d'un BL : `partiel` si des reliquats subsistent, `livre` sinon. | `WizardValidationService::validerBordereau` |

### 7.3 Bordereaux de livraison

| Réf. | Règle | Implémentation |
|---|---|---|
| **RG-BL-01** | Un bordereau **ne peut être créé que pour un BC en statut `valide` ou `partiel`**. | `BordereauLivraisonService::creer` |
| **RG-BL-02** | Un bordereau est toujours créé en statut `brouillon`. | `BordereauLivraisonService::creer` |
| **RG-BL-03** | La quantité livrée d'une ligne **ne peut excéder le reste à livrer** de la ligne de commande correspondante. | `BordereauLivraisonService::creer` / `::modifier` + attribut `max` côté client |
| **RG-BL-04** | La référence de bordereau physique est **unique dans toute l'application**. | Contrainte SQL + validation + contrôle service |
| **RG-BL-05** | Chaque article livré doit exister dans le bon de commande rattaché. | `BordereauLivraisonService` |
| **RG-BL-06** | **Seul un bordereau en brouillon est modifiable ou supprimable.** | `BordereauLivraison::estModifiable()` |
| **RG-BL-07** | Un bordereau comporte au moins une ligne de quantité ≥ 1. | FormRequests + contrainte SQL `CHECK` |
| **RG-BL-08** | La modification d'un bordereau **supprime puis recrée l'intégralité de ses lignes**. | `BordereauLivraisonService::modifier` |

### 7.4 Assistant d'intégration

| Réf. | Règle | Implémentation |
|---|---|---|
| **RG-WZ-01** | L'assistant n'est accessible que pour un bordereau en statut `brouillon` ou `wizard`. | `BordereauLivraison::peutLancerWizard()` |
| **RG-WZ-02** | L'ouverture de l'assistant fait **passer un bordereau `brouillon` en statut `wizard`** — il n'est alors plus modifiable ni supprimable. | `BordereauLivraisonController@wizard` |
| **RG-WZ-03** | **Seuls les articles de type `equipement` et `licence` génèrent une étape.** Les consommables et prestations sont traités automatiquement. | Filtrage `lignesWizard` |
| **RG-WZ-04** | Un bordereau **ne contenant aucun équipement ni licence est validé et intégré immédiatement**, sans passage par l'assistant. | `BordereauLivraisonController@wizard` |
| **RG-WZ-05** | Toutes les étapes doivent être marquées « complétées » avant que la finalisation ne soit possible. | Contrôle client + `WizardValidationService::validerBordereau` |
| **RG-WZ-06** | Le nombre d'unités saisies doit **correspondre exactement** à la quantité livrée de la ligne. | `WizardValidationService::validerBordereau` |
| **RG-WZ-07** | Les saisies sont persistées à chaque étape et **rechargées en cas de reprise ultérieure**. | Table `achat_wizard_data` |
| **RG-WZ-08** | Les données temporaires de l'assistant sont **purgées après une validation réussie**. | `WizardValidationService::validerBordereau` |

### 7.5 Intégration dans le parc

| Réf. | Règle | Implémentation |
|---|---|---|
| **RG-INT-01** | **Un bordereau déjà validé ne peut pas être revalidé.** | `WizardValidationService::validerBordereau` |
| **RG-INT-02** | L'intégralité de l'intégration est exécutée **dans une transaction unique** : tout échec annule l'ensemble des créations. | `DB::transaction` |
| **RG-INT-03** | Le **numéro de série** est obligatoire pour chaque unité d'équipement et doit être **unique dans tout le parc**. | `EquipementIntegrationService::creerEquipement` |
| **RG-INT-04** | Le **code inventaire** est obligatoire et unique. S'il n'est pas saisi, il est **généré automatiquement** selon le pattern paramétré, avec verrouillage du compteur en base pour éviter les collisions concurrentes. | `CodeInventaireGeneratorService::generer` |
| **RG-INT-05** | Un équipement créé par intégration prend le statut **`en_stock`** et l'état **`bon`**. Sa valeur d'achat est le prix unitaire du bon de commande — **et non le prix indicatif du catalogue**. | `EquipementIntegrationService` |
| **RG-INT-06** | Chaque équipement créé génère une entrée d'**historique de type `acquisition`** référençant le BL et le BC. | `HistoriqueChangement::create` |
| **RG-INT-07** | Une licence crée le **logiciel s'il n'existe pas** (`firstOrCreate` sur la désignation), puis une licence par unité, en statut `VALIDE`, rattachée au fournisseur du BC. | `WizardValidationService` |
| **RG-INT-08** | Un consommable livré déclenche **trois écritures** : incrément de `achat_articles.stock_actuel`, incrément de `parc_info_consommables.quantite_stock_actuel` *(création du consommable si absent)*, et création d'un mouvement d'entrée dans ParcInfo. | `WizardValidationService` |
| **RG-INT-09** | La validation d'un bordereau crée en outre une **entrée de stock dans le module Stock** (mouvement + lot FIFO) sur le magasin de type `CONSOMMABLE` actif, créé par défaut si aucun n'existe. | `EntreeStockService::creerDepuisBL` |
| **RG-INT-10** | Les compteurs `quantite_livree` des lignes de commande sont incrémentés, puis le statut du BC est recalculé. | `WizardValidationService` |

### 7.6 Numérotation

| Réf. | Règle | Implémentation |
|---|---|---|
| **RG-NUM-01** | Le numéro de bon de commande suit le format `{préfixe}-{année}-{séquence sur 4 chiffres}`, la séquence étant le nombre de BC de l'année civile + 1. En cas de collision, la séquence est incrémentée jusqu'à obtenir un numéro libre. | `BonCommandeService::genererNumeroCommande` |
| **RG-NUM-02** | Le numéro de bordereau suit la même logique avec le préfixe `BL`. | `BordereauLivraisonService::genererNumeroLivraison` |
| **RG-NUM-03** | Le code inventaire suit le pattern paramétrable `INV-{YYYY}-{SEQUENCE:4}`, le compteur étant persisté en base et verrouillé (`lockForUpdate`) pendant la génération. | `CodeInventaireGeneratorService` |
| **RG-NUM-04** | Les préfixes et le pattern sont **modifiables en base** (`achat_parametres`) sans redéploiement. | `Parametre::getVal` |

### 7.7 Documents joints

| Réf. | Règle | Implémentation |
|---|---|---|
| **RG-DOC-01** | Un document est rattaché **soit à un bon de commande, soit à un bordereau** (relation polymorphe). Tout autre type est refusé. | `DocumentController@store` |
| **RG-DOC-02** | La taille maximale d'un fichier joint est de **10 Mo**. | Validation `max:10240` |
| **RG-DOC-03** | À défaut de nom saisi, le nom original du fichier est conservé. | `DocumentController@store` |
| **RG-DOC-04** | Le téléchargement d'un document dont le fichier physique est absent renvoie une erreur 404 « Fichier introuvable. » | `DocumentController@download` |

---

## 8. Workflows de bout en bout

### 8.1 Cycle de vie d'un bon de commande

```
                    ┌──────────────┐
   création ───────▶│  BROUILLON   │
                    └──────┬───────┘
        modifiable ✔       │
        supprimable ✔      │ valider (RG-BC-04)
        annulable ✔        ▼
                    ┌──────────────┐
                    │    VALIDÉ    │────── annuler ──▶ ┌─────────┐
                    └──────┬───────┘                   │ ANNULÉ  │
        BL possible ✔      │                           └─────────┘
        modifiable ✘       │ validation d'un BL
                           ▼
                    ┌──────────────┐
                    │   PARTIEL    │◀─┐  reliquats restants
                    └──────┬───────┘  │
                           │──────────┘  validation d'un autre BL
                           │ tout livré
                           ▼
                    ┌──────────────┐
                    │    LIVRÉ     │  état terminal
                    └──────────────┘
```

**Transitions et déclencheurs**

| De | Vers | Déclencheur | Écran |
|---|---|---|---|
| *(néant)* | `brouillon` | Enregistrement du formulaire | E-04 |
| `brouillon` | `valide` | Bouton « Valider la commande » | E-06 |
| `brouillon` | `annule` | Bouton « Annuler » | E-03, E-06 |
| `valide` | `annule` | Bouton « Annuler » | E-03, E-06 |
| `valide` | `partiel` | Validation d'un BL laissant des reliquats | E-12 |
| `valide` | `livre` | Validation d'un BL soldant la commande | E-12 |
| `partiel` | `livre` | Validation du BL final | E-12 |

### 8.2 Cycle de vie d'un bordereau de livraison

```
   création ──▶ ┌────────────┐  ouverture du wizard  ┌──────────┐
                │ BROUILLON  │──────────────────────▶│  WIZARD  │
                └─────┬──────┘   (RG-WZ-02)          └────┬─────┘
   modifiable ✔       │                                   │
   supprimable ✔      │ wizard sans équipement ni licence │ finalisation
                      │        (RG-WZ-04)                 │
                      └───────────────┬───────────────────┘
                                      ▼
                               ┌────────────┐
                               │   VALIDÉ   │  état terminal, irréversible
                               └────────────┘
```

> **Point d'attention métier** : le simple fait d'**ouvrir** l'assistant fait sortir le bordereau du statut brouillon et le rend définitivement non modifiable, même si l'utilisateur quitte l'écran sans rien saisir. Il n'existe aucun mécanisme de retour au statut brouillon (cf. AN-10).

### 8.3 Workflow de validation d'un bordereau

Séquence exécutée par `WizardValidationService::validerBordereau`, **intégralement dans une transaction** :

```
 1. CONTRÔLE PRÉALABLE
    ├─ Bordereau déjà validé ? ────────────────▶ ÉCHEC « Ce bordereau a déjà été validé. »
    └─ Pour chaque ligne équipement/licence :
       ├─ Données du wizard absentes ou non finalisées ?
       │  └─▶ ÉCHEC « Les informations d'inventaire pour '<article>' ne sont pas finalisées. »
       └─ Nombre d'unités ≠ quantité livrée ?
          └─▶ ÉCHEC « La quantité saisie dans l'assistant pour '<article>'
                      ne correspond pas à la quantité livrée. »

 2. TRAITEMENT LIGNE PAR LIGNE
    ├─ Récupération du prix unitaire depuis la ligne de commande
    │  └─ Article absent du BC ? ──▶ ÉCHEC
    │
    ├─ TYPE ÉQUIPEMENT — pour chaque unité :
    │     ├─ Génération du code inventaire si non saisi
    │     ├─ Contrôle d'unicité du numéro de série dans le parc  ──▶ ÉCHEC si doublon
    │     ├─ Contrôle d'unicité du code inventaire dans le parc  ──▶ ÉCHEC si doublon
    │     ├─ Création de la fiche Équipement (statut « en_stock », état « bon »)
    │     └─ Création de l'entrée d'historique « acquisition »
    │
    ├─ TYPE LICENCE :
    │     ├─ Création du logiciel si inexistant (firstOrCreate sur la désignation)
    │     └─ Pour chaque unité : création d'une Licence (statut VALIDE)
    │
    ├─ TYPE CONSOMMABLE :
    │     ├─ Incrément de achat_articles.stock_actuel
    │     ├─ Création du consommable ParcInfo si absent (type « GEN-CONS »)
    │     ├─ Incrément de la quantité en stock + date de dernier approvisionnement
    │     └─ Création du mouvement de consommable (type « entree »)
    │
    └─ Incrément de quantite_livree sur la ligne de commande

 3. MISE À JOUR DU BON DE COMMANDE
    └─ total livré ≥ total commandé ? ──▶ statut « livre »   sinon ──▶ statut « partiel »

 4. MISE À JOUR DU BORDEREAU
    └─ statut « valide »

 5. MODULE STOCK
    └─ EntreeStockService::creerDepuisBL — mouvement d'entrée + lot FIFO par article

 6. NETTOYAGE
    └─ Purge des enregistrements achat_wizard_data du bordereau

 ▶ RETOUR : { equipements: [...], licences: [...] }
   Message : « Bordereau validé avec succès ! Intégration effectuée :
               N équipement(s) créé(s) et M licence(s) créée(s). »
```

**Garantie transactionnelle** — Toute exception à n'importe quelle étape annule l'intégralité du traitement : aucun équipement partiellement créé, aucun compteur incrémenté, aucun statut modifié. L'utilisateur voit le message d'erreur précis et peut corriger sa saisie.

### 8.4 Scénario nominal complet

| # | Acteur | Action | Écran | Résultat |
|---|---|---|---|---|
| 1 | Acheteur | Crée l'article « Ordinateur portable HP ProBook » | E-02 / M-01 | Article actif au catalogue |
| 2 | Acheteur | Crée un BC pour 5 unités chez le fournisseur X | E-04 | `BC-2026-0001` en brouillon |
| 3 | Validateur | Valide le bon de commande | E-06 | Statut `valide`, verrouillé |
| 4 | Magasinier | Reçoit 3 unités, crée le bordereau | E-10 | `BL-2026-0001` en brouillon, reste à livrer 2 |
| 5 | Magasinier | Lance l'assistant | E-12 | Bordereau en statut `wizard` |
| 6 | Magasinier | Saisit 3 numéros de série et les caractéristiques | E-12 | Étape complétée |
| 7 | Magasinier | Finalise | E-12 | 3 équipements créés, codes inventaire générés, BC en `partiel` |
| 8 | Magasinier | Reçoit les 2 unités restantes | E-10 → E-12 | `BL-2026-0002`, 2 équipements créés, **BC en `livre`** |
| 9 | Acheteur | Consulte la fiche du BC | E-06 | Onglet « Équipements intégrés » : 5 fiches, onglet « Bordereaux » : 2 cartes |

---

## 9. Sécurité et matrice des permissions

### 9.1 Permissions déclarées

22 permissions sont déclarées dans `config/permissions.php` et créées par `PermissionsAchatSeeder` (paquet Spatie).

| Permission | Libellé | Utilisée ? |
|---|---|---|
| `achat.dashboard.view` | Voir le tableau de bord Achat | ✔ *(E-01 et E-15)* |
| `achat.articles.view` | Voir les articles | ✔ |
| `achat.articles.create` | Créer des articles | ✔ |
| `achat.articles.edit` | Modifier les articles | ✔ |
| `achat.articles.delete` | Supprimer les articles | ✔ |
| `achat.bons_commande.view` | Voir les bons de commande | ✔ |
| `achat.bons_commande.create` | Créer des bons de commande | ✔ |
| `achat.bons_commande.edit` | Modifier les bons de commande (brouillon) | ✔ *(également utilisée pour valider et annuler)* |
| `achat.bons_commande.valider` | Valider les bons de commande | ✘ **jamais contrôlée** |
| `achat.bons_commande.annuler` | Annuler les bons de commande | ✘ **jamais contrôlée** |
| `achat.bordereaux.view` | Voir les bordereaux de livraison | ✔ |
| `achat.bordereaux.create` | Créer des bordereaux de livraison | ✔ |
| `achat.bordereaux.edit` | Modifier les bordereaux (brouillon) | ✔ *(également utilisée pour le wizard)* |
| `achat.bordereaux.valider` | Valider les bordereaux via wizard | ✘ **jamais contrôlée** |
| `achat.stocks.view` | Voir les stocks consommables | ✔ |
| `achat.stocks.entree` | Enregistrer des entrées de stock | ✘ *(fonctionnalité absente)* |
| `achat.stocks.sortie` | Enregistrer des sorties de stock | ✘ *(fonctionnalité absente)* |
| `achat.stocks.inventaire` | Effectuer un inventaire | ✘ *(fonctionnalité absente)* |
| `achat.licences.view` | Voir les licences | ✘ *(écran absent)* |
| `achat.licences.manage` | Gérer les licences | ✘ *(écran absent)* |
| `achat.rapports.view` | Voir les rapports | ✘ *(E-15 utilise `dashboard.view`)* |
| `achat.rapports.export` | Exporter les rapports | ✘ |

**Deux permissions sont contrôlées dans le code mais absentes de la déclaration** : `achat.bons_commande.delete` et `achat.bordereaux.delete` (cf. AN-01).

### 9.2 Matrice écran / permission

| Écran | Consultation | Actions de création | Actions de modification | Actions de suppression |
|---|---|---|---|---|
| E-01 Tableau de bord | `dashboard.view` | — | — | — |
| E-02 Catalogue | `articles.view` | `articles.create` *(créer, dupliquer)* | `articles.edit` *(modifier, activer/désactiver)* | `articles.delete` |
| E-03 Liste BC | `bons_commande.view` | `bons_commande.create` | `bons_commande.edit` *(annuler)* | `bons_commande.delete` ⚠ |
| E-04 Création BC | `bons_commande.create` | idem | — | — |
| E-05 Modification BC | `bons_commande.edit` | — | idem | — |
| E-06 Fiche BC | `bons_commande.view` | `bordereaux.create` *(créer BL)* | `bons_commande.edit` *(valider, annuler)* | *(documents : aucun contrôle)* ⚠ |
| E-07/E-08 Impression BC | `bons_commande.view` | — | — | — |
| E-09 Liste BL | `bordereaux.view` | `bordereaux.create` | `bordereaux.edit` *(wizard)* | `bordereaux.delete` ⚠ |
| E-10 Création BL | `bordereaux.create` | idem | — | — |
| E-11 Fiche BL | `bordereaux.view` | — | `bordereaux.edit` | *(documents : aucun contrôle)* ⚠ |
| E-12 Assistant | `bordereaux.edit` | — | `bordereaux.edit` *(finalisation)* | — |
| E-13 PDF BL | `bordereaux.view` | — | — | — |
| E-14 Stocks | `stocks.view` | — | — | — |
| E-15/E-16 Statistiques | `dashboard.view` ⚠ | — | — | — |

⚠ = écart identifié, voir chapitre 12.

### 9.3 Matrice rôle / permission

| Domaine | Acheteur | Validateur Achat | Magasinier |
|---|:---:|:---:|:---:|
| Tableau de bord | ✔ | ✔ | ✔ |
| Articles — consultation | ✔ | ✔ | ✔ |
| Articles — création / modification / suppression | ✔ | ✘ | ✘ |
| BC — consultation | ✔ | ✔ | ✔ |
| BC — création | ✔ | ✔ | ✘ |
| BC — modification | ✔ | ✔ | ✘ |
| BC — validation / annulation | ✘ *(via `edit`)* | ✔ | ✘ |
| BL — consultation | ✔ | ✔ | ✔ |
| BL — création / modification / assistant | ✘ | ✘ | ✔ |
| Stocks — consultation | ✔ | ✘ | ✔ |
| Rapports — consultation | ✔ | ✔ | ✘ |
| Rapports — export | ✘ | ✔ | ✘ |

> Le rôle **Acheteur** dispose de `achat.bons_commande.edit`. Comme les actions de validation et d'annulation contrôlent cette permission au lieu des permissions dédiées, **un acheteur peut de fait valider ses propres commandes** — la séparation des rôles voulue par la configuration n'est pas effective (cf. AN-02).

### 9.4 Mécanismes de contrôle

| Niveau | Mécanisme |
|---|---|
| Route | Middleware `['auth', 'verified']` sur l'ensemble du groupe |
| Contrôleur | `$this->authorize('permission')` via le trait `AuthorizesRequests` |
| FormRequest | `authorize()` → `$this->user()->can('permission')` |
| Vue | Directives `@can(...)` masquant les boutons non autorisés |
| Données | Aucune restriction par périmètre : tout utilisateur autorisé voit **toutes** les données du module |

---

## 10. Messages, erreurs et états d'écran

### 10.1 Messages de succès

| Contexte | Message |
|---|---|
| Article créé | L'article '*désignation*' a été créé avec succès. |
| Article modifié | L'article '*désignation*' a été mis à jour avec succès. |
| Article supprimé | L'article a été supprimé avec succès. |
| Article activé / désactivé | L'article a été activé / désactivé avec succès. |
| Article dupliqué | L'article a été dupliqué sous le code '*code*'. |
| BC créé | Le bon de commande '*numéro*' a été créé avec succès. |
| BC modifié | Le bon de commande '*numéro*' a été modifié avec succès. |
| BC validé | Le bon de commande '*numéro*' a été validé avec succès. |
| BC annulé | Le bon de commande '*numéro*' a été annulé. |
| BC supprimé | Le bon de commande a été supprimé avec succès. |
| BL créé | Le bordereau de livraison '*numéro*' a été créé avec succès. |
| BL modifié | Le bordereau '*numéro*' a été modifié avec succès. |
| BL supprimé | Le bordereau de livraison a été supprimé avec succès. |
| Étape du wizard | Étape sauvegardée avec succès. |
| Intégration réussie | Bordereau validé avec succès ! Intégration effectuée : *N* équipement(s) créé(s) et *M* licence(s) créée(s). |
| Validation directe (consommables seuls) | Le bordereau ne contenant que des consommables, il a été validé et intégré directement. |
| Document ajouté | Document ajouté avec succès. |
| Document supprimé | Document supprimé avec succès. |

### 10.2 Messages d'erreur métier (HTTP 422)

| Contexte | Message |
|---|---|
| Catégorie manquante | La catégorie d'équipement est obligatoire pour les articles de type Équipement. |
| Catégorie en trop | La catégorie d'équipement doit être nulle pour les articles qui ne sont pas des Équipements. |
| Article référencé | L'article est référencé dans des bons de commande. Il a été désactivé au lieu d'être supprimé. |
| BC non modifiable | Ce bon de commande ne peut plus être modifié car il n'est plus en statut brouillon. |
| BC non supprimable | Ce bon de commande ne peut pas être supprimé car il n'est plus en statut brouillon. |
| BC non validable | Ce bon de commande ne peut pas être validé (soit il n'est pas en brouillon, soit il n'a pas de lignes). |
| BC non annulable | Ce bon de commande ne peut pas être annulé. |
| BC de statut incompatible | Impossible de créer une livraison pour un bon de commande en statut : *statut*. |
| Référence physique en doublon | Un bordereau de livraison physique avec la référence '*réf*' existe déjà. |
| Quantité excessive | La quantité livrée (*n*) dépasse le reste à livrer (*m*) pour l'article '*désignation*'. |
| Article hors BC | L'article ID *n* n'existe pas dans le bon de commande. |
| BL non modifiable | Ce bordereau de livraison ne peut plus être modifié car il n'est plus en brouillon. |
| BL non supprimable | Ce bordereau de livraison ne peut pas être supprimé car il n'est plus en statut brouillon. |
| Wizard inaccessible | L'assistant ne peut pas être lancé pour ce bordereau. |
| BL déjà validé | Ce bordereau de livraison a déjà été validé. |
| Étape non finalisée | Les informations d'inventaire pour l'article '*désignation*' ne sont pas finalisées. |
| Quantité incohérente | La quantité saisie dans l'assistant pour '*désignation*' ne correspond pas à la quantité livrée. |
| N° de série manquant | Le numéro de série est obligatoire pour l'article : *désignation* |
| Code inventaire manquant | Le code inventaire est obligatoire pour l'article : *désignation* |
| N° de série en doublon | Le numéro de série '*valeur*' existe déjà dans le parc. |
| Code inventaire en doublon | Le code inventaire '*valeur*' existe déjà dans le parc. |
| Type de document invalide | Type de document invalide. |
| Fichier absent | Aucun fichier fourni. |

### 10.3 Messages de validation de formulaire

| Champ | Message |
|---|---|
| `categorie_equipement_id` | La catégorie d'équipement est obligatoire pour les Équipements. |
| `seuil_alerte` | Le seuil d'alerte est obligatoire pour les Consommables. |
| `duree_validite_mois` | La durée de validité est obligatoire pour les Licences. |
| `reference_constructeur` | Cette référence constructeur existe déjà pour cette marque. |
| `lignes` | Le bon de commande doit contenir au moins une ligne. / Le bordereau de livraison doit contenir au moins une ligne livrée. |
| `lignes.*.article_id` | L'article est obligatoire. / L'article sélectionné n'existe pas. |
| `lignes.*.quantite` | La quantité est obligatoire. / La quantité doit être un nombre entier. / La quantité doit être supérieure ou égale à 1. |
| `lignes.*.prix_unitaire` | Le prix unitaire est obligatoire. / Le prix unitaire doit être un nombre. / Le prix unitaire doit être supérieur ou égal à 0. |
| `lignes.*.quantite_livree` | La quantité livrée doit être supérieure ou égale à 1. |
| `ref_bordereau_physique` | Cette référence de bordereau physique existe déjà. |

### 10.4 États d'écran normalisés

| État | Traitement |
|---|---|
| **Chargement** | Bouton en `disabled` + spinner Bootstrap + libellé « Enregistrement… » / « Sauvegarde… » / « Intégration en cours… ». Pour les tables asynchrones : ligne unique avec spinner et texte explicatif. |
| **Liste vide** | Ligne unique sur toute la largeur, icône `fa-info-circle`, texte gris centré, formulation spécifique à l'écran. |
| **Erreur de chargement** | Ligne unique en rouge avec icône `fa-exclamation-triangle`. |
| **Droits insuffisants** | Le bouton n'est **pas rendu** dans le HTML (directive `@can`). Une tentative d'accès direct à la route renvoie une erreur 403 Laravel non personnalisée. |
| **Action indisponible** | Le bouton est rendu mais en `disabled`, avec un tooltip explicatif (« Supprimer (Brouillon uniquement) »). |

---

## 11. Intégrations inter-modules

### 11.1 Lectures depuis ParcInfo

| Ressource ParcInfo | Consommateur Achat | Usage |
|---|---|---|
| `parc_info_marques` | Catalogue, filtres | Marque obligatoire d'un article |
| `parc_info_categories_equipements` | Catalogue, wizard | Catégorie et **champs personnalisés** utilisés dans l'assistant |
| `parc_info_fournisseurs` | Articles, BC, statistiques | Fournisseur préféré, fournisseur du BC |
| `parc_info_equipements` | E-06, E-11 | Restitution des équipements générés (par `ref_bordereau`) |

### 11.2 Écritures vers ParcInfo (validation d'un BL)

| Table cible | Déclencheur | Contenu |
|---|---|---|
| `parc_info_equipements` | Article de type équipement | Une fiche par unité : catégorie, code inventaire, n° de série, marque, modèle = désignation de l'article, date d'acquisition = date de livraison, valeur d'achat = prix unitaire du BC, `ref_bordereau` = n° du BL, statut `en_stock`, état `bon`, caractéristiques techniques |
| `parc_info_historique_changements` | Article de type équipement | Entrée `acquisition` mentionnant le n° de BL et le n° de BC |
| `parc_info_logiciels` | Article de type licence | Créé si absent : nom = désignation, code = 50 premiers caractères du code article |
| `parc_info_licences` | Article de type licence | Une licence par unité : clé, dates, coûts, fournisseur, statut `VALIDE` |
| `parc_info_consommables` | Article de type consommable | Créé si absent sous le type générique `GEN-CONS`, puis quantité incrémentée |
| `parc_info_mouvements_consommables` | Article de type consommable | Mouvement `entree` avec référence de commande et utilisateur |

### 11.3 Écritures vers le module Stock

`WizardValidationService` appelle `EntreeStockService::creerDepuisBL` qui, **pour chaque ligne du bordereau** :

1. Sélectionne le premier magasin actif de type `CONSOMMABLE`, à défaut le premier magasin actif ; **crée un magasin `MAG-DEFAULT` si aucun n'existe** ;
2. Crée un `StockMouvement` de type `ENTREE`, d'origine `BL` ;
3. Crée un `StockLot` alimentant la valorisation FIFO.

> **Conséquence fonctionnelle** : les articles de type **équipement et licence sont eux aussi enregistrés en stock**, alors qu'ils ont par ailleurs généré des fiches d'équipement individuelles. Cette double comptabilisation doit être arbitrée (cf. AN-12).

### 11.4 Événements applicatifs

| Événement | Écouteurs déclarés | État réel |
|---|---|---|
| `BonCommandeValide` | `NotifierUtilisateurs` | ✔ Dispatché — écrit une ligne de log |
| `BonCommandeAnnule` | `NotifierUtilisateurs` | ✔ Dispatché — écrit une ligne de log |
| `BordereauLivraisonValide` | `CreerEquipementsDansParcInfo`, `MettreAJourStockConsommables`, `CreerLicences`, `HistoriserAcquisition`, `NotifierUtilisateurs` | ✘ **Jamais dispatché**. Les 4 premiers écouteurs ont un corps vide : la logique est exécutée en ligne dans `WizardValidationService` pour préserver l'intégrité transactionnelle |
| `EquipementCreeViaAchat` | `HistoriserAcquisition` | ✘ Jamais dispatché |

---

## 12. Anomalies et dette fonctionnelle

> Écarts constatés entre le comportement implémenté et l'intention fonctionnelle apparente. Chaque entrée est vérifiable dans le code.

| Réf. | Sévérité | Constat | Localisation | Effet fonctionnel |
|---|---|---|---|---|
| **AN-01** | **Bloquante** | Les permissions `achat.bons_commande.delete` et `achat.bordereaux.delete` sont contrôlées dans le code mais **absentes de `config/permissions.php`**, donc jamais créées en base. | `BonCommandeController@destroy`, `BordereauLivraisonController@destroy`, vues `index` | Les boutons Supprimer ne s'affichent **pour personne** et la suppression est inaccessible, y compris pour l'Admin. |
| **AN-02** | **Majeure** | La validation et l'annulation d'un BC contrôlent `bons_commande.edit` au lieu des permissions dédiées `valider` / `annuler`. Idem pour le wizard qui contrôle `bordereaux.edit` et non `bordereaux.valider`. | `BonCommandeController@valider` / `@annuler`, `BordereauLivraisonController@wizard` | **La séparation des rôles n'est pas effective** : tout utilisateur pouvant modifier un BC peut le valider. |
| **AN-03** | **Majeure** | Le taux de TVA est **codé en dur à 18 %** dans le JavaScript et dans la fiche BC, alors que chaque article porte un `taux_tva` (valeur par défaut 20,00 en base de données, 18 dans le formulaire). | `create.js`, `bons_commande/show.blade.php`, migration articles | Les montants TTC affichés sont faux dès qu'un article a un taux différent. **Trois valeurs de référence coexistent.** |
| **AN-04** | **Majeure** | `montant_total` stocke un montant **hors taxes**, mais les écrans le présentent parfois comme un « Montant Total » sans qualification, et les pieds de tableau annoncent un « Montant Total TTC » calculé à l'affichage uniquement. | `BonCommande::recalculerMontantTotal`, E-01, E-03, E-06 | Ambiguïté sur la nature des montants agrégés dans les statistiques et les rapports. |
| **AN-05** | **Majeure** | Les requêtes d'agrégation mensuelle utilisent `TO_CHAR(date_commande, 'YYYY-MM')`, **spécifique à PostgreSQL**. La suite de tests s'exécute sur SQLite en mémoire. | `AchatController@index`, `StatistiquesController@index` | Les écrans E-01 et E-15 ne sont pas testables automatiquement et échoueraient sur un autre SGBD. |
| **AN-06** | Moyenne | Le champ Code Article est présenté comme « facultatif — Génération auto si vide », mais il est `required|unique` côté serveur et **aucune génération automatique n'est implémentée**. | M-01, `StoreArticleRequest` | L'utilisateur qui laisse le champ vide reçoit une erreur de validation contredisant le libellé affiché. |
| **AN-07** | Moyenne | Les agrégations mensuelles appliquent `limit(6)` **après un tri croissant**, ce qui retourne les **6 premiers mois** de l'historique et non les 6 derniers. | `AchatController@index`, `StatistiquesController@index` | Les graphiques « Évolution mensuelle » figent progressivement les données les plus anciennes. |
| **AN-08** | Moyenne | L'événement `BordereauLivraisonValide` n'est **jamais dispatché** et ses 5 écouteurs ont un corps vide. | `Events/`, `Listeners/`, `WizardValidationService` | Toute extension par écouteur (notification, synchronisation) est inopérante. Code mort à retirer ou à activer. |
| **AN-09** | Moyenne | L'onglet « Journal système » de E-06 n'est pas un journal : il reconstruit trois lignes à partir de `created_at`, `updated_at` et `date_validation`. | `bons_commande/show.blade.php` | L'onglet suggère une traçabilité complète qui n'existe pas. |
| **AN-10** | Moyenne | L'ouverture de l'assistant fait passer le bordereau en statut `wizard` **de façon irréversible**. Aucun retour au statut brouillon n'est prévu. | `BordereauLivraisonController@wizard` | Un clic accidentel sur « Lancer l'intégration » rend le bordereau définitivement non modifiable et non supprimable. |
| **AN-11** | Moyenne | Lorsqu'un article ne peut être supprimé, le contrôleur renvoie `success: true` avec le message d'échec. | `ArticleController@destroy` | L'interface affiche une notification de succès pour une opération qui n'a pas eu lieu. |
| **AN-12** | Moyenne | La validation d'un BL alimente **trois systèmes de stock** : `achat_articles.stock_actuel`, `parc_info_consommables`, et le module Stock (lots FIFO). Les équipements et licences sont eux aussi injectés dans le module Stock. | `WizardValidationService`, `EntreeStockService::creerDepuisBL` | Triple comptabilisation des consommables et comptabilisation indue des équipements. Le référentiel de stock faisant foi doit être arbitré. |
| **AN-13** | Mineure | L'écran États & Statistiques contrôle `achat.dashboard.view` au lieu de `achat.rapports.view`, et l'export PDF ne contrôle pas `achat.rapports.export`. | `StatistiquesController` | Les permissions de rapport déclarées n'ont aucun effet. |
| **AN-14** | Mineure | 7 permissions déclarées ne correspondent à aucune fonctionnalité : `stocks.entree`, `stocks.sortie`, `stocks.inventaire`, `licences.view`, `licences.manage`, `rapports.view`, `rapports.export`. | `config/permissions.php` | L'écran d'administration des droits présente des permissions sans portée. |
| **AN-15** | Mineure | La modale de sélection de BC a un en-tête sombre en E-10 et bleu en E-11 ; les en-têtes de plusieurs modales combinent `bg-primary` et `text-primary` *(texte bleu sur fond bleu)*. | `bordereaux/create.blade.php`, `bordereaux/show.blade.php`, `bons_commande/partials/modals.blade.php` | Incohérence de charte et lisibilité dégradée de certains titres. |
| **AN-16** | Mineure | `public/js/modules/achat/bons-commande/show.js` (324 lignes) **n'est référencé par aucune vue** ; la logique équivalente est dupliquée en ligne dans `show.blade.php`. Le code d'upload/suppression de documents est également dupliqué à l'identique entre E-06 et E-11. | `public/js/modules/achat/`, vues `show` | Fichier mort et triple maintenance du même comportement. |
| **AN-17** | Mineure | `DocumentController` ne contrôle **aucune permission** ni propriété : tout utilisateur authentifié peut téléverser, télécharger ou supprimer un document de n'importe quel BC ou BL. | `DocumentController` | Accès non contrôlé aux pièces jointes. |
| **AN-18** | Mineure | En mode édition d'un BL, l'utilisateur peut **changer le bon de commande de rattachement**, ce qui recharge intégralement les lignes. | `bordereaux/show.js` | Un bordereau peut être rattaché à un BC différent de celui de sa création, sans trace. |
| **AN-19** | Mineure | `AchatController` conserve les méthodes `create`, `store`, `show`, `edit`, `update`, `destroy` issues du squelette, dont trois renvoient des vues inexistantes (`achat::create`, `achat::show`, `achat::edit`). | `AchatController` | Code mort. Aucune route n'y mène actuellement. |
| **AN-20** | Mineure | Le sélecteur du bouton « Lancer/Continuer l'intégration » en E-11 est `$('.btn-success, .btn-warning')`, qui capture **tous** les boutons de ces classes dans la page. | `bordereaux/show.js` | Le passage en mode édition peut masquer des boutons non concernés. |

**Synthèse** : 1 anomalie bloquante, 4 majeures, 7 moyennes, 8 mineures.

---

## 13. Annexes

### 13.1 Table des routes

| Méthode | URI | Nom | Contrôleur | Écran |
|---|---|---|---|---|
| GET | `/achat` | `achat.dashboard.index` | `AchatController@index` | E-01 |
| GET | `/achat/articles` | `achat.articles.index` | `ArticleController@index` | E-02 |
| POST | `/achat/articles` | `achat.articles.store` | `ArticleController@store` | M-01 |
| GET | `/achat/articles/{article}` | `achat.articles.show` | `ArticleController@show` | M-01 *(chargement)* |
| PUT | `/achat/articles/{article}` | `achat.articles.update` | `ArticleController@update` | M-01 |
| DELETE | `/achat/articles/{article}` | `achat.articles.destroy` | `ArticleController@destroy` | E-02 |
| POST | `/achat/articles/{article}/toggle-actif` | `achat.articles.toggle-actif` | `ArticleController@toggleActif` | E-02 |
| POST | `/achat/articles/{article}/dupliquer` | `achat.articles.dupliquer` | `ArticleController@dupliquer` | E-02 |
| GET | `/achat/bons-commande` | `achat.bons-commande.index` | `BonCommandeController@index` | E-03 |
| GET | `/achat/bons-commande/create` | `achat.bons-commande.create` | `BonCommandeController@create` | E-04 |
| POST | `/achat/bons-commande` | `achat.bons-commande.store` | `BonCommandeController@store` | E-04 |
| GET | `/achat/bons-commande/{bon_commande}` | `achat.bons-commande.show` | `BonCommandeController@show` | E-06 |
| GET | `/achat/bons-commande/{bon_commande}/edit` | `achat.bons-commande.edit` | `BonCommandeController@edit` | E-05 |
| PUT | `/achat/bons-commande/{bon_commande}` | `achat.bons-commande.update` | `BonCommandeController@update` | E-05 |
| DELETE | `/achat/bons-commande/{bon_commande}` | `achat.bons-commande.destroy` | `BonCommandeController@destroy` | E-03 |
| POST | `/achat/bons-commande/{bon_commande}/valider` | `achat.bons-commande.valider` | `BonCommandeController@valider` | E-06 |
| POST | `/achat/bons-commande/{bon_commande}/annuler` | `achat.bons-commande.annuler` | `BonCommandeController@annuler` | E-03, E-06 |
| GET | `/achat/bons-commande/{bon_commande}/imprimer` | `achat.bons-commande.imprimer` | `BonCommandeController@imprimer` | E-07 / E-08 |
| GET | `/achat/bons-commande/{bon_commande}/lignes-a-livrer` | `achat.bons-commande.lignes-a-livrer` | `BordereauLivraisonController@getLignesALivrer` | E-10, E-11 |
| GET | `/achat/bordereaux` | `achat.bordereaux.index` | `BordereauLivraisonController@index` | E-09 |
| GET | `/achat/bordereaux/create` | `achat.bordereaux.create` | `BordereauLivraisonController@create` | E-10 |
| POST | `/achat/bordereaux` | `achat.bordereaux.store` | `BordereauLivraisonController@store` | E-10 |
| GET | `/achat/bordereaux/{bordereaux}` | `achat.bordereaux.show` | `BordereauLivraisonController@show` | E-11 |
| PUT | `/achat/bordereaux/{bordereaux}` | `achat.bordereaux.update` | `BordereauLivraisonController@update` | E-11 |
| DELETE | `/achat/bordereaux/{bordereaux}` | `achat.bordereaux.destroy` | `BordereauLivraisonController@destroy` | E-09 |
| GET | `/achat/bordereaux/{bordereau}/wizard` | `achat.bordereaux.wizard` | `BordereauLivraisonController@wizard` | E-12 |
| POST | `/achat/bordereaux/{bordereau}/wizard/{article}/sauvegarder` | `achat.bordereaux.wizard.sauvegarder` | `BordereauLivraisonController@sauvegarderWizardEtape` | E-12 |
| POST | `/achat/bordereaux/{bordereau}/wizard/valider` | `achat.bordereaux.wizard.valider` | `BordereauLivraisonController@validerBordereau` | E-12 |
| GET | `/achat/bordereaux/{bordereau}/imprimer` | `achat.bordereaux.imprimer` | `BordereauLivraisonController@imprimer` | E-13 |
| GET | `/achat/stocks` | `achat.stocks.index` | `StockController@index` | E-14 |
| GET | `/achat/statistiques` | `achat.statistiques.index` | `StatistiquesController@index` | E-15 |
| GET | `/achat/statistiques/data` | `achat.statistiques.data` | `StatistiquesController@getData` | E-15 |
| GET | `/achat/statistiques/pdf` | `achat.statistiques.pdf` | `StatistiquesController@generatePdf` | E-16 |
| POST | `/achat/documents` | `achat.documents.store` | `DocumentController@store` | E-06, E-11 |
| GET | `/achat/documents/{document}/telecharger` | `achat.documents.telecharger` | `DocumentController@download` | E-06, E-11 |
| DELETE | `/achat/documents/{document}` | `achat.documents.destroy` | `DocumentController@destroy` | E-06, E-11 |

**36 routes**, toutes sous `['auth', 'verified']`.

### 13.2 Correspondance écran / fichiers

| Écran | Vue Blade | Script JS |
|---|---|---|
| E-01 | `index.blade.php` | *(inline — Chart.js)* |
| E-02 | `articles/index.blade.php` | `articles/index.js` |
| E-03 | `bons_commande/index.blade.php` | `bons-commande/index.js` |
| E-04 | `bons_commande/create.blade.php` | `bons-commande/create.js` |
| E-05 | `bons_commande/edit.blade.php` | `bons-commande/create.js` *(partagé)* |
| E-06 | `bons_commande/show.blade.php` | *(inline)* — `bons-commande/show.js` **non chargé** |
| E-07 | `bons_commande/imprimer.blade.php` | *(inline)* |
| E-08 | `bons_commande/print_pdf.blade.php` | — |
| E-09 | `bordereaux/index.blade.php` | `bordereaux/index.js` |
| E-10 | `bordereaux/create.blade.php` | `bordereaux/create.js` |
| E-11 | `bordereaux/show.blade.php` | `bordereaux/show.js` + inline |
| E-12 | `bordereaux/wizard.blade.php` | `bordereaux/wizard.js` |
| E-13 | `bordereaux/print_pdf.blade.php` | — |
| E-14 | `stocks/index.blade.php` | *(inline)* |
| E-15 | `statistiques/index.blade.php` | *(inline)* |
| E-16 | `statistiques/pdf.blade.php` | — |
| M-01 | inclus dans `articles/index.blade.php` | `articles/index.js` |
| M-03, M-04 | `bons_commande/partials/modals.blade.php` | `bons-commande/create.js` |
| M-05 | inclus dans `bordereaux/create.blade.php` et `bordereaux/show.blade.php` | `bordereaux/create.js`, `bordereaux/show.js` |

### 13.3 Paramétrage

**`config/config.php`** — paramétrage statique (redéploiement requis) :

| Clé | Valeur | Variable d'environnement |
|---|---|---|
| `code_inventaire_pattern` | `INV-{YYYY}-{SEQUENCE:4}` | `ACHAT_CODE_INVENTAIRE_PATTERN` |
| `prefix_bon_commande` | `BC` | `ACHAT_PREFIX_BC` |
| `prefix_bordereau_livraison` | `BL` | `ACHAT_PREFIX_BL` |
| `types_articles` | équipement, consommable, licence, prestation | — |
| `statuts_bc` | 5 statuts avec libellé et couleur | — |
| `statuts_bl` | 3 statuts avec libellé et couleur | — |
| `seuils_stock` | rupture 0, critique 0.2, alerte 0.5, faible 1.0 | — |

> Les **seuils de stock** définis dans la configuration ne sont **pas exploités** : E-14 applique une comparaison binaire `stock_actuel <= seuil_alerte`.

**Table `achat_parametres`** — paramétrage dynamique, modifiable en base sans redéploiement (aucun écran d'administration n'est fourni pour l'éditer).

### 13.4 Couverture de tests

`Modules/Achat/tests/Feature/AchatManagementTest.php` — 9 tests fonctionnels :

| Test | Couvre |
|---|---|
| `test_can_access_achat_dashboard` | E-01 |
| `test_can_access_articles_index` | E-02 |
| `test_can_create_article` | M-01 |
| `test_can_create_bon_commande` | E-04 |
| `test_can_validate_bon_commande` | E-06 |
| `test_can_create_bordereau_livraison` | E-10 |
| `test_can_validate_bordereau_livraison_via_wizard` | E-12 + intégration complète |
| `test_can_print_bon_commande` | E-07 |
| `test_can_stream_pdf_bon_commande` | E-08 |

**Non couverts** : modification et suppression d'un BC ou d'un BL, annulation, duplication et bascule d'état d'un article, écran des stocks, statistiques et rapports, gestion documentaire, livraisons partielles successives.

### 13.5 Conventions de nommage du document

| Préfixe | Signification | Plage |
|---|---|---|
| `E-nn` | Écran | E-01 à E-16 |
| `M-nn` | Modale de formulaire ou de sélection | M-01 à M-08 |
| `SW-nn` | Boîte de dialogue SweetAlert | SW-01 à SW-08 |
| `F-n` | Fonctionnalité de la cartographie | F1 à F5 |
| `RG-XXX-nn` | Règle de gestion par domaine | ART, BC, BL, WZ, INT, NUM, DOC |
| `AN-nn` | Anomalie ou dette fonctionnelle | AN-01 à AN-20 |

---

*Fin du document — Spécifications Fonctionnelles Détaillées du module Achat, version 1.0.*
