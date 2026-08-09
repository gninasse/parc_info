# API Inter-Modules — Contrat de référence

> **Version** : 1.0 — 08/08/2026 · **Statut** : à valider (prérequis PRQ-06 du chantier Achat)
> **Objet** : contrat unique des échanges entre les modules **Catalogue**, **Stock**, **Achat** et **ParcInfo**. Ce document consolide et remplace les sections éparses : SFD Catalogue §2.5, SFD Stock §2.6, SFD Achat §2.6, `RACCORDEMENT_Achat_Stock.md` §5. En cas de divergence, **le présent document fait foi** ; il s'amende, il ne se contourne pas.
> **Gouvernance** : toute évolution d'un contrat = amendement de ce document + mise à jour des **tests de snapshot** du module fournisseur + revue du ou des modules consommateurs. Les snapshots figés en CI **sont** le contrat exécutable.

---

## 1. Principes communs

### 1.1 Deux mécanismes, deux usages

| Mécanisme | Usage | Propriétés |
|---|---|---|
| **API HTTP JSON** (routes `…/api/…`) | **Lectures** : recherches, fiches compactes, restes à livrer, historiques | Permission dédiée `{module}.api.view` · réponses bornées · sans état |
| **Services internes transactionnels** (appel de classe PHP) | **Écritures inter-modules** : intégration d'une réception, contre-passation, création de licences | Appelés **dans la transaction** de l'opération appelante · atomiques · idempotents · jamais exposés en HTTP |

Règle structurante (leçon AN-08) : **aucun événement asynchrone, aucun listener** pour les écritures inter-modules — l'intégration est synchrone et transactionnelle, ou elle n'est pas.

### 1.2 Engagements de tout endpoint HTTP

1. **Permission** : `catalogue.api.view` / `stock.api.view` / `achat.api.view` contrôlée serveur (middleware), 403 nominative sinon ;
2. **Bornage** : toute liste est limitée (50 par défaut, `limit` ≤ 100) ; au-delà, le consommateur affine (`q`) ;
3. **Portabilité** : `LIKE` uniquement, aucune fonction SQL propriétaire ;
4. **Stabilité** : réponses via API Resources ; champs jamais retirés ni renommés sans amendement (ajouts autorisés) ;
5. **Erreurs** : 403 / 404 / 422 (`{ "message": …, "errors": { champ: [...] } }`) — **jamais** d'exception brute ni de 500 silencieux ;
6. **Neutralité** : aucune écriture, aucun effet de bord (les GET sont rejouables à l'infini) ;
7. **Format** : montants en `string` décimale (`"650000.00"`), dates `YYYY-MM-DD`, horodatages ISO 8601, identifiants entiers.

### 1.3 Engagements de tout service interne

Signature typée et documentée ici · exécution **dans la transaction appelante** (l'échec du service fait échouer l'opération appelante, rien n'est écrit nulle part) · **idempotence** par clé naturelle (`entree_id`, `mouvement_id`, jeton) · toute exécution journalisée dans les deux modules avec référence croisée · 422 sémantique remontée à l'appelant sous forme d'exception dédiée (`AchatReceptionException` → mappée champ par champ par l'écran Stock).

### 1.4 Matrice des flux

| Fournisseur → Consommateur | Contrats | Mécanisme |
|---|---|---|
| Catalogue → Achat, Stock, ParcInfo | §2 (articles, fournisseurs) | HTTP |
| Stock → Achat | §3.1–3.2 (disponibilité, entrées liées) | HTTP |
| Achat → Stock | §4.1–4.2 (BC à livrer, lignes) ; §4.5–4.6 (historique prix, cumul — aussi consommés en interne par Achat) | HTTP |
| Stock → Achat | §5.1–5.2 (intégration de réception, contre-passation) | **Service interne** |
| Achat → ParcInfo | §5.3 (création de licences) | **Service interne** |

---

## 2. Ce que le module CATALOGUE expose (HTTP — permission `catalogue.api.view`)

### 2.1 `GET /catalogue/api/articles` — recherche bornée

Paramètres : `q` (code ou nom, LIKE), `nature` (`equipement|consommable|piece|licence|prestation`*), `categorie_id`, `fournisseur_prefere_id` (tri de pertinence : ses articles d'abord), `actifs=1` (défaut), `limit`.
*(\* `prestation` disponible dès PRQ-02.)*

```json
{ "data": [ {
  "id": 12, "code": "EQP-00001", "designation": "Ordinateur portable Dell Latitude 3540",
  "nature": "equipement", "unite": "unité", "est_stockable": true,
  "prix_indicatif": "650000.00", "taux_tva": "18.00",
  "categorie_equipement_id": 3, "logiciel_id": null,
  "fournisseur_principal": { "id": 4, "nom": "Sonabel-Info" },
  "actif": true } ],
  "meta": { "count": 1, "limited": false } }
```

Consommé par : M-01/M-02 (Achat), modale article (Stock), écrans ParcInfo. Garantie : `taux_tva` toujours renseigné (défaut 18.00) ; `logiciel_id` **non null** exigé par Achat pour commander une licence (contrôle consommateur, garde C11).

### 2.2 `GET /catalogue/api/articles/{id}` — fiche compacte

Mêmes champs, sous la clé `data`. 404 si inexistant (les inactifs restent lisibles : `actif=false`).

**`compte_comptable`** *(livré par P0-B)* : l'imputation de l'article, **nullable** et de **format libre** en v1 — le plan comptable de l'établissement n'est pas arrêté dans l'application, et imposer un format reviendrait à choisir à la place du service financier. Il est exposé par `§2.1` comme par `§2.2`, et alimentera l'état par imputation d'Achat (D-24).

### 2.3 `GET /catalogue/api/fournisseurs` — recherche bornée

`q`, `actifs`, `limit` → `{ id, nom, contact, telephone, email, actif, cree_le }`. Le champ `cree_le` alimente le signal « fournisseur récent » d'Achat (UX4-03).

### 2.4 `GET /catalogue/api/articles/{id}/journal-prix` *(livré par P0-C)*

Historique des modifications du **prix indicatif**, lu dans le journal du Catalogue :

```json
{ "article_id": 12, "code": "CONS-00001", "prix_indicatif": "52000.00",
  "fenetre_mois": 12,
  "data": [ { "date": "2026-07-12", "ancien": "645000.00",
              "nouveau": "820000.00", "par": "R. Ouédraogo" } ] }
```

Du plus récent au plus ancien, borné à **12 mois et 50 entrées**. Alimente la décomposition du prix (PO-01) et le signal « référence modifiée < 30 j » (A14).

**Ce que l'endpoint ne rend PAS** : le journal d'un article contient tous ses changements (catégorie, fournisseur, désactivation). Seules les modifications de `prix_indicatif` sont servies — exposer le reste donnerait, à qui détient `catalogue.api.view`, un historique qu'il n'a pas demandé, et noierait le signal dans le bruit.

Cet endpoint est le seul endroit où se voit une manœuvre simple : relever le prix indicatif juste avant de commander fait disparaître l'écart de prix affiché à l'acheteur.

---

## 3. Ce que le module STOCK expose (HTTP — permission `stock.api.view`)

### 3.1 `GET /stock/api/articles/{id}/disponibilite`

`{ "article_id": 7, "stock_total": 42, "par_magasin": [ { "magasin_id": 1, "nom": "Magasin CHU-YO", "quantite": 30 } ], "seuil": 10, "sous_seuil": false }` — affichage informatif dans les écrans Achat (aucune décision d'Achat ne s'appuie dessus : le stock appartient à Stock, RGC-08).

### 3.2 Entrées liées à un bon de commande

Les bons d'entrée **liés à un BC**, pour la section « En cours côté magasin » (UX-12) :

> **Écart assumé entre ce contrat et le code** — cette lecture était spécifiée en endpoint HTTP (`GET /stock/api/entrees/liees`). Elle est implémentée en **lecture directe** de `stock_entrees` par `Achat\Services\ReceptionsBonCommande`, pour la même raison que `RechercheBonCommande` : la fiche d'un bon ne doit pas dépendre du chargement d'un module voisin, ni d'un aller-retour HTTP, pour afficher SES données. La forme des données ci-dessous reste le contrat ; l'endpoint sera ajouté le jour où un consommateur tiers en aura besoin.

```json
{ "data": [ {
  "id": 58, "libelle": "Brouillon #58", "statut": "REFERENCEMENT",
  "cree_par": "Salamata K.", "cree_le": "2026-08-20T08:12:00Z",
  "magasin": "Magasin CHU-YO",
  "progression": { "saisies": 7, "attendues": 10 },
  "lignes": [ { "article_id": 12, "quantite": 10 } ] } ] }
```

Engagement consommateur (Achat) : affichage **informatif avec badge « non intégré »**, dégradation partielle si indisponible (jamais de page blanche, les compteurs Achat font foi).

Depuis **BR-03**, chaque entrée liée expose aussi son **dossier documentaire** :

```json
{ "data": [ {
  "id": 58, "libelle": "Brouillon #58", "statut": "REFERENCEMENT",
  "bl_joint": true,
  "documents": [ { "id": 12, "type": "bl_fournisseur",
                   "nom_original": "bl-fournisseur.pdf" } ],
  "bordereau_pdf": "/achat/bons-commande/41/receptions/58/bordereau" } ] }
```

- `type` ∈ `bl_fournisseur` | `photo_livraison` | `autre` (BR-01) ;
- `bl_joint` : indicateur **informatif** — une photo de livraison ne vaut pas BL ;
- `bordereau_pdf` : nul tant que le bon d'entrée n'est pas validé (un livreur ne signe pas une intention), et **toujours une route Achat** (voir la note proxy ci-dessous) ;
- une pièce supprimée reste listée (pierre tombale) mais sans lien de téléchargement.

> **Note proxy (IA-16)** — les liens rendus à un utilisateur d'Achat ne pointent **jamais** vers une URL Stock. Ils passent par les routes proxy `/achat/bons-commande/{id}/receptions/{entree}/…`, qui vérifient les permissions d'**Achat** puis relaient le fichier depuis le disque privé de Stock. Un profil Achat sans aucun droit Stock consulte donc les bordereaux de SES commandes ; un profil Stock sans droit Achat n'ouvre pas ces routes. Le rattachement de la pièce au bon consulté est vérifié à chaque appel : la permission seule ne suffit pas.

---

## 3.3 Écarts BL — `stock_entrees.ecarts_bl` *(BR-04)*

Le rapprochement entre ce que le bordereau du fournisseur annonçait et ce qui a été compté. Comme §3.2, il est **lu directement** par Achat (colonne JSON), sans endpoint HTTP :

```json
[ { "article_id": 12, "designation": "Toner 26A",
    "quantite_annoncee_bl": 10, "quantite_comptee": 8,
    "motif": "manquant" } ]
```

`motif` ∈ `manquant` | `endommage_refuse` | `excedent_refuse`. La `designation` est écrite **par le serveur** depuis le catalogue au moment du dépôt : ce qui s'imprime sur la pièce de réclamation ne vient jamais du client.

**Contrat sémantique, non négociable** : ces quantités **n'affectent aucun compteur**. Les reliquats d'Achat ne connaissent que le **compté** (RGC-02 inchangé). L'écart documente une réclamation et alimente le 9e signal (§SFD Achat 7.7), rien d'autre. C'est cette neutralité qui permet au magasinier de le déclarer sans risque, donc de le déclarer.

---

## 4. Ce que le module ACHAT expose (HTTP — permission `achat.api.view`)

### 4.1 `GET /achat/api/bons-commande/a-livrer`

Pour la modale M-02 et le scan QR (Stock). Paramètres : `q` (numéro ou article, LIKE), `fournisseur_id`, `limit`. Ne retourne que `VALIDE` et `PARTIEL`.

```json
{ "data": [ {
  "id": 41, "numero": "BC-2026-0041", "statut": "PARTIEL",
  "fournisseur": { "id": 4, "nom": "Sonabel-Info" },
  "valide_le": "2026-08-13", "lignes_restantes": 1,
  "unites_restantes": 4, "montant_ttc": "10856000.00" } ] }
```

### 4.2 `GET /achat/api/bons-commande/{id}/lignes-a-livrer`

Le contrat de pré-remplissage du bon d'entrée (raccordement §2.3) :

```json
{ "bon_commande": { "id": 41, "numero": "BC-2026-0041", "statut": "PARTIEL",
    "fournisseur_id": 4 },
  "lignes": [ {
    "ligne_id": 101, "article_id": 12, "code": "EQP-00001",
    "designation": "Ordinateur portable Dell Latitude 3540",
    "nature": "equipement", "quantite_commandee": "10.00",
    "quantite_livree": "6.00", "reste_a_livrer": "4.00",
    "prix_unitaire_ht": "830000.00", "taux_tva": "18.00" } ] }
```

422 si le BC n'est ni `VALIDE` ni `PARTIEL` (« Ce bon n'est pas livrable (statut : Brouillon). »). Engagements Stock : quantités plafonnées au `reste_a_livrer` (à la saisie ET revérifiées par le service §5.1), `cout_unitaire` pré-rempli de `prix_unitaire_ht`, fournisseur imposé = `fournisseur_id`.

### 4.3 `GET /achat/api/bons-commande/resoudre?numero=BC-2026-0041`

Résolution d'un numéro scanné (QR) → `{ "id": 41, "numero": …, "statut": … }` ou 404 (« Aucun bon de commande BC-2026-0099 »).

### 4.4 `GET /achat/api/articles/{id}/historique-prix`

La référence non manipulable (A14) — calculée sur les lignes de BC `VALIDE`+ :

```json
{ "article_id": 12,
  "dernier_paye": { "prix": "645000.00", "numero": "BC-2026-0033", "date": "2026-06-12" },
  "moyenne_3_derniers": "648300.00", "nb_bc_references": 7 }
```

`dernier_paye: null` si aucun BC validé ne référence l'article (première commande).

### 4.5 `GET /achat/api/fournisseurs/{id}/cumul-mois`

Pour le Swal de visa (UX2-08) : `{ "fournisseur_id": 4, "mois": "2026-08", "nb_bc_valides": 2, "cumul_ttc": "12400000.00", "premier_bc": false, "fournisseur_cree_le": "2026-08-02" }`.

### 4.6 `GET /achat/api/bons-commande/{id}/receptions`

Vue consolidée (miroir de l'onglet Réceptions, pour d'éventuels écrans tiers) : liste des intégrations (`entree_id`, numéro ENT, date, lignes/quantités) — lecture des compteurs Achat, source de vérité.

Depuis **BR-03/BR-04**, l'onglet Réceptions de la fiche enrichit chaque carte de ce que Stock a déposé : le **bordereau de réception** (route proxy), les **pièces jointes typées**, et les **écarts BL** avec leur détail. Ces informations sont *illustratives* : elles n'entrent dans aucun calcul d'Achat, dont les compteurs restent établis par §5.1 seul.

### 4.7 `POST /achat/api/bons-commande/brouillon-depuis-articles` *(D-21)*

Le seul point d'**écriture** de cette API. Il ferme la boucle « rupture → commande » : le magasinier qui constate un manque ouvre lui-même un brouillon, au lieu de le signaler par téléphone à charge pour l'acheteur de tout ressaisir.

```json
// requête
{ "article_ids": [12, 44], "magasin": "Magasin CHU-YO", "fournisseur_id": 4 }

// réponse
{ "success": true, "message": "Brouillon créé à partir de 2 article(s) en alerte.",
  "data": { "id": 91, "nb_lignes": 2, "fournisseur_id": 4,
            "url": "/achat/bons-commande/91/modifier" } }
```

**Permissions** : `achat.api.view` (le groupe) **et** `achat.bons_commande.store` — ce point d'entrée écrit, lire l'API ne suffit pas.

Trois règles, toutes assumées :

1. il crée un **BROUILLON**, jamais un bon soumis. Le geste part du magasin : il ne saurait engager l'établissement. L'acheteur reprend la main sur un bon qu'il complète, corrige ou supprime ;
2. le **fournisseur n'est jamais deviné**. Un bon s'adresse à un fournisseur unique. Si les articles n'ont pas le même fournisseur préféré, la demande est refusée en **422** avec `motif: "fournisseur_indetermine"` et la liste des fournisseurs concernés — l'appelant pose la question, puis renvoie `fournisseur_id`. Élire un fournisseur au hasard produirait des lignes commandées au mauvais endroit, erreur silencieuse découverte à la livraison ;
3. la **quantité proposée** est `seuil × facteur_reapprovisionnement` (paramètre d'établissement, 2 par défaut), 1 à défaut de seuil. C'est un point de départ : calculer un réapprovisionnement optimal demanderait une consommation historique et des délais fournisseurs dont on ne dispose pas.

La provenance est journalisée (`creation_depuis_alerte_seuil`, avec le magasin et les codes d'articles) : six mois plus tard, on doit pouvoir dire d'où venait un bon — et donc mesurer si la boucle fonctionne.

### 4.8 Routes proxy documentaires *(BR-03 — non publiques)*

| Route | Permission exigée | Réponses |
|---|---|---|
| `GET /achat/bons-commande/{id}/receptions/{entree}/bordereau` | `achat.bons_commande.index` **et** `achat.documents.view` | 200 PDF · 403 sans permission · 404 si l'entrée n'est pas une réception validée de ce bon |
| `GET /achat/bons-commande/{id}/receptions/{entree}/documents/{doc}` | idem | 200 fichier · 403 · 404 hors rattachement · **410** pierre tombale |

Ce ne sont pas des API : elles servent l'interface d'Achat. Elles figurent ici parce qu'elles franchissent une frontière de module, et que ce franchissement est **l'endroit le plus sécurité-sensible** du raccordement (voir IA-16, §6).

---

## 5. Services internes transactionnels

### 5.1 `AchatReceptionService::integrer(StockEntree $entree): ResultatIntegration`

**Appelant** : la transaction de validation du bon d'entrée Stock (étape 4 du workflow `RACCORDEMENT` §3), uniquement si `entree.bon_commande_id` non nul et `nature = livraison`.
**Contrat d'entrée** : entrée avec lignes `{article_id, quantite}` ; toutes les lignes doivent correspondre à des lignes du BC.
**Traitement (dans la transaction appelante)** :
1. BC ∈ {`VALIDE`,`PARTIEL`} sinon `AchatReceptionException` (« bon non livrable ») ;
2. **Verrou** sur les lignes du BC ; re-contrôle `quantite ≤ reste_a_livrer` ligne à ligne — sinon exception portant les erreurs **par ligne** (l'écran Stock les mappe en 422 : « Ligne Latitude 3540 : reste à livrer 4 — pour l'excédent, créez un bon de commande complémentaire ou refusez à la livraison ») ;
3. **Idempotence** : si `entree_id` déjà intégré → retour du résultat initial, **aucun nouvel incrément** ;
4. Incréments `quantite_livree`, recalcul du statut (`PARTIEL`/`LIVRE`), journal Achat (« Réception ENT-2026-0034 (6/10) intégrée ») avec référence croisée.

**Retour** : `{ statut_bc: "PARTIEL", lignes: [{ligne_id, nouvelle_livree, reste}] }` — affiché dans le compte-rendu de validation Stock.

### 5.2 `AchatReceptionService::contrePasser(StockMouvement $contreMouvement): ResultatIntegration`

**Appelant** : la transaction de contre-passation Stock, si le mouvement d'origine appartient à une entrée liée. Décréments symétriques (plancher 0 — jamais négatif), statut recalculé (`LIVRE` → `PARTIEL` possible), journal des deux côtés. Idempotence par `mouvement_id`.

### 5.3 `ParcInfoLicenceService::creerDepuisReception(ReceptionLicences $reception): Collection`

**Appelant** : la transaction de finalisation du wizard Achat (SFD §7.4). Crée N `parc_info_licences` : `logiciel_id` = celui de l'article (jamais de création de logiciel — garde C11), `fournisseur_id` du BC, `cout` = prix figé de la ligne, `cle`/`date_activation`/`date_expiration` du tampon, `reference` = numéro du BC. Unicité des clés revérifiée en base ; violation → exception → rollback intégral côté Achat.

---

## 6. Matrice permissions × consommateurs

| Permission | Attribuée aux rôles | Ouvre |
|---|---|---|
| `catalogue.api.view` | Rôles Achat, Stock, ParcInfo | §2 |
| `stock.api.view` | Rôles Achat | §3 |
| `achat.api.view` | Rôles Stock (Magasinier, Superviseur) | §4.1–4.6 (lecture) |
| `achat.documents.view` + `achat.bons_commande.index` | Rôles Achat | §4.8 (proxy documentaire) |
| `achat.api.view` + `achat.bons_commande.store` | Rôles Stock habilités à commander | §4.7 (brouillon depuis une alerte) |
| *(services internes)* | — sans permission HTTP : sécurité portée par l'opération appelante | §5 |

### IA-16 — la frontière documentaire *(BR-05)*

> **Un document de réception n'est jamais accessible sans la permission du MODULE CONSULTÉ.**

BR-03 a ouvert une porte entre les deux modules : Achat sert des fichiers qui appartiennent à Stock. C'est utile, et c'est le genre de commodité qui devient une faille sans surveillance. La matrice croisée, vérifiée par `Achat\Tests\Feature\InvariantDocumentsReceptionTest` :

| Profil | Proxy Achat (pièce / bordereau) | Routes Stock (pièce / bordereau) |
|---|---|---|
| Acheteur pur (aucun droit Stock) | **200 / 200** — c'est le but | 403 / 403 |
| Acheteur sans `documents.view` | 403 / 403 | 403 / 403 |
| Magasinier pur (aucun droit Achat) | 403 / 403 | **200 / 200** |
| Les deux casquettes | 200 / 200 | 200 / 200 |
| Aucun droit | 403 / 403 | 403 / 403 |

Deux verrous, et le second compte autant que le premier :

1. la **permission du module consulté** — les droits Stock n'ouvrent pas les routes d'Achat, et réciproquement ;
2. le **rattachement** — la pièce doit appartenir à une entrée liée AU bon consulté. Sans lui, un identifiant deviné donnerait accès à tout le magasin, permission en poche. Réponse : **404**, pas 403 — pour l'acheteur, une pièce absente de son dossier n'existe pas, et nous n'apprenons rien à qui sonde.

Les fichiers vivent hors racine web : aucune URL directe n'existe, dans aucun des deux modules.

---

## 7. Tests de contrat (exécutables, CI)

1. **Snapshots** : chaque endpoint HTTP a un test de snapshot dans le module **fournisseur** (structure + types) — casser un snapshot = amendement obligatoire de ce document ;
2. **Consommateurs** : chaque module consommateur teste contre des **fixtures conformes aux snapshots** (pas contre le module réel, sauf tests d'intégration dédiés) ;
3. **Intégration bout en bout** (suite conjointe, bi-SGBD) : liaison M-02 → pré-remplissage §4.2 → validation avec `integrer()` (nominal, plafond, concurrence, rejeu) → contre-passation → licences ;
4. **Dégradation** : Achat avec l'API Stock coupée (§3.2) → onglet dégradé, compteurs exacts ;
5. **Frontière documentaire (IA-16)** : matrice croisée des cinq profils × quatre routes, plus le contrôle de rattachement — `InvariantDocumentsReceptionTest`. Une régression de sécurité doit faire tomber un test dont le nom dit « sécurité » ;
6. **Neutralité des écarts BL (§3.3)** : BL annonçant 10, compté à 8 → la ligne de commande passe à 8 livrées et 12 restantes. Aucun compteur ne connaît le 10.

## 8. Évolutions prévues (hors contrat v1)

`POST` de création de BC pré-rempli depuis une alerte de seuil Stock (« Commander », backlog inter-modules) · exposition du compte comptable (§2.2, activée par PRQ-03) · webhooks comptables (rapprochement factures) · recherche globale multi-modules (Core).

---

*Fin du contrat v1.0. Validation requise : équipes Catalogue, Stock, Achat + MOA. Après validation : figer les snapshots (D-10), puis lancer D-01.*
