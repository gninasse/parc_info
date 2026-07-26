# Spécifications Fonctionnelles — Module Stock

> **Version:** 1.0 — **Date:** 11 Juillet 2026
> **Protocole:** v2.2 (planches 00→09 par fonctionnalité)

---

## Table des Matières

1. [Architecture & Décisions](#architecture)
2. [F1 — Gestion des Magasins](#f1)
3. [F2 — Stock par Article & Magasin](#f2)
4. [F3 — Entrées de Stock](#f3)
5. [F4 — Sorties de Stock](#f4)
6. [F5 — Transferts Inter-Magasin](#f5)
7. [F6 — Inventaires](#f6)
8. [F7 — Valorisation du Stock](#f7)
9. [F8 — Alertes & Rapports](#f8)
10. [Schéma Global](#schema)

---

## Architecture & Décisions {#architecture}

| Composant | Valeur |
|---|---|
| Framework | Laravel 10.x |
| Base de données | PostgreSQL |
| Module system | nwidart/laravel-modules |
| Permissions | spatie/laravel-permission |
| Activity Log | spatie/laravel-activitylog |
| Frontend | AdminLTE + Bootstrap 5 + Bootstrap Table |
| JavaScript | jQuery + Select2 + SweetAlert2 |

### Décisions architecturales clés

- **Articles** : réutilisation de `achat_articles` sans duplication
- **Valorisation** : FIFO uniquement (pas de CUMP)
- **Seuils** : globaux par article (pas par magasin)
- **Droits** : par utilisateur ET par rôle — le ROLE prime sur USER
- **Mouvements** : table unique `stock_mouvements` pour F3+F4+F5+F6
- **Intégration Achat** : appel direct `EntreeStockService::creerDepuisBL()` depuis `WizardValidationService`

### Tables créées (12 tables)

```
stock_magasins
stock_responsables_magasin
stock_droits_magasin
stock_articles_magasin
stock_lots
stock_mouvements
stock_mouvements_affectations
stock_transferts
stock_inventaires
stock_inventaire_lignes
stock_snapshots
stock_snapshot_lignes
```

---

## F1 — Gestion des Magasins {#f1}

### CDCF

**Périmètre**
- Création, modification, consultation et inactivation des magasins
- Gestion des responsables (optionnels, issus du module GRH)
- Gestion des droits d'accès par utilisateur ET par rôle
- Blocage des mouvements sur un magasin inactif

### Exigences Fonctionnelles

| ID | Exigence | Priorité |
|---|---|---|
| EF-F1-01 | Créer un magasin avec code unique, libellé, description, statut | Must |
| EF-F1-02 | Modifier les informations d'un magasin | Must |
| EF-F1-03 | Activer / désactiver un magasin | Must |
| EF-F1-04 | Bloquer tous les mouvements de stock sur un magasin inactif | Must |
| EF-F1-05 | Affecter un ou plusieurs responsables (nullable, issus GRH) | Must |
| EF-F1-06 | Définir le rôle du responsable : principal ou adjoint | Should |
| EF-F1-07 | Gérer les droits d'accès par utilisateur + par magasin | Must |
| EF-F1-08 | Gérer les droits d'accès par rôle + par magasin | Must |
| EF-F1-09 | Consulter la fiche magasin (infos + responsables + droits) | Must |
| EF-F1-10 | Consulter le stock actuel d'un magasin | Must |
| EF-F1-11 | Consulter l'historique des mouvements d'un magasin | Must |
| EF-F1-12 | Lister et filtrer les magasins (statut, responsable) | Must |

### Modèle de Données

```sql
stock_magasins (id, code UNIQUE, libelle, description, statut[actif|inactif], created_by, updated_by, timestamps, deleted_at)
stock_responsables_magasin (id, magasin_id FK, employe_id FK nullable, role[principal|adjoint], date_debut, date_fin nullable, timestamps)
stock_droits_magasin (id, magasin_id FK, type_sujet[USER|ROLE], sujet_id, peut_lire, peut_entrer_stock, peut_sortir_stock, peut_transferer, peut_inventorier, peut_administrer, UNIQUE(magasin_id,type_sujet,sujet_id))
```

### Règles de Gestion

| ID | Règle |
|---|---|
| RG-F1-01 | Code magasin unique et immuable après création |
| RG-F1-02 | Libellé obligatoire, min 3 caractères |
| RG-F1-03 | Magasin inactif bloque toute entrée, sortie, transfert, inventaire |
| RG-F1-04 | Les droits ROLE priment sur les droits USER |
| RG-F1-05 | Si aucun ROLE ne s'applique, les droits USER sont utilisés |
| RG-F1-06 | Un seul responsable "principal" par magasin à la fois |
| RG-F1-07 | Tout magasin est visible dans la liste, inactifs grisés |
| RG-F1-08 | Magasins inactifs masqués partout sauf dans /stock/magasins |

### Algorithme résolution droits

```php
function peutFaire(User $user, Magasin $magasin, string $permission): bool {
    $roles = $user->roles->pluck('id');
    $droitRole = DroitMagasin::where('magasin_id', $magasin->id)
        ->where('type_sujet', 'ROLE')->whereIn('sujet_id', $roles)->first();
    if ($droitRole) return (bool) $droitRole->$permission;
    $droitUser = DroitMagasin::where('magasin_id', $magasin->id)
        ->where('type_sujet', 'USER')->where('sujet_id', $user->id)->first();
    return $droitUser ? (bool) $droitUser->$permission : false;
}
```

### Permissions F1

```
stock.magasins.view, stock.magasins.create, stock.magasins.edit, stock.magasins.admin
```

### Routes F1

```
GET    /stock/magasins
GET    /stock/magasins/create
POST   /stock/magasins
GET    /stock/magasins/{magasin}
GET    /stock/magasins/{magasin}/edit
PUT    /stock/magasins/{magasin}
DELETE /stock/magasins/{magasin}
POST   /stock/magasins/{magasin}/activer
POST   /stock/magasins/{magasin}/desactiver
POST   /stock/magasins/{magasin}/responsables
DELETE /stock/magasins/{magasin}/responsables/{responsable}
POST   /stock/magasins/{magasin}/droits
PUT    /stock/magasins/{magasin}/droits/{droit}
DELETE /stock/magasins/{magasin}/droits/{droit}
```


---

## F2 — Stock par Article & Magasin {#f2}

### Décisions
- Articles = `achat_articles` (pas de duplication)
- Seuils globaux sur `achat_articles.seuil_alerte`
- Valorisation FIFO via `stock_lots`

### Modèle de Données

```sql
stock_articles_magasin (
  id, article_id FK→achat_articles, magasin_id FK→stock_magasins,
  quantite_actuelle INT default 0, valeur_stock_fifo DECIMAL(14,2) default 0,
  derniere_entree_at TIMESTAMP nullable, derniere_sortie_at TIMESTAMP nullable,
  timestamps, UNIQUE(article_id, magasin_id)
)

stock_lots (
  id, article_id FK, magasin_id FK,
  quantite_initiale INT, quantite_restante INT,
  cout_unitaire DECIMAL(12,4), date_entree DATE,
  mouvement_id FK nullable→stock_mouvements,
  timestamps,
  INDEX(article_id, magasin_id, date_entree),
  CHECK(quantite_restante >= 0), CHECK(quantite_restante <= quantite_initiale)
)
```

### Statuts d'alerte

| Condition | Statut | Couleur |
|---|---|---|
| qte > seuil_alerte | OK | Vert |
| 0 < qte <= seuil_alerte | ALERTE | Orange |
| qte = 0 | RUPTURE | Rouge |

### Règles de Gestion

| ID | Règle |
|---|---|
| RG-F2-01 | Un article ne peut être initialisé qu'une seule fois par magasin |
| RG-F2-02 | Initialisation impossible sur magasin inactif |
| RG-F2-03 | Coût unitaire obligatoire à l'initialisation (lot FIFO) |
| RG-F2-04 | quantite_restante d'un lot ne peut pas être négative |
| RG-F2-05 | Valeur stock FIFO = Σ(lot.quantite_restante × lot.cout_unitaire) |
| RG-F2-06 | Lots FIFO consommés dans l'ordre date_entree ASC |

### Services

- `StockArticleService` : initialiser(), updateSeuil()
- `FifoService` : calculerSortie(), consommerLots(), calculerValeur()

### Routes F2

```
GET  /stock/articles
GET  /stock/articles/{article}
POST /stock/articles/initialiser
POST /stock/articles/{article}/seuil
GET  /stock/articles/{article}/lots
```

---

## F3 — Entrées de Stock {#f3}

### Table principale : stock_mouvements

```sql
stock_mouvements (
  id,
  type_mouvement ENUM[ENTREE, SORTIE, TRANSFERT_ENTRANT, TRANSFERT_SORTANT,
                      REGULARISATION_PLUS, REGULARISATION_MOINS,
                      INVENTAIRE_PLUS, INVENTAIRE_MOINS],
  article_id FK→achat_articles,
  magasin_id FK→stock_magasins,
  quantite INT,
  cout_unitaire DECIMAL(12,4) nullable,
  type_origine ENUM[MANUEL, BL, TRANSFERT, INVENTAIRE] default MANUEL,
  origine_id BIGINT nullable,
  reference_document TEXT nullable,
  motif TEXT nullable,
  valide_at TIMESTAMP nullable,
  created_by FK→users nullable,
  updated_by FK→users nullable,
  timestamps, deleted_at,
  INDEX(article_id, magasin_id), INDEX(type_mouvement),
  INDEX(type_origine, origine_id), INDEX(created_at)
)
```

### Types d'entrée manuelle

- `ENTREE` (approvisionnement)
- `ENTREE` (retour)
- `REGULARISATION_PLUS`

### Intégration Achat

`WizardValidationService` appelle directement `EntreeStockService::creerDepuisBL()` après validation BL.
- type_origine = BL, origine_id = bordereau_livraison.id
- cout_unitaire = prix_unitaire de la ligne BL
- Si article non initialisé → initialisation automatique

### Règles de Gestion

| ID | Règle |
|---|---|
| RG-F3-01 | Entrée impossible sur magasin inactif |
| RG-F3-02 | Coût unitaire obligatoire pour ENTREE et REGULARISATION_PLUS |
| RG-F3-03 | Motif obligatoire pour REGULARISATION_PLUS |
| RG-F3-04 | Transaction atomique : mouvement + lot + MAJ stock |
| RG-F3-05 | Entrée BL non supprimable |
| RG-F3-06 | Suppression manuelle : admin + created_at < 24h |
| RG-F3-07 | quantite > 0 obligatoire |

### Routes F3

```
GET    /stock/entrees
POST   /stock/entrees
GET    /stock/entrees/{mouvement}
DELETE /stock/entrees/{mouvement}
```


---

## F4 — Sorties de Stock {#f4}

### Table stock_mouvements_affectations

```sql
stock_mouvements_affectations (
  id, mouvement_id FK UNIQUE→stock_mouvements,
  type_affectation_parcinfo ENUM[EQUIPEMENT, CONSOMMABLE, LICENCE],
  affectation_id BIGINT,
  type_cible ENUM[EMPLOYE, SERVICE, DIRECTION, UNITE, POSTE],
  cible_id BIGINT, timestamps,
  INDEX(type_affectation_parcinfo, affectation_id),
  INDEX(type_cible, cible_id)
)
```

### Routage affectation ParcInfo par type article

| type_article | Action ParcInfo |
|---|---|
| equipement | AffectationEquipement (PERMANENTE) |
| consommable | MouvementConsommable + MAJ parc_info_consommables.quantite_stock_actuel |
| licence | AffectationLicence |
| prestation | Aucune affectation |

### Règles de Gestion

| ID | Règle |
|---|---|
| RG-F4-01 | Stock vérifié AVANT la transaction |
| RG-F4-02 | Transaction atomique : sortie + FIFO + affectation ParcInfo |
| RG-F4-03 | Rollback total si affectation ParcInfo échoue |
| RG-F4-04 | Régularisation (-) : admin uniquement + motif obligatoire |
| RG-F4-05 | Sortie impossible sur magasin inactif |
| RG-F4-06 | Sortie non modifiable après validation |

### Services

- `SortieStockService` : creer(), creerRegularisation()
- `ParcInfoAffectationService` : creerDepuisSortie(), affecterEquipement(), affecterConsommable(), affecterLicence()

### Routes F4

```
GET  /stock/sorties
POST /stock/sorties
GET  /stock/sorties/{mouvement}
POST /stock/sorties/regularisation
```

---

## F5 — Transferts Inter-Magasin {#f5}

### Table stock_transferts

```sql
stock_transferts (
  id, numero_transfert TEXT UNIQUE,
  magasin_source_id FK→stock_magasins,
  magasin_destination_id FK→stock_magasins,
  article_id FK→achat_articles,
  quantite INT,
  statut ENUM[EN_ATTENTE, VALIDE, REJETE, ANNULE] default EN_ATTENTE,
  motif_creation TEXT,
  motif_rejet TEXT nullable,
  created_by FK nullable, updated_by FK nullable,
  valide_par FK nullable, date_validation TIMESTAMP nullable,
  mouvement_sortant_id FK nullable→stock_mouvements,
  mouvement_entrant_id FK nullable→stock_mouvements,
  timestamps, deleted_at,
  CHECK(magasin_source_id != magasin_destination_id)
)
```

### Workflow

```
EN_ATTENTE → VALIDE  (responsable destination ou admin, auto-validation OK)
EN_ATTENTE → REJETE  (motif obligatoire)
EN_ATTENTE → ANNULE  (créateur ou admin)
VALIDE/REJETE/ANNULE → terminal
```

### Validation : double mouvement atomique

1. Vérifier stock source suffisant
2. BEGIN TRANSACTION
3. Calculer FIFO + consommer lots source
4. Créer TRANSFERT_SORTANT (source)
5. MAJ SAM source
6. Créer lot destination (même coût FIFO source)
7. Créer TRANSFERT_ENTRANT (destination)
8. MAJ SAM destination
9. Update transfert statut=VALIDE + mouvement_ids
10. COMMIT

### Règles de Gestion

| ID | Règle |
|---|---|
| RG-F5-01 | magasin_source ≠ magasin_destination (CHECK DB) |
| RG-F5-02 | Les deux magasins actifs à la création ET validation |
| RG-F5-03 | Stock vérifié à la validation (pas à la création) |
| RG-F5-04 | Validation : peut_transferer sur destination OU admin |
| RG-F5-05 | Auto-validation autorisée (créateur = responsable dest.) |
| RG-F5-06 | Coût FIFO source conservé sur lot destination |
| RG-F5-07 | Numéro format TRF-YYYY-XXXX |

### Routes F5

```
GET  /stock/transferts
POST /stock/transferts
GET  /stock/transferts/{transfert}
POST /stock/transferts/{transfert}/valider
POST /stock/transferts/{transfert}/rejeter
POST /stock/transferts/{transfert}/annuler
```


---

## F6 — Inventaires {#f6}

### Tables

```sql
stock_inventaires (
  id, numero_inventaire TEXT UNIQUE,
  magasin_id FK, date_inventaire DATE,
  statut ENUM[EN_COURS, CLOTURE, ANNULE] default EN_COURS,
  nombre_articles INT default 0, nombre_ecarts INT default 0,
  created_by FK nullable, updated_by FK nullable,
  valide_par FK nullable, date_cloture TIMESTAMP nullable,
  timestamps, deleted_at, INDEX(magasin_id, statut)
)

stock_inventaire_lignes (
  id, inventaire_id FK CASCADE,
  article_id FK, quantite_theorique INT,
  quantite_reelle INT nullable, ecart INT nullable,
  cout_unitaire_reference DECIMAL(12,4) nullable,
  mouvement_id FK nullable→stock_mouvements,
  timestamps, UNIQUE(inventaire_id, article_id)
)
```

### Règles clés

| ID | Règle |
|---|---|
| RG-F6-01 | Un seul inventaire EN_COURS par magasin |
| RG-F6-02 | Snapshot théorique figé à la création |
| RG-F6-03 | Sauvegarde partielle sans mouvement |
| RG-F6-04 | Validation bloquée si quantite_reelle nullable |
| RG-F6-05 | Écart > 0 → INVENTAIRE_PLUS + lot FIFO (cout=cout_unitaire_reference) |
| RG-F6-06 | Écart < 0 → INVENTAIRE_MOINS + consommation FIFO |
| RG-F6-07 | Écart = 0 → aucun mouvement |
| RG-F6-08 | Validation atomique tous mouvements |
| RG-F6-09 | Annulation admin uniquement |
| RG-F6-10 | Numéro format INV-YYYY-XXXX |

### Routes F6

```
GET  /stock/inventaires
POST /stock/inventaires
GET  /stock/inventaires/{inventaire}
POST /stock/inventaires/{inventaire}/lignes
POST /stock/inventaires/{inventaire}/valider
POST /stock/inventaires/{inventaire}/annuler
```

---

## F7 — Valorisation du Stock {#f7}

### Tables

```sql
stock_snapshots (
  id, reference TEXT UNIQUE,
  type ENUM[MENSUEL, MANUEL],
  date_snapshot TIMESTAMP,
  valeur_totale_globale DECIMAL(16,2) default 0,
  created_by FK nullable, timestamps
)

stock_snapshot_lignes (
  id, snapshot_id FK CASCADE,
  magasin_id FK, article_id FK,
  quantite INT default 0,
  valeur_fifo DECIMAL(14,2) default 0,
  cout_unitaire_moyen DECIMAL(12,4) default 0,
  timestamps, UNIQUE(snapshot_id, magasin_id, article_id)
)
```

### Règles clés

| ID | Règle |
|---|---|
| RG-F7-01 | Valeur FIFO = Σ(lot.quantite_restante × lot.cout_unitaire) |
| RG-F7-02 | Snapshot atomique (tous magasins en une transaction) |
| RG-F7-03 | Snapshot immuable après création |
| RG-F7-04 | Référence mensuelle format YYYY-MM |
| RG-F7-05 | Référence manuelle format YYYY-MM-MANUEL-N |
| RG-F7-06 | Scheduler : 1er du mois à 00h05 |

### Routes F7

```
GET  /stock/valorisation
GET  /stock/valorisation/data
GET  /stock/valorisation/snapshots
POST /stock/valorisation/snapshots
GET  /stock/valorisation/snapshots/{snapshot}
GET  /stock/valorisation/snapshots/comparer
GET  /stock/valorisation/pdf
GET  /stock/valorisation/snapshots/{snapshot}/pdf
POST /stock/valorisation/recalculer
```

---

## F8 — Alertes & Rapports {#f8}

### Pas de nouvelles tables — utilise l'existant

- `stock_articles_magasin` → alertes
- `stock_mouvements` → rapports
- `stock_transferts` → rapport transferts
- `notifications` (Laravel standard) → in-app

### Notifications in-app

| Classe | Déclencheur | Destinataires |
|---|---|---|
| StockEnRupture | qte = 0 après sortie | Responsables + admins magasin |
| StockSousSeuil | qte <= seuil après sortie | Responsables magasin |
| TransfertEnAttente | Transfert créé EN_ATTENTE | Responsables magasin destination |

### Rapports séparés (+ export PDF chacun)

1. Rapport Entrées (filtres : magasin, type, article, période)
2. Rapport Sorties (filtres : magasin, type, article, cible, période)
3. Rapport Transferts (filtres : source, destination, statut, période)
4. Rapport Stock par Magasin (filtres : magasin, type, statut alerte)

### Routes F8

```
GET  /stock (dashboard)
GET  /stock/dashboard/data
GET  /stock/alertes
GET  /stock/alertes/count
POST /stock/notifications/{id}/lire
POST /stock/notifications/lire-tout
GET  /stock/rapports/entrees + /pdf
GET  /stock/rapports/sorties + /pdf
GET  /stock/rapports/transferts + /pdf
GET  /stock/rapports/stock + /pdf
```

---

## Schéma Global {#schema}

### Services (13)

```
MagasinService, DroitMagasinService
StockArticleService, FifoService
EntreeStockService, SortieStockService
ParcInfoAffectationService
TransfertService
InventaireService
SnapshotService, RecalculFifoService
DashboardService, RapportService
```

### Intégrations modules externes

| Module | Point d'intégration | Sens |
|---|---|---|
| Achat | WizardValidationService → EntreeStockService::creerDepuisBL() | Achat → Stock |
| ParcInfo | ParcInfoAffectationService → AffectationEquipement / MouvementConsommable / AffectationLicence | Stock → ParcInfo |
| GRH | grh_dossiers_employes (responsables + cibles) | Stock → GRH |
| Organisation | Direction, Service, Unité, PosteTravail (cibles affectation) | Stock → Organisation |

### Permissions complètes

```
stock.magasins.view / create / edit / admin
stock.articles.view / admin
stock.entrees.view / create / admin
stock.sorties.view / create / admin
stock.transferts.view / create / admin
stock.inventaires.view / create / admin
stock.valorisation.view / admin
stock.rapports.view
stock.dashboard.view
```
