# Spécifications Fonctionnelles Détaillées - Module Achat

**Référence** : OPENCODE-SFD-ACHAT-v1.0  
**Date** : 11 Juillet 2026  
**Version** : 1.0  
**Statut** : Approuvé  

---

## Table des Matières

1. [Introduction](#1-introduction)
2. [Contexte et Objectifs](#2-contexte-et-objectifs)
3. [Portée du Module](#3-portée-du-module)
4. [Acteurs et Rôles](#4-acteurs-et-rôles)
5. [Spécifications Fonctionnelles par Composant](#5-spécifications-fonctionnelles-par-composant)
6. [Règles Métier](#6-règles-métier)
7. [Modèle de Données](#7-modèle-de-données)
8. [Flux et Workflows](#8-flux-et-workflows)
9. [Interfaces et API](#9-interfaces-et-api)
10. [Sécurité et Permissions](#10-sécurité-et-permissions)
11. [Exigences Non Fonctionnelles](#11-exigences-non-fonctionnelles)
12. [Annexes](#12-annexes)

---

## 1. Introduction

### 1.1 Présentation
Ce document décrit les **Spécifications Fonctionnelles Détaillées** du **Module Achat** de l'application **ParcInfo**.

### 1.2 Public Cible
- Équipes de développement
- Architectes logiciels
- Responsables fonctionnels
- Testeurs

### 1.3 Glossaire
| Terme | Définition |
|-------|-----------|
| **BC** | Bon de Commande |
| **BL** | Bordereau de Livraison |
| **SFD** | Spécifications Fonctionnelles Détaillées |
| **ParcInfo** | Système de gestion du parc informatique |

---

## 2. Contexte et Objectifs

### 2.1 Contexte
Le **Module Achat** s'intègre dans **ParcInfo** pour gérer l'ensemble du cycle d'achat, de la création des articles catalogues à l'intégration des équipements dans le parc.

### 2.2 Objectifs
- Centraliser la gestion des achats et approvisionnements
- Automatiser les workflows de validation et d'intégration
- Intégration transparente avec le module ParcInfo
- Traçabilité complète des commandes, livraisons et stocks
- Générer des rapports et statistiques

### 2.3 Problématiques Résolues
- Double saisie des équipements → Intégration automatique via assistant
- Suivi des reliquats de livraison → Gestion des quantités livrées vs commandées
- Alertes de stock → Seuils de rupture et d'alerte configurables
- Traçabilité administrative → Historisation des validations et mouvements

---

## 3. Portée du Module

### 3.1 Fonctionnalités Incluses
✅ Catalogue des Articles (équipements, consommables, licences, prestations)  
✅ Bons de Commande (CRUD, validation, annulation, impression)  
✅ Bordereaux de Livraison (CRUD, validation via assistant, impression)  
✅ Gestion des Stocks consommables avec seuils d'alerte  
✅ Intégration automatique avec ParcInfo (création équipements/licences)  
✅ Gestion des documents joints  
✅ Statistiques et Rapports (tableaux de bord, exports PDF)  
✅ Paramétrage (préfixes, patterns, seuils)  

### 3.2 Fonctionnalités Exclues
❌ Gestion des fournisseurs (module ParcInfo)  
❌ Gestion des marques (module ParcInfo)  
❌ Gestion des catégories d'équipements (module ParcInfo)  
❌ Comptabilité analytique  
❌ Workflow de signature électronique  

---

## 4. Acteurs et Rôles

### 4.1 Acteurs Externes
| Acteur | Description | Responsabilités |
|--------|-------------|------------------|
| **Acheteur** | Utilisateur en charge des achats | Création BC, suivi commandes |
| **Validateur Achat** | Responsable validation | Validation/annulation BC |
| **Magasinier** | Gestionnaire des stocks | Réception livraisons, gestion stocks |
| **Administrateur** | Super-utilisateur | Configuration, gestion utilisateurs |

### 4.2 Matrice RACI
| Fonctionnalité | Acheteur | Validateur | Magasinier | Administrateur |
|---------------|----------|-----------|-----------|--------------|
| Créer article | A/R | C | C | - |
| Créer BC | A/R | C | I | - |
| Valider BC | C | A/R | I | - |
| Créer BL | - | C | A/R | - |
| Valider BL | - | C | A/R | - |
| Gérer stocks | I | - | A/R | - |
| Voir statistiques | I | I | I | A/R |
| Configurer | - | - | - | A/R |

**Légende**: A=Responsable, R=Exécutant, C=Consulté, I=Informed

---

## 5. Spécifications Fonctionnelles par Composant

---

### 5.1 Catalogue des Articles

#### Types d'Articles
| Type | Description | Spécificités |
|------|-------------|--------------|
| **Équipement** | Matériel informatique physique | Catégorie obligatoire, numéro de série, code inventaire |
| **Consommable** | Produits à usage unique | Seuil d'alerte, gestion de stock |
| **Licence** | Logiciels et droits | Durée de validité, clé de licence |
| **Prestation** | Services externes | Pas de gestion de stock |

#### Fonctionnalités
| ID | Fonctionnalité | Description | Priorité |
|----|----------------|-------------|----------|
| CAT-001 | Créer un article | Ajout d'un nouvel article au catalogue | Haute |
| CAT-002 | Modifier un article | Mise à jour des informations | Haute |
| CAT-003 | Lister les articles | Affichage paginé et filtré | Haute |
| CAT-004 | Rechercher un article | Recherche par code, désignation, description | Moyenne |
| CAT-005 | Dupliquer un article | Création d'une copie | Moyenne |
| CAT-006 | Activer/Désactiver | Changement de statut actif/inactif | Moyenne |
| CAT-007 | Supprimer | Suppression (désactivation si référencé) | Moyenne |

#### Champs par Type
**Communs**: code_article, designation, description, type_article, reference_constructeur, marque_id, fournisseur_prefere_id, prix_indicatif, unite_mesure, taux_tva, compte_comptable, url_fiche_technique, image, actif

**Équipement**: categorie_equipement_id (requis)
**Consommable**: seuil_alerte (requis), stock_actuel
**Licence**: duree_validite_mois (requis)

---

### 5.2 Bons de Commande

#### Cycle de Vie
```
Brouillon → Valide → Partiel → Livré
       ↓
     Annulé
```
- **Brouillon**: Modifiable, supprimable
- **Valide**: Non modifiable, peut être annulé, peut générer des BL
- **Partiel**: Livraison partielle, peut recevoir d'autres BL
- **Livré**: Livraison complète (terminal)
- **Annulé**: Commande annulée (terminal)

#### Fonctionnalités
| ID | Fonctionnalité | Description | Priorité |
|----|----------------|-------------|----------|
| BC-001 | Créer un BC | Création d'un nouveau bon de commande | Haute |
| BC-002 | Modifier un BC | Modification (uniquement en brouillon) | Haute |
| BC-003 | Valider un BC | Passage au statut 'valide' | Haute |
| BC-004 | Annuler un BC | Annulation (brouillon ou valide) | Moyenne |
| BC-005 | Lister les BC | Affichage paginé et filtré | Haute |
| BC-006 | Imprimer un BC | Génération PDF/impression | Moyenne |

#### Champs
- `numero_commande`: Unique, format {prefix}-YYYY-XXXX
- `fournisseur_id`: Référence au fournisseur (ParcInfo)
- `date_commande`: Date de création
- `statut`: brouillon, valide, partiel, livre, annule
- `montant_total`: Calculé automatiquement
- `commentaire`: Notes optionnelles
- `valide_par`, `date_validation`: Audit de validation

#### Lignes de Commande
- `article_id`, `quantite` (>0), `prix_unitaire` (>=0), `quantite_livree` (default: 0)
- **Calculés**: reste_a_livrer = quantite - quantite_livree, montant_ligne = quantite * prix_unitaire

---

### 5.3 Bordereaux de Livraison

#### Cycle de Vie
```
Brouillon → Wizard → Valide
```
- **Brouillon**: Modifiable, supprimable
- **Wizard**: En cours de saisie des unités
- **Valide**: Intégration terminée (terminal)

#### Fonctionnalités
| ID | Fonctionnalité | Description | Priorité |
|----|----------------|-------------|----------|
| BL-001 | Créer un BL | À partir d'un BC validé/partiel | Haute |
| BL-002 | Modifier un BL | Uniquement en brouillon | Moyenne |
| BL-003 | Lancer Wizard | Assistant d'intégration | Haute |
| BL-004 | Sauvegarder étape | Sauvegarde temporaire | Moyenne |
| BL-005 | Valider BL | Validation finale avec intégration | Haute |
| BL-006 | Imprimer BL | Génération PDF | Moyenne |

#### Champs
- `numero_livraison`: Unique, format {prefix}-YYYY-XXXX
- `bon_de_commande_id`: Référence au BC associé
- `date_livraison`: Date de réception
- `ref_bordereau_physique`: Référence du document physique (unique)
- `statut`: brouillon, wizard, valide

#### Assistant d'Intégration (Wizard)
Pour chaque unité d'**équipement** ou **licence**:
- **Équipement**: numero_serie (requis, unique), code_inventaire (requis, unique, généré si vide), champs_valeurs (JSON)
- **Licence**: cle_licence, date_activation, date_expiration

Pour les **consommables**: Validation directe sans saisie d'unités

---

### 5.4 Gestion des Stocks

#### Fonctionnalités
| ID | Fonctionnalité | Description | Priorité |
|----|----------------|-------------|----------|
| STK-001 | Consulter stocks | Liste des consommables avec niveaux | Haute |
| STK-002 | Filtrer par statut | Filtre: en alerte / stock correct | Moyenne |
| STK-003 | Mise à jour automatique | +quantité livrée lors validation BL | Automatique |

#### Seuils de Stock (basés sur seuil_alerte)
| Niveau | Facteur | Couleur | Action |
|--------|---------|---------|--------|
| Rupture | 0 | Rouge | Commande urgente |
| Critique | 0.2 (20%) | Orange | Alerte forte |
| Alerte | 0.5 (50%) | Jaune | Alerte |
| Faible | 1.0 (100%) | Vert | Stock normal |

---

### 5.5 Intégration avec ParcInfo

#### Équipements
Lors de la validation d'un BL contenant des équipements:
1. Création de fiche dans `parc_info_equipements`
2. Création d'historique dans `parc_info_historique_changements`

**Champs créés**:
- categorie_id, code_inventaire, numero_serie, marque_id, modele, date_acquisition, valeur_achat, ref_bordereau, statut='en_stock', etat='bon'

#### Licences
1. Création ou récupération du logiciel dans `parc_info_logiciels`
2. Création de la licence dans `parc_info_licences`

**Champs créés**: logiciel_id, cle_licence, date_acquisition, date_activation, date_expiration, cout_unitaire, fournisseur_id, actif=true, statut='VALIDE'

#### Consommables
1. Mise à jour du stock de l'article catalogue
2. Création/mise à jour du consommable dans `parc_info_consommables`
3. Création d'un mouvement de stock dans `parc_info_mouvements_consommables`

---

### 5.6 Gestion des Documents

#### Fonctionnalités
| ID | Fonctionnalité | Description | Priorité |
|----|----------------|-------------|----------|
| DOC-001 | Ajouter | Upload de fichier (max 10MB) | Moyenne |
| DOC-002 | Télécharger | Récupération du fichier | Moyenne |
| DOC-003 | Supprimer | Suppression du fichier et de l'enregistrement | Moyenne |

#### Types Supportés
PDF, Images (jpg, jpeg, png, gif), Documents Office, Fichiers texte

---

### 5.7 Statistiques et Rapports

#### Indicateurs Clés (Dashboard)
- Total Articles, Total BC, BC Brouillon/Validés/Livrés
- Total BL, BL Brouillon/Wizard/Validés
- Alertes Stock (articles consommables sous seuil)
- Graphiques: Dépenses par Mois, par Fournisseur, Répartition Catalogue

#### Rapports
| Rapport | Description | Filtres |
|---------|-------------|---------|
| Rapport Global BC | Liste complète des bons de commande | Fournisseur, statut, dates |
| Dépenses par Fournisseur | Détail des dépenses par fournisseur | Fournisseur |
| Reliquats de Livraison | Lignes partiellement livrées | Fournisseur |
| Articles les plus commandés | Classement par quantité | - |

**Format**: HTML (tableau), Export PDF

---

### 5.8 Paramétrage

#### Paramètres Configurables
| Clé | Valeur par Défaut | Description |
|-----|------------------|-------------|
| pattern_code_inventaire | INV-{YYYY}-{SEQUENCE:4} | Pattern pour codes inventaire |
| prefix_bon_commande | BC | Préfixe des numéros de BC |
| prefix_bordereau_livraison | BL | Préfixe des numéros de BL |
| compteur_inventaire_annee | 0 | Compteur pour codes inventaire |

---

## 6. Règles Métier

---

### 6.1 Règles sur les Articles
| ID | Règle | Contrainte |
|----|-------|------------|
| R-ART-01 | `code_article` doit être unique | Base de données |
| R-ART-02 | `reference_constructeur` unique par `marque_id` | Base de données |
| R-ART-03 | `designation` >= 3 caractères | Validation |
| R-ART-04 | `categorie_equipement_id` requis si type='equipement' | Validation |
| R-ART-05 | `categorie_equipement_id` NULL si type!='equipement' | Validation |
| R-ART-06 | `seuil_alerte` requis si type='consommable' | Validation |
| R-ART-07 | `duree_validite_mois` requis si type='licence' | Validation |
| R-ART-08 | Article référencé dans BC → désactivation à la place de suppression | Service |
| R-ART-09 | `prix_indicatif` >= 0 | Validation |
| R-ART-10 | 0 <= `taux_tva` <= 100 | Validation |

---

### 6.2 Règles sur les Bons de Commande
| ID | Règle | Contrainte |
|----|-------|------------|
| R-BC-01 | `numero_commande` unique | Base de données |
| R-BC-02 | BC doit contenir au moins une ligne | Validation |
| R-BC-03 | `quantite` > 0 pour chaque ligne | Base + Validation |
| R-BC-04 | `prix_unitaire` >= 0 | Validation |
| R-BC-05 | `montant_total` = Σ(quantite × prix_unitaire) | Service |
| R-BC-06 | Modification uniquement en statut 'brouillon' | Service |
| R-BC-07 | Validation uniquement en statut 'brouillon' avec lignes | Service |
| R-BC-08 | Annulation uniquement en statut 'brouillon' ou 'valide' | Service |

---

### 6.3 Règles sur les Bordereaux de Livraison
| ID | Règle | Contrainte |
|----|-------|------------|
| R-BL-01 | BL ne peut être créé que pour BC en statut 'valide' ou 'partiel' | Service |
| R-BL-02 | `ref_bordereau_physique` doit être unique | Base de données |
| R-BL-03 | `quantite_livree` <= `reste_a_livrer` du BC | Service |
| R-BL-04 | Modification uniquement en statut 'brouillon' | Service |
| R-BL-05 | BL doit contenir au moins une ligne | Validation |
| R-BL-06 | `quantite_livree` > 0 | Base + Validation |
| R-BL-07 | Toutes les données du wizard doivent être complètes pour validation | Service |

---

### 6.4 Règles sur les Équipements (Intégration)
| ID | Règle | Contrainte |
|----|-------|------------|
| R-EQP-01 | `numero_serie` doit être unique dans parc_info_equipements | Service |
| R-EQP-02 | `code_inventaire` doit être unique dans parc_info_equipements | Service |
| R-EQP-03 | `numero_serie` obligatoire | Service |
| R-EQP-04 | `code_inventaire` obligatoire | Service |
| R-EQP-05 | Si `code_inventaire` non fourni, génération automatique | Service |

---

## 7. Modèle de Données

---

### 7.1 Tables Principales

```
achat_parametres (id, cle, valeur, description, timestamps)

achat_articles (
  id, code_article, designation, description, type_article,
  reference_constructeur, marque_id, categorie_equipement_id, fournisseur_prefere_id,
  prix_indicatif, unite_mesure, taux_tva, compte_comptable,
  seuil_alerte, stock_actuel, duree_validite_mois, url_fiche_technique, image, actif,
  created_by, updated_by, timestamps, deleted_at
  INDEX: type_article+categorie, actif
  UNIQUE: code_article, (marque_id+reference_constructeur)
)

achat_bons_commande (
  id, numero_commande, fournisseur_id, date_commande, statut,
  montant_total, commentaire, valide_par, date_validation,
  created_by, updated_by, timestamps, deleted_at
  INDEX: numero_commande, statut
)

achat_lignes_commande (
  id, bon_de_commande_id, article_id, quantite, prix_unitaire, quantite_livree, timestamps
  CHECK: quantite > 0
)

achat_bordereaux_livraison (
  id, numero_livraison, bon_de_commande_id, date_livraison,
  ref_bordereau_physique, statut, commentaire,
  created_by, updated_by, timestamps, deleted_at
  INDEX: numero_livraison, ref_bordereau_physique, statut
  UNIQUE: numero_livraison, ref_bordereau_physique
)

achat_lignes_livraison (
  id, bordereau_livraison_id, article_id, quantite_livree, timestamps
  CHECK: quantite_livree > 0
)

achat_wizard_data (
  id, bordereau_livraison_id, article_id, unites_data, attributs_communs, completed, timestamps
)

achat_documents (
  id, nom, fichier_path, taille, type_mime, notes,
  documentable_id, documentable_type,
  created_by, updated_by, timestamps, deleted_at
)
```

---

## 8. Flux et Workflows

---

### 8.1 Workflow Principal: Du BC au BL Validé

```
1. CRÉATION DU BON DE COMMANDE (Acheteur)
   - Sélectionner fournisseur
   - Ajouter lignes (articles, quantités, prix)
   - BC créé en statut 'brouillon'
   - Numéro généré automatiquement

2. VALIDATION DU BC (Validateur)
   - Vérifier lignes
   - Confirmer validation
   - BC passe en statut 'valide'
   - valide_par et date_validation enregistrés

3. CRÉATION DU BL (Magasinier)
   - Sélectionner BC (valide/partiel)
   - Saisir date livraison
   - Saisir ref_bordereau_physique
   - Ajouter lignes avec quantités livrées
   - BL créé en statut 'brouillon'

4. VALIDATION DU BL (Magasinier)
   - Lancer wizard
   - Pour chaque équipement/licence:
     * Saisie des informations spécifiques
     * Sauvegarde temporaire possible
   - Validation finale
   - Pour chaque ligne:
     * Création équipement/licence dans ParcInfo
     * Mise à jour stock pour consommables
   - Mise à jour BC: 'livré' si tout livré, 'partiel' sinon
   - BL passe en statut 'valide'
```

---

### 8.2 Workflow Alternatif: Validation Directe des Consommables

```
1. Magasinier crée le BL
2. Lance le wizard
3. Système détecte que toutes les lignes sont des consommables
4. Système saute l'étape de saisie des unités
5. Système:
   - Met à jour le stock de chaque article
   - Crée/met à jour les consommables dans ParcInfo
   - Crée les mouvements de stock
   - Met à jour le statut BC
   - Met à jour le statut BL en 'valide'
6. BL validé, BC mis à jour
```

---

### 8.3 Workflow d'Annulation

**Annulation BC**:
- Pré-conditions: BC en 'brouillon' OU 'valide'
- Action: Mise à jour statut = 'annule'
- Résultat: BC annulé (terminal)

**Suppression BL**:
- Pré-conditions: BL en 'brouillon'
- Action: Soft delete
- Résultat: BL supprimé

---

## 9. Interfaces et API

---

### 9.1 Routes Web

#### Articles
- GET `/achat/articles` - Liste
- POST `/achat/articles` - Créer
- GET `/achat/articles/{article}` - Détails
- PUT/PATCH `/achat/articles/{article}` - Modifier
- DELETE `/achat/articles/{article}` - Supprimer
- POST `/achat/articles/{article}/toggle-actif` - Activer/Désactiver
- POST `/achat/articles/{article}/dupliquer` - Dupliquer

#### Bons de Commande
- GET `/achat/bons-commande` - Liste
- GET `/achat/bons-commande/create` - Formulaire création
- POST `/achat/bons-commande` - Créer
- GET `/achat/bons-commande/{bon_commande}` - Détails
- GET `/achat/bons-commande/{bon_commande}/edit` - Formulaire modification
- PUT/PATCH `/achat/bons-commande/{bon_commande}` - Modifier
- DELETE `/achat/bons-commande/{bon_commande}` - Supprimer
- POST `/achat/bons-commande/{bon_commande}/valider` - Valider
- POST `/achat/bons-commande/{bon_commande}/annuler` - Annuler
- GET `/achat/bons-commande/{bon_commande}/imprimer` - Imprimer

#### Bordereaux de Livraison
- GET `/achat/bordereaux` - Liste
- GET `/achat/bordereaux/create` - Formulaire création
- POST `/achat/bordereaux` - Créer
- GET `/achat/bordereaux/{bordereau}` - Détails
- PUT/PATCH `/achat/bordereaux/{bordereau}` - Modifier
- DELETE `/achat/bordereaux/{bordereau}` - Supprimer
- GET `/achat/bordereaux/{bordereau}/wizard` - Assistant
- POST `/achat/bordereaux/{bordereau}/wizard/{article}/sauvegarder` - Sauvegarder étape
- POST `/achat/bordereaux/{bordereau}/wizard/valider` - Valider
- GET `/achat/bordereaux/{bordereau}/imprimer` - Imprimer
- GET `/achat/bons-commande/{bon_commande}/lignes-a-livrer` - Lignes à livrer

#### Autres
- GET `/achat` - Dashboard
- GET `/achat/stocks` - Stocks
- GET `/achat/statistiques` - Statistiques
- GET `/achat/statistiques/data` - Données rapports
- GET `/achat/statistiques/pdf` - Export PDF
- POST `/achat/documents` - Ajouter document
- GET `/achat/documents/{document}/telecharger` - Télécharger
- DELETE `/achat/documents/{document}` - Supprimer

---

### 9.2 Format des Réponses API

**Succès**:
```json
{
  "success": true,
  "message": "Opération réussie",
  "data": { ... },
  "redirect": "url"
}
```

**Erreur**:
```json
{
  "success": false,
  "message": "Description de l'erreur",
  "errors": {"champ": ["Erreur"]}
}
```

---

## 10. Sécurité et Permissions

---

### 10.1 Liste des Permissions

| Permission | Description |
|------------|-------------|
| `achat.dashboard.view` | Voir le tableau de bord |
| `achat.articles.view` | Voir les articles |
| `achat.articles.create` | Créer des articles |
| `achat.articles.edit` | Modifier les articles |
| `achat.articles.delete` | Supprimer les articles |
| `achat.bons_commande.view` | Voir les bons de commande |
| `achat.bons_commande.create` | Créer des bons de commande |
| `achat.bons_commande.edit` | Modifier les bons de commande (brouillon) |
| `achat.bons_commande.valider` | Valider les bons de commande |
| `achat.bons_commande.annuler` | Annuler les bons de commande |
| `achat.bordereaux.view` | Voir les bordereaux de livraison |
| `achat.bordereaux.create` | Créer des bordereaux |
| `achat.bordereaux.edit` | Modifier les bordereaux (brouillon) |
| `achat.bordereaux.valider` | Valider les bordereaux via wizard |
| `achat.stocks.view` | Voir les stocks consommables |
| `achat.rapports.view` | Voir les rapports |
| `achat.rapports.export` | Exporter les rapports |

### 10.2 Rôles Prédéfinis

**Acheteur**: achat.dashboard.view, achat.articles.*, achat.bons_commande.view/create/edit, achat.bordereaux.view, achat.stocks.view, achat.licences.view, achat.rapports.view

**Validateur Achat**: achat.dashboard.view, achat.articles.view, achat.bons_commande.*, achat.bordereaux.view, achat.rapports.*

**Magasinier**: achat.dashboard.view, achat.articles.view, achat.bons_commande.view, achat.bordereaux.*, achat.stocks.*, achat.licences.view

---

### 10.3 Middleware et Autorisation

- **Middleware**: `auth`, `verified` pour toutes les routes
- **Autorisation**: `$this->authorize('permission')` dans les contrôleurs
- **Vues**: `@can('permission')` pour afficher/masquer des éléments

---

## 11. Exigences Non Fonctionnelles

---

### 11.1 Performance
| Requête | Temps Max | Optimisation |
|---------|-----------|--------------|
| Liste articles (1000+) | < 500ms | Pagination, indexes |
| Liste BC (1000+) | < 500ms | Pagination, indexes |
| Création BC | < 1s | Transaction unique |
| Validation BL | < 2s | Transactions imbriquées |
| Export PDF | < 3s | Génération asynchrone possible |

### 11.2 Compatibilité
- PHP: 8.1+
- Laravel: 10.x
- Base de données: MySQL 8.0 / PostgreSQL 14
- Navigateurs: Chrome 100+, Firefox 100+, Safari 15+
- JavaScript: ES6

### 11.3 Stockage
| Type | Volume Estimé | Rétention |
|------|---------------|-----------|
| Articles | 10 000 | Illimitée |
| Bons de Commande | 100 000 | 10 ans |
| Bordereaux | 200 000 | 10 ans |
| Documents | 1 GB | 10 ans |

### 11.4 Audit et Traçabilité
- Champs `created_by`, `updated_by` sur toutes les tables principales
- Soft deletes (`deleted_at`)
- Historique dans `parc_info_historique_changements` pour les équipements
- Laravel Logs pour les événements système

---

## 12. Annexes

---

### 12.1 Configuration Technique

**Variables d'Environnement**:
```bash
ACHAT_CODE_INVENTAIRE_PATTERN=INV-{YYYY}-{SEQUENCE:4}
ACHAT_PREFIX_BC=BC
ACHAT_PREFIX_BL=BL
```

**Publication**:
```bash
php artisan vendor:publish --tag=achat-config
php artisan vendor:publish --tag=achat-module-views
```

---

### 12.2 Structure des Fichiers

```
Modules/Achat/
├── app/
│   ├── Events/ (4 événements)
│   ├── Http/
│   │   ├── Controllers/ (6 contrôleurs)
│   │   └── Requests/ (6 requêtes)
│   ├── Listeners/ (5 listeners)
│   ├── Models/ (7 modèles + 1 trait)
│   ├── Providers/ (3 providers)
│   └── Services/ (6 services)
├── config/ (2 fichiers)
├── database/
│   ├── migrations/ (7 migrations)
│   └── seeders/ (4 seeders)
├── resources/
│   ├── assets/ (JS, SCSS)
│   └── views/ (15+ vues Blade)
├── routes/ (web.php, api.php)
├── tests/ (1 test feature)
└── composer.json, module.json, package.json, vite.config.js
```

---

### 12.3 Bonnes Pratiques

- **Nommage**: Préfixe `achat_` pour les tables, PascalCase pour les classes
- **Architecture**: SOLID, DRY, Separation of Concerns, Dependency Injection
- **Transactions**: Toutes les opérations critiques dans des transactions DB
- **Gestion des erreurs**: Exceptions pour les erreurs métier, validation via FormRequest

---

### 12.4 Historique

| Version | Date | Modifications |
|---------|------|---------------|
| 1.0 | 11/07/2026 | Version initiale complète |

---

### 12.5 Références
- [Documentation Laravel](https://laravel.com/docs)
- [ParcInfo - Documentation Technique](../../README.md)
- [GEMINI.md](../../GEMINI.md)

---

**Fin du Document**  
*Généré par Mistral Vibe - Co-Authored-By: Mistral Vibe <vibe@mistral.ai>*
