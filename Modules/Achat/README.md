# Module Achat — CHU-YO

Chaîne d'engagement fournisseur : bons de commande, visa, réceptions et reliquats.

> **Documents contractuels** : `SFD_Achat.md` (le SFD fait foi) · `SPEC_UX_Achat.md` v1.1 (annexe normative)
> **Conventions transverses** : `PATTERNS.md`, `DESIGN.md`

---

## État d'avancement

Le module est **fonctionnellement complet** : la chaîne d'engagement va de la
saisie du brouillon au reliquat soldé, réceptions physiques comprises.

| Livré | Contenu |
|---|---|
| Structure | `module.json` (requires Core, Catalogue, Stock, ParcInfo, Organisation), providers, layout, sidebar, navbar |
| Modèle de données | Les 8 tables du SFD §6.2, avec leurs contraintes `CHECK` actives sur SQLite **et** PostgreSQL |
| Permissions | Les 20 permissions du SFD §5 + les 3 rôles seedés, intégrés aux écrans de Core |
| Écrans | A-01 tableau de bord · A-02 liste · A-03 saisie · A-04 fiche (Lignes, Réceptions, Licences, Documents, Chronologie) · A-05 réception de licences · A-06 reliquats · A-07 rapports et Signaux · A-08 administration |
| Circuit | Brouillon → soumission → visa → validation numérotée ; renvoi motivé, reprise par l'auteur, annulation, clôture de reliquat |
| Réceptions (PRQ-05) | Liaison depuis Stock, intégration transactionnelle sous verrou, idempotence, contre-passation |
| Licences et prestations | Wizard de saisie des clés (reprise de session), service fait |
| Régularisation | Rattachement d'équipements existants, dette réelle, extinction automatique |
| Restitution | 9 Signaux, rapports exportables (CSV/XLSX/PDF), PDF du bon avec QR |
| **Bordereaux (lot BR)** | Pièces jointes typées et pierre tombale · bordereau de réception signable · **proxy documentaire** (un acheteur sans droit Stock consulte les pièces de ses commandes) · **écarts BL** structurés |
| Tests | 513 tests serveur, joués sur les deux SGBD, plus deux harnais navigateur |

---

## Installation

```bash
php artisan module:enable Achat
php artisan migrate --path=Modules/Achat/database/migrations
php artisan module:seed Achat          # permissions, rôles et paramètres
```

Le module **dépend du Catalogue et du Stock** : leurs permissions d'API doivent
exister, sinon le seeder s'arrête avec un message explicite plutôt que de créer
des rôles muets.

---

## Rôles seedés (SFD §5)

| Rôle | Peut | Ne peut pas |
|---|---|---|
| **Acheteur** | saisir, soumettre, réceptionner les licences, régulariser, joindre des pièces | **valider** |
| **Validateur Achat** | valider, renvoyer, annuler, clôturer, supprimer une pièce | **créer / soumettre** |
| **Consultation Achat** | lire, exporter les rapports | toute écriture |

La séparation commande/visa (RGC-11) est **structurelle** : aucun rôle par défaut
ne cumule `store` et `valider`. Le cumul reste possible, mais résulte alors d'un
acte d'administration explicite — et l'auto-validation est marquée, pas bloquée
(SFD §1.5).

`achat.rapports.signaux` n'est accordée à aucun rôle par défaut : c'est une
permission d'audit, attribuée nominativement.

---

## Paramètres (écran A-08)

Le métier paramétrable vit dans la table `achat_parametres`, jamais dans
`config/` (leçon AN-13/14). Les 8 clés v1 du SFD §6.2 :

| Clé | Type | Défaut | Rôle |
|---|---|---|---|
| `prefixe_numerotation` | texte | `BC` | Préfixe des numéros de bon |
| `delai_alerte_reliquat_jours` | entier | `30` | Seuil d'ancienneté d'un reliquat |
| `seuil_ecart_prix_pct` | entier | `20` | Déclenche la pilule d'écart de prix |
| `taille_max_piece_mo` | entier | `10` | Taille maximale d'une pièce jointe |
| `regularisation_active` | booléen | `true` | Ouvre la saisie des BC d'intérim |
| `intermede_debut` / `intermede_fin` | date | `2026-07-27` / ouverte | Bornes de la régularisation |
| `motifs_observation` | json | 4 motifs | Pilules de l'écran A-03 |

**La lecture passe toujours par `Services\AchatParametres`**, jamais par le
modèle : c'est lui qui déclare le type de chaque clé (un booléen revient
booléen, une date revient `Carbon` ou `null`) et qui gère le cache, invalidé à
chaque écriture. Sans ce point de passage unique, chaque appelant
réinterpréterait « 1 » ou « 30 » à sa façon.

`regularisation_active` est semé à **vrai** : le plan de mise en service
(SFD §9.2) prévoit la saisie des BC d'intérim en « semaine 0 », avant
l'ouverture générale. La porte se refermera seule à dette zéro (extinction
automatique A15) et sa réouverture sera un acte d'administration journalisé.

> ⚠️ Le seeder est **idempotent** : il ne réécrit jamais une valeur existante.
> Une base installée avant le 05/08/2026 conserve donc ses anciennes valeurs
> (`taille_max_piece_mo = 5`, `regularisation_active = 0`, `motifs_observation`
> absent). Les ajuster depuis l'écran A-08, ou supprimer les lignes concernées
> avant de rejouer le seeder.

---

## Raccordement Achat ⇄ Stock (PRQ-05)

Le module est la **source de vérité du reste à livrer** ; le Stock lui notifie
les réceptions physiques.

| Sens | Mécanisme | Contrat |
|---|---|---|
| Stock → Achat (lecture) | HTTP, `achat.api.view` | `a-livrer`, `lignes-a-livrer`, `resoudre` (QR) |
| Stock → Achat (écriture) | **Service interne transactionnel** | `AchatReceptionService::integrer()` / `contrePasser()` |
| Achat → Stock | HTTP, `stock.api.view` | `entrees/liees` (onglet Réceptions) |

Le service est appelé **dans la transaction de validation** du bon d'entrée :
son échec fait échouer la validation Stock, et rien n'est écrit nulle part.
C'est ce qui interdit au stock physique et au reste à livrer de diverger.

Trois protections : le bon doit être livrable ; le plafond est revérifié **sous
verrou** au moment de l'intégration (deux bons d'entrée concurrents sur le même
reste ne peuvent pas passer tous les deux) ; l'idempotence par `entree_id`
permet de rejouer une notification sans double incrément.

### Le dossier documentaire de la livraison (lot BR)

L'acheteur qui conteste une facture a besoin du BL signé. Il devait ouvrir le
module Stock, donc en avoir les droits : dans les faits, il téléphonait au
magasin. Deux routes **proxy** d'Achat servent désormais ces pièces :

```
GET /achat/bons-commande/{id}/receptions/{entree}/bordereau
GET /achat/bons-commande/{id}/receptions/{entree}/documents/{doc}
```

Elles vérifient `achat.bons_commande.index` **et** `achat.documents.view`, puis
relaient le fichier depuis le disque privé de Stock. Aucune permission Stock
n'est exigée, aucune URL Stock n'est rendue.

Le verrou qui compte vraiment est le **troisième** : la pièce doit appartenir à
une entrée liée à CE bon (invariant **IA-16**). Sans lui, un identifiant deviné
donnerait accès à tout le magasin, permission en poche. La réponse est `404` et
non `403` : pour l'acheteur, une pièce absente de son dossier n'existe pas, et
nous n'apprenons rien à qui sonde. `InvariantDocumentsReceptionTest` balaie la
matrice complète — cinq profils × quatre routes.

Les **écarts BL** (`stock_entrees.ecarts_bl`) remontent aussi ici, avec une
règle qu'il ne faut jamais assouplir : ils **ne modifient aucun compteur**. Les
reliquats ne connaissent que le compté. C'est cette neutralité qui permet au
magasinier de déclarer un écart sans risque, donc de le déclarer.

## Tests

```bash
php artisan test Modules/Achat            # SQLite (suite par défaut)
Modules/Achat/tests/postgres.sh           # PostgreSQL réel, schéma isolé
Modules/Achat/tests/migrations.sh         # migrate / rollback / réinstallation
Modules/Achat/tests/Navigateur/executer.sh # écrans réels dans un DOM (jsdom)
php Modules/Achat/tests/Recette/lot_br.php # recette du lot BR sur la base réelle
```

La **recette du lot BR** (CDC §12.3, REC-23 → REC-28) joue le parcours du
recetteur sur la base de développement : vrais comptes, vraies permissions,
vrais contrôleurs — dépôt du BL au comptoir, garde `bl_obligatoire_si_commande`,
bordereau signé avec écarts, consultation croisée depuis Achat, pierre tombale,
9e signal, et Stock coupé. Tout se déroule dans une transaction **annulée** :
la base ressort intacte. Le bordereau PDF et la fiche produits sont laissés
dans le dossier temporaire pour inspection à l'œil.

### Les pièges rencontrés, à connaître avant de toucher au module

**1. SQLite perd les CHECK dès qu'une migration recrée une table.**
SQLite ne sait ajouter ni un `CHECK` ni une clé étrangère à une table
existante : Laravel la **recrée** alors intégralement, et les contraintes
posées par `SchemaChecks` disparaissent **sans la moindre erreur**. La base
reste fonctionnelle, son filet de sécurité s'est évaporé en silence. Découvert
parce que `SchemaInvariantsTest` a cessé de passer après l'ajout des colonnes
de renvoi.

La parade : sur SQLite, ajouter les colonnes **sans** contrainte de clé
étrangère, ce qui autorise un vrai `ALTER TABLE ADD COLUMN` laissant la table
intacte ; la contrainte reste posée sur PostgreSQL, où les suites tournent
aussi. `MigrationsCycleTest::test_les_check_survivent_a_toutes_les_migrations`
monte désormais la garde en relisant la DDL réelle.

**2. Blade et les accès de tableau.**
Les vues Blade du module ont livré deux fois le même piège : **une directive
`@if`, `@json` ou toute expression inline contenant un accès de tableau
(`$tableau['clé']`) fait échouer l'analyseur Blade** (« Unclosed '[' does not
match ')' »), en général au moment du rendu et donc en 500. La parade retenue
partout : extraire la valeur dans un bloc `@php` en amont, puis n'utiliser que
des variables simples dans les directives.

La vérification **navigateur** complète les tests PHPUnit, qui s'arrêtent à la
charge JSON. Elle rend la page réelle, y injecte la charge réellement servie,
exécute le vrai JavaScript de la vue dans un DOM, et contrôle ce que
l'utilisateur voit : colonnes, pilules de statut, montants qualifiés TTC,
pictogramme de régularisation, barres de livraison accessibles, boutons
d'action, pied de tableau, état vide. C'est là qu'on attrape ce qu'un test
serveur ne voit pas — une ancre morte, un montant non qualifié, un bouton grisé
sans diagnostic.

Elle a besoin d'un jeu couvrant **tous** les statuts, sinon la grille
actions × statut n'est vérifiée que sur un cas sur sept :

```bash
php artisan tinker --execute="require 'Modules/Achat/tests/Navigateur/donnees_demo.php';"
```

Le cycle de migrations est vérifié séparément parce qu'un `migrate` qui passe ne
prouve rien sur le `rollback` : c'est au retrait que se révèlent les
dépendances. Le raccordement PRQ-05 en a fourni un exemple — la FK posée par
Stock empêchait le `DROP` de `achat_bons_commande`, et le rollback échouait.

La convention 6 exige que les suites passent sur **les deux** SGBD. Les `CHECK`
ne se posent pas de la même façon (SQLite ne sait pas les ajouter après coup,
d'où la recréation de table par `SchemaChecks`) : réussir sur l'un ne prouve
rien pour l'autre. Le script PostgreSQL migre dans un schéma isolé et le
supprime ensuite ; vos données ne sont jamais touchées.

### Les autres pièges, dans l'ordre où ils ont mordu

**3. La division entière en SQL.** `taux_tva / 100` vaut **0** quand les deux
opérandes sont entiers : toute une colonne de TVA à zéro, sans erreur. Écrire
`/ 100.0`.

**4. `SUM` ajouté à une projection casse sur PostgreSQL, pas sur SQLite.**
PostgreSQL exige que toute colonne projetée figure au `GROUP BY` ; SQLite s'en
accommode. Un agrégat ajouté à une requête Eloquent embarque les colonnes du
`select` implicite et des relations chargées d'avance. Parade : `select()`
explicite et `withoutEagerLoads()`.

**5. `addMonth()` saute un mois depuis le 31.** Le 31 janvier + 1 mois donne le
3 mars (débordement). Utiliser `addMonthNoOverflow()` partout où une échéance
se calcule.

**6. Une requête en erreur AVORTE la transaction PostgreSQL.** Interroger une
table absente (module non installé) lève `25P02` et condamne **tout ce qui
suit**, y compris hors du service fautif : le `try/catch` attrape l'exception
mais la page est déjà perdue. SQLite, lui, tolère la même faute en silence.
Il faut donc vérifier `Schema::hasTable()` pour **chaque** table interrogée,
et pas seulement pour la première. Trouvé par la suite PostgreSQL, invisible
sur SQLite.

**7. Une trace `activity()` sans sujet ne porte pas le module.** Le journal
filtre par `module` : une trace émise sans `->tap()` sort du périmètre et
disparaît des chronologies. De même, `causedBy()` doit être posé
explicitement dans les services appelés hors HTTP, sinon l'auteur est perdu.

**8. Un test qui recopie l'appel d'une vue finit par mentir.** Le test du
bordereau reconstruisait les paramètres passés au gabarit ; à l'ajout d'une
donnée, quatre tests sont tombés alors que le produit était juste. Un test doit
appeler le **vrai** contrôleur et intercepter le rendu, jamais réimplémenter ce
qu'il vérifie.

**9. jsdom : `$(document).ready` est différé, et `submit` n'est pas `click`.**
Un harnais qui contrôle le DOM immédiatement après l'injection mesure un écran
dont les gestionnaires ne sont pas encore branchés. Et déclencher `click` sur
un bouton `type="submit"` appelle `requestSubmit()`, non implémenté par jsdom :
c'est l'événement `submit` du formulaire qu'il faut déclencher. Enfin, chaque
module JS doit être injecté **dans sa propre portée** (comme le ferait un
module ES), sans quoi deux utilitaires homonymes provoquent un
« already declared » qui n'existe pas dans le navigateur.

---

## Écarts signalés (SFD)

| # | Écart | Traitement retenu |
|---|---|---|
| 1 | ~~`RACCORDEMENT_Achat_Stock.md` absent~~ — **levé le 05/08/2026** | Document fourni ; le raccordement est implémenté côté serveur : colonne de liaison, 6 endpoints, service d'intégration avec plafond sous verrou, idempotence et contre-passation. Reste à câbler dans les **écrans** Stock (modale M-02, scan QR, pré-remplissage). |
| 2 | **Contradiction interne** : §1.4 annonce « `ANNULE` — numéro jamais attribué », mais §7.5 n'autorise l'annulation que depuis `VALIDE`, qui porte déjà un numéro | Le numéro est **conservé** à l'annulation : le retirer creuserait un trou dans la séquence (contraire à IA-3) et effacerait la trace d'un document ayant pu circuler. Documenté par un test ; à trancher par la MOA. |
| 3 | §6.2 prévoit un `CHECK` « `est_regularisation = false OR date_document BETWEEN bornes d'intérim` » | **Impossible en `CHECK` statique** : les bornes sont des paramètres modifiables (`achat_parametres`). La garde est applicative, à poser à la création du BC de régularisation. |
| 4 | La nature `prestation` (PRQ-02) et le compte comptable (PRQ-03) sont des amendements **Catalogue** non livrés | Le modèle les accepte (`nature` figée en chaîne, `service_fait_*` présents) sans les exiger. |
| 5 | Le jalon d'installation annonce « **17 permissions** » | Le SFD §5 en compte **20 distinctes** : son tableau tient sur 14 lignes, dont 4 regroupent plusieurs permissions (`store` / `update` / `destroy`, `annuler` / `cloturer`, `documents.view` / `store` / `delete`, `rapports.view` / `export`). Le SFD faisant foi, les 20 sont posées — ni manquante, ni surnuméraire (vérifié par test). |
| 6 | La maquette **P-08**, désignée comme référence de l'écran A-02, **n'existe pas dans le dépôt** | Aucun fichier ni mention (recherche exhaustive : le seul « P-08 » du dépôt est `EF-RAP-08`, un identifiant d'exigence sans rapport). L'écran suit donc `SPEC_UX_Achat.md` A-02, qui est déclaré normatif, et les gabarits réels du module Stock. À confirmer si une maquette graphique existe hors dépôt. |
| 7 | L'action **« Reprendre »** (SFD §7.1 : l'auteur défait sa propre soumission) n'a **pas de permission dédiée** au SFD §5 | Rattachée à `achat.bons_commande.soumettre`, dont elle est l'exacte réciproque, et **restreinte à l'auteur du bon** (vérifié par test). Une permission dédiée serait à créer si la MOA veut dissocier les deux gestes. |
| 8 | Le contrat `API_Inter_Modules.md` §2.1 garantit `taux_tva` **toujours renseigné** sur `GET /catalogue/api/articles`, et un tri `fournisseur_prefere_id` | Ni l'un ni l'autre n'étaient implémentés. Corrigé **dans le module Catalogue** (le contrat lui appartient) : `taux_tva` est exposé avec repli à 18.00, et le tri de pertinence est exprimé en `CASE WHEN` portable. Le test snapshot du Catalogue a été mis à jour — rupture délibérée qui rapproche l'API de son contrat. |
| 9 | `API_Inter_Modules.md` §3.2 spécifie un endpoint HTTP `GET /stock/api/entrees/liees` | **Implémenté en lecture directe** de `stock_entrees` par `ReceptionsBonCommande`, comme `RechercheBonCommande` : la fiche d'un bon ne doit pas dépendre du chargement d'un module voisin, ni d'un aller-retour HTTP, pour afficher SES données. La forme des données reste le contrat ; l'endpoint sera ajouté quand un consommateur tiers en aura besoin. Écart consigné au document (§3.2). |
| 10 | Le SFD Achat §7.7 annonce **8 signaux** | Le lot BR-04 en ajoute un **9e** (écarts de livraison par fournisseur), prévu par le recueil de prompts et non par le SFD initial. §7.7 amendé en conséquence. |
| 11 | Le SFD Stock ne décrivait pas la table `stock_documents` | Décrite au §6.2 avec le typage des pièces (BR-01), la pierre tombale et les paramètres `bl_obligatoire_si_commande` / `afficher_couts_bordereau`. |
| 12 | `SPEC_UX_Achat.md` §15 gèle huit textes que le code formule autrement | Les textes livrés ont été jugés **meilleurs** et la spec a été alignée sur eux : ils citent la clé en doublon plutôt qu'un numéro de ligne dans une grille défilante, rappellent la quantité annoncée face au reste, nomment les formats réellement admis. Vérifié par `scripts/audit_textes.py`, qui confronte les textes gelés au code à chaque exécution. |
| 13 | La carte Signaux annonçait « 8 indicateurs » après l'ajout du 9e (BR-04) | Corrigé, et **verrouillé par un test** qui compare le libellé au nombre réellement servi : un chiffre écrit en dur dans une vue ne se met pas à jour tout seul. Défaut trouvé par la revue des textes de D-19, pas par les suites. |
