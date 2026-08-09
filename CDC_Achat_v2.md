# Cahier des Charges Fonctionnel — Module Achat (v2.0)

**Maître d'ouvrage** : Direction des Systèmes d'Information — CHU-YO
**Application** : Système de Gestion du Parc Informatique
**Module concerné** : Achat & Approvisionnement — **reconstruction intégrée**
**Version** : 2.0 — à valider
**Date** : 3 août 2026
**Nature du document** : Expression de besoin et exigences contractuelles pour la reconstruction du module

---

## Préambule — Portée et usage de ce document

### Historique et statut

| Version | Date | Nature |
|---|---|---|
| 1.0 | 26/07/2026 | Référentiel d'exigences avec niveaux de couverture par l'implémentation d'alors (`CDC_Achat.md`) |
| — | 27/07/2026 | **Suppression des modules Achat et Stock** (branche `refactor/stock-rebuild`). Acquisition en saisie manuelle ParcInfo depuis cette date |
| — | 01/08/2026 | Livraison du **SFD du module Catalogue** v1.0 (référentiel unique des articles et fournisseurs) |
| — | 03/08/2026 | Livraison du **SFD du module Stock** v3.0 consolidé (magasins, entrées/sorties/transferts, sérialisation D10, décisions D1–D17) |
| **2.0** | 03/08/2026 | **Présent document** — cahier des charges de **reconstruction**, réécrit pour la nouvelle architecture modulaire |

La v1.0 décrivait un module autonome portant son propre catalogue, ses bordereaux et son intégration au parc. La v2.0 décrit un module **recentré sur la chaîne d'engagement** (commande, validation, suivi des réceptions, réception dématérialisée des licences, restitutions), **intégré** aux modules Catalogue, Stock, ParcInfo et Core. Les colonnes de couverture de la v1.0 sont sans objet (le code a été supprimé) ; elles sont remplacées par une colonne **Origine** qui trace chaque exigence vers la v1.0 (`v1:EF-XXX-nn`), la marque comme **adaptée** (A), **nouvelle** (N) ou **transférée** vers un autre module (annexe 14.1).

### Documents de référence

| Document | Rôle vis-à-vis du présent CDC |
|---|---|
| `CDC_Achat.md` (v1.0) | Source des exigences métier ; conservé comme trace |
| `SFD_Achat.md` (v1.0) | Description de l'implémentation de référence supprimée ; source des anomalies AN-01 à AN-20 à ne pas reproduire |
| `SFD_Catalogue.md` (v1.0) | Référentiel articles / catégories / fournisseurs — décisions C1 à C11 |
| `SFD_Stock.md` (v3.0 consolidé, 03/08/2026) | Magasins, niveaux, mouvements, bons d'entrée/sortie/transfert, **sérialisation des équipements** (D10), décisions D1–D17 |
| `API_Stock.md`, `TESTS_Stock.md`, `PLAN_Dev_Stock.md` | Contrats d'API, invariants I1–I17, trajectoire de développement Stock |
| `ANALYSE_ParcInfo.md`, `ANALYSE_Core.md` | Architecture des modules consommés |
| `PATTERNS.md`, `DESIGN.md` | Conventions de développement et charte graphique |

### Conventions du document

Priorités **MoSCoW** : **M** indispensable · **S** important · **C** souhaitable · **W** hors périmètre de la version.
Les volumétries et charges (chap. 4.3 et 13) sont des **hypothèses à confirmer**.

---

## Table des matières

1. [Contexte et architecture cible](#1-contexte-et-architecture-cible)
2. [Objectifs du module](#2-objectifs-du-module)
3. [Périmètre et décisions de cadrage](#3-périmètre-et-décisions-de-cadrage)
4. [Acteurs et organisation](#4-acteurs-et-organisation)
5. [Processus métier cibles](#5-processus-métier-cibles)
6. [Exigences fonctionnelles](#6-exigences-fonctionnelles)
7. [Exigences non fonctionnelles](#7-exigences-non-fonctionnelles)
8. [Règles de gestion contractuelles](#8-règles-de-gestion-contractuelles)
9. [Contraintes](#9-contraintes)
10. [Prérequis inter-modules](#10-prérequis-inter-modules)
11. [Livrables attendus](#11-livrables-attendus)
12. [Recette et critères d'acceptation](#12-recette-et-critères-dacceptation)
13. [Lotissement et trajectoire](#13-lotissement-et-trajectoire)
14. [Annexes](#14-annexes)

---

## 1. Contexte et architecture cible

### 1.1 Contexte

Le CHU-YO exploite l'application « Parc Info », organisée en modules : **Core** (socle, utilisateurs, permissions, journal), **Organisation**, **Grh**, **ParcInfo** (parc, licences, référentiels), **Catalogue** (nouveau — référentiel des articles et fournisseurs) et **Stock** (en reconstruction — niveaux, mouvements, réceptions).

Le premier module Achat, livré puis retiré le 27/07/2026, a démontré la pertinence de la chaîne fonctionnelle (catalogue → commande → réception → intégration au parc) tout en révélant des défauts structurels documentés (triple comptabilisation du stock, séparation des rôles inopérante, montants TTC erronés, absence de journalisation réelle — cf. `SFD_Achat.md` chap. 12). Depuis son retrait, l'acquisition se fait par saisie manuelle dans ParcInfo, **sans lien avec un bon de commande** : chaque jour d'intérim accroît la dette de traçabilité patrimoniale.

### 1.2 Architecture cible — répartition des responsabilités

La reconstruction s'inscrit dans une architecture où chaque donnée a un **module teneur unique** :

| Donnée | Module teneur | Le module Achat… |
|---|---|---|
| Articles, catégories, fournisseurs | **Catalogue** | consomme (lecture, API) |
| Niveaux de stock, mouvements, magasins | **Stock** | n'en porte **aucun** |
| Réceptions physiques, **sérialisation** des équipements | **Stock** | fournit le contexte (BC, prix) et en consolide le suivi |
| Fiches équipements, logiciels, licences | **ParcInfo** | crée les **licences** à la réception dématérialisée |
| Engagement fournisseur : bons de commande, lignes, reliquats, pièces justificatives | **Achat** | est le teneur unique |
| Utilisateurs, permissions, journal d'activité | **Core** | consomme |

```
┌────────────┐  articles, fournisseurs  ┌─────────────────────────────┐
│ CATALOGUE  │─────────(lecture)───────▶│           ACHAT             │
└────────────┘                          │  BC · lignes · reliquats    │
┌────────────┐  lignes à livrer, prix   │  réception licences · états │
│   STOCK    │◀────────(API)────────────│  pièces justificatives      │
│ réceptions │─── réception validée ───▶│                             │
│sérialisation│      (notification)     └──────────────┬──────────────┘
└─────┬──────┘                                         │ création licences
      │ fiches équipements (statut en stock)           ▼
      ▼                                        ┌────────────┐
┌────────────┐                                 │  PARCINFO  │
│  PARCINFO  │                                 │ logiciels/ │
│ équipements│                                 │  licences  │
└────────────┘                                 └────────────┘
```

### 1.3 Enjeux

| Enjeu | Description |
|---|---|
| **Traçabilité patrimoniale** | Remonter de tout équipement du parc à son bon de commande, son fournisseur, son prix et sa date d'acquisition — y compris à travers la frontière Achat / Stock (sérialisation). |
| **Unicité des référentiels** | Aucune duplication : articles et fournisseurs au Catalogue, quantités au Stock, engagement à l'Achat. |
| **Maîtrise de la dépense** | États consolidés de l'engagement par période, fournisseur et nature, sur des montants HT/TTC exacts. |
| **Séparation des fonctions** | Celui qui commande ne valide pas ; garanti par des permissions dédiées dès la conception. |
| **Conformité administrative** | Pièces justificatives conservées, journal des événements non altérable, piste d'audit reconstituable. |
| **Résorption de l'intérim** | Offrir un mécanisme de régularisation documentaire des acquisitions réalisées hors module depuis le 27/07/2026. |

---

## 2. Objectifs du module

### 2.1 Objectif général

> Doter le CHU-YO d'un module d'engagement fournisseur couvrant le cycle de vie des bons de commande et le suivi consolidé des réceptions, **intégré nativement** aux modules Catalogue, Stock et ParcInfo, garantissant la traçabilité complète de la chaîne d'acquisition sans dupliquer aucun référentiel.

### 2.2 Objectifs opérationnels

| Réf. | Objectif | Indicateur de réussite |
|---|---|---|
| **OBJ-01** | Toute commande émise depuis le référentiel Catalogue | 100 % des lignes de BC référencent un article Catalogue |
| **OBJ-02** | Dématérialiser le cycle de vie des bons de commande | Aucun BC émis hors application |
| **OBJ-03** | Assurer la traçabilité commande → réception → équipement à travers les modules | Tout équipement sérialisé rattachable à sa ligne de BC, son prix et son fournisseur |
| **OBJ-04** | Suivi automatique des livraisons partielles et des reliquats | État des reliquats exact et disponible à tout moment ; aucun compteur tenu manuellement |
| **OBJ-05** | Réception dématérialisée des licences | 100 % des licences acquises créées dans ParcInfo depuis Achat |
| **OBJ-06** | Produire les états de dépense | États standards exportables PDF et tableur, montants HT/TTC exacts |
| **OBJ-07** | Conserver les pièces justificatives | BC signés et bordereaux fournisseurs numérisés, accès contrôlé |
| **OBJ-08** | Résorber la dette de l'intérim | Acquisitions 27/07/2026 → mise en service du module documentées par régularisation |

---

## 3. Périmètre et décisions de cadrage

### 3.1 Décisions de cadrage (A1 à A10)

Ces décisions structurent le périmètre. Elles prolongent les décisions C1–C11 du Catalogue et sont **à valider par la maîtrise d'ouvrage avant le lot A1**.

| # | Décision | Détail et conséquences |
|---|---|---|
| **A1** | **La réception physique est un acte du module Stock.** | Le « bordereau de livraison » du module Achat v1.0 disparaît : la réception est un **bon d'entrée Stock de nature `livraison`** (triptyque brouillon → référencement → validé, SFD Stock §1.5). Lorsqu'elle provient d'une commande, l'entrée **référence le BC Achat** (validé ou partiel) : lignes pré-remplies depuis le reste à livrer, fournisseur imposé par le BC. Stock v3.0 n'a préparé que l'ancrage (`reference_externe`, D11) : ce raccordement est une **évolution conjointe du module Stock**, prérequis PRQ-05, spécifiée dans `API_Inter_Modules.md`. Achat consolide le suivi (fiche BC, états) sans dupliquer la saisie. |
| **A2** | **Le reliquat est tenu par Achat.** | `quantite_livree` vit sur la ligne de commande. Stock notifie chaque réception validée via le contrat d'API (§6.4) ; l'incrément est transactionnel et idempotent. Achat est la **source de vérité du reste à livrer**, que Stock interroge et plafonne à la saisie. |
| **A3** | **La réception des licences est dématérialisée et portée par Achat.** | Conforme C10 (`est_stockable = false`) : aucune écriture Stock, création directe des `parc_info_licences` rattachées au logiciel de l'article (C11). |
| **A4** | **Prix et taux de TVA sont figés sur la ligne de commande.** | Copiés du Catalogue à la saisie (prix indicatif → prix négocié modifiable ; `taux_tva` → taux de la ligne). Une modification ultérieure au Catalogue **n'altère jamais** un BC existant. |
| **A5** | **La nature « prestation » est réintroduite au Catalogue.** | 5ᵉ nature, non stockable (même mécanisme que `licence`), sans sérialisation ni licence : la réception vaut **constat de service fait**, saisi dans Achat. *Amendement du SFD Catalogue requis (prérequis PRQ-02). Repli si refusé : les prestations restent hors périmètre (W).* |
| **A6** | **Le compte comptable d'imputation est porté par l'article Catalogue.** | Colonne à ajouter à `catalogue_articles` (prérequis PRQ-03) ; conditionne l'état de dépense par imputation (EF-RAP-12). |
| **A7** | **Les fournisseurs sont référencés dans le Catalogue** (décision C4). | FK des BC → `catalogue_fournisseurs`. *Repli si C4 invalidée : `parc_info_fournisseurs`, sans autre impact sur ce CDC.* |
| **A8** | **Permissions dédiées par action sensible, dès la conception.** | `valider`, `annuler`, `cloturer`, `regulariser`, `receptionner-licences` distinctes de `edit` (leçon AN-01/AN-02). Toute permission déclarée correspond à une fonctionnalité contrôlée. |
| **A9** | **La journalisation s'appuie sur le journal d'activité Core** (`spatie/laravel-activitylog`, trait `LogsActivityWithModule`, `module = 'achat'`). | Chaque changement d'état est un événement journalisé (auteur, date, motif), consultable depuis la fiche du document (leçon AN-09). |
| **A10** | **La sérialisation Stock reçoit d'Achat les données de valorisation.** | Les fiches ParcInfo naissent du bon d'entrée (D10 : catégorie/marque/modèle hérités de l'article, statut « en stock »). Pour une entrée liée à un BC : le `cout_unitaire` des lignes est **pré-rempli du prix figé de la ligne de commande** (et non du prix indicatif Catalogue), la valeur et la date d'acquisition des fiches en découlent, et la remontée fiche → mouvement → entrée → **BC** est toujours possible (ENF-TRA-04). Contrat au §6.4 et dans `API_Inter_Modules.md`. |

### 3.2 Périmètre fonctionnel inclus

| Domaine | Contenu |
|---|---|
| **Bons de commande** | Émission depuis le Catalogue, validation, annulation, clôture de reliquat, impression, cycle de vie automatique |
| **Suivi des réceptions** | Consolidation des réceptions Stock d'un BC, reliquats, traçabilité vers les équipements sérialisés |
| **Réception des licences** | Assistant de saisie des clés, création des licences ParcInfo (et constat de service fait pour les prestations — A5) |
| **Contrat inter-modules** | API exposée à Stock : lignes à livrer, plafonds, notification de réception, données de valorisation |
| **Restitution** | Tableau de bord, états statistiques, exports PDF et tableur |
| **Documentation** | Pièces jointes sur les bons de commande, accès contrôlé |
| **Régularisation** | Documentation a posteriori des acquisitions de l'intérim |
| **Paramétrage** | Écran d'administration : préfixe de numérotation, délais d'alerte |

### 3.3 Périmètre exclu

| Exclusion | Module ou traitement responsable |
|---|---|
| Référentiel des articles, catégories, fournisseurs | **Catalogue** (C1–C11) — v1:EF-CAT transférées, cf. annexe 14.1 |
| Réception physique, magasins, niveaux, mouvements, inventaires | **Stock** — v1:EF-BL et EF-STK transférées |
| Sérialisation des équipements (n° série, code inventaire, champs dynamiques) | **Stock** (décision C3) — v1:EF-INT-01 à 05, 08, 11, 15 à 19 transférées |
| Cycle de vie des équipements et des licences après acquisition | **ParcInfo** |
| Calcul d'amortissement, facturation, mandatement, règlement | Système comptable et financier (Achat fournit les données sources) |
| Consultation fournisseurs, appels d'offres, marchés | Service des marchés |
| Demande d'achat amont, circuit d'approbation multi-niveaux | Lot ultérieur (W) |
| Retours et litiges fournisseurs | Lot ultérieur (W) |

### 3.4 Interfaces avec le système d'information

| Système | Nature de l'échange | Sens | Criticité |
|---|---|---|---|
| **Catalogue** | Articles (recherche bornée, fiche compacte), fournisseurs, catégories — API §2.5 du SFD Catalogue | Lecture | **Bloquante** |
| **Stock** | Lignes à livrer d'un BC, plafonds, données de valorisation (pré-remplissage du bon d'entrée — évolution PRQ-05, ancre D11) | Achat → Stock | **Bloquante** |
| **Stock** | Notification de validation d'un bon d'entrée lié à un BC (et contre-mouvement éventuel) | Stock → Achat | **Bloquante** |
| **Stock** | API existantes §2.6 (disponibilité d'un article, magasins) pour affichage informatif | Lecture | Moyenne |
| **ParcInfo — licences** | Création des licences rattachées au logiciel de l'article (C11) | Écriture | Forte |
| **ParcInfo — logiciels** | Lecture (via l'article Catalogue, par transitivité) | Lecture | Forte |
| **Core** | Utilisateurs, permissions Spatie, journal d'activité, layout, navigation | Lecture / écriture journal | **Bloquante** |
| **Stockage de fichiers** | Pièces jointes | Lecture / écriture | Moyenne |

> **EXI-INT-00** — Le module Achat ne duplique **aucun** référentiel : ni article, ni fournisseur, ni quantité en stock, ni fiche d'équipement. Il est le teneur unique de l'engagement (BC, lignes, reliquats, pièces). Toute donnée d'un autre module est consommée par API ou relation, jamais recopiée — à l'exception des valeurs **figées** à la ligne (prix, taux de TVA, désignation au moment de la commande) qui constituent une photographie contractuelle et non une duplication de référentiel.

---

## 4. Acteurs et organisation

### 4.1 Acteurs utilisateurs

| Acteur | Profil | Responsabilités dans Achat |
|---|---|---|
| **Acheteur** | Agent du service approvisionnement | Saisit les bons de commande, suit les reliquats, réceptionne les licences et constate le service fait, joint les pièces |
| **Validateur Achat** | Responsable hiérarchique | Valide, annule et clôture les bons de commande |
| **Magasinier** | Agent du magasin | Réceptionne physiquement **dans le module Stock** (hors périmètre Achat) ; consulte les BC et reliquats |
| **Consultant** | Direction, contrôle de gestion | Consulte états et rapports, sans modification |
| **Administrateur** | Administrateur applicatif | Paramètre le module, gère les habilitations |

> **EXI-ORG-01** — La séparation entre **celui qui commande** et **celui qui valide** est garantie techniquement par des permissions dédiées (A8). Un utilisateur ne disposant que du profil Acheteur ne peut en aucun cas valider un bon de commande, y compris par accès direct aux routes.

### 4.2 Matrice RACI

**R** = Réalise · **A** = Approuve · **C** = Consulté · **I** = Informé · *(les activités portées par un autre module sont indiquées en italique)*

| Activité | Module | Acheteur | Validateur | Magasinier | Consultant | Admin |
|---|---|:---:|:---:|:---:|:---:|:---:|
| Référencer un article | *Catalogue* | R | I | C | | |
| Saisir un bon de commande | Achat | **R** | I | | | |
| Valider / annuler un BC | Achat | C | **R/A** | I | | |
| Clôturer un reliquat | Achat | C | **R/A** | I | | |
| *Réceptionner physiquement (équipements, consommables, pièces)* | *Stock* | I | I | **R** | | |
| *Sérialiser les équipements* | *Stock* | I | | **R** | | |
| Réceptionner des licences / constater un service fait | Achat | **R** | I | | | |
| Régulariser une acquisition de l'intérim | Achat | **R** | **A** | C | | |
| Joindre une pièce justificative | Achat | **R** | **R** | | | |
| Consulter les états | Achat | C | C | C | **R** | |
| Paramétrer le module, gérer les habilitations | Achat/Core | | | | | **R/A** |

### 4.3 Volumétrie de référence *(hypothèses v1.0 reconduites, à confirmer)*

| Objet | Volume annuel estimé | Cumul à 5 ans |
|---|---|---|
| Bons de commande | 300 à 500 | ~2 000 |
| Lignes de commande | 1 500 à 2 500 | ~10 000 |
| Réceptions (Stock) rattachées | 400 à 700 | ~3 000 |
| Licences réceptionnées | 100 à 300 | ~1 000 |
| Documents joints | 800 à 1 400 | ~6 000 fichiers, ~15 Go |
| Utilisateurs simultanés | 5 à 15 | — |

---

## 5. Processus métier cibles

### 5.1 Processus principal — De l'engagement à l'intégration (inter-modules)

```
  CATALOGUE          ACHAT             ACHAT             STOCK              ACHAT
 ┌──────────┐   ┌──────────┐      ┌──────────┐      ┌──────────────┐   ┌──────────┐
 │ Article  │──▶│ Émission │─────▶│Validation│─────▶│  Réception   │──▶│ Reliquat │
 │référencé │   │  du BC   │      │  du BC   │      │+sérialisation│   │mis à jour│
 └──────────┘   └──────────┘      └──────────┘      └──────┬───────┘   └────┬─────┘
   Gestionnaire   Acheteur         Validateur        Magasinier             │auto
   catalogue                                               │                ▼
                                                           ▼          BC partiel
                                                    Équipements       ou livré
                                                    au parc (ParcInfo)
                                                    Stock à jour
```

**Branche licences (et prestations — A5)** : après validation du BC, la réception est saisie **dans Achat** (assistant de clés / constat de service fait) et crée les licences dans ParcInfo. Aucun passage par Stock.

**Jalons de contrôle**

| Jalon | Module | Contrôle | Conséquence du franchissement |
|---|---|---|---|
| Émission | Achat | Au moins une ligne, un fournisseur, prix et TVA figés | Brouillon librement modifiable |
| **Validation** | Achat | Contrôle hiérarchique, permission dédiée | **BC non modifiable et engageant** ; réceptions possibles |
| Réception physique | Stock | Quantités ≤ reste à livrer (plafond fourni par Achat) | Sérialisation + entrée en stock + notification à Achat |
| Réception licences | Achat | Saisie complète des clés, quantité exacte | **Création atomique et définitive** des licences |
| Clôture | Achat | Reliquat abandonné, motif obligatoire | BC clos, réceptions conservées, engagement résiduel levé |

### 5.2 Livraisons partielles et reliquats

```
BC validé : 10 unités commandées
     │
     ├──▶ Réception Stock n°1 : 6 reçues, sérialisées ──▶ notification ──▶ BC « partiel », reste = 4
     │
     ├──▶ Réception Stock n°2 : 4 reçues, sérialisées ──▶ notification ──▶ BC « livré », reste = 0
     │
     └──▶ (variante) fournisseur défaillant ──▶ clôture du reliquat ──▶ BC « clôturé », les 6 reçues conservées
```

> **EXI-PRO-01** *(v1:EXI-PRO-01, A)* — Le reste à livrer de chaque ligne est calculé par Achat, exposé à Stock, proposé par défaut à la saisie d'une réception et **infranchissable** (contrôle à la saisie Stock **et** au traitement de la notification côté Achat).

### 5.3 Traitement selon la nature de l'article

| Nature (Catalogue) | Réception | Enregistrements générés | Module réalisateur |
|---|---|---|---|
| **Équipement** | Physique — Stock | N fiches `parc_info_equipements` sérialisées, valorisées au prix de ligne (A10) + historique « acquisition » + entrée en stock | Stock |
| **Consommable / pièce** | Physique — Stock | Mouvement d'entrée + niveau de stock | Stock |
| **Licence** | Dématérialisée — Achat | N licences ParcInfo rattachées au logiciel de l'article | Achat |
| **Prestation** *(A5)* | Constat de service fait — Achat | Aucun objet ; la ligne est marquée réalisée | Achat |

### 5.4 Cas particuliers à traiter

| Cas | Traitement attendu | Exigence |
|---|---|---|
| Commande annulée avant validation | Statut annulé, aucune trace d'engagement | EF-BC-11 |
| Commande annulée après validation, avant toute réception | Statut annulé, engagement levé, journalisé | EF-BC-11 |
| Commande partiellement livrée puis abandonnée | **Clôture du reliquat** sans annuler les réceptions intégrées, motif obligatoire | EF-BC-18 |
| Réception excédentaire | Refus à la saisie Stock (plafond) **et** rejet de la notification côté Achat | EF-API-03 |
| Matériel refusé à la réception | Non-comptabilisé ; motif tracé côté Stock, visible depuis la fiche BC | EF-REC-06 |
| Contre-passation d'une réception erronée (si prévue par le SFD Stock) | Décrément notifié à Achat, journalisé, statut BC recalculé | EF-API-05 |
| Erreur constatée après sérialisation | Correction dans ParcInfo ; procédure formalisée au guide utilisateur | LIV-08 |
| Acquisition de l'intérim (27/07 → mise en service) | BC de **régularisation** a posteriori, marqué, rattachable aux équipements saisis manuellement | EF-BC-20 |

---

## 6. Exigences fonctionnelles

### 6.1 Domaine « Bons de commande » — EF-BC

| Réf. | Exigence | Prio. | Origine |
|---|---|:---:|---|
| **EF-BC-01** | Créer un bon de commande à destination d'un fournisseur du Catalogue, comportant une ou plusieurs lignes d'articles recherchés via l'API Catalogue (recherche par code, désignation, nature, catégorie). | M | v1:EF-BC-01 (A) |
| **EF-BC-02** | Attribuer un numéro unique, séquentiel et annualisé (`{préfixe}-{année}-{séquence}`), généré **à la validation, sous verrou transactionnel** (convention commune au module Stock : aucun trou de séquence, les brouillons étant identifiés « Brouillon #id »). | M | v1:EF-BC-02 + ENF-FIA-02 (A) |
| **EF-BC-03** | Pré-remplir le prix de ligne avec le prix indicatif Catalogue et permettre la saisie d'un **prix négocié** distinct. | M | v1:EF-BC-03 (A) |
| **EF-BC-04** | **Figer sur chaque ligne**, au moment de la saisie : prix négocié, taux de TVA (copié de l'article, ajustable par l'acheteur), désignation. Aucune modification ultérieure du Catalogue n'altère un BC. | M | N (A4) |
| **EF-BC-05** | Calculer et afficher en temps réel le montant de chaque ligne et les totaux **HT, TVA et TTC** du bon, à partir des taux figés par ligne. | M | v1:EF-BC-04/05 (A) |
| **EF-BC-06** | Qualifier explicitement HT ou TTC **tout montant affiché ou exporté**, dans tous les écrans, états et PDF. | M | v1:EF-BC-05 + ENF-FIA-04 (A) |
| **EF-BC-07** | Gérer les statuts : brouillon, validé, partiellement livré, livré, annulé, **clôturé**. | M | v1:EF-BC-06 (A) |
| **EF-BC-08** | Permettre la modification et la suppression d'un BC **en brouillon uniquement**, sous permissions déclarées et effectives. | M | v1:EF-BC-07 (A) |
| **EF-BC-09** | Permettre la validation par un utilisateur détenant la **permission dédiée** `achat.bons_commande.valider`, en journalisant identité et horodatage. | M | v1:EF-BC-08 + ENF-SEC-02 (A) |
| **EF-BC-10** | La validation rend le BC non modifiable et ouvre le droit aux réceptions (Stock pour le physique, Achat pour les licences). | M | v1:EF-BC-09 (A) |
| **EF-BC-11** | Permettre l'annulation (permission dédiée) d'un BC non encore réceptionné ; l'interdire dès la première réception intégrée. | M | v1:EF-BC-10/11 (A) |
| **EF-BC-12** | Faire évoluer automatiquement le statut au fil des notifications de réception : `partiel` si reliquat, `livré` si soldé. | M | v1:EF-BC-12 (A2) |
| **EF-BC-13** | Présenter, pour chaque ligne : quantité commandée, quantité livrée, reste à livrer, état de livraison. | M | v1:EF-BC-13 |
| **EF-BC-14** | Rechercher et filtrer les BC par fournisseur, statut, période et recherche libre. | M | v1:EF-BC-14 |
| **EF-BC-15** | Produire un BC imprimable A4 aux couleurs de l'établissement, exportable en PDF. | M | v1:EF-BC-15 |
| **EF-BC-16** | Présenter, depuis la fiche d'un BC : les réceptions Stock rattachées (avec lien), les équipements sérialisés issus de chacune, les licences créées, les pièces jointes. | M | v1:EF-BC-16 (A) |
| **EF-BC-17** | Présenter une **chronologie journalisée réelle** des événements (création, validation, réceptions, clôture, annulation) : auteur, date, motif — issue du journal d'activité Core, non reconstruite à l'affichage. | M | v1:EF-BC-17 + ENF-TRA-03 (A9) |
| **EF-BC-18** | Permettre la **clôture du reliquat** d'un BC partiellement livré et abandonné (permission dédiée, motif obligatoire), sans annuler les réceptions intégrées. | M | v1:EF-BC-18 (S→M) |
| **EF-BC-19** | Alerter sur les BC validés dont le reliquat est ouvert depuis plus d'un délai paramétrable. | S | v1:EF-BC-19 (C→S) |
| **EF-BC-20** | Permettre la saisie d'un BC de **régularisation** : marqué distinctement, daté de la période d'intérim, sans workflow de réception physique, rattachable a posteriori aux équipements saisis manuellement dans ParcInfo ; identifiable et isolable dans tous les états. | C | N (OBJ-08) |

### 6.2 Domaine « Suivi des réceptions » — EF-REC

*Remplace le domaine « Bordereaux de livraison » v1:EF-BL, dont la saisie physique est transférée au module Stock (A1). Les exigences ci-dessous portent la part Achat de la chaîne.*

| Réf. | Exigence | Prio. | Origine |
|---|---|:---:|---|
| **EF-REC-01** | Toute réception physique d'articles commandés est saisie **dans Stock** (bon d'entrée de nature `livraison`) en référence obligatoire à un BC en statut `validé` ou `partiel` ; Achat refuse toute notification portant sur un BC dans un autre statut. Les entrées sans BC (retours, régularisations, unités hors commande) restent possibles côté Stock mais sont sans effet sur Achat. | M | v1:EF-BL-01/02 (A1) |
| **EF-REC-02** | Exposer à Stock, pour un BC donné, les lignes à livrer : article, quantité commandée, quantité livrée, **reste à livrer**, prix figé, fournisseur, n° de BC (contrat §6.4). | M | v1:EF-BL-05 (A) |
| **EF-REC-03** | Garantir, en tant que source de vérité, qu'aucune réception ne dépasse le reste à livrer : plafond appliqué à la saisie Stock **et** revérifié au traitement de la notification (rejet transactionnel en cas de dépassement ou de concurrence). | M | v1:EF-BL-07 / RGC-02 (A2) |
| **EF-REC-04** | Traiter la notification de réception validée de façon **transactionnelle et idempotente** : incrément des `quantite_livree`, recalcul du statut du BC, journalisation. Une même réception notifiée deux fois ne produit qu'un seul incrément. | M | N (A2) |
| **EF-REC-05** | Consolider, depuis Achat, la liste des réceptions d'un BC : n° de bon d'entrée Stock (`ENT-…`), date de livraison, magasin, lignes et quantités, observation typée (« Livraison conforme », « Écart BL — réclamation », « Refus à la livraison »), lien direct vers le bon dans Stock. | M | v1:EF-BL-16/17 (A) |
| **EF-REC-06** | Restituer, depuis la fiche BC, les équipements sérialisés générés par chaque réception (traçabilité descendante), ainsi que les refus et motifs de non-conformité consignés côté Stock. | S | v1:EF-BL-17/18 (A) |
| **EF-REC-07** | Produire un état des reliquats consolidé (tous BC), filtrable par fournisseur et ancienneté. | M | v1:EF-RAP-07 |

### 6.3 Domaine « Réception dématérialisée — licences et prestations » — EF-LIC

| Réf. | Exigence | Prio. | Origine |
|---|---|:---:|---|
| **EF-LIC-01** | Permettre, depuis la fiche d'un BC validé, la réception dématérialisée des lignes de nature `licence` : totale ou partielle, plafonnée au reste à livrer. | M | N (A3) |
| **EF-LIC-02** | Proposer un assistant de saisie présentant un bloc par unité : **clé de licence** (obligatoire), date d'activation (obligatoire), date d'expiration (facultative). | M | v1:EF-INT-02/06 (A) |
| **EF-LIC-03** | Permettre l'enregistrement partiel des saisies et leur reprise sans perte ; indiquer visuellement l'avancement. | M | v1:EF-INT-07/08 |
| **EF-LIC-04** | Vérifier que le nombre d'unités saisies correspond exactement à la quantité réceptionnée ; interdire la finalisation sinon. | M | v1:EF-INT-09/10 |
| **EF-LIC-05** | À la finalisation : créer une licence ParcInfo par unité, **rattachée au logiciel référencé par l'article** (C11), portant fournisseur du BC, coût = prix figé de la ligne, référence du BC. Ne jamais créer de logiciel. | M | v1:EF-INT-06 (A, C11) |
| **EF-LIC-06** | Exécuter la création de façon **atomique** : toute erreur annule l'intégralité (aucune licence, aucun compteur, aucun statut modifié). | M | v1:EF-INT-13 / RGC-05 |
| **EF-LIC-07** | Demander une confirmation explicite mentionnant le caractère définitif, puis rendre compte du nombre de licences créées. | M | v1:EF-INT-12/14 |
| **EF-LIC-08** | N'effectuer **aucune écriture de stock** pour les natures non stockables (C10). | M | N (C10) |
| **EF-LIC-09** | Pour les lignes de nature `prestation` (A5) : permettre le **constat de service fait** (date, commentaire), qui vaut réception de la ligne sans créer d'objet. | S | v1:§5.3 (A5) |

### 6.4 Domaine « Contrat inter-modules » — EF-API

*Contrat détaillé à formaliser dans `API_Inter_Modules.md` (recommandation reprise du SFD Catalogue §2.5). Mêmes engagements que les API Catalogue et Stock : permission dédiée, requêtes portables, listes bornées, aucune exception brute.*

| Réf. | Exigence | Prio. | Origine |
|---|---|:---:|---|
| **EF-API-01** | Exposer `lignes à livrer d'un BC` : contrat EF-REC-02, consommé par l'écran de réception Stock. | M | N |
| **EF-API-02** | Exposer les **données de valorisation** nécessaires à la sérialisation Stock (A10) : prix figé de la ligne, fournisseur, n° et date du BC, article et sa catégorie d'équipements ParcInfo. | M | N (A10) |
| **EF-API-03** | Recevoir la **notification de réception validée** (réception, lignes, quantités) ; la traiter selon EF-REC-03/04 ; répondre par le nouvel état du BC. | M | N (A2) |
| **EF-API-04** | Exposer la recherche des BC ouverts d'un fournisseur ou d'un article (aide à la saisie Stock et aux écrans ParcInfo). | S | N |
| **EF-API-05** | Lorsqu'un **contre-mouvement** Stock (§7.6 du SFD Stock) porte sur un mouvement issu d'une entrée liée à un BC : recevoir la notification, décrémenter les `quantite_livree` correspondantes, recalculer le statut du BC, journaliser l'événement dans la chronologie. | S | N |
| **EF-API-06** | Protéger l'ensemble des endpoints par la permission `achat.api.view`, accordée aux rôles des modules consommateurs (dont Magasinier). | M | N (A8) |

### 6.5 Domaine « Restitution et états » — EF-RAP

| Réf. | Exigence | Prio. | Origine |
|---|---|:---:|---|
| **EF-RAP-01** | Tableau de bord du module : BC par statut, montant engagé sur la période (HT et TTC), reliquats ouverts, dernières commandes et dernières réceptions consolidées. | M | v1:EF-RAP-01/05 (A) |
| **EF-RAP-02** | Évolution mensuelle des dépenses engagées sur **12 mois glissants**, tous les mois présents, en ordre chronologique — y compris les mois à zéro. | M | v1:EF-RAP-02 (leçon AN-07) |
| **EF-RAP-03** | Répartition des achats par fournisseur (montants et volumes), sur période paramétrable. | M | v1:EF-RAP-03/06 |
| **EF-RAP-04** | État global des bons de commande filtrable par statut, fournisseur, période ; totaux qualifiés HT/TTC. | M | v1:EF-RAP-04 |
| **EF-RAP-05** | État des reliquats consolidé (cf. EF-REC-07), filtrable par fournisseur et ancienneté. | M | v1:EF-RAP-07 |
| **EF-RAP-06** | Articles les plus commandés (volumes et montants), par nature et par catégorie Catalogue. | S | v1:EF-RAP-08 (A) |
| **EF-RAP-07** | Tout état est exportable en **PDF** avec période et filtres visibles sur le document. | M | v1:EF-RAP-09 |
| **EF-RAP-08** | Tout état est exportable en **tableur** (CSV et XLSX — fast-excel, comme Stock). | S | v1:EF-RAP-10 (C→S) |
| **EF-RAP-09** | Taux de complétion des livraisons et délai moyen commande → première réception, par fournisseur. | C | v1:EF-RAP-11 |
| **EF-RAP-10** | Dépense par catégorie Catalogue (S) et par **imputation comptable** (C — conditionnée au prérequis PRQ-03). | S/C | v1:EF-RAP-12 (A6) |
| **EF-RAP-11** | Les BC de **régularisation** (EF-BC-20) sont identifiables et isolables dans tous les états (option inclure/exclure, exclus par défaut des statistiques de dépense). | C | N |

### 6.6 Domaine « Pièces justificatives » — EF-DOC

| Réf. | Exigence | Prio. | Origine |
|---|---|:---:|---|
| **EF-DOC-01** | Joindre des documents à un bon de commande (PDF, images ; taille maximale paramétrée), à tout stade de son cycle de vie. | M | v1:EF-DOC-01/02 |
| **EF-DOC-02** | Typer chaque pièce : BC signé, bordereau fournisseur numérisé, facture pro forma, autre. | M | v1:EF-DOC-03 |
| **EF-DOC-03** | Lister les pièces sur la fiche BC avec type, auteur, date de dépôt. | M | v1:EF-DOC-04 |
| **EF-DOC-04** | Soumettre consultation et téléchargement à une **permission dédiée**, contrôlée serveur — y compris l'URL directe du fichier. | M | v1:EF-DOC-06 (leçon AN-17) |
| **EF-DOC-05** | Soumettre la suppression d'une pièce à une permission dédiée ; la journaliser (auteur, date, fichier). | M | v1:EF-DOC-05 (A) |
| **EF-DOC-06** | Stocker les fichiers hors racine web, avec contrôle serveur du type MIME et nommage neutre (aucune exécution possible). | M | N (durcissement) |

### 6.7 Domaine « Administration et paramétrage » — EF-ADM

| Réf. | Exigence | Prio. | Origine |
|---|---|:---:|---|
| **EF-ADM-01** | Offrir un **écran d'administration** du module (leçon v1 : table de paramètres sans écran = paramétrage inopérant). | M | v1:EF-ADM-01/05 (A) |
| **EF-ADM-02** | Paramètres gérés : préfixe de numérotation des BC, délai d'alerte des reliquats (EF-BC-19), taille maximale des pièces jointes, activation des BC de régularisation. | M | v1:EF-ADM-02/03 (A) |
| **EF-ADM-03** | Toute modification de paramètre est applicable **sans redéploiement** et journalisée. | M | v1:EF-ADM-03 |
| **EF-ADM-04** | Toute permission déclarée dans `config/permissions.php` correspond à une fonctionnalité effective et est créée en base par le seeder (`cores:sync-permissions achat`). | M | v1:EF-ADM-04 (leçon AN-01) |

---

## 7. Exigences non fonctionnelles

### 7.1 Sécurité et habilitations — ENF-SEC

| Réf. | Exigence | Prio. |
|---|---|:---:|
| **ENF-SEC-01** | Contrôle des permissions **côté serveur** sur toutes les routes (`HasMiddleware`), y compris `.data`, cascades, API et téléchargements. Le masquage d'interface n'est jamais le seul contrôle. | M |
| **ENF-SEC-02** | Permissions **dédiées** pour chaque action sensible : `valider`, `annuler`, `cloturer`, `receptionner-licences`, `regulariser`, `documents.view/delete` — distinctes de `update` (leçon AN-02 : la validation contrôlée par `edit` rendait la séparation des rôles inopérante). | M |
| **ENF-SEC-03** | Rôles seedés : **Acheteur**, **Validateur Achat**, **Consultation Achat** ; `achat.api.view` accordée aux rôles Stock (Magasinier, Superviseur). Seeders idempotents. | M |
| **ENF-SEC-04** | Aucune restriction par périmètre de données en v1 (tout utilisateur habilité voit tous les BC) : choix assumé, documenté, réévalué si le périmètre organisationnel s'élargit. | C |

### 7.2 Traçabilité et audit — ENF-TRA

| Réf. | Exigence | Prio. |
|---|---|:---:|
| **ENF-TRA-01** | Journal d'activité Core (`spatie/activitylog`, `module='achat'`) sur tous les objets : création, modification, validation, annulation, clôture, réceptions notifiées, dépôts et suppressions de pièces. | M |
| **ENF-TRA-02** | `created_by` sur toutes les tables ; `valide_par`/`valide_le` sur les BC. Les chronologies affichées proviennent du journal réel, jamais reconstruites à l'affichage (leçon AN-09). | M |
| **ENF-TRA-03** | Un BC validé est **immuable** ; brouillons supprimables réellement (cascade lignes), documents validés jamais supprimables — l'absence de permission de suppression sur les validés documente l'immutabilité (convention Stock §5). | M |
| **ENF-TRA-04** | **Traçabilité transversale garantie** : depuis toute fiche équipement ParcInfo issue d'une commande, remonter mouvement → bon d'entrée → **ligne de BC** (prix figé, fournisseur, dates) ; depuis tout BC, descendre vers les équipements sérialisés et licences créées. | M |
| **ENF-TRA-05** | Les notifications inter-modules (réceptions, contre-mouvements) sont journalisées des deux côtés avec référence croisée. | M |

### 7.3 Fiabilité et intégrité — ENF-FIA

| Réf. | Exigence | Prio. |
|---|---|:---:|
| **ENF-FIA-01** | Montants calculés **une seule fois, côté serveur**, à partir des prix et taux figés par ligne ; règles d'arrondi définies et testées ; aucun recalcul divergent en interface (leçon AN-03/AN-04). | M |
| **ENF-FIA-02** | Numéros attribués à la validation, sous verrou transactionnel, par séquence persistée — jamais par comptage de lignes (leçon v1 ; convention Stock §6.1). | M |
| **ENF-FIA-03** | Validations et traitements de notifications **idempotents** (jeton / clé d'idempotence) : ni double validation, ni double incrément. | M |
| **ENF-FIA-04** | Un message de succès n'est affiché que si l'opération a réellement abouti ; toute erreur serveur produit une erreur explicite mappée champ par champ (422), jamais un succès (leçon AN-11). | M |
| **ENF-FIA-05** | Intégrité référentielle : FK `restrict` vers Catalogue (article, fournisseur d'un BC non supprimables s'ils sont référencés — garde C8 côté Catalogue) ; libellés dénormalisés figés à la validation pour l'impression. | M |

### 7.4 Performance — ENF-PER

| Réf. | Exigence | Prio. |
|---|---|:---:|
| **ENF-PER-01** | Listes servies en pagination serveur (Bootstrap Table), recherches indexées ; affichage < 2 s pour la volumétrie de référence (§4.3) sur 5 ans. | M |
| **ENF-PER-02** | Endpoints API bornés (limite de résultats), réponses < 500 ms sur les contrats inter-modules. | S |

### 7.5 Ergonomie — ENF-ERG

| Réf. | Exigence | Prio. |
|---|---|:---:|
| **ENF-ERG-01** | Patterns UI du projet : Bootstrap Table serveur, modales duales, SweetAlert2 (récapitulatifs chiffrés avec avertissement d'immutabilité avant validation), Select2, badges de nature du Catalogue, charte `DESIGN.md`. | M |
| **ENF-ERG-02** | Montants affichés en **FCFA**, formatés selon les conventions du projet, toujours qualifiés HT/TTC. | M |
| **ENF-ERG-03** | Anti-double-soumission sur toutes les validations ; 422 mappées jusque dans les lignes dynamiques avec bascule vers l'onglet fautif (convention Stock §4). | M |
| **ENF-ERG-04** | Écrans de consultation utilisables sur mobile ; la saisie des clés de licences est utilisable au clavier seul. | S |

### 7.6 Qualité technique — ENF-TEC

| Réf. | Exigence | Prio. |
|---|---|:---:|
| **ENF-TEC-01** | Socle du projet : Laravel 12 + nwidart, module `Achat` autonome, `module.json` avec `requires: ["Core", "Catalogue", "Stock", "ParcInfo"]`, JS dans `public/js/modules/achat/`, dompdf, fast-excel. Le préambule des 15 conventions de `PROMPTS_Dev_Catalogue.md` s'applique intégralement. | M |
| **ENF-TEC-02** | **Portabilité SGBD** : aucune fonction propriétaire (leçon AN-05 : `TO_CHAR` rendait deux écrans intestables) ; `LIKE` portable ; suites de tests exécutées sur SQLite **et** PostgreSQL. | M |
| **ENF-TEC-03** | Couverture de tests : chemins nominaux **et** cas d'erreur — matrice de permissions (403 sur toutes les routes), plafonds de réception, idempotence, atomicité des licences, calculs de montants et d'arrondis, snapshots des API (leçon v1 : 9 tests, chemin nominal uniquement). | M |
| **ENF-TEC-04** | Aucun code mort ni architecture fantôme : pas d'événements déclarés non dispatchés, pas de listeners vides (leçon AN-08) ; le choix intégration synchrone transactionnelle est explicite et assumé. | M |
| **ENF-TEC-05** | Une source de paramétrage unique : la table de paramètres du module avec son écran (EF-ADM) ; `config/` réservé aux constantes techniques (leçon v1 : double source config.php / table sans précédence définie). | M |

---

## 8. Règles de gestion contractuelles — RGC

Ces règles sont **opposables** : leur violation constitue une non-conformité bloquante en recette.

| Réf. | Règle |
|---|---|
| **RGC-01** | Un BC validé est définitivement non modifiable. Toute correction passe par l'annulation (si rien n'est reçu) ou la clôture du reliquat — jamais par la modification. |
| **RGC-02** | Aucune réception ne peut excéder le reste à livrer d'une ligne. Le plafond est contrôlé à la saisie Stock **et** revérifié transactionnellement par Achat au traitement de la notification. |
| **RGC-03** | Une réception validée est irréversible. La correction d'une erreur passe par un contre-mouvement Stock, notifié à Achat et journalisé des deux côtés (EF-API-05). |
| **RGC-04** | Tout équipement issu d'une commande naît par sérialisation Stock (D10) et reste rattachable à sa ligne de BC (fiche → mouvement → entrée → BC). |
| **RGC-05** | Toute intégration est atomique : création des licences (Achat) et validation d'entrée (Stock) réussissent entièrement ou échouent entièrement, sans état intermédiaire. |
| **RGC-06** | La valeur d'acquisition d'un équipement ou d'une licence est le **prix figé de la ligne de commande**, jamais le prix indicatif du Catalogue. |
| **RGC-07** | Prix, taux de TVA et désignation sont figés sur la ligne à la saisie du BC ; aucune modification ultérieure du Catalogue n'altère un BC existant. |
| **RGC-08** | Le module Achat ne tient **aucun niveau de stock** ; toute quantité en magasin relève exclusivement du module Stock. |
| **RGC-09** | Toute réception physique transite par un bon d'entrée Stock ; toute réception de licence ou constat de service fait relève d'Achat. Une nature non stockable ne génère jamais d'écriture de stock (C10). |
| **RGC-10** | Une licence créée par Achat est rattachée au logiciel référencé par l'article (C11) ; Achat ne crée jamais de logiciel. |
| **RGC-11** | La séparation commande / validation est garantie par des permissions dédiées : aucun utilisateur ne peut valider un BC qu'il a le seul droit de saisir. |
| **RGC-12** | Le journal d'activité est la source des chronologies ; il n'est ni altérable ni reconstruit à l'affichage. |

---

## 9. Contraintes

### 9.1 Contraintes techniques

| Réf. | Contrainte |
|---|---|
| CTR-01 | Stack imposée : Laravel 12, nwidart/laravel-modules, Spatie Permission + Activitylog, PostgreSQL en production, dompdf, fast-excel, Ziggy, Bootstrap 5. |
| CTR-02 | Respect de `PATTERNS.md`, `DESIGN.md` et des 15 conventions de développement du projet. |
| CTR-03 | Les API inter-modules respectent les engagements communs : permission dédiée, requêtes portables, listes bornées, aucune exception brute (SFD Catalogue §2.5, SFD Stock §2.6). |
| CTR-04 | Aucune migration ne modifie les tables des autres modules ; les évolutions Catalogue (PRQ-02/03) et Stock (PRQ-05) sont portées par leurs modules respectifs. |

### 9.2 Contraintes réglementaires et de gestion

| Réf. | Contrainte |
|---|---|
| CTR-05 | Montants en FCFA ; taux de TVA par défaut 18 % (porté par le Catalogue, C9), ajustable par ligne. |
| CTR-06 | Pièces justificatives conservées pendant toute la durée légale de conservation des pièces comptables ; jamais supprimées avec le BC. |
| CTR-07 | Le contexte d'audit de l'établissement est encore papier : les PDF (BC, états) sont des documents **imprimables et signables** de qualité professionnelle. |
| CTR-08 | Les procédures de marchés publics (seuils, appels d'offres) restent hors application ; le module trace l'exécution, pas la passation. |

### 9.3 Contrainte de calendrier

Le développement d'Achat ne démarre qu'après : Catalogue installé, migré et recetté (lot C) ; Stock installé avec inventaire d'ouverture réalisé (plan de mise en service SFD Stock §9.2). Le lot A2 est co-planifié avec l'équipe Stock (PRQ-05).

---

## 10. Prérequis inter-modules — PRQ

| Réf. | Prérequis | Porté par | Bloque |
|---|---|---|---|
| **PRQ-01** | Module Catalogue installé, données ParcInfo migrées (`catalogue:migrate-parcinfo`), recetté. | Catalogue | Lot A1 |
| **PRQ-02** | Amendement Catalogue : **nature `prestation`** (5ᵉ nature, non stockable — décision A5). | Catalogue | EF-LIC-09 |
| **PRQ-03** | Amendement Catalogue : champ **compte comptable** sur l'article (décision A6). | Catalogue | EF-RAP-10 (imputation) |
| **PRQ-04** | Module Stock v3.0 installé, rôles attribués, inventaire d'ouverture réalisé. | Stock | Lot A2 |
| **PRQ-05** | Évolution conjointe Stock « **raccordement commandes** » (ancre D11) : lien bon d'entrée ↔ BC, pré-remplissage des lignes depuis le reste à livrer, plafond bloquant, fournisseur imposé, `cout_unitaire` pré-rempli du prix figé, notification de validation (et de contre-mouvement) vers Achat. Amendement du SFD Stock + `TESTS_Stock.md`. | Stock + Achat | Lot A2 |
| **PRQ-06** | `API_Inter_Modules.md` rédigé et validé : contrats Catalogue §2.5, Stock §2.6 et Achat §6.4 consolidés (recommandation du SFD Catalogue reprise). | Transverse | Lots A2/A3 |
| **PRQ-07** | Backlog Catalogue acté pour les écarts hérités non repris : duplication d'article (v1:EF-CAT-09), photo et fiche technique (v1:EF-CAT-11). | Catalogue | — (confort) |

---

## 11. Livrables attendus

| Réf. | Livrable |
|---|---|
| LIV-01 | Module `Achat` complet : code, migrations, seeders (permissions, rôles, paramètres), conforme aux conventions du projet. |
| LIV-02 | Contrats d'API implémentés et documentés dans `API_Inter_Modules.md`. |
| LIV-03 | Suites de tests SQLite + PostgreSQL couvrant ENF-TEC-03, intégrées à `TESTS_Stock.md`/plan de tests global pour la chaîne inter-modules. |
| LIV-04 | Gabarit PDF du bon de commande aux couleurs de l'établissement, validé par la MOA sur maquette avant développement. |
| LIV-05 | Guide utilisateur (acheteur, validateur) incluant la procédure de correction post-intégration et la procédure de régularisation de l'intérim. |
| LIV-06 | Cahier de recette exécuté (chap. 12) avec procès-verbal. |
| LIV-07 | Matrice de traçabilité exigences ↔ code ↔ tests. |
| LIV-08 | `README.md` du module et `ANALYSE_Achat.md` de fin de chantier (convention du projet). |

---

## 12. Recette et critères d'acceptation

### 12.1 Critères globaux

1. **100 % des exigences M** satisfaites et démontrées ; **90 % des S** ; les C sont bonus.
2. **Aucune violation des RGC** (non-conformité bloquante).
3. Recette **conjointe Achat + Stock** pour la chaîne de réception (scénarios marqués ⇄) : exécutée sur un environnement où Catalogue, Stock et ParcInfo sont installés avec données représentatives.
4. Matrice de permissions vérifiée exhaustivement (403 attendus compris).

### 12.2 Scénarios de recette

| Réf. | Scénario | Exigences principales | Type |
|---|---|---|---|
| REC-01 | Créer un BC multi-lignes depuis le Catalogue, vérifier prix/TVA figés et totaux HT/TVA/TTC. | EF-BC-01→06, ENF-FIA-01 | Achat |
| REC-02 | Modifier le prix indicatif au Catalogue après création : le BC est inchangé. | EF-BC-04, RGC-07 | Achat |
| REC-03 | Tenter de valider un BC avec un profil Acheteur seul : refus (403) ; valider avec Validateur : numéro attribué, BC immuable. | EF-BC-09/10, RGC-11, ENF-SEC-02 | Achat |
| REC-04 | Tenter de modifier/supprimer un BC validé : 409/refus explicite. | EF-BC-08, RGC-01 | Achat |
| REC-05 | Annuler un BC validé sans réception ; tenter après une réception : refus. | EF-BC-11 | ⇄ |
| REC-06 | Réception partielle : bon d'entrée Stock lié au BC, lignes pré-remplies du reste à livrer, validation → statut `partiel`, reliquat exact. | EF-REC-01→05, EF-API-01/03 | ⇄ |
| REC-07 | Tenter une réception excédentaire : plafond bloquant à la saisie ; notification forgée au-delà du reste : rejet transactionnel. | EF-REC-03, RGC-02 | ⇄ |
| REC-08 | Solder le BC par une seconde réception : statut `livré` ; rejouer la même notification : aucun double incrément. | EF-BC-12, EF-REC-04, ENF-FIA-03 | ⇄ |
| REC-09 | Réception d'équipements : fiches sérialisées valorisées au prix figé de la ligne ; remontée fiche → entrée → BC vérifiée. | A10, RGC-04/06, ENF-TRA-04 | ⇄ |
| REC-10 | Clôturer le reliquat d'un BC partiellement livré (motif obligatoire) : réceptions conservées, statut `clôturé`, journalisé. | EF-BC-18, RGC-01 | Achat |
| REC-11 | Contre-mouvement Stock sur une entrée liée : décrément notifié, statut recalculé, chronologie complète. | EF-API-05, RGC-03 | ⇄ |
| REC-12 | Réception dématérialisée de licences : assistant, sauvegarde partielle, quantité stricte, création atomique rattachée au logiciel de l'article, aucun mouvement de stock. | EF-LIC-01→08, RGC-05/09/10 | Achat |
| REC-13 | Provoquer une erreur en cours de création des licences : rollback complet, aucun succès affiché. | EF-LIC-06, ENF-FIA-04 | Achat |
| REC-14 | Constat de service fait sur une ligne prestation (si PRQ-02 livré). | EF-LIC-09 | Achat |
| REC-15 | Chronologie d'un BC : tous les événements (création → validation → réceptions → clôture) issus du journal, auteurs et horodatages exacts. | EF-BC-17, ENF-TRA-01/02, RGC-12 | ⇄ |
| REC-16 | États : 12 mois glissants ordonnés (mois vides inclus), répartitions, reliquats ; exports PDF (filtres visibles) et XLSX. | EF-RAP-02→08 | Achat |
| REC-17 | Pièces justificatives : dépôt typé, consultation refusée sans permission (y compris URL directe), suppression journalisée. | EF-DOC-01→06 | Achat |
| REC-18 | Administration : modifier le préfixe et le délai d'alerte sans redéploiement ; effet immédiat, journalisé. | EF-ADM-01→03 | Achat |
| REC-19 | Matrice de permissions : chaque route testée avec et sans permission (403 systématiques). | ENF-SEC-01, EF-ADM-04 | Achat |
| REC-20 | BC de régularisation : saisie a posteriori, marquage, exclusion par défaut des états, rattachement à des équipements de l'intérim. | EF-BC-20, EF-RAP-11 | Achat |
| REC-21 | Concurrence : deux validations simultanées de BC → numéros distincts sans trou ni collision ; double clic de validation → un seul effet. | ENF-FIA-02/03 | Achat |
| REC-22 | Suites de tests exécutées sur SQLite et PostgreSQL, vertes. | ENF-TEC-02/03 | Achat |

### 12.3 Scénarios ajoutés par le lot BR (bordereaux de réception)

| Réf. | Scénario | Ce qu'il faut voir | Type |
|---|---|---|---|
| REC-23 | **Le BL au comptoir, depuis un mobile.** Le magasinier photographie le bordereau du livreur et le joint au bon d'entrée en le typant « Bordereau du fournisseur ». Puis l'administrateur active `bl_obligatoire_si_commande` et un collègue tente de valider une entrée liée à une commande sans pièce jointe. | La photo est acceptée (formats image), la pièce apparaît typée. La validation sans BL est refusée en **422** avec un message qui nomme la pièce attendue. Le paramètre remis à `false`, la même validation passe. | Stock |
| REC-24 | **Le bordereau signé par le livreur, avec écarts.** Réception où le BL annonce 10 cartons et où 8 sont comptés : le magasinier déclare l'écart (motif « Manquant »), valide, puis imprime le bordereau de réception. | Le PDF porte le bloc rouge « Écarts constatés » (annoncé 10, compté 8, écart −2, motif), les deux cadres de signature, **aucun montant**, et la mention « Seules les quantités COMPTÉES entrent en stock ». Côté Achat : la ligne de commande est à **8 livrées / 12 restantes** — le 10 annoncé n'entre dans aucun compteur. | ⇄ |
| REC-25 | **La consultation croisée.** Se connecter avec un compte **Achat pur, sans aucun rôle Stock**. Ouvrir la fiche de sa commande, onglet Réceptions : imprimer le bordereau, télécharger le BL, déplier l'écart. Puis tenter la même chose sur une pièce appartenant à une AUTRE commande, et enfin sur les routes du module Stock. | Les pièces de SES commandes s'ouvrent (c'est le but). La pièce d'une autre commande répond **404**, les routes Stock **403**. Aucune URL du magasin n'apparaît dans le code source de la page. Inversement, un magasinier sans droits Achat reçoit 403 sur les routes proxy. | ⇄ |
| REC-26 | **La pièce retirée.** Supprimer une pièce d'un bon d'entrée déjà validé, motif à l'appui. | Le fichier disparaît du disque, la **ligne reste** (barrée, motivée, signée) et le téléchargement répond **410**, y compris depuis Achat. Une suppression sans motif est refusée. | Stock |
| REC-27 | **Le 9e signal.** Sur une période comportant plusieurs livraisons d'un même fournisseur dont une seule en écart, ouvrir la carte Signaux. | Le fournisseur figure avec son **taux** (1 sur 3 → 33,3 %) et son dénominateur. Un fournisseur sans aucun écart n'apparaît pas : le signal signale, il n'accuse pas. | Achat |
| REC-28 | **Le module Stock coupé.** Rendre le module Stock indisponible et ouvrir une fiche de bon de commande engagé. | La fiche s'affiche entièrement ; l'onglet Réceptions perd ses pièces et affiche son indisponibilité ; les compteurs et reliquats d'Achat restent exacts. Aucune page en erreur. | ⇄ |

---

## 13. Lotissement et trajectoire

Trajectoire de **construction** (et non de remédiation — la v1.0 du CDC lotissait des correctifs sur un code depuis supprimé). Charges indicatives à affiner.

| Lot | Contenu | Charge | Prérequis |
|---|---|---|---|
| **A0 — Cadrage et contrats** | Validation des décisions A1–A10 par la MOA ; rédaction et validation d'`API_Inter_Modules.md` (PRQ-06) ; spécification de l'évolution Stock « raccordement commandes » (PRQ-05) ; amendements Catalogue (PRQ-02/03) ; maquette du PDF de BC (LIV-04). | 3 j | SFD Catalogue et Stock validés |
| **A1 — Socle bons de commande** | Module, modèles, migrations, permissions et rôles ; CRUD BC + lignes (recherche Catalogue, prix/TVA figés, calculs serveur) ; validation/annulation/statuts ; numérotation à la validation ; PDF ; chronologie ; journal. | 10 j | PRQ-01 |
| **A2 — Chaîne de réception** *(conjoint Stock)* | Côté Stock : lien entrée ↔ BC, pré-remplissage, plafonds, notification (PRQ-05). Côté Achat : EF-API-01→06, traitement idempotent, statuts automatiques, consolidation fiche BC, état des reliquats, clôture. | 8 j (dont ≈ 3 côté Stock) | A1, PRQ-04/05/06 |
| **A3 — Licences et prestations** | Assistant de réception dématérialisée, création atomique des licences ParcInfo, constat de service fait (si PRQ-02). | 5 j | A1 |
| **A4 — Restitutions, documents, administration** | Tableau de bord, états et exports, pièces justificatives sécurisées, écran d'administration. | 7 j | A1 (A2 pour les états de réception) |
| **A5 — Régularisation et confort** | BC de régularisation de l'intérim, alertes reliquats, finitions UX. | 4 j | A2 |
| **Recette conjointe** | Exécution du cahier (chap. 12), PV, corrections. | 2 j | Tous |

**Total indicatif : ≈ 39 jours.** Jalon de mise en service : à l'issue du lot A2, la chaîne commande → réception → sérialisation est opérationnelle ; les lots A3–A5 peuvent suivre en production.

---

## 14. Annexes

### 14.1 Matrice de re-répartition des exigences v1.0

| Domaine v1.0 | Devenu | Détail |
|---|---|---|
| EF-CAT-01→13 (catalogue) | **Module Catalogue** | Couvert par le SFD Catalogue (C1–C11). Écarts à reporter : nature prestation (→ PRQ-02), compte comptable (→ PRQ-03), duplication et photo/fiche technique (→ PRQ-07). |
| EF-BC-01→19 (bons de commande) | **Achat — EF-BC v2** | Repris et durcis (numéro à la validation, prix/TVA figés A4, clôture M) ; + EF-BC-20 (régularisation, N). |
| EF-BL-01→18 (bordereaux de livraison) | **Stock (bons d'entrée) + Achat EF-REC** | La saisie physique et le triptyque relèvent du SFD Stock (§3.4, §7.1) ; Achat porte le lien au BC, les plafonds, la consolidation et les reliquats. |
| EF-INT-01→19 (assistant d'intégration) | **Scindé** | Équipements : wizard de référencement + sérialisation Stock (D10/D13). Licences : Achat EF-LIC (assistant, atomicité, C11). Champs dynamiques par catégorie : fiches complétées dans ParcInfo (D10). |
| EF-STK-01→07 (stock) | **Module Stock** | Niveaux, alertes en cascade, mouvements, inventaires (D9, §7.4). EF-STK-05 (référentiel unique) : résolu par conception — RGC-08. |
| EF-RAP-01→12 | **Achat — EF-RAP v2** | Recentrés sur la dépense et l'engagement ; les rapports de stock (valorisation, consommation par bénéficiaire) relèvent de Stock §3.9. |
| EF-DOC-01→07 | **Achat — EF-DOC v2** | Repris, habilitations M (leçon AN-17), durcissement stockage. |
| EF-ADM-01→05 | **Achat — EF-ADM v2** | Écran d'administration M (leçon v1). |
| ENF v1.0 | **ENF v2 + SFD Stock** | Les exigences transverses (verrous, idempotence, portabilité, permissions serveur) sont désormais des conventions communes aux trois modules. |

### 14.2 Anomalies héritées (SFD_Achat v1.0 chap. 12) — parades dans ce CDC

| Anomalie v1 | Parade v2 |
|---|---|
| AN-01 permission `destroy` jamais créée | EF-ADM-04, REC-19 |
| AN-02 validation contrôlée par `edit` | ENF-SEC-02, RGC-11, REC-03 |
| AN-03/04 TVA triple source, montants recalculés | A4, EF-BC-04, ENF-FIA-01, REC-01/02 |
| AN-05 `TO_CHAR` non portable | ENF-TEC-02, REC-22 |
| AN-06 code article non généré | Catalogue (C9) |
| AN-07 graphique « 6 premiers mois » | EF-RAP-02, REC-16 |
| AN-08 événements fantômes | ENF-TEC-04 |
| AN-09 chronologie reconstruite | EF-BC-17, ENF-TRA-02, RGC-12 |
| AN-10 wizard irréversible | Triptyque Stock (retour au brouillon D16) ; EF-LIC-03 |
| AN-11 succès affiché sur échec | ENF-FIA-04, REC-13 |
| AN-12 triple comptage du stock | RGC-08, architecture cible §1.2 |
| AN-13/14 paramétrage sans écran | EF-ADM-01→03, ENF-TEC-05 |
| AN-17 documents sans contrôle | EF-DOC-04/06, REC-17 |
| AN-18 reliquat sans clôture | EF-BC-18 (M), REC-10 |
| Numérotation par comptage (ENF-FIA-02 v1) | ENF-FIA-02, REC-21 |

### 14.3 Glossaire

| Terme | Définition |
|---|---|
| **BC** | Bon de commande — engagement fournisseur porté par Achat. |
| **Bon d'entrée** | Document Stock matérialisant une réception physique (triptyque brouillon → référencement → validé). |
| **Reliquat / reste à livrer** | Quantité commandée non encore réceptionnée d'une ligne ; source de vérité : Achat. |
| **Sérialisation** | Création des fiches individuelles `parc_info_equipements` à la validation d'un bon d'entrée (D10). |
| **Réception dématérialisée** | Réception sans magasin (licences, prestations), portée par Achat. |
| **Prix figé** | Prix (et taux de TVA) copié sur la ligne à la saisie du BC, insensible aux évolutions du Catalogue. |
| **Notification** | Message inter-modules (Stock → Achat) émis à la validation d'un bon d'entrée lié à un BC. |
| **Contre-mouvement** | Mouvement Stock inverse et motivé corrigeant un mouvement validé. |
| **BC de régularisation** | BC saisi a posteriori pour documenter une acquisition de la période d'intérim, hors workflow de réception. |
| **Service fait** | Constat de réalisation d'une prestation, valant réception de la ligne. |

### 14.4 Décisions soumises à signature de la MOA

Au même titre que D3 et le cumul de rôles du SFD Stock : **A1** (réception physique = Stock), **A2** (reliquat tenu par Achat), **A5** (réintroduction des prestations), **A6** (compte comptable au Catalogue), **A8** (permissions dédiées) — et la reconduction de **D3** (pas de circuit d'approbation en v1, la traçabilité est le contrôle), qui s'applique aussi aux bons de commande (pas de double visa au-delà d'un seuil de montant en v1 ; backlog v2 avec le circuit de demande).

---

*Fin du document v2.0 — 03/08/2026. Ce CDC remplace la v1.0 comme référentiel d'exigences du module Achat ; la v1.0 et le SFD v1.0 restent les traces de l'implémentation de référence. Prochaines étapes : validation MOA des décisions A1–A10 (§14.4), rédaction d'`API_Inter_Modules.md` (PRQ-06), amendements Catalogue et Stock (PRQ-02/03/05), puis SFD Achat.*
