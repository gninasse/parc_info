# Spécifications Fonctionnelles Détaillées — Module ParcInfo

Projet : **Parc Info (CHU-YO Keystone)**
Application : Laravel 12 (PHP 8.2) — architecture modulaire `nwidart/laravel-modules`
Module : `Modules/ParcInfo`
Préfixe de routes web : `parc-info/` — Espace de noms des routes : `parc-info.*`

---

## 1. Présentation générale

### 1.1 Objet du module
Le module **ParcInfo** assure la gestion complète du parc informatique et des actifs IT d'un établissement (CHU-YO). Il couvre :

- L'inventaire de tous les équipements informatiques et réseau (poste de travail, serveurs, réseau, téléphonie, périphériques, infrastructure, sécurité).
- Le cycle de vie des équipements : acquisition, mise en service, affectation, changement d'état/statut, réforme, historique.
- La gestion des logiciels et des licences (cycle de vie, affectation, renouvellement, conformité).
- La gestion des consommables (stock, mouvements, alertes de rupture).
- La gestion des fournisseurs, contacts et contrats de maintenance.
- Les bons de répartition (réception et distribution d'équipements avec signature).
- Un moteur de **configuration dynamique** (catégories d'équipements, champs personnalisés, dictionnaires de valeurs).
- Les **analyses** : états paramétrables (≈60 rapports exportables) et statistiques décisionnelles.

### 1.2 Dépendances inter-modules
- **Organisation** : `Direction`, `Service`, `Unite`, `Local`, `Etage`, `Batiment`, `Site`, `PosteTravail`.
- **Grh** : `DossierEmploye` (employés, matricules).
- **Core** : socle applicatif (utilisateurs, layout, permissions).

### 1.3 Briques techniques
- Autorisations : `spatie/laravel-permission` (permissions et rôles `Admin` / `super-admin`).
- Journalisation : `spatie/laravel-activitylog` (traçabilité des opérations d'écriture).
- Exports : `rap2hpoutre/fast-excel` (CSV/XLSX) et `barryvdh/laravel-dompdf` (PDF).
- Front : Blade + Bootstrap 5 + Bootstrap Table + SweetAlert2 (voir `DESIGN.md`).

### 1.4 Principe d'architecture des équipements
Le module repose sur un modèle **hybride** :

- Une **table parente unique** `parc_info_equipements` contenant les attributs communs à tout équipement.
- Un moteur **dynamique** : chaque équipement appartient à une **catégorie** (`CategorieEquipement`) ; les attributs spécifiques sont décrits par des **champs configurables** (`ChampConfig`) et stockés en JSON dans la colonne `champs_valeurs`.
- Des tables de spécialisation historiques (`parc_info_ordinateurs`, `parc_info_serveurs`, etc.) issues de la conception initiale, progressivement remplacées par le mécanisme dynamique (voir commande `MigrateEquipmentToDynamic`).

Un **seul contrôleur générique** (`EquipementDynamiqueController`) gère toutes les catégories d'équipements. Les routes exposent des préfixes métiers (`ordinateurs`, `serveurs`, `imprimantes`, …) qui injectent la `category` correspondante.

---

## 2. Acteurs et rôles

| Acteur | Description |
|---|---|
| **Administrateur DSI (Admin / super-admin)** | Accès complet, configuration dynamique (catégories, champs, dictionnaires), attribution de permissions. |
| **Gestionnaire de parc** | Saisie et gestion des équipements, affectations, bons de répartition, consommables. |
| **Gestionnaire licences/logiciels** | Gestion du catalogue logiciel, licences, fournisseurs, contrats. |
| **Consultant / Analyste** | Consultation, tableaux de bord, états et statistiques, exports. |

Les accès sont contrôlés par des **permissions granulaires** (Spatie). Les permissions des catégories dynamiques sont créées automatiquement lors de la création d'une catégorie (`parcinfo.{code_pluriel}.{index|store|update|destroy}`) et affectées aux rôles `Admin` et `super-admin`.

---

## 3. Modèle de données (principales entités)

### 3.1 Équipement (`parc_info_equipements`)
Attributs communs : `code_inventaire` (unique, auto-généré `INV-{année}-{séquence}`), `numero_serie` (unique), `categorie_id`, `marque_id`, `modele`, `date_acquisition`, `date_mise_en_service`, `valeur_achat`, `duree_vie_probable` (années), `date_fin_garantie`, `statut`, `etat`, `tags` (array), `ref_bordereau`, rattachement organisationnel dénormalisé (`direction_id`, `service_id`, `unite_id`, `local_id`), `champs_valeurs` (JSON dynamique).

**Statuts** (7) : `en_stock_magasin`, `en_stock_dsi`, `en_stock`, `en_service`, `en_reparation`, `perdu`, `reforme`.
**États physiques** (4) : `bon`, `passable`, `mauvais`, `avarie`.

Règles automatiques (modèle) :
- Le rattachement organisationnel (`direction_id`/`service_id`/`unite_id`) est **synchronisé automatiquement** à partir de l'affectation active lors de chaque sauvegarde.
- Toutes les modifications sont journalisées (activity log).

### 3.2 Configuration dynamique
- **`CategorieEquipement`** : `code` (unique, `^[a-z0-9\-]+$`), `libelle`, `icone`.
- **`ChampConfig`** : champ configurable rattaché à une catégorie — `code`, `libelle`, `type_champ` (`text|number|select|boolean|date|textarea`), `source_options` (`DICT:<code>` ou tableau JSON), `regles_validation`, `nom_panel`, `ordre_affichage`, visibilité (`afficher_dans_modal`, `afficher_dans_show`, `afficher_dans_liste`), `ordre_colonne_liste`.
- **`Dictionnaire`** / **`DictionnaireValeur`** : listes de valeurs de référence réutilisables. Codes système non supprimables : `type_cpu`, `type_ram`, `type_disque`, `type_os`, `type_imprimante`, `type_mobile`.

### 3.3 Affectations et historique
- **`AffectationEquipement`** : `code`, `date_debut`, `date_fin`, `statut` (bool actif), `type_affectation` (`TEMPORAIRE|PERMANENTE`), `type_cible` (`EMPLOYE|POSTE|LOCAL|DIRECTION|SERVICE|UNITE`), cibles (`dossier_employe_id`, `poste_travail_id`, `local_id`) et rattachement (`niveau_rattachement`, `direction_id`, `service_id`, `unite_id`). **Une seule affectation active** par équipement.
- **`HistoriqueChangement`** : traçabilité par équipement (`type_changement` = `STATUT|ETAT|AFFECTATION|MOUVEMENT|TECHNIQUE`, ancien/nouveau statut/état, `motif`, `reference_document`, utilisateur, date).

### 3.4 Logiciels & licences
- **`Logiciel`** : `code`, `nom`, `editeur_id`, `type_licence_id`, `est_actif`.
- **`Editeur`**, **`TypeLicence`** : référentiels.
- **`Licence`** : `logiciel_id`, `cle_licence`, `numero_contrat`, `contrat_maintenance_id`, `type_activation` (`volume|concurrent|subscription|free`), `modele_licencing` (`device|user|concurrent|named`), `nombre_postes_accordes`, `nombre_postes_utilises`, dates (`acquisition`, `activation`, `expiration`, `renouvellement`), coûts (`cout_unitaire`, `cout_total`, `devise`), `fournisseur_id`, `contact_support_id`, `statut` (`actif|expire|en_renouvellement|suspendu`), `actif` (bool).
- **`AffectationLicence`** : rattachement d'une licence à un équipement ou un employé (`actif`, `date_affectation`, `date_fin_affectation`).
- **`DocumentLicence`** : documents rattachés (nom, type, chemin, date, notes).

### 3.5 Consommables
- **`Consommable`** : `code`, `nom`, `type_consommable_id`, `marque_id`, `modele_reference`, `compatible_equipements` (JSON), `fournisseur_principal_id`, `cout_unitaire`, `quantite_stock_actuel`, `quantite_stock_min`, `quantite_stock_max`, `stock_reserve_maintenance`, `date_dernier_approvisionnement`, `est_actif`.
- **`MouvementConsommable`** : mouvements de stock (`Consommation`/`Achat`, quantité, date, utilisateur, cibles équipement/employé/service/unité, prix, référence commande).
- **`AffectationConsommable`** : consommable fourni à un équipement (quantité, date, remplacement prévu).
- **`TypeConsommable`** : référentiel.

### 3.6 Fournisseurs
- **`Fournisseur`** : `code`, `nom`, `type`, coordonnées, `conditions_paiement`, `delai_livraison`, `fiabilite_score`, `est_actif`.
- **`Contact`** : contacts rattachés à un fournisseur.
- **`ContratMaintenance`** : `reference`, `nom`, `fournisseur_id`, dates, `cout`, `est_actif`.

### 3.7 Bons de répartition
- **`BonRepartition`** : `numero_bon`, `fournisseur_id`, `date_bon`, statut dérivé (`en_cours`/`cloture`).
- **`LigneBonRepartition`** : équipement, destination (`type_cible`, direction/service), `nom_receptionniste`, `date_livraison`, `est_signe`, `date_signature`, `affectation_id`, `observation`.

---

## 4. Catégories d'équipements gérées

Catégories socle (« hardcodées ») avec routes dédiées : **Ordinateur, Écran, Unité Centrale, Serveur (physique), Serveur Virtuel, Mobile/Tablette, Switch, Routeur, WiFi, Pare-feu, Onduleur, Baie/Rack, Brassage, Caméra IP, Imprimante, Scanner, Téléphonie, Terminal IP**.

Chaque catégorie dispose d'un jeu de **champs dynamiques préconfigurés** (voir `ParcInfoConfigSeeder`) regroupés en panneaux (ex. ordinateur : `Configuration`, `Performances`, `Stockage`, `Système & Licences`, `Sécurité & BIOS`, `Réseau`).

**Catégories dynamiques** : toute catégorie créée par un administrateur (hors liste socle) obtient automatiquement, au runtime, un jeu complet de routes CRUD + recherche + affectation (`informatique/{pluriel}`).

---

## 5. Spécifications fonctionnelles par domaine

### 5.1 Tableau de bord (`DashboardController`, `ParcInfoController@dashboard`)
**Route** : `GET /parc-info/dashboard` — Permission : `parcinfo.dashboard.view`.

Le tableau de bord présente :
- **Compteurs globaux** : total équipements, en service, en réparation, hors service (perdu + réformé), en stock.
- **Ventilation par catégorie** : postes de travail, serveurs (physiques + virtuels), imprimantes, scanners, téléphones, caméras, mobiles, équipements réseau (switch + routeur + pare-feu + wifi + terminaux IP), licences actives, consommables actifs.
- **Alertes** : garanties expirées, renouvellements prévus (garantie ≤ 90 j), licences expirées / en alerte / surexploitées, consommables en rupture.
- **Flux récents** : 5 derniers équipements créés (icône, libellé de site, badge statut, lien de détail).
- **Graphiques** : répartition par type (6 segments), répartition par statut, répartition par état physique.

**Recherche globale** : `GET /parc-info/search/equipements` (JSON) — recherche sur code, modèle, numéro de série, marque ; filtres `type` et `statut`.

### 5.2 Gestion des équipements (`EquipementDynamiqueController`)

#### 5.2.1 Liste (`index` / `getData`)
- **`index`** affiche la page liste d'une catégorie : filtres (site, direction, statut), colonnes dynamiques (`afficher_dans_liste`), et modal de saisie (`afficher_dans_modal`).
- **`getData`** (JSON, pagination Bootstrap Table) : chargement avec marque + affectation active complète ; filtres `statut`, `site_id`, `direction_id` ; recherche plein texte sur code, numéro de série, modèle, marque et **valeurs dynamiques** (`champs_valeurs::text`) ; tri et pagination serveur.

#### 5.2.2 Création (`store`)
- Génère `code_inventaire` automatiquement si absent.
- Validation statique (unicité code/série, dates, valeur, statut/état) **+ validation dynamique** issue des `ChampConfig` de la catégorie.
- Transaction : création de l'équipement, normalisation des `tags`, écriture des `champs_valeurs`.
- **Affectation initiale optionnelle** : si `type_cible` fourni et `skip_affectation` faux, création d'une `AffectationEquipement` (PERMANENTE, active), résolution du rattachement, historisation `AFFECTATION` (et `MOUVEMENT` si local).

#### 5.2.3 Consultation (`show` / `showJson`)
- Fiche complète : caractéristiques, champs dynamiques groupés par panneau, affectation active, historique complet, affectations de licences, lignes de bons, licences disponibles.
- Pattern « In-Place Edit » : champs désactivés par défaut, activés via bouton « Modifier ».

#### 5.2.4 Modification (`update`)
- Mise à jour des caractéristiques et `champs_valeurs` (hors affectation).
- Un changement de `local_id` génère un historique `MOUVEMENT` (ancien → nouvel emplacement).

#### 5.2.5 Changement de statut / d'état (`updateStatut` / `updateEtat`)
- **Statut** : motif obligatoire ; si passage vers un statut `en_stock*` avec affectation active, celle-ci est **fermée automatiquement** ; historisation `STATUT`.
- **État** : motif obligatoire ; historisation `ETAT`.

#### 5.2.6 Affectation / Désaffectation (`storeAffectation` / `desaffecter`)
- **Affectation** : ferme l'affectation active existante, crée la nouvelle affectation (cible EMPLOYE/POSTE/LOCAL/DIRECTION/SERVICE/UNITE), **mise en service automatique** si l'équipement était en stock ; historisation `AFFECTATION` (+ `MOUVEMENT` si local).
- **Désaffectation** : motif obligatoire ; ferme l'affectation, remet l'équipement `en_stock` et `local_id = null` ; triple historisation (`AFFECTATION`, `STATUT`, `MOUVEMENT`).

#### 5.2.7 Suppression (`destroy`)
- Suppression de l'équipement (cascade sur affectations/historique).

#### 5.2.8 Ajouts rapides & recherches
- `storeMarque` (unicité libellé), `storeDictionnaireValeur` (ajout valeur dictionnaire depuis les formulaires).
- Recherches AJAX : `searchEmployes`, `searchPostes`, `searchLocaux` (autocomplétion, limite 20).

#### 5.2.9 Impression d'étiquettes
- Étiquette unitaire (`imprimerEtiquette`).
- Étiquettes multiples (`imprimerEtiquettesSelectionnees`, sélection par IDs).
- **Centre d'impression** (`centreImpression` + `getEquipementsData`) : sélection multi-critères (catégorie, statut, site, direction, recherche) pour impression en lot.

### 5.3 Logiciels (`LogicielController`)
CRUD catalogue logiciel : liste filtrée (éditeur, type de licence, recherche), création/édition, activation/désactivation (`toggleStatus`), suppression **bloquée si des licences sont rattachées**. Quick-add éditeur. Consultation avec panneau de création de licence intégré.

### 5.4 Licences (`LicenceController`)
Cycle de vie complet :
- **Liste + statistiques** : total actives, expirant sous 30 j, expirées, surexploitées, coût annuel total.
- **Création / édition / activation / suppression** (suppression bloquée si affectations actives).
- **Affectation** (`affecter`) : à un équipement (`device`) ou un employé (`user`) ; contrôle de capacité (`postes_utilises < postes_accordes`, sauf si accordés = 0) ; incrément du compteur d'utilisation.
- **Désaffectation** (`desaffecter`) : clôture de l'affectation, décrément du compteur.
- **Renouvellement** (`renouveler`) : nouvelle date d'expiration (postérieure à l'actuelle), remise en statut `actif`, ajout du coût de renouvellement au coût total.
- **Indicateurs** (accesseurs) : `taux_utilisation`, `statut_validite` (`VALIDE`/`ALERTE`/`EXPIREE`), `disponibilites`.
- Quick-add fournisseur et contrat depuis le formulaire de licence.

**Traitement automatisé** (`Jobs\VerifierExpirationLicences`) :
- Licences expirant sous 30 j (et non expirées) → statut `en_alerte` + log.
- Licences expirées → statut `expire` + `actif = false` + log.

### 5.5 Consommables (`ConsommableController`)
- **Liste + statistiques** : total, en rupture, valeur totale du stock, mouvements du mois.
- Filtres : type, statut de stock (`rupture` ≤ min, `alerte` ≤ min × 1,5).
- **Création / édition / activation / suppression** (suppression bloquée si historique de mouvements).
- **Consommation** (`consommer`) : sortie de stock ; contrôle de stock suffisant ; création d'un mouvement `Consommation` ; affectation à un équipement optionnelle ; décrément du stock.
- **Approvisionnement** (`approvisionner`) : entrée de stock ; mouvement `Achat` ; mise à jour de la date d'approvisionnement ; incrément du stock.
- Quick-add type de consommable.
- **Service `GestionStockService`** : détection des ruptures, recommandations de réapprovisionnement (quantité, coût prévisionnel, urgence), renouvellements prévus (≤ 7 j).

### 5.6 Fournisseurs, contacts, contrats
- **Fournisseurs** (`FournisseurController`) : CRUD, activation/désactivation, suppression bloquée si licences rattachées.
- **Contacts** : CRUD imbriqué au fournisseur (nom **ou** prénom requis).
- **Contrats de maintenance** (`ContratMaintenanceController`) : CRUD, référence unique, dates cohérentes, suppression bloquée si licences rattachées.

### 5.7 Bons de répartition (`BonRepartitionController`)
Workflow de réception/distribution :
1. **Création** du bon (fournisseur, date, numéro).
2. **Ajout de lignes** (`addLigne`) : uniquement des équipements en stock, non déjà présents dans un bon en attente.
3. **Modification/suppression de lignes** possible tant que non signées.
4. **Signature de ligne** (`signerLigne`) = réception effective : clôture de l'affectation active existante, création d'une affectation permanente (destination direction/service), passage de l'équipement `en_service`, double historisation (`STATUT` + `AFFECTATION`), ligne marquée signée.
5. **Statut du bon** : `cloture` lorsque toutes les lignes sont signées, sinon `en_cours`.
6. **Impression PDF** (`imprimer`, A4 paysage).
7. **Suppression** du bon interdite s'il contient au moins une ligne signée.

### 5.8 Référentiels
CRUD standardisés (liste filtrée + data JSON + modal) pour : Types CPU, Types Disque, Types OS, Types RAM, Marques, Types Imprimante, Types Mobile, Types Licence, Types Consommable, Éditeurs.

**Catégories d'équipement** (`CategorieController`) :
- CRUD catégories ; création génère automatiquement 4 permissions Spatie et les attribue à `Admin`/`super-admin` ; suppression bloquée si des équipements existent (sinon nettoyage des champs + permissions).
- Gestion imbriquée des **champs dynamiques** (`ChampConfig`) : code (`^[a-z0-9_]+$`, unique par catégorie), type, source d'options, règles de validation, panneau, ordre, visibilités.

**Dictionnaires** (`DictionnaireController`) :
- CRUD dictionnaires (code `^[a-z0-9_]+$`) ; codes système protégés (non supprimables/non renommables sur le code).
- Gestion des **valeurs** (unicité par dictionnaire) ; suppression d'un dictionnaire bloquée s'il contient des valeurs.

### 5.9 Analyses

#### 5.9.1 États paramétrables (`Analyse\EtatController`) — permission `parc-info.analyse.etats.view`
Moteur de rapports piloté par `report_type` (≈60 types) avec filtres (direction, service, statut, état, recherche), pagination, et **export** CSV / Excel (FastExcel) / PDF (DomPDF paysage). Familles de rapports :
- Parc global, par statut, par état.
- Garantie (jours restants / expirée), fin de vie théorique.
- Historique des changements.
- Affectation organisationnelle : non affectés, par direction/service/unité/site/local/employé/poste, structures vides, distribution organisationnelle.
- Fiches techniques par type d'équipement (ordinateurs, serveurs physiques/virtuels, imprimantes, scanners, réseau, téléphones IP, mobiles, caméras, infrastructure).
- Licences : utilisation, expirées, sous/sur-utilisées, par employé/équipement, documents, logiciels par éditeur.
- Consommables : état des stocks, sous seuil, affectations, remplacements en retard, mouvements, mouvements par structure.
- Contrats & fournisseurs : contrats actifs/expirant, équipements couverts/non couverts, fournisseurs actifs.
- Utilisateurs & accès : rôles/permissions, employés sans compte, comptes sans dossier employé.

#### 5.9.2 Statistiques décisionnelles (`Analyse\StatistiquesController`) — permission `parc-info.analyse.statistiques.view`
Agrégats (JSON) en 8 blocs :
1. Volumétrie (par type, statut, état, top marques).
2. Valeur résiduelle & vétusté (amortissement linéaire), taux de disponibilité.
3. Parc par organisation (direction/service, taux de couverture, pires directions).
4. Affectations (actives/terminées, durées moyennes, plus réaffectés).
5. Licences & conformité (coûts, sièges, taux, par modèle/statut/éditeur).
6. Consommables (valeur stock, ruptures, coût par service, achats).
7. Maintenance & incidents (réparations, durée moyenne, récurrences, réformes).
8. Finances (contrats, consommables, licences, budget estimé, ratio équipement/employé).

---

## 6. Règles de gestion transversales

| # | Règle |
|---|---|
| RG-01 | Un équipement possède **au plus une affectation active** à un instant donné. |
| RG-02 | Toute création/changement/désaffectation ferme l'affectation active précédente. |
| RG-03 | L'affectation d'un équipement en stock le passe automatiquement `en_service`. |
| RG-04 | La désaffectation (ou passage en `en_stock*` alors qu'une affectation est active) clôture l'affectation et remet en stock. |
| RG-05 | Le rattachement organisationnel de l'équipement est dérivé de l'affectation active (synchronisation automatique). |
| RG-06 | Toute mutation (statut, état, affectation, mouvement) est historisée avec motif et utilisateur. |
| RG-07 | Les champs spécifiques d'un équipement sont pilotés par la configuration dynamique de sa catégorie (validation + affichage). |
| RG-08 | Le `code_inventaire` est unique et auto-généré (`INV-{année}-{séquence}`) ; le `numero_serie` est unique. |
| RG-09 | Une licence ne peut être affectée au-delà de son nombre de postes accordés (sauf accordés = 0). |
| RG-10 | Les compteurs de postes de licence sont maintenus par les affectations/désaffectations. |
| RG-11 | Une sortie de consommable est refusée si le stock est insuffisant. |
| RG-12 | Suppressions protégées : logiciel (licences liées), fournisseur/contrat (licences liées), catégorie (équipements liés), dictionnaire (valeurs liées), consommable (mouvements liés), bon (ligne signée), licence (affectations actives). |
| RG-13 | Les dictionnaires et catégories « système » (codes réservés) sont protégés contre la suppression/renommage. |
| RG-14 | Une ligne de bon signée est immuable ; sa signature déclenche mise en service + affectation. |
| RG-15 | Le job d'expiration bascule automatiquement les licences en alerte/expirées. |

---

## 7. Sécurité et autorisations
- Toutes les routes web sont protégées par le middleware `auth`.
- Les permissions sont définies dans `config/permissions.php` (dashboard, un jeu par catégorie socle, référentiels, analyses) et complétées dynamiquement à la création de catégories.
- Les contrôleurs d'analyse et de référentiels appliquent des middlewares `permission:` par action.

## 8. Interfaces & ergonomie (rappel `DESIGN.md`)
- Style **plat et minimaliste**, dense, orienté données ; Bootstrap 5, police *Source Sans 3*.
- Listes : carte de filtres → barre d'outils (boutons icônes) → tableau Bootstrap Table.
- Formulaires en modales (`modal-lg`), fiches en « In-Place Edit ».
- Confirmations destructives via SweetAlert2.
- Contrainte d'identité CHU-YO : pas de croix médicale.

## 9. Points d'attention / dette technique identifiée
- La vérification de permission de `DashboardController@index` est actuellement **désactivée** (abort commenté).
- Coexistence des tables de spécialisation historiques et du modèle dynamique (`champs_valeurs`) ; migration en cours via `MigrateEquipmentToDynamic`.
- L'affectation de licence est possible si `nombre_postes_accordes = 0` (le contrôle de capacité est alors contourné).
- Le job `VerifierExpirationLicences` utilise le statut `en_alerte` alors que l'énumération de statut prévoit `en_renouvellement`/`suspendu` (à harmoniser).

---

*Document généré par analyse du code source du module `Modules/ParcInfo`.*
