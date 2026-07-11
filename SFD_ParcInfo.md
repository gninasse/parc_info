# Spécifications Fonctionnelles Détaillées (SFD) — Module ParcInfo

Ce document présente l'analyse complète et les Spécifications Fonctionnelles Détaillées (SFD) du module **ParcInfo** (Gestion du Parc Informatique et Asset Management) au sein de la plateforme hospitalière.

---

## 1. Introduction et Objectifs du Module

Le module **ParcInfo** est une solution complète de gestion des actifs informatiques (IT Asset Management - ITAM). Il est conçu pour répertorier, suivre et optimiser l'ensemble des ressources matérielles, logicielles et consommables de l'établissement (par exemple, le CHU Yalgado Ouédraogo).

### Objectifs Principaux :
*   **Inventaire Centralisé :** Répertorier tous les matériels informatiques (ordinateurs, serveurs, routeurs, switches, imprimantes, etc.) avec leurs spécifications techniques précises.
*   **Gestion du Cycle de Vie :** Suivre chaque actif depuis son acquisition (bordereau de livraison, garantie) jusqu'à sa réforme, en passant par ses phases d'utilisation et de réparation.
*   **Affectation Rigoureuse :** Associer les matériels à des collaborateurs, à des postes de travail physiques ou directement à des locaux, tout en maintenant l'historique complet des mouvements.
*   **Conformité Logicielle :** Gérer le catalogue de logiciels, suivre l'utilisation et l'expiration des licences, et calculer le retour sur investissement (ROI) des contrats de maintenance.
*   **Suivi des Consommables :** Gérer les stocks de consommables (toners, câbles, etc.) avec des mécanismes d'alerte sur seuil minimal et de réapprovisionnement automatique.
*   **Traçabilité & Audit :** Enregistrer chaque changement d'état ou mouvement physique/organisationnel dans un historique immuable pour répondre aux exigences d'audit.

---

## 2. Architecture Globale et Modèle de Données

Le module s'appuie sur une architecture de base de données robuste intégrant l'héritage de table (Parent-Enfant), des liaisons hiérarchiques avec le module d'organisation physique et administrative, et un système d'audit.

### 2.1. Le Pattern d'Héritage de Table (Parent-Child)
Afin d'éviter la duplication de colonnes communes tout en permettant des spécifications techniques propres à chaque type d'équipement, le module implémente une architecture parent-enfant :
*   **Table Parente :** `parc_info_equipements` contient les attributs d'inventaire généraux et administratifs.
*   **Tables Enfants (Spécialisées) :** `parc_info_ordinateurs`, `parc_info_serveurs`, `parc_info_mobiles`, etc., partagent la clé primaire `equipement_id` qui est également une clé étrangère pointant vers `parc_info_equipements.id` (avec suppression en cascade).

Toutes les opérations d'écriture sur ces entités s'exécutent au sein d'une transaction de base de données (`DB::transaction()`) pour garantir la cohérence absolue entre la table parente et la table enfant spécialisée.

### 2.2. Schéma Relationnel Global

```
                               ┌───────────────────────────────┐
                               │     parc_info_marques         │
                               └──────────────┬────────────────┘
                                              │ 1
                                              │ N
┌────────────────────────┐ N  1┌──────────────▼────────────────┐
│ organisation_directions├────┬┤     parc_info_equipements     │
└────────────────────────┘    ││        (Table Parente)        │
                              │└──────────────┬────────────────┘
┌────────────────────────┐ N  │               │ 1
│  organisation_services ├────┤               ├─────────────────────────────────────────┐
└────────────────────────┘    │               │ 1 (héritage 1:1 via PK/FK)              │ 1
                              │               │                                         │
┌────────────────────────┐ N  │      ┌────────▼──────────────┐                ┌─────────▼──────────────┐
│   organisation_unites  ├────┘      │ parc_info_ordinateurs │                │  parc_info_ordinateurs │
└────────────────────────┘           └───────────────────────┘                └────────────────────────┘
                                              │ 1                                       │ 1
                                              │ N                                       │ N
                               ┌──────────────▼────────────────┐              ┌─────────▼──────────────┐
                               │parc_info_affectation_equipements             │parc_info_historique_...│
                               └───────────────────────────────┘              └────────────────────────┘
```

---

## 3. Détail des Entités de Matériels (Les 10 Catégories)

Chaque catégorie de matériel informatique possède un modèle Eloquent distinct et un formulaire de saisie dédié. Les caractéristiques de ces équipements sont présentées ci-dessous :

### 3.1. Ordinateurs (`parc_info_ordinateurs`)
Représente les postes de travail clients (portables, fixes et stations de travail).
*   **Type PC :** Portable, Fixe, Workstation.
*   **Processeur :** Type de CPU (clé étrangère) et modèle textuel (ex: *Intel Core i7-1185G7*).
*   **Mémoire Vive (RAM) :** Capacité en Go et type de RAM (DDR3, DDR4, DDR5).
*   **Stockage :** Capacité en Go et type de disque (SSD NVMe, SSD SATA, HDD).
*   **Système d'Exploitation :** Type d'OS (clé étrangère).
*   **Licences Système intégrées :** Type de licence Windows (OEM, CLE, AUCUNE) et clé, licence Office et clé.
*   **Sécurité physique & BIOS :** Support TPM 2.0 (booléen), Secure Boot (booléen), version BIOS, version UEFI.
*   **Identité Réseau :** Nom d'hôte, compte administrateur local, domaine/groupe de travail, adresse MAC Wi-Fi, adresse MAC Ethernet.
*   **Spécificité portable :** Nombre de cycles de batterie.

### 3.2. Serveurs Physiques (`parc_info_serveurs`)
Gère les serveurs installés en baie de brassage/salle informatique.
*   **Rôle du Serveur :** Application, Base de données, Fichiers, Web, Active Directory (AD/DC).
*   **Processeur :** Type de CPU, nombre de processeurs physiques, nombre total de cœurs.
*   **Mémoire Vive (RAM) :** Capacité en Go et type de RAM.
*   **Stockage :** Capacité en Go et type de disque.
*   **Réseau :** Nom d'hôte, domaine, adresse IP, adresse MAC.
*   **Virtualisation :** Hyperviseur installé (VMware ESXi, Hyper-V, Proxmox).
*   **Emplacement physique en baie :** Clé étrangère vers le serveur hôte (auto-référence), position U de départ et de fin dans la baie.

### 3.3. Serveurs Virtuels (`parc_info_serveurs_virtuels` / Séparé)
Machines virtuelles (VM) hébergées sur les serveurs physiques.
*   **Liaison physique :** Clé étrangère pointant vers le serveur hôte physique (`serveur_hote_id`).
*   **Allocation ressources :** Nombre de vCPUs affectés, RAM en Go affectée, stockage virtuel (Go) alloué.
*   **Système d'Exploitation :** OS installé et version.
*   **Réseau :** Adresse IP virtuelle, adresse MAC virtuelle, VLAN associé.

### 3.4. Équipements Mobiles (`parc_info_mobiles`)
Tablettes, smartphones et lecteurs de codes-barres utilisés dans les services de soin.
*   **Type de mobile :** Clé étrangère vers le référentiel des types mobiles (Tablette, Smartphone, Lecteur code-barre).
*   **Sécurité & Téléphonie :** Double emplacement SIM (IMEI 1 & IMEI 2), numéro de téléphone associé.
*   **Système d'Exploitation :** Version de l'OS (ex: *Android 13*).
*   **Gestion d'entreprise :** Statut MDM (Enrôlé, Non enrôlé) pour l'administration de sécurité centralisée.
*   **Caractéristiques physiques :** Capacité de la batterie (mAh), état de l'écran, présence d'une coque de protection (booléen).

### 3.5. Imprimantes (`parc_info_imprimantes`)
Imprimantes réseau ou locales et photocopieurs multifonctions.
*   **Technologie :** Laser, Jet d'encre, Matricielle.
*   **Capacités d'impression :** Option Couleur (booléen), Option Multifonction (booléen).
*   **Fonctions supportées :** Liste textuelle ou tags (Scan, Print, Copy, Fax).
*   **Réseau :** Adresse IP fixe, communauté SNMP de supervision.

### 3.6. Équipements Réseau (`parc_info_equipements_reseaux` : Switches, Routeurs, WiFi, Pare-feux)
Actifs de l'infrastructure réseau de l'hôpital.
*   **Type d'équipement :** Switch, Routeur, Point d'accès, Firewall (géré via un contrôleur unifié).
*   **Caractéristiques physiques :** Nombre de ports physiques, vitesse maximale (Mbps), alimentation PoE (Power over Ethernet - booléen).
*   **Logiciel & Firmware :** Version du firmware actif.
*   **Réseau & IP :** Adresse IP d'administration, masque de sous-réseau, passerelle, VLAN de management, communauté SNMP.
*   **Gestion :** Switch/Équipement manageable (booléen).
*   **Emplacement en baie :** Position de départ et de fin en unité Rack (U).

### 3.7. Téléphonie IP (`parc_info_telephones`)
Postes téléphoniques fixes et combinés sans fil DECT.
*   **Technologie :** Clé IP (booléen) distinguant la VOIP de la téléphonie classique.
*   **Configuration :** Numéro d'extension unique (ex: *4102*).
*   **Protocole supporté :** SIP, H.323, SCCP (Cisco).
*   **Réseau :** Adresse MAC Ethernet unique, adresse IP affectée par DHCP ou statique.
*   **Extension matérielle :** Nombre de modules d'extension de touches associés.

### 3.8. Infrastructures Physiques : Baies & Racks (`parc_info_infrastructures` / Racks)
Conteneurs de serveurs et d'équipements réseaux.
*   **Capacité :** Hauteur totale mesurée en unités standard (ex: *42U*).
*   **Alimentation :** Présence de PDU (Power Distribution Unit) redondants, nombre de prises disponibles.

### 3.9. Onduleurs (`parc_info_infrastructures` / Onduleurs)
Dispositifs de secours électrique pour la continuité des soins.
*   **Puissance nominale :** Mesurée en Voltampères (VA).
*   **Autonomie théorique :** Mesurée en minutes lors d'une charge moyenne.
*   **Maintenance préventive :** Date de dernier remplacement des batteries chimiques.

### 3.10. Scanners et Caméras IP (`parc_info_scanners` et `parc_info_cameras_ip`)
Matériels spécialisés pour l'imagerie/numérisation et la sécurité des bâtiments.
*   **Scanners :** Résolution optique maximale (DPI), format maximal pris en charge (A4, A3), option Recto-Verso automatique (booléen), présence d'un chargeur de documents automatique (booléen), type de capteur (CIS, CCD).
*   **Caméras IP :** Résolution maximale (mégapixels), technologie de vision nocturne (IR), protocole de flux (RTSP, ONVIF), stockage interne ou liaison vers un serveur d'enregistrement (NVR).

---

## 4. Règles de Gestion et Processus Métier Clés

### 4.1. Génération Automatique et Immutabilité du Code d'Inventaire
Le code d'inventaire (`code_inventaire`) est l'identifiant unique de référence de l'actif au sein de l'établissement.
1.  **Format Strict :** `{PREFIX}-{ANNEE}-{SEQUENCE}`
    *   `{PREFIX}` est déterminé en fonction du type de matériel :
        *   `ORD` pour ordinateurs
        *   `SER` pour serveurs
        *   `MOB` pour mobiles/tablettes
        *   `IMP` pour imprimantes
        *   `NET` pour équipements réseau
        *   `TEL` pour téléphones
        *   `INF` pour infrastructures
        *   `SCA` pour scanners
        *   `CAM` pour caméras
    *   `{ANNEE}` correspond à l'année d'acquisition sur 4 chiffres (ex: `2026`).
    *   `{SEQUENCE}` est un numéro séquentiel unique incrémenté automatiquement sur 4 chiffres (ex: `0001`, `0002`).
2.  **Immutabilité :** Une fois généré en base de données par le backend, ce code est **strictement non modifiable**. Dans toutes les interfaces utilisateur de mise à jour, le champ du code d'inventaire doit obligatoirement être affiché en lecture seule (`readonly` ou `disabled`).

### 4.2. Cycle de Vie et Gestion des Statuts
Chaque équipement progresse à travers plusieurs états définis dans l'énumération du champ `statut` de la table `parc_info_equipements` :
*   `en_stock` : L'équipement est physiquement stocké dans le dépôt informatique, prêt à être déployé.
*   `en_service` : L'équipement est activement affecté et installé sur le terrain.
*   `en_reparation` : L'équipement présente une anomalie et est confié au service technique ou à un prestataire externe.
*   `perdu` : L'équipement est déclaré perdu, volé ou introuvable.
*   `reforme` : L'équipement est obsolète ou endommagé de façon irréversible, retiré définitivement du parc.

#### Règles de transition d'état (Statut/État) :
*   Tout changement de statut ou d'état physique (`bon`, `passable`, `mauvais`, `avarie`) exige la saisie obligatoire d'un **motif explicite** par l'utilisateur.
*   Ce motif, accompagné des anciens et nouveaux états/statuts, de l'identité de l'utilisateur connecté et de la date, est consigné en base dans la table `parc_info_historique_changements`.

### 4.3. Système d'Affectations Dynamique et Traçabilité
L'affectation d'un équipement informatique lie celui-ci à une cible terrain. Le module supporte trois types de cibles d'affectation (`type_cible`) :
1.  **EMPLOYE :** Liaison directe avec la fiche d'un agent de l'hôpital (`grh_dossiers_employes`).
2.  **POSTE :** Liaison à un poste de travail virtuel ou physique configuré dans la structure organisationnelle (`organisation_postes_travail`).
3.  **LOCAL :** Liaison physique à une pièce ou bureau de l'établissement (`organisation_locaux`).

#### Gestion des Niveaux de Rattachement Hiérarchique (Spatio-Administratif) :
Pour chaque affectation active, le système enregistre également le rattachement administratif de l'actif informatique aux trois niveaux configurables du module `Organisation` :
*   **Direction** (`direction_id`)
*   **Service** (`service_id`)
*   **Unité** (`unite_id`)

Ces champs sont automatiquement propagés et synchronisés sur la table parente de l'équipement (`parc_info_equipements`) afin de permettre des filtres rapides de recherche et des statistiques globales.

#### Flux de Désaffectation ("Unassign") :
L'action de désaffectation rompt le lien actif entre l'équipement et sa cible. Elle obéit aux règles strictes suivantes :
1.  **Fermeture de l'affectation active :** Le système met à jour l'enregistrement dans `parc_info_affectation_equipements` en basculant le booléen `statut` à `false` (inactif) et en enregistrant la `date_fin` à l'instant présent.
2.  **Retour au stock :** Le statut de l'équipement dans la table `parc_info_equipements` repasse automatiquement à `en_stock`.
3.  **Log d'historique :** Un mouvement de type `AFFECTATION` est inséré dans l'historique (`parc_info_historique_changements`) indiquant la fin de l'affectation avec la mention du motif saisi par le technicien.

---

## 5. Module Logiciels & Licences (Software Asset Management)

Le module intègre un moteur de gestion de conformité et d'allocation des licences logicielles.

### 5.1. Gestion du Catalogue de Logiciels (`parc_info_logiciels`)
Enregistre les applications autorisées au sein de l'hôpital avec leur éditeur (géré via un référentiel global), leur version et leur statut global de validité.

### 5.2. Gestion des Licences d'Utilisation (`parc_info_licences`)
Une licence représente un droit d'usage acheté auprès d'un fournisseur pour un logiciel précis.
*   **Types d'activation :** Volume (KMS/MAK), Concurrent, Abonnement annuel (SaaS), Gratuit/OpenSource.
*   **Modèles de tarification/licenciement :** Par poste physique (Device), Par utilisateur nommé (User), Par accès concurrent (Concurrent), Par compte nominatif (Named).
*   **Nombre de postes accordés vs utilisés :** Le système calcule dynamiquement le nombre d'affectations actives de la licence et le compare à la limite autorisée (`nombre_postes_accordes`).

### 5.3. Moteur d'Optimisation des Licences (Services de Calcul)
Le fichier `LicenceService.php` contient les algorithmes d'analyse et d'optimisation suivants :

#### 1. Détection de la Surexploitation (Alerte Non-Conformité) :
Identifie les licences dont le nombre d'installations ou d'affectations actives dépasse les droits contractuels acquis :
$$\text{nombre\_postes\_utilises} > \text{nombre\_postes\_accordes}$$
Ces licences sont immédiatement classées comme non conformes, ce qui présente un risque légal et financier pour l'établissement.

#### 2. Calcul du Retour sur Investissement (ROI) :
Calcule le coût d'acquisition réel par utilisateur ou par poste pour un logiciel donné en analysant l'ensemble de ses licences actives :
$$\text{Coût par utilisateur} = \frac{\sum(\text{Coût Total des Licences Actives})}{\text{Nombre d'Affectations Actives}}$$
Cette métrique permet de détecter les logiciels onéreux sous-exploités.

#### 3. Optimisation et Recommandations d'Allocation :
Le système propose automatiquement deux types d'actions préventives :
*   **Alerte Sous-exploitation :** Si le taux d'utilisation de la licence ($\frac{\text{postes utilisés}}{\text{postes accordés}}$) est inférieur à **30%** pour un contrat de plus de 5 postes, le système recommande de réduire le nombre de postes lors du prochain renouvellement ou de résilier la souscription.
*   **Alerte Surexploitation :** Si le taux d'utilisation dépasse **90%**, le système recommande une extension immédiate pour éviter un blocage ou une non-conformité.

#### 4. Job Planifié d'Expiration :
Un traitement d'arrière-plan (`VerifierExpirationLicences.php`) s'exécute quotidiennement pour :
*   Vérifier les dates d'échéance des licences.
*   Basculer automatiquement le statut d'une licence de `actif` à `expire` si sa date d'échéance est dépassée.
*   Générer des alertes de renouvellement pour les licences arrivant à échéance dans les 30, 60 ou 90 jours.

---

## 6. Module Consommables & Gestion des Stocks

Ce sous-module permet d'éviter l'interruption des services de soin en assurant la disponibilité des consommables informatiques indispensables (cartouches d'encre, toners d'imagerie, rubans thermiques pour étiquettes de prélèvement, etc.).

### 6.1. Fiche Consommable (`parc_info_consommables`)
*   **Stock Actuel :** Quantité physique présente en réserve.
*   **Stock Minimal (Alerte) :** Seuil critique déclenchant une alerte visuelle et une demande de réapprovisionnement.
*   **Stock Maximal :** Quantité cible pour éviter le surstockage inutile.
*   **Stock Réservé :** Quantité mise de côté spécifiquement pour les opérations de maintenance curative préventive planifiées.

### 6.2. Mouvements de Stock et Consommation
Tout changement de stock donne lieu à l'écriture d'un mouvement de stock (`MouvementConsommable`) :
*   **Approvisionnement (Entrée) :** Augmente le stock actuel, enregistre le prix unitaire d'achat, le fournisseur et met à jour la `date_dernier_approvisionnement`.
*   **Consommation (Sortie) :** Diminue le stock actuel. Le formulaire de sortie exige la spécification de l'équipement informatique de destination, ainsi que le service ou l'unité d'affectation pour assurer une imputation analytique précise des coûts.

### 6.3. Analyse de Réserve et Algorithmes (`GestionStockService.php`)
*   **Détection des Ruptures de Stock :** Liste les consommables dont la quantité en stock actuel est inférieure ou égale au seuil de stock minimal (`quantite_stock_min`).
*   **Recommandations d'Achat :** Calcule automatiquement la quantité optimale à commander pour chaque référence en rupture ou alerte :
$$\text{Quantité à commander} = \text{quantite\_stock\_max} - \text{quantite\_stock\_actuel}$$
Il associe à cette recommandation une estimation du coût prévisionnel (Quantité $\times$ Coût Unitaire) et un niveau d'urgence (CRITIQUE si le stock actuel est de 0, NORMAL sinon).
*   **Renouvellements Prévus :** Détecte à l'avance les consommables à remplacer à court terme en analysant les dates théoriques de prochain changement enregistrées sur les imprimantes ou serveurs (ex: *prochain changement de filtre à air ou kit de fusion sous 7 jours*).

---

## 7. Interfaces et Expérience Utilisateur (UI Patterns)

Le module ParcInfo applique des standards d'interface rigoureux basés sur Bootstrap 5, Tailwind CSS et des composants dynamiques JavaScript.

### 7.1. Le Pattern "Wizard Modal" à 3 Étapes
La création de nouveaux équipements (ordinateurs, serveurs, téléphones, etc.) s'effectue obligatoirement au sein d'un assistant d'enregistrement ("Wizard") intégré dans une modale, découpé en 3 étapes distinctes :

```
┌────────────────────────────────────────────────────────────────────────┐
│                        AJOUTER UN ÉQUIPEMENT                           │
├────────────────────────────────────────────────────────────────────────┤
│  (1) STATUT  ======[ En cours ]====== (2) TECHNIQUE ===== (3) AFFECTATION │
├────────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  Sélectionnez l'état initial de l'équipement :                         │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │ [Radio] En Stock (Disponible en réserve technique)               │  │
│  ├──────────────────────────────────────────────────────────────────┤  │
│  │ [Radio] En Service (Saisie technique + Affectation requise)       │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                        │
├────────────────────────────────────────────────────────────────────────┤
│ [Annuler]                                        [Précédent] [Suivant] │
└────────────────────────────────────────────────────────────────────────┘
```

*   **Étape 1 : Statut de l'actif**
    *   Choix de l'état initial de l'actif dans le stock hospitalier via de grandes cartes interactives (En Stock, En Service, En Réparation).
*   **Étape 2 : Informations Techniques**
    *   Saisie des caractéristiques matérielles (Numéro de série unique, Marque, Modèle, CPU, RAM, etc.).
    *   Le champ **Code d'inventaire** est affiché en lecture seule avec la mention *"Généré automatiquement"*.
*   **Étape 3 : Affectation Initiale (Optionnelle si "En Stock")**
    *   Choix de la cible : Employé, Poste de travail ou Local.
    *   Recherche dynamique AJAX des fiches cibles.
    *   Affichage d'un panneau récapitulatif avec les détails de la cible sélectionnée (ex: *Nom, Matricule et Unité pour un employé*).

### 7.2. Tableaux de Données Réactifs (Bootstrap Table)
Toutes les vues d'index de matériel exploitent le composant **Bootstrap Table** configuré en mode serveur (`data-side-pagination="server"`) :
*   **Boutons de Barre d'Outils réactifs (Toolbar) :** Les boutons d'action de modification (Éditer) et de destruction (Supprimer) situés dans la barre d'outils au-dessus du tableau sont **désactivés par défaut**. Ils s'activent de manière réactive uniquement lorsqu'une ligne du tableau est cochée par l'utilisateur.
*   **Pagination & Recherche :** La pagination et la recherche par mot-clé s'exécutent côté serveur via des requêtes AJAX renvoyées par la méthode `getData` du contrôleur correspondant.

### 7.3. Sélecteurs Cascades et Saisie Rapide ("QuickAdd")
Pour simplifier l'expérience de saisie des fiches d'équipement, deux fonctionnalités ergonomiques majeures sont implémentées :
1.  **Saisie en ligne instantanée ("QuickAdd") :** À côté de chaque sélecteur de clé étrangère (Marque, Modèle de CPU, Type de RAM, Éditeur, Fournisseur, etc.), un bouton **"+"** est disponible. Son clic ouvre une modale secondaire d'insertion rapide par SweetAlert2. Une fois le nouvel enregistrement validé par AJAX, le sélecteur d'origine est automatiquement rafraîchi avec la nouvelle option sélectionnée, évitant ainsi à l'utilisateur de quitter son formulaire en cours.
2.  **Sélecteurs Cascades Locaux :** Lors de la configuration géographique d'une affectation ou d'un emplacement de poste de travail, les sélecteurs de localisation se mettent à jour dynamiquement via AJAX selon la hiérarchie :
$$\text{Site} \longrightarrow \text{Bâtiment} \longrightarrow \text{Étage} \longrightarrow \text{Local}$$
Le système utilise des endpoints AJAX dédiés (ex: `unites.by-service` et `locaux.by-etage`) pour charger uniquement les données cohérentes avec le niveau supérieur choisi.

### 7.4. Dialogues de Confirmation de Sécurité (SweetAlert2)
Toutes les actions destructives (Suppression d'un matériel, suspension d'un contrat) ou modifiant l'état de fonctionnement d'un actif (mise en réparation, réforme) déclenchent l'apparition d'un dialogue de confirmation SweetAlert2 (`Swal.fire`).
*   Pour les changements de statut, la modale SweetAlert2 intègre une zone de saisie de texte obligatoire demandant à l'utilisateur d'indiquer le **motif** du changement de statut de l'équipement, assurant l'alimentation fiable du journal d'historique.

---

## 8. Exigences Techniques, Sécurité et Audit

### 8.1. Intégration Ziggy (Routage JavaScript)
Afin de préserver la maintenabilité de l'application et d'éviter les erreurs de liens lors des mises en production, **aucun fichier JavaScript du module ne doit contenir d'URL codée en dur**.
*   Toutes les requêtes asynchrones (AJAX `fetch` ou `axios`) doivent utiliser le helper Ziggy pour générer des routes dynamiques nommées :
```javascript
let url = route('parc-info.ordinateurs.data');
```

### 8.2. Droits d'Accès et Rôles (Permissions Spatie)
La sécurité interne s'appuie sur le package `spatie/laravel-permission`. Des permissions granulaires régulent l'affichage des informations et les actions d'écriture du module :
*   `parc_info.dashboard.view` : Accès au tableau de bord général et aux statistiques.
*   `parc_info.equipement.view` : Consultation des fiches de matériel.
*   `parc_info.equipement.create` : Création de nouveaux matériels via l'assistant.
*   `parc_info.equipement.edit` : Modification des informations techniques ou administratives.
*   `parc_info.equipement.delete` : Suppression définitive d'un actif (généralement restreint aux administrateurs).
*   `parc_info.licence.manage` : Gestion complète du catalogue de logiciels et de l'attribution des licences.
*   `parc_info.stock.manage` : Droits de gestion des consommables et de saisie des entrées/sorties de stock.

### 8.3. Journal d'Audit et Activités (Spatie Activitylog)
Outre l'historique métier consigné dans `parc_info_historique_changements`, toutes les opérations majeures de création, de mise à jour et de suppression sur les modèles Eloquent du module implémentent le trait `LogsActivity` de `spatie/laravel-activitylog`.
*   Le système enregistre automatiquement les valeurs d'attributs modifiées (uniquement les champs modifiés via `logOnlyDirty()`) pour retracer l'origine de toute modification en cas d'incident technique ou d'anomalie d'inventaire.

---

## 9. Conclusion

Le module **ParcInfo** offre une solution robuste, hautement intégrée et adaptée aux exigences rigoureuses du milieu hospitalier. Grâce à son architecture parent-enfant garantissant l'intégrité technique, son système d'affectation flexible connecté aux ressources humaines, et son suivi de conformité logicielle analytique, il constitue le garant d'une gestion transparente et optimisée des ressources informatiques de l'établissement.
