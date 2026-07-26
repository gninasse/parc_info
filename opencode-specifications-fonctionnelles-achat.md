# Spécifications Fonctionnelles Détaillées — Module Achat

> Document généré par analyse statique du code source du module `Achat`
> (Laravel 10+ / package `nwidart/laravel-modules`) — Projet **ParcInfo**.
> Référence de code : `Modules/Achat/`

---

## 1. Présentation et périmètre

Le module **Achat** gère le cycle de vie des approvisionnements de l'organisation, de la création d'un catalogue d'articles jusqu'à l'intégration physique des biens dans le parc informatique (ParcInfo), en passant par les bons de commande (BC) et les bordereaux de livraison (BL).

**Objectif principal** : piloter les achats et réceptionner automatiquement les biens dans le module ParcInfo (équipements, licences, consommables) à la validation d'un bordereau de livraison.

**Périmètre fonctionnel** :
- Gestion d'un catalogue d'articles (équipements, consommables, licences, prestations).
- Émission et suivi des bons de commande fournisseur.
- Réception des marchandises via bordereaux de livraison avec assistant d'intégration.
- Gestion des stocks de consommables.
- Suivi documentaire des BC et BL.
- Tableaux de bord et rapports d'achat (PDF/impression).

**Hors périmètre** (assuré par d'autres modules) :
- La gestion des fournisseurs, marques, catégories, logiciels, équipements, licences et consommables elles-mêmes (module `ParcInfo`).
- La facturation et la comptabilité (uniquement des champs `compte_comptable` et `montant_total` sont présents).

---

## 2. Architecture technique

| Élément | Détail |
| --- | --- |
| Framework | Laravel (modèle MVC) |
| Module | `nwidart/laravel-modules` — namespace `Modules\Achat` |
| Authentification | Middleware `auth` + `verified` sur les routes web ; `auth:sanctum` sur l'API |
| Autorisation | Spatie Permission (permissions + rôles) |
| Base de données | PostgreSQL (usage de `TO_CHAR`, contraintes `CHECK` conditionnelles) |
| Vues | Blade, Bootstrap Table (JSON/AJAX), composants Blade dédiés |
| PDF | `Barryvdh\DomPDF\Facade\Pdf` |
| Fichiers | Stockage `public` (`storage/app/public`) |

### 2.1 Arborescence
- `app/Models/` — 8 modèles (Article, BonCommande, LigneCommande, BordereauLivraison, LigneLivraison, Document, Parametre, WizardData)
- `app/Services/` — logique métier (BonCommandeService, BordereauLivraisonService, WizardValidationService, EquipementIntegrationService, ArticleService, CodeInventaireGeneratorService)
- `app/Http/Controllers/` — 7 contrôleurs
- `app/Http/Requests/` — validation des formulaires
- `app/Events/` + `app/Listeners/` — système événementiel
- `app/Providers/` — enregistrement routes, événements, services, vues
- `database/migrations/` — 8 migrations
- `database/seeders/` — seeders permissions, articles, paramètres
- `resources/views/` — interfaces (index, create, edit, show, wizard, PDF)
- `config/` — `config.php` (paramétrage), `permissions.php` (droits)

### 2.2 Points d'entrée (routes `web.php`)
Toutes les routes sont protégées par `auth` + `verified`.

| Verbe | URI | Contrôleur | Action | Permission |
| --- | --- | --- | --- | --- |
| GET | `/` | AchatController | Tableau de bord | `achat.dashboard.view` |
| GET/POST | `articles` (+ `toggle-actif`, `dupliquer`) | ArticleController | CRUD catalogue | `achat.articles.*` |
| GET/POST | `bons-commande` (+ `valider`, `annuler`, `imprimer`) | BonCommandeController | CRUD BC | `achat.bons_commande.*` |
| GET/POST | `bordereaux` (+ `wizard`, `wizard/.../sauvegarder`, `wizard/valider`, `imprimer`) | BordereauLivraisonController | CRUD BL + assistant | `achat.bordereaux.*` |
| GET | `stocks` | StockController | Vue stocks consommables | `achat.stocks.view` |
| GET | `statistiques` (+ `data`, `pdf`) | StatistiquesController | Rapports | `achat.dashboard.view` |
| POST/GET/DEL | `documents` | DocumentController | Pièces jointes | (non vérifiée explicitement) |

> **Observation** : l'API (`routes/api.php`) déclare un `apiResource('achats', AchatController)` mais `AchatController` n'implémente que `index()` de façon fonctionnelle ; `store/show/edit/update/destroy` sont vides. L'API n'est donc pas réellement exploitée.

---

## 3. Modèle de données

### 3.1 Schéma relationnel (tables `achat_*`)

```
parc_info_fournisseurs ──< achat_bons_commande >───< achat_lignes_commande >─── achat_articles
                                   │                         │                         │
                                   │                         │                         ├─< parc_info_marques
                                   │                         │                         ├─< parc_info_categories_equipements
                                   │                         │                         └─< parc_info_fournisseurs (préféré)
                                   │                         │
                                   └──< achat_bordereaux_livraison >───< achat_lignes_livraison >─── achat_articles
                                                │
                                                └──< achat_wizard_data >─── achat_articles

achat_documents (polymorphique : bon_commande / bordereau)
achat_parametres (cle/valeur)
achat_articles ──< (self) référencé par lignes_commande et lignes_livraison
```

### 3.2 Détail des entités

#### Article (`achat_articles`)
Catalogue central. Champs clés : `code_article` (unique), `designation`, `type_article` (enum : `equipement`, `consommable`, `licence`, `prestation`), `reference_constructeur`, `marque_id`, `categorie_equipement_id` (nullable), `fournisseur_prefere_id`, `prix_indicatif`, `unite_mesure`, `taux_tva`, `compte_comptable`, `seuil_alerte`, `stock_actuel`, `duree_validite_mois`, `url_fiche_technique`, `image`, `actif`.
- Index : `[type_article, categorie_equipement_id]`, `actif`.
- Contrainte unique composite : `(marque_id, reference_constructeur)` → `unique_ref_marque`.
- SoftDeletes + champs d'audit (`created_by`, `updated_by` via trait `HasAuditFields`).

#### Bon de Commande (`achat_bons_commande`)
`numero_commande` (unique, généré `BC-YYYY-####`), `fournisseur_id`, `date_commande`, `statut` (enum : `brouillon`, `valide`, `partiel`, `livre`, `annule`), `montant_total`, `commentaire`, `valide_par`, `date_validation`.
- Relations : `fournisseur`, `validateur` (User), `lignesCommande`, `bordereauxLivraison`, `documents` (morph).
- Méthodes métier : `recalculerMontantTotal()`, `estModifiable()`, `estValidable()`, `estAnnulable()`.

#### Ligne de Commande (`achat_lignes_commande`)
`bon_de_commande_id`, `article_id`, `quantite`, `prix_unitaire`, `quantite_livree` (cumul).
- Accessors : `reste_a_livrer`, `montant_ligne`.
- Contrainte `CHECK (quantite > 0)` (hors SQLite).

#### Bordereau de Livraison (`achat_bordereaux_livraison`)
`numero_livraison` (unique, `BL-YYYY-####`), `bon_de_commande_id`, `date_livraison`, `ref_bordereau_physique` (unique), `statut` (enum : `brouillon`, `wizard`, `valide`), `commentaire`.
- Relations : `bonCommande`, `lignesLivraison`, `wizardData`, `documents`.
- Méthodes : `estModifiable()`, `peutLancerWizard()`.

#### Ligne de Livraison (`achat_lignes_livraison`)
`bordereau_livraison_id`, `article_id`, `quantite_livree`.
- Contrainte `CHECK (quantite_livree > 0)` (hors SQLite).

#### WizardData (`achat_wizard_data`)
Stockage temporaire des saisies d'intégration : `bordereau_livraison_id`, `article_id`, `unites_data` (JSON), `attributs_communs` (JSON), `completed` (booléen).
- `unites_data` contient pour chaque unité : `numero_serie`, `code_inventaire`, `cle_licence`, `date_activation`, `date_expiration`, `champs_valeurs` (caractéristiques techniques).

#### Document (`achat_documents`)
Polymorphe (`documentable`). `nom`, `fichier_path`, `taille`, `type_mime`, `notes`.

#### Parametre (`achat_parametres`)
Clé/valeur générique. Utilisé pour : `prefix_bon_commande`, `prefix_bordereau_livraison`, `pattern_code_inventaire`, `compteur_inventaire_annee`.

### 3.3 Paramétrage (`config/config.php`)
- `code_inventaire_pattern` : `INV-{YYYY}-{SEQUENCE:4}`
- `prefix_bon_commande` : `BC` ; `prefix_bordereau_livraison` : `BL`
- `types_articles`, `statuts_bc`, `statuts_bl` (libellés + couleurs)
- `seuils_stock` : `rupture=0`, `critique=0.2`, `alerte=0.5`, `faible=1.0` (seuils exprimés en ratio du `seuil_alerte`)

---

## 4. Rôles et permissions

Source : `config/permissions.php` + `PermissionsAchatSeeder`.

### 4.1 Permissions
| Permission | Libellé |
| --- | --- |
| `achat.dashboard.view` | Voir le tableau de bord |
| `achat.articles.view/create/edit/delete` | Gestion du catalogue |
| `achat.bons_commande.view/create/edit/valider/annuler` | Gestion des BC |
| `achat.bordereaux.view/create/edit/valider` | Gestion des BL |
| `achat.stocks.view/entree/sortie/inventaire` | Gestion des stocks |
| `achat.licences.view/manage` | Gestion des licences |
| `achat.rapports.view/export` | Rapports |

### 4.2 Rôles suggérés
- **Acheteur** : dashboard, articles (tous), BC (view/create/edit), bordereaux (view), stocks (view), licences (view), rapports (view).
- **Validateur Achat** : dashboard, articles (view), BC (tous), bordereaux (view), rapports (view/export).
- **Magasinier** : dashboard, articles (view), BC (view), bordereaux (tous), stocks (tous), licences (view).

> **Observation** : les permissions `achat.stocks.entree/sortie/inventaire`, `achat.licences.manage` et `achat.rapports.export` sont déclarées mais **non utilisées** dans le code (aucune route/contrôleur ne les vérifie). Les routes `stocks` et `statistiques` ne font que vérifier `view`.

---

## 5. Cas d'utilisation (fonctionnalités)

### UC1 — Consulter le tableau de bord
`AchatController::index()`. Indicateurs : total articles, BC par statut (brouillon/valide/livré), BL par statut, alertes de stock, dépenses par mois (6 derniers), dépenses par fournisseur (Top 5), répartition catalogue.

### UC2 — Gérer le catalogue d'articles
`ArticleController` + `ArticleService`.
- Lister (filtres : type, marque, catégorie, actif, recherche), créer, modifier, supprimer, activer/désactiver (`toggleActif`), dupliquer (`dupliquer`).
- Upload d'image (`store('articles','public')`).
- Règle métier (service) : un équipement **doit** avoir une catégorie ; un non-équipement **ne doit pas** en avoir.
- Suppression : si l'article est référencé dans une commande → désactivation automatique + exception informative (pas de suppression physique → intégrité référentielle).

### UC3 — Créer et suivre un Bon de Commande
`BonCommandeController` + `BonCommandeService`.
- Création : au moins une ligne (article, quantité ≥ 1, prix unitaire ≥ 0). Numéro auto `BC-YYYY-NNNN`. Statut initial `brouillon`. `montant_total` recalculé (`quantite * prix_unitaire`).
- Modification : uniquement en statut `brouillon` (recréation des lignes).
- Validation (`valider`) : passe en `valide`, horodatage + `valide_par`. Déclenche `BonCommandeValide` → notification (log).
- Annulation (`annuler`) : depuis `brouillon` ou `valide` → `annule`. Déclenche `BonCommandeAnnule`.
- Suppression : uniquement en `brouillon`.
- Impression PDF (`imprimer?pdf`) via DomPDF.

### UC4 — Réceptionner via un Bordereau de Livraison
`BordereauLivraisonController` + `BordereauLivraisonService`.
- Création : le BC lié doit être `valide` ou `partiel` ; la `ref_bordereau_physique` doit être unique ; la quantité livrée par ligne ne peut dépasser le `reste_a_livrer` du BC.
- Modification : uniquement en `brouillon`, même contrôles.
- Suppression : uniquement en `brouillon`.
- Impression PDF.

### UC5 — Assistant d'intégration (Wizard)
`BordereauLivraisonController::wizard` + `WizardValidationService`.
- Lancement : le BL passe de `brouillon` → `wizard`.
- Pour chaque ligne de type **équipement** ou **licence**, saisie unitaire (n° série + code inventaire pour équipement ; clé/date activation/expiration pour licence ; caractéristiques techniques dynamiques issues de la catégorie ParcInfo).
- Sauvegarde progressive (`sauvegarderWizardEtape`) dans `achat_wizard_data`.
- Si le BL ne contient que des consommables → validation directe sans wizard.
- Finalisation (`validerBordereau`) :
  - Vérifie que chaque étape equipement/licence est `completed` et que `count(unites_data) == quantite_livree`.
  - **Équipement** → création d'une fiche `Equipement` (ParcInfo) avec `ref_bordereau`, statut `en_stock`, état `bon`, historisation `acquisition`. Code inventaire généré si absent.
  - **Licence** → création `Logiciel` (firstOrCreate) + `Licence` (ParcInfo).
  - **Consommable** → incrément `stock_actuel` article + création/mise à jour `Consommable` ParcInfo + `MouvementConsommable` (entrée).
  - Mise à jour `quantite_livree` des lignes de commande.
  - Recalcul statut BC : `livre` si total livré ≥ total commandé, sinon `partiel`.
  - BL → `valide` ; purge des `wizard_data`.
  - Déclenche `BordereauLivraisonValide`.

### UC6 — Consulter les stocks de consommables
`StockController::index()`. Liste les articles de type `consommable` avec calcul d'alerte (`stock_actuel <= seuil_alerte`). Filtres : `alerte` / `ok`, recherche.
> **Observation** : les actions `entree`/`sortie`/`inventaire` ne sont pas implémentées (le stock ne bouge que via la validation des BL).

### UC7 — Rapports et statistiques
`StatistiquesController`.
- Tableau de bord dédié (volumes, taux de complétion, évolution mensuelle, Top fournisseurs, répartition par type).
- 4 rapports exportables en PDF : `global_purchases`, `by_supplier_detail`, `reliquats`, `popular_articles`.
- Filtres : fournisseur, statut, dates.

### UC8 — Gestion documentaire
`DocumentController`. Upload (10 Mo max) lié à un BC ou BL (polymorphique), téléchargement, suppression.
> **Observation** : aucune vérification de permission n'est effectuée dans `DocumentController` ; le type est limité à `bon_commande`/`bordereau`.

---

## 6. Règles métier identifiées (R-xxx)

| ID | Règle | Implémentation |
| --- | --- | --- |
| R-ART-04 | Équipement ⇒ catégorie obligatoire | `ArticleService::validerReglesMetier` |
| R-ART-05 | Non-équipement ⇒ catégorie nulle | `ArticleService::validerReglesMetier` |
| R-ART-10 | Article référencé ⇒ désactivation au lieu de suppression | `ArticleService::supprimer` |
| R-BC-01 | BC modifiable seulement en `brouillon` | `BonCommande::estModifiable` |
| R-BC-02 | BC validable si `brouillon` + au moins une ligne | `BonCommande::estValidable` |
| R-BC-03 | BC annulable depuis `brouillon`/`valide` | `BonCommande::estAnnulable` |
| R-BL-01 | BL créable seulement sur BC `valide`/`partiel` | `BordereauLivraisonService::creer` |
| R-BL-02 | `ref_bordereau_physique` unique | `BordereauLivraisonService` + migration |
| R-BL-03 | `quantite_livree` ≤ `reste_a_livrer` du BC | `BordereauLivraisonService` |
| R-BL-04 | BL modifiable seulement en `brouillon` | `BordereauLivraisonService::modifier` |
| R-WIZ-01 | Wizard requis pour équipements/licences | `WizardValidationService::validerBordereau` |
| R-WIZ-02 | `count(unites_data)` == `quantite_livree` | `WizardValidationService` |
| R-WIZ-03 | N° série et code inventaire obligatoires et uniques (ParcInfo) | `EquipementIntegrationService` |
| R-CODE | Génération séquentielle et unique des numéros/codes | Services générateurs (anti-concurrence via `lockForUpdate`) |

---

## 7. Intégrations avec le module ParcInfo

Le module Achat dépend fortement de `Modules\ParcInfo` (modèles) :
- `Fournisseur`, `Marque`, `CategorieEquipement` (catalogue, BC, BL).
- `Equipement` + `HistoriqueChangement` (création via wizard).
- `Logiciel`, `Licence` (création via wizard).
- `Consommable`, `TypeConsommable`, `MouvementConsommable` (mise à jour stock).
- Les équipements créés portent `ref_bordereau` = `numero_livraison` (lien de traçabilité), et les vues Achat affichent les équipements liés à un BC/BL via cette référence.

**Système événementiel** : `EventServiceProvider` lie `BordereauLivraisonValide` à 5 listeners (`CreerEquipementsDansParcInfo`, `CreerLicences`, `MettreAJourStockConsommables`, `HistoriserAcquisition`, `NotifierUtilisateurs`). **Constat** : ces listeners sont des stubs vides — toute la logique d'intégration est exécutée *inline* dans `WizardValidationService` (au sein d'une transaction unique) pour garantir la cohérence et faciliter le retour UI. `NotifierUtilisateurs` écrit uniquement dans les logs.

---

## 8. Écrans / Vues (Blade)

| Vue | Description |
| --- | --- |
| `index.blade.php` | Tableau de bord (cartes stats + graphiques) |
| `articles/index` | Tableau catalogue + modale création/édition |
| `bons_commande/{index,create,edit,show,imprimer,print_pdf}` | Gestion BC + PDF |
| `bordereaux/{index,create,show,wizard,print_pdf}` | Gestion BL + assistant + PDF |
| `stocks/index` | Tableau consommables + alertes |
| `statistiques/{index,pdf}` | Rapports |
| `layouts/master` + partials (navbar, sidebar) | Gabarit commun |
| Composants Blade : `_badge_statut_bc`, `_badge_statut_bl`, `_badge_type_article`, `_stats_card` | Réutilisables |

L'assistant (`wizard.blade.php`) est un stepper vertical : une étape par article (équipement/licence) + étape de validation finale. Champs dynamiques selon `categorie->champs` (ParcInfo).

---

## 9. Non-conformités et points d'attention

1. **Listeners événementiels inactifs** : `CreerEquipementsDansParcInfo`, `CreerLicences`, `MettreAJourStockConsommables`, `HistoriserAcquisition` sont vides. La logique réside dans `WizardValidationService`. Risque de confusion/maintenance.
2. **Permissions déclarées non utilisées** : `stocks.entree/sortie/inventaire`, `licences.manage`, `rapports.export`, `bordereaux.valider` (le contrôleur utilise `achat.bordereaux.edit` pour valider) sont définies mais non appliquées.
3. **Vérification d'autorisation sur BC** : `valider`/`annuler` utilisent `achat.bons_commande.edit` au lieu d'une permission dédiée `valider`/`annuler` (commentaire de code le reconnaît).
4. **API non fonctionnelle** : `apiResource('achats', AchatController)` pointe vers un contrôleur dont les actions CRUD sont vides.
5. **Gestion documentaire sans permission** : `DocumentController` ne vérifie aucun droit.
6. **Double source de vérité stock consommable** : le stock est dupliqué entre `achat_articles.stock_actuel` et `parc_info_consommables.quantite_stock_actuel` ; seul le flux de validation BL les synchronise.
7. **Logique de reliquat / statut `partiel`** : le passage en `partiel` est correctement géré, mais aucune clôture automatique d'un BC `partiel` resté en attente n'est prévue.
8. **Concurrence** : la génération des numéros BC/BL utilise un `count()` + boucle anti-doublon (non atomique hors transaction verrouillée), contrairement aux codes inventaire qui utilisent `lockForUpdate`.

---

## 10. Préconisations

1. **Activer ou supprimer** les listeners d'événements pour éviter la logique orpheline ; privilégier soit le service, soit les listeners (pas les deux).
2. **Appliquer les permissions** `valider`/`annuler`/`export`/`stocks.*`/`licences.manage` dans les contrôleurs correspondants.
3. **Sécuriser `DocumentController`** avec `authorize()` et limiter les types MIME autorisés.
4. **Clarifier l'API** : soit implémenter les actions du `AchatController` API, soit retirer la route `apiResource`.
5. **Centraliser la gestion des stocks** consommables pour éviter la dérive de synchronisation (mono-source).
6. **Sécuriser la génération des numéros BC/BL** avec un verrou de séquence (ex. `lockForUpdate` ou colonne compteur dédiée) pour les environnements multi-utilisateurs.
7. **Ajouter une clôture/relance** des BC en statut `partiel` (rapport de reliquat déjà présent) et une gestion des retours/avaries en réception.

---

## 11. Résumé des flux métier

```
[Article] ──catalogue──> [Bon de Commande (brouillon)]
        │                         │ valider
        │                         ▼
        │                  [Bon de Commande (valide)]
        │                         │ créer BL (R-BL-01/02/03)
        │                         ▼
        │                  [Bordereau Livraison (brouillon)]
        │                         │ lancer wizard
        │                         ▼
        │                  [Bordereau Livraison (wizard)]
        │                   saisie équipements/licences
        │                         │ valider
        │                         ▼
        └──────────intègre──────── [ParcInfo : Equipement / Licence / Consommable + Mouvement]
                                   [Bordereau Livraison (valide)] + MAJ quantite_livree BC
                                   [Bon de Commande (livre | partiel)]
```

---

*Fin du document — Spécifications Fonctionnelles Détaillées du module Achat (ParcInfo).*
