# Module Stock — Plan de tests

> **Date** : 02/08/2026 · **Document lié** : `SFD_Stock.md` **(v2.0 — catalogue unifié)** · **Statut** : version 2.0. **Substitution transverse** : toute mention « consommable/pièce » dans les tests désigne désormais un `article_id` → `catalogue_articles` ; le `CHECK` testé est `article_id` XOR `equipement_id` ; le module Catalogue (et son propre plan de tests, SFD_Catalogue §9.4) est un prérequis des suites Stock. **v2.3 (D13/D14/D16)** : statuts `EN_INTEGRATION` → `REFERENCEMENT` (entrées) et nouvelle phase `POINTAGE` (sorties) ; le test de resynchronisation du tampon (v2.1) est **supprimé**, remplacé par I16 (verrouillage + retour au brouillon). **v2.2 (D15)** : « document » se lit désormais selon son type — `stock_entrees`/`stock_sorties`/`stock_transferts`/`stock_inventaires` avec leurs tables de lignes ; le `CHECK` des mouvements devient « exactement une FK document (entrée/sortie/transfert/inventaire), ou aucune pour un contre-mouvement autoporté » — à tester explicitement ; la numérotation et son test de collision (I8) s'entendent **par table**.
> **Objectif** : ne pas reproduire la dette de tests des modules existants (1 seul test dans Grh, dossiers Feature vides). Le module Stock est celui où l'absence de tests coûterait le plus cher : il manipule des quantités, des transactions multi-modules et un journal réputé inaltérable.

## 0. Organisation et conventions

- Emplacement : `Modules/Stock/tests/Unit/` et `Modules/Stock/tests/Feature/` ; factories pour tous les modèles (`MagasinFactory`, `PieceFactory`, `DocumentFactory`, `MouvementFactory`) + réutilisation des factories amont (Employe, Equipement…).
- Base de tests : `RefreshDatabase`. **Contrainte de portabilité** : la suite doit passer sur SQLite *et* PostgreSQL — c'est le test indirect de l'engagement « aucune fonction PostgreSQL-only » (SFD §9.4). Les contraintes `CHECK` étant portées par la base, prévoir une exécution CI sur PostgreSQL au minimum.
- Nommage : `test_{comportement}_{condition}` en français technique, un invariant = au moins un test dédié nommé `invariant_…`.
- Cible de couverture : 100 % des invariants (§1), 100 % des routes pour la matrice de permissions (§5), tous les workflows nominaux + cas d'erreur listés.

## 1. Invariants (tests prioritaires — bloquants pour toute mise en production)

| # | Invariant (SFD) | Test |
|---|---|---|
| I1 | Niveau jamais négatif (§7.0) | Sortie de 10 sur un niveau de 5 → 422, niveau inchangé, aucun mouvement créé |
| I2 | Niveau jamais négatif **en concurrence** (§6.1) | Deux sorties parallèles de 6 sur un niveau de 10 (transactions concurrentes simulées) → une seule passe, niveau final 4 |
| I3 | Niveau = Σ mouvements (D9) | Séquence aléatoire (property-based ou boucle seedée) de réceptions/sorties/transferts/ajustements → recalcul = colonne, et la commande artisan de contrôle renvoie 0 écart |
| I4 | Transfert atomique (§7.5) | Échec forcé sur l'entrée cible (contrainte violée) → rollback complet : ni sortie source, ni document |
| I5 | Sortie d'équipement atomique (D8) | Échec forcé de la création d'affectation ParcInfo → rollback : équipement toujours « en stock », toujours rattaché, aucun mouvement |
| I6 *(révisé v2.1)* | Immutabilité **après validation** | `PUT`/`DELETE` sur un document `VALIDE` → 409 ; autorisés sur `BROUILLON`/`EN_INTEGRATION` (D12) ; aucun UPDATE/DELETE applicatif sur `stock_mouvements` (inventaire des routes) |
| I7 | Contre-mouvement borné (§7.8) | Contre-mouvement d'une entrée alors que le stock a déjà été sorti → 422 si niveau résultant < 0 |
| I8 | Numérotation sans collision (§7.0) | Créations concurrentes de documents → numéros distincts, séquence continue par année et par type |
| I9 | Idempotence de validation (§4.4) | Double POST avec le même jeton → un seul document créé, second appel renvoyant le premier |
| I10 | Blocage pendant inventaire (§7.7) | Inventaire `EN_COURS` sur un périmètre → sortie sur un article du périmètre → 422 explicite ; article hors périmètre → OK |
| I11 *(v2.0)* | Sérialisation atomique (D10) | Réception de 5 unités d'un article-modèle dont 1 n° de série en doublon → rollback complet : 0 fiche ParcInfo, 0 mouvement, 0 rattachement |
| I12 *(v2.0)* | Garde `est_stockable` | POST forgé d'une réception/sortie/transfert sur un article `licence` → 422 ; aucun niveau créé |
| I13 *(v2.1)* | Brouillon sans effet (D12) | Création + modifications + suppression d'un brouillon complet : 0 mouvement, 0 niveau, 0 fiche ParcInfo, 0 numéro consommé |
| I14 *(v2.1)* | Validation conditionnée au tampon (D13) | Tampon incomplet ou n° de série en doublon (tampon ou `parc_info_equipements`) → validation 422, statut inchangé ; complété → validation OK, fiches créées, tampon purgé |
| I15 *(v2.1)* | Disponible re-contrôlé | Brouillon de sortie (qté 5, dispo 8) ; le stock tombe à 3 avant validation → validation 422 listant la ligne, aucun mouvement |
| I16 *(v2.3)* | Verrouillage des quantités (D16) | En `RÉFÉRENCEMENT`/`POINTAGE` : PUT sur lignes/quantités → 409 ; « retour au brouillon » → tampon purgé, quantités déverrouillées, action journalisée ; en `BROUILLON`, PUT libre |
| I17 *(v2.3)* | Pointage cohérent (D14) | Pointer une unité d'un autre magasin, déjà « en service », ou déjà pointée dans une autre sortie non validée → 422 ; validation exigeant N unités pointées par ligne modèle × N |

## 2. Tests unitaires (modèles et services)

- **Génération de codes** : `MAG-{CODE_SITE}`, formats `{PREFIXE}-{année}-{séq}` (remise à 1 au changement d'année ; préfixes REC/SOR/TRF/INV/AJU).
- **Contraintes CHECK** : une seule FK article par mouvement/niveau/ligne d'inventaire ; `equipement_id` interdit dans `stock_niveaux` ; quantité mouvement > 0 ; quantité équipement = 1.
- **Seuil effectif** (§7.6 v2.0) : cascade **à deux niveaux** — seuil local (`stock_niveaux.seuil`) → `catalogue_articles.seuil_defaut` → aucun ; statuts `OK`/`SOUS_SEUIL`/`RUPTURE` ; pose d'un seuil sur un article sans niveau → création de la ligne à 0.
- **Dénormalisation bénéficiaire** : le libellé est figé à la validation et survit à la suppression du bénéficiaire (simulation `set null`).
- **Journalisation** : chaque modèle utilise `LogsActivityWithModule` avec `module = 'stock'`.

## 3. Tests Feature par workflow (SFD §7)

### 3.1 Réception
**Sérialisation (D10)** : réception « article-modèle × 3 » avec 3 n° de série → 3 fiches `parc_info_equipements` créées (catégorie/marque/modèle hérités de l'article, statut « en stock »), 3 mouvements unitaires, 3 rattachements ; unicité des n° de série contrôlée (I11). Nominal quantitatif (niveau créé puis incrémenté, coût historisé sur le mouvement) ; nominal équipement (mouvement unitaire + rattachement) ; refus : article désactivé (422), quantité ≤ 0, magasin inactif, équipement déjà rattaché (« c'est un transfert »), article inexistant au catalogue (D7 — pas de création implicite).

### 3.2 Sortie directe
Nominal vers chacun des **6 types de bénéficiaires** (D5) avec FK + libellé corrects ; décrément exact multi-lignes ; refus au-delà du disponible **par ligne** ; franchissement de seuil → statut du niveau passe à `SOUS_SEUIL` (I/O du dashboard testée via `.data`).

### 3.3 Sortie d'équipement (D8)
Nominal : mouvement + détachement + affectation ParcInfo créée (`type_cible`, `date_debut`) + statut « en service » + référence croisée sur le bon ; refus : équipement d'un autre magasin, équipement non « en stock » ; I5 pour l'atomicité.

### 3.4 Retours
Retour quantitatif tracé en nature « retour » ; retour d'équipement : clôture d'affectation (`date_fin`), statut « en stock », rattachement au magasin d'accueil, motif obligatoire.

### 3.5 Transfert
Nominal : paire `TRANSFERT_SORTIE`/`TRANSFERT_ENTREE` liée au même document, niveaux source/cible corrects ; refus : cible = source, quantité > disponible source, équipement absent de la source ; I4.

### 3.6 Inventaire
Ouverture fige les théoriques (un mouvement hors périmètre pendant `EN_COURS` ne modifie pas la feuille) ; saisie partielle → progression % ; validation par superviseur → un `AJUSTEMENT` par écart non nul, motif requis, niveaux alignés ; pointage équipements (Absent → détachement, Trouvé → rattachement) ; annulation sans effet sur les niveaux ; inventaire d'ouverture (théoriques 0) comme reprise initiale (§9.3).

### 3.7 Incohérence équipement (§7.3)
Affectation faite directement côté ParcInfo sur un équipement rattaché → le rapport de cohérence la liste ; résolution à l'inventaire.

## 4. Tests des API inter-modules (§8)

Formats de réponse contractuels (snapshot JSON) pour `/stock/api/magasins`, `disponibilite/{consommable|piece}/{id}`, `equipements/{id}/magasin` ; réponses bornées ; 403 sans `stock.api.view` ; article inconnu → 404 propre (pas d'exception brute — leçon Grh sur les `$e->getMessage()` exposés).

## 5. Matrice de permissions (systématique)

Test paramétré parcourant **toutes les routes nommées `stock.*`** : utilisateur sans la permission → **403** (jamais 200, jamais 500) ; utilisateur avec la permission → 2xx/422 ; invité → redirection login. Vérifications ciblées : `.data`, cascades et `toggle-status` couverts (anti-régression des oublis d'Organisation) ; `super-admin` passe tout ; rôles seedés (Magasinier ne peut ni valider un inventaire, ni contre-mouvementer, ni ajuster un seuil ; Consultation ne peut rien créer).

## 6. Gardes de suppression (§2.7)

422 pour : pièce avec mouvements ; magasin avec niveaux > 0 ou mouvements ; fournisseur référencé par une réception (garde côté ParcInfo — test d'intégration croisé) ; équipement référencé par un mouvement. `set null` vérifiés : local, responsable, bénéficiaires (l'historique reste lisible — libellé dénormalisé).

## 7. Non-régression amont

Le chantier d'intégration touche d'autres modules ; tests à ajouter chez eux : `Site::magasins()` réactivée ne casse plus (`ANALYSE_Organisation` §9 n°9) ; suppression d'`ApprovisionnerConsommableRequest` sans référence restante ; écran affectations consommables ParcInfo passé en lecture (§9.2) ; `php artisan view:cache` global passe toujours.

## 8. Priorisation

| Vague | Contenu | Moment |
|---|---|---|
| 1 | Invariants I1–I10 + matrice de permissions | Avec les premières migrations/contrôleurs (TDD recommandé) |
| 2 | Workflows §3 + gardes §6 | Au fil du développement de chaque écran |
| 3 | API §4 + non-régression amont §7 | Chantier d'intégration |

---
*Fin du document.*
