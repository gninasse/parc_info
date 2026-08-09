# Prompts de développement — Module Achat

> **Usage** : suite ordonnée de prompts **D-01 → D-20**, un par fonctionnalité, à donner à l'assistant de développement dans l'ordre (chaque prompt suppose les précédents livrés et leurs tests verts). Coller le **PRÉAMBULE** en tête de chaque prompt. Ne jamais lancer deux prompts en parallèle sur la même table.
> **Références** (dans le dépôt) : `SFD_Achat.md` v1.0 (contrat), `SPEC_UX_Achat.md` v1.1 (annexe normative UI), `RACCORDEMENT_Achat_Stock.md`, `CDC_Achat_v2.md`, maquettes P-01→P-10 validées.
> **Prérequis avant D-01** : PRQ-02/03 (amendements Catalogue livrés), Stock v3.0 installé, `API_Inter_Modules.md` validé.

---

## PRÉAMBULE — à coller en tête de CHAQUE prompt

```
Projet : application modulaire Laravel 12 + nwidart (CHU-YO). Tu développes le
module ACHAT. Documents contractuels : SFD_Achat.md (et son annexe SPEC_UX_Achat.md),
RACCORDEMENT_Achat_Stock.md. En cas de doute, le SFD fait foi ; signale tout écart
au lieu de l'inventer.

CONVENTIONS NON NÉGOCIABLES :
1.  Module autonome Modules/Achat ; module.json requires ["Core","Catalogue","Stock","ParcInfo","Organisation"].
2.  Permissions Spatie déclarées dans Modules/Achat/config/permissions.php,
    synchronisées par cores:sync-permissions achat ; contrôle SERVEUR via
    HasMiddleware sur TOUTES les routes (.data, PDF, exports, API, cascades).
    Toute permission déclarée = une fonctionnalité effective + seedée.
3.  Journal : trait LogsActivityWithModule, module='achat' ; created_by partout.
    Les chronologies affichées viennent du journal, jamais reconstruites.
4.  Transactions : toute écriture multi-tables est atomique ; numéros attribués
    À LA VALIDATION sous verrou (séquence par table, aucun trou) ; opérations
    idempotentes (jeton / entree_id).
5.  Montants : calculés UNE SEULE FOIS côté serveur, à partir des valeurs FIGÉES
    à la ligne (designation, nature, prix_unitaire_ht, taux_tva copiés du
    Catalogue à l'ajout) ; jamais recalculés en JS pour l'enregistrement ;
    toujours qualifiés HT/TTC à l'affichage.
6.  SQL portable : LIKE uniquement, aucune fonction propriétaire (pas de TO_CHAR,
    ILIKE via whereRaw interdit) ; les suites de tests passent sur SQLite ET PostgreSQL.
7.  UI : patterns du projet — Bootstrap Table serveur, modales (jamais de page
    pour une sélection), SweetAlert2 récapitulatifs chiffrés avant tout
    irréversible, Select2, pilules de statut (jaune = SOUMIS), compteurs en pied,
    boutons sans droit ABSENTS / bloqués par l'état GRISÉS + title diagnostic.
    JS dans public/js/modules/achat/ ; textes : SPEC_UX §15 (définitifs).
8.  Erreurs : 422 mappées champ par champ (lignes dynamiques comprises),
    409 sur édition concurrente ou document verrouillé, 403 nominatives ;
    un succès affiché = un succès réel.
9.  Aucune duplication de référentiel (EXI-INT-00) : articles/fournisseurs lus
    au Catalogue, quantités tenues par Stock ; seules les photographies
    contractuelles (valeurs figées) sont copiées.
10. Aucun code mort : pas d'événement non dispatché, pas de listener vide —
    intégration synchrone transactionnelle assumée.
11. Tests systématiques à chaque prompt : Feature (nominal + erreurs + 403) ;
    les invariants IA-x du SFD §9.4 concernés par le prompt sont couverts.
12. Livraison d'un prompt = migrations + code + seeders + tests verts +
    courte note de ce qui a été fait et des écarts éventuels.
```

---

## Phase A — Socle (lot A1 du CDC)

### D-01 — Squelette du module

```
[PRÉAMBULE]
OBJECTIF : le module Achat existe, s'installe, s'affiche, et ses permissions sont posées.
À FAIRE :
- Génération nwidart du module Achat ; module.json (requires §1.1 du SFD) ;
  ServiceProvider, routes web vides sous prefix /achat, name achat.*.
- config/permissions.php : les 17 permissions du SFD §5 (libellés FR) ;
  seeder des 3 rôles (Acheteur, Validateur Achat, Consultation Achat) idempotent ;
  attribution croisée : achat.api.view aux rôles Stock, stock.api.view et
  catalogue.api.view aux rôles Achat.
- Chrome : entrée sidebar "CHU-YO | ACHAT" (Tableau de bord, Bons de commande,
  Reliquats, Rapports, RÉFÉRENTIELS>Administration, AUTRES MODULES), topbar
  2 accès rapides, breadcrumb — conforme maquettes (SPEC_UX §0.1).
- Page /achat placeholder (dashboard vide, EV-01) protégée par achat.dashboard.view.
CRITÈRES : module activable/désactivable proprement ; cores:sync-permissions achat OK ;
un utilisateur sans rôle Achat reçoit une 403 nominative sur /achat.
TESTS : installation, seeder idempotent (double exécution), 403/200 selon rôle.
```

### D-02 — Migrations, modèles, factories

```
[PRÉAMBULE]
OBJECTIF : le modèle de données complet du SFD §6, sans aucun écran.
À FAIRE :
- 7 migrations : achat_bons_commande, achat_lignes_commande,
  achat_receptions_licences, achat_tampon_licences, achat_documents,
  achat_regularisation_rattachements, achat_parametres — champs, enums, CHECK,
  index et onDelete EXACTEMENT comme SFD §6.2/§6.4.
- Modèles Eloquent : relations, casts, scopes (parStatut, regularisations,
  aLivrer), trait de journalisation, constantes de statuts.
- Service AchatParametres (get/set typé, cache) ; seeder des paramètres v1
  (prefixe BC, delai 30 j, seuil 20 %, taille 10 Mo, bornes d'intérim,
  regularisation_active=true, motifs d'observation).
- Factories réalistes (montants FCFA, natures via articles Catalogue de test).
CRITÈRES : migrate/rollback propres sur SQLite et PostgreSQL ; CHECK vérifiés
(quantite_livree<=quantite refusé en base).
TESTS : contraintes (CHECK, uniques, FK restrict/cascade), scopes, paramètres.
```

### D-03 — Liste des bons de commande (A-02)

```
[PRÉAMBULE]
OBJECTIF : l'écran A-02 complet (SPEC_UX), sur données de factories.
À FAIRE :
- Route index + endpoint .data (Bootstrap Table serveur) : colonnes, tri,
  pagination ; filtres statut (multi), fournisseur, période, régularisations ;
  recherche "tous formats" : BC-…, Brouillon #id, ENT-… (résout via le lien
  entrée→BC, stub en attendant D-11), texte libre (fournisseur/article via LIKE).
- Grille actions × statut (SFD §1.4 / SPEC_UX A-02) : boutons absents/grisés+title ;
  pied "N bons · Total affiché … FCFA TTC" ; pictogramme régularisation ;
  EV-02 ; bouton Nouveau + menu régularisation (visible selon regularisation_active).
CRITÈRES : identique à la maquette P-08 ; permissions par action vérifiées serveur.
TESTS : .data (filtres, recherche formats), 403 par permission, totaux du filtre.
```

### D-04 — Saisie du brouillon, étape ① (A-03 + M-01)

```
[PRÉAMBULE]
OBJECTIF : créer/éditer un brouillon complet : en-tête, lignes, calculs, modale.
À FAIRE :
- Pages create/edit (stepper visuel, étape ① — maquette P-01) ; store/update
  limités au statut BROUILLON (409 sinon) ; verrou optimiste (updated_at, 409
  message SPEC_UX §15.2).
- En-tête : fournisseur (Select2 sur API Catalogue, verrouillé si lignes),
  date, service demandeur (Organisation) + reference_demande, observation
  (pilules depuis paramètres).
- Lignes : ajout via M-01 (modale de sélection Catalogue en mode MULTI :
  coches, colonne TVA, tri fournisseur préféré, sélection persistante, lien D7,
  compteur) ; à l'ajout, COPIE FIGÉE designation/nature/taux_tva + prix pré-rempli
  du prix indicatif ; édition qté/prix/TVA (pilule) ; suppression ; garde
  licence sans logiciel (bandeau ligne).
- Calculs serveur : montants ligne + totaux HT/TVA/TTC recalculés à chaque
  enregistrement (arrondis définis et testés) ; le JS n'affiche qu'une
  prévisualisation.
- Endpoints PO-01 : GET articles/{id}/historique-prix (dernier payé, moyenne
  3 BC validés, modif récente du prix indicatif via journal Catalogue) ;
  pilule d'écart (seuil paramétré) informative.
- Pied collant à compteurs ; garde de sortie ; EV et textes §15.
CRITÈRES : maquettes P-01 pixel-perfect raisonnable ; IA-1 (cohérence des
montants) et IA-2 (valeurs figées : modifier l'article au Catalogue ne change
rien au brouillon) démontrés.
TESTS : store/update/destroy brouillon, 409 hors brouillon, copie figée,
calculs+arrondis, historique-prix, 422 mappées, 403.
```

### D-05 — Récapitulatif, soumission, renvoi, reprise (étape ② + M-06)

```
[PRÉAMBULE]
OBJECTIF : le circuit BROUILLON ⇄ SOUMIS.
À FAIRE :
- Étape ② : composant "Récapitulatif de BC" (partiel Blade réutilisable —
  il resservira au visa) : lecture seule, totaux, encarts d'avertissement
  (écarts de prix dépliables, licence sans logiciel bloquante).
- POST soumettre : contrôles (≥1 ligne, prix>0, licences OK), SW-01,
  transition SOUMIS + soumis_par/le + verrouillage + journal.
- POST renvoyer (permission valider, M-06 motif) et POST reprendre (auteur) :
  retour BROUILLON, motif journalisé, encart jaune à la réouverture (P-02).
- Badge sidebar/topbar "à valider" (comptage SOUMIS, visible permission valider).
CRITÈRES : un Acheteur seul ne peut pas valider ni renvoyer ; le brouillon
renvoyé affiche le motif ; chronologie exacte.
TESTS : transitions permises/interdites (matrice), 403, journal, badge.
```

### D-06 — Validation et numérotation

```
[PRÉAMBULE]
OBJECTIF : le visa — cœur transactionnel du module.
À FAIRE :
- Endpoint GET fournisseurs/{id}/cumul-mois (Swal) ; assemblage des signaux
  instantanés (écarts vs dernier payé, fournisseur récent/premier BC — journal
  Catalogue) pour SW-02.
- POST valider : transaction — re-contrôles (statut SOUMIS, article/fournisseur
  actifs), NUMÉRO sous verrou (séquence annualisée par table, prefixe paramétré),
  dénormalisations (fournisseur_libelle, montants), valide_par/le, journal ;
  idempotence par jeton. SW-02 avec textes §15 (signaux inclus, jamais bloquants).
- Marquage auto-validation (created_by = valide_par) : badge fiche + donnée
  pour le rapport Signaux (D-16).
CRITÈRES : IA-3 (concurrence : 20 validations parallèles → numéros uniques
consécutifs sans trou), IA-5 (double clic → un seul effet).
TESTS : concurrence de numérotation, idempotence, refus article désactivé (422
explicite), 403, journal.
```

### D-07 — PDF du bon de commande

```
[PRÉAMBULE]
OBJECTIF : l'objet juridique imprimable (SPEC_UX §17).
À FAIRE :
- GET {id}/pdf (dompdf) : gabarit complet — en-tête, QR CODE du numéro seul
  (lisible douchette), parties, tableau (en-tête répété), totaux + montant TTC
  en toutes lettres, cadres de signature, pied paginé.
- Filigranes : BROUILLON—SANS VALEUR (prévisualisation uniquement, jamais
  listée), RÉGULARISATION, ANNULÉ.
CRITÈRES : conforme maquette/gabarit ; montants = ceux de la base (IA-1) ;
QR lu par une douchette réelle (test manuel consigné).
TESTS : contenus (numéro, totaux, mentions), filigrane selon statut, 403.
```

### D-08 — Fiche BC : bandeau, onglet Lignes, chronologie

```
[PRÉAMBULE]
OBJECTIF : l'écran A-04, hors réceptions/licences/documents (prompts dédiés).
À FAIRE :
- Page show : bandeau d'état (numéro/pilule/fournisseur/montant + badges
  régularisation, fournisseur récent, auto-validation) ; barre d'actions
  contextuelle complète (grille §1.4) — actions branchées sur D-05/D-06,
  annulation/clôture stubs 501 propres (D-14).
- Onglet Lignes : commandée/livrée/reste + barres de progression (livree=0
  pour l'instant), totaux + PO-02 (décomposition par taux).
- Onglet Chronologie : timeline depuis le journal (types d'événements,
  puces colorées, rejets rouges — maquette P-04) ; compteurs d'onglets.
CRITÈRES : la chronologie reflète EXACTEMENT le journal (IA-14) ; doctrine
absent/grisé respectée.
TESTS : rendu par statut, chronologie vs journal, 403 par action.
```

### D-09 — Pièces justificatives (onglet Documents, M-05, M-08)

```
[PRÉAMBULE]
OBJECTIF : le dossier documentaire du BC, avec la pierre tombale.
À FAIRE :
- POST documents (M-05) : type (pilules), fichier PDF/image, taille max
  paramétrée, contrôle MIME serveur, stockage hors racine web, nom neutre.
- GET documents/{id}/telecharger sous permission documents.view (l'URL directe
  du fichier est inaccessible).
- DELETE : avant validation du BC → suppression réelle (Swal simple) ;
  après → M-08 motif obligatoire, PIERRE TOMBALE (est_supprime, motif,
  auteur, date — fichier physique effacé, ligne conservée, journal + chronologie).
- Onglet Documents (maquette P-04) : tableau, pilules de type, tombstones
  grisées, EV-05.
CRITÈRES : IA-13 ; un utilisateur sans documents.view ne télécharge rien,
même avec l'URL.
TESTS : upload (types/taille), téléchargement 403, tombstone, journal.
```

## Phase B — Raccordement Stock (lot A2 — chantier conjoint)

### D-10 — API exposée par Achat

```
[PRÉAMBULE]
OBJECTIF : les 6 endpoints du SFD §2.6, consommés par Stock (et par les
écrans Achat).
À FAIRE :
- GET api/bons-commande/a-livrer (filtres fournisseur/q, statuts VALIDE|PARTIEL,
  borné 50) ; GET api/bons-commande/{id}/lignes-a-livrer (article, nature,
  reste, prix figé, TVA) ; GET api/articles/{id}/historique-prix (déjà D-04 —
  déplacer/mutualiser ici) ; GET api/fournisseurs/{id}/cumul-mois (D-06 —
  mutualiser) ; POST api/receptions et api/receptions/contre : SQUELETTES
  validés (payload, permission, 422) mais logique en D-12.
- Permission achat.api.view sur tout ; réponses JSON stables (resources) ;
  aucune exception brute.
CRITÈRES : snapshots de réponses figés (contrat API_Inter_Modules.md).
TESTS : snapshots des 6 endpoints, bornage, 403, portabilité LIKE.
```

### D-11 — Côté Stock : le mode « Livraison sur commande » (amendement du module Stock)

```
[PRÉAMBULE — préciser : tu interviens dans Modules/Stock, contrat =
RACCORDEMENT_Achat_Stock.md §2, sans rien casser des tests Stock existants]
OBJECTIF : lier un bon d'entrée à un BC.
À FAIRE :
- Migration Stock : stock_entrees.bon_commande_id FK nullable restrict.
- Étape ① du bon d'entrée : bouton "Lier à une commande…" (modale M-02, même
  gabarit que la modale article : filtres, radio, statuts, pied informatif),
  consommant achat.api a-livrer ; liaison par SCAN du QR dans le champ ;
  encart bleu "Livraison sur BC-… [Voir le BC] [Délier]" ; fournisseur imposé
  verrouillé ; lignes PRÉ-REMPLIES du reste (qté modifiable à la baisse,
  plafond champ par champ, message pédagogique du §15.2), coût pré-rempli du
  PRIX FIGÉ, alerte ±20 % vs prix figé ; articles hors BC refusés ;
  reference_externe = n° de BL papier ; pied "sur BC-… · reste global après
  ce bon : N".
- Étapes ②③ inchangées.
CRITÈRES : conformité RACCORDEMENT §2.3 ; une entrée sans commande fonctionne
strictement comme avant (non-régression Stock complète).
TESTS : liaison/déliaison, pré-remplissage, plafonds UI+serveur, fournisseur
imposé, non-régression suites Stock.
```

### D-12 — La notification transactionnelle et l'onglet Réceptions

```
[PRÉAMBULE]
OBJECTIF : la frontière vivante — réceptions Stock → reliquats Achat.
À FAIRE :
- Service AchatReceptionService::integrer(entree) appelé DANS la transaction
  de validation du bon d'entrée Stock (appel de service interne, pas
  d'événement) : contrôle statut BC, RE-CONTRÔLE du reste SOUS VERROU ligne à
  ligne (422 ligne à ligne → toute la validation Stock échoue), incréments
  quantite_livree, statut PARTIEL/LIVRE, chronologie "Réception ENT-… (x/y)
  intégrée" ; IDEMPOTENCE par entree_id.
- ::contrePasser(mouvement) : décréments symétriques, plancher 0, statut
  recalculé (LIVRE→PARTIEL possible), chronologie.
- Onglet Réceptions de A-04 (maquette P-03) : section "Intégrées" (compteurs
  Achat) + section "En cours côté magasin" (lecture API Stock des entrées liées
  non validées, badge informatif, DÉGRADATION PARTIELLE si l'API échoue) ;
  liens croisés bidirectionnels BC ⇄ bon d'entrée.
- Recherche A-02 : résolution ENT-… → BC (lever le stub de D-03).
CRITÈRES : IA-4 (deux entrées concurrentes sur le même reste : la seconde
échoue proprement), IA-5 (rejeu entree_id sans double), IA-6 (statuts justes,
aller-retour contre-mouvement).
TESTS : concurrence (verrous), idempotence, contre-passation, dégradation
API, recette conjointe REC-06→09 automatisée.
```

## Phase C — Réceptions dématérialisées (lot A3)

### D-13 — Wizard licences et service fait (A-05, M-04)

```
[PRÉAMBULE]
OBJECTIF : la réception des natures non stockables.
À FAIRE :
- Ouverture : garde logiciel rattaché (message + lien Catalogue) ; création
  achat_receptions_licences EN_COURS (quantité ≤ reste).
- Wizard (maquette P-05) : tampon ligne à ligne sauvegardé À CHAQUE Entrée,
  unicité tampon + parc_info_licences (contrôle live), "appliquer à toutes"
  (date d'activation), collage/CSV avec rapport (acceptées/doublons/vides),
  reprise, retour arrière (SW-05 → ABANDONNEE, tampon vidé, journal).
- POST finaliser : SW-03 → transaction — re-contrôles (complétude, unicité,
  reste sous verrou) → N licences ParcInfo (logiciel de l'article, fournisseur
  du BC, coût = prix figé, référence BC) + incrément livree + statut +
  purge tampon + FINALISEE + journal. Échec = rollback intégral, jamais de
  succès affiché.
- M-04 service fait (prestations, si PRQ-02) : date+commentaire → ligne soldée.
- Onglet Licences de A-04 (progressions, boutons avec diagnostics).
CRITÈRES : IA-7 (atomicité : injection d'une erreur à la 20e licence → zéro
écriture), IA-8 (tampon persistant/purgé), IA-9 (garde logiciel).
TESTS : les 3 invariants + doublons + quantité stricte + réception partielle
+ 403 + statut BC après finalisation.
```

## Phase D — Fin de vie et régularisation (lots A2/A5)

### D-14 — Reliquats, clôture, annulation (A-06, M-03, M-07)

```
[PRÉAMBULE]
OBJECTIF : la to-do fournisseurs et les fins de vie.
À FAIRE :
- Écran A-06 (maquette P-06) : .data serveur, pilules d'âge (délai paramétré),
  filtres, pied "Engagé non livré : … FCFA TTC", EV-04, export.
- POST cloturer (M-03, permission cloturer) : PARTIEL → CLOTURE, motif,
  lignes conservées, journal ; alimente les données "clôturés non livrés".
- POST annuler (M-07) : VALIDE sans AUCUNE réception (physique, licence,
  service fait) → ANNULE, motif, journal ; sinon bouton absent + garde serveur.
- KPI dashboard "Reliquats > N j" (stub levé en D-18).
CRITÈRES : totaux exacts par filtre ; annulation refusée dès la moindre
réception (y compris licences).
TESTS : transitions, gardes, totaux, badges d'âge, 403.
```

### D-15 — Régularisation de l'intérim (mode A-03, M-09)

```
[PRÉAMBULE]
OBJECTIF : documenter la dette de l'intérim, porte encadrée (A15).
À FAIRE :
- Création "BC de régularisation" (permission regulariser, param
  regularisation_active, date bornée à l'intérim — CHECK + 422 §15.2) :
  bandeau hachuré, circuit SOUMIS→VALIDE obligatoire, aucune réception,
  exclusion des statistiques par défaut, filigrane PDF.
- M-09 rattachements : GET equipements-candidats (ParcInfo sans commande
  d'origine), liens uniques, chronologie, décrément de la dette.
- EXTINCTION : au passage de la dette à zéro, regularisation_active=false
  automatique + journal ; POST regularisation/reactiver (admin, SW-06).
- Répercussion ParcInfo (petit lot séparé si nécessaire) : lien "Voir la
  commande d'origine" sur la fiche équipement (chaîne mouvement→entrée→BC,
  et rattachement de régularisation).
CRITÈRES : IA-11 (bornes, visa, extinction, unicité du rattachement).
TESTS : bornes de dates, extinction/réactivation journalisées, unicité,
exclusion des stats, 403.
```

## Phase E — Restitution et pilotage (lot A4)

### D-16 — Rapports, exports, Signaux (A-07)

```
[PRÉAMBULE]
OBJECTIF : la page Rapports complète.
À FAIRE :
- Cartes : État des BC, Dépenses par fournisseur, par catégorie, Évolution
  12 mois (mois vides inclus, chronologiques), Reliquats, Régularisation
  (compteur de dette + liste à rattacher) ; carte grisée Imputation (PRQ-03).
- Exports CSV/XLSX (fast-excel) et PDF par carte : période+filtres imprimés,
  QUALIFICATION HT/TTC DANS LE TITRE ; option "inclure les régularisations"
  décochée par défaut PARTOUT.
- Carte SIGNAUX (permission rapports.signaux) : les 8 indicateurs du SFD §7.7,
  calculés à la volée, exportables.
- COHÉRENCE CONTRACTUELLE : mêmes services de calcul que le dashboard
  (une source, un arrondi, un périmètre) — test dédié dashboard=rapport=export.
CRITÈRES : IA-1 étendu (cohérence tri-support), IA-12 (calcul dernier payé).
TESTS : chaque carte (données, filtres, mois vides), exports (titres, contenus),
signaux (8 requêtes), 403 signaux.
```

### D-17 — Administration (A-08)

```
[PRÉAMBULE]
OBJECTIF : l'écran de paramètres (leçon v1 : jamais de table sans écran).
À FAIRE :
- Page A-08 (maquette P-09) : préfixe (aperçu live), délai reliquats, seuil
  d'écart, taille pièces, motifs d'observation (pilules éditables),
  interrupteur régularisation (état auto + Rouvrir SW-06).
- PATCH parametres/{cle} : validation par clé, effet SANS redéploiement,
  journal (ancienne→nouvelle valeur).
CRITÈRES : chaque paramètre modifié change le comportement immédiatement
(test sur le délai des badges d'âge et le seuil d'écart).
TESTS : validations par clé, effets, journal, 403.
```

### D-18 — Tableau de bord final (A-01)

```
[PRÉAMBULE]
OBJECTIF : lever le placeholder de D-01 (maquette P-07).
À FAIRE : 6 KPI cliquables vers leurs listes filtrées (À valider [permission],
Engagé du mois HT/TTC, BC ouverts, Reliquats>N, En cours de réception [API
Stock, dégradation partielle], Dette d'intérim [masquée à zéro]) ; graphique
12 mois (service partagé D-16) ; tableau reliquats anciens ; fil des 10
derniers événements (journal) ; actions rapides ; EV-01 ; squelettes de
chargement progressifs.
CRITÈRES : chaque chiffre du dashboard = celui de sa liste/rapport (test).
TESTS : KPI par permission, cohérence chiffres, dégradation API Stock.
```

## Phase F — Durcissement et livraison

### D-19 — Durcissement transverse et invariants

```
[PRÉAMBULE]
OBJECTIF : la ceinture de sécurité complète avant recette.
À FAIRE :
- TESTS_Achat.md : matrice permissions EXHAUSTIVE (chaque route × chaque rôle,
  .data/PDF/exports/API compris → 403 attendus) ; les 15 invariants IA-1→IA-15
  du SFD §9.4 chacun couvert par au moins un test nommé IAxx_… ;
  suites exécutées sur SQLite ET PostgreSQL en CI.
- Revue anti-code-mort (événements, listeners, routes orphelines, JS non chargé) ;
  revue des textes vs SPEC_UX §15 (gel) ; audit des uploads (MIME, chemins).
- Accessibilité des parcours critiques : tabulation A-03/A-05, Entrée=suivante,
  focus des Swals (SPEC_UX §18) — tests manuels consignés.
CRITÈRES : CI verte bi-SGBD ; zéro permission sans fonctionnalité et
réciproquement (script de vérification config vs seed vs routes).
```

### D-20 — Recette conjointe, mise en service, documentation

```
[PRÉAMBULE]
OBJECTIF : livrer.
À FAIRE :
- Exécution du cahier de recette CDC §12 (REC-01→22) sur environnement intégré
  (Catalogue+Stock+ParcInfo) — scénarios ⇄ joués avec l'équipe Stock ; PV.
- Plan de mise en service SFD §9.2 : semaine 0 (saisie des régularisations),
  guide A5, jalon J+30 (3 indicateurs outillés dans les rapports).
- Documentation : README du module, guide utilisateur (acheteur/validateur,
  procédures de correction et de régularisation), ANALYSE_Achat.md de fin de
  chantier (convention du projet), mise à jour d'API_Inter_Modules.md,
  report des amendements A11–A16 dans CDC_Achat_v2.md.
CRITÈRES : 100 % des M du CDC démontrées, 0 violation RGC, % réceptions liées
à un BC mesurable.
```

---

## Récapitulatif de séquencement

| Phase | Prompts | Livrable jalon |
|---|---|---|
| A — Socle | D-01 → D-09 | Chaîne brouillon → soumis → validé → PDF + documents, autonome |
| B — Raccordement | D-10 → D-12 | **Jalon de mise en service possible** : commande → réception → sérialisation opérationnelle |
| C — Licences | D-13 | Natures non stockables couvertes |
| D — Fin de vie | D-14 → D-15 | Reliquats, clôtures, régularisation avec extinction |
| E — Pilotage | D-16 → D-18 | Rapports, signaux, admin, dashboard |
| F — Livraison | D-19 → D-20 | CI bi-SGBD, recette conjointe, docs |

Règles de conduite : un prompt = une PR = tests verts avant le suivant · tout écart au SFD se signale et s'arbitre (le SFD s'amende, il ne se contourne pas) · D-11 et D-12 se planifient avec l'équipe Stock (gel de version Stock pendant le chantier).

---

*Recueil v1.0 — 08/08/2026. Compagnon de `PROMPTS_Maquettes_Achat.md` ; contrats : `SFD_Achat.md` + `SPEC_UX_Achat.md` + `RACCORDEMENT_Achat_Stock.md`.*

---
---

# VOLUME 2 — Prompts préalables (hors module Achat, avant D-01)

> Ces prompts s'exécutent dans les modules **Catalogue** et **Stock** et dans les documents transverses. Ils lèvent les prérequis PRQ-02/03/05 (partiel) et préparent les composants que D-04 et D-13 réutilisent. Coller le PRÉAMBULE en adaptant la première ligne (« tu interviens dans Modules/Catalogue » / « Modules/Stock »). **Règle absolue : non-régression complète des suites de tests du module hôte.**

### P0-A — Catalogue : nature `prestation` (PRQ-02)

```
[PRÉAMBULE — module CATALOGUE ; contrat : CDC_Achat_v2.md décision A5,
SFD_Catalogue.md décisions C9/C10]
OBJECTIF : 5e nature d'article, non stockable, pour les commandes de services.
À FAIRE :
- Migration : ajout de 'prestation' à l'enum nature ; est_stockable=false
  imposé pour cette nature (même mécanisme que licence, C10).
- Formulaire article : nature Prestation → masque les champs de stock
  (seuil, unité facultative), pas de logiciel, pas de catégorie équipement ;
  badge de nature "S" (couleur à ajouter au formatters.js partagé).
- Gardes : toute tentative d'écriture de stock sur une prestation → 422
  ("Un article non stockable ne peut pas entrer en stock") — même garde que
  les licences, étendue.
- API §2.1 d'API_Inter_Modules.md : la nature apparaît dans les réponses et
  le filtre nature l'accepte ; snapshot mis à jour.
- Génération de code : préfixe PRE-#####.
CRITÈRES : un article prestation se crée, se recherche, se commande (test de
fixture pour Achat) et ne peut JAMAIS toucher Stock.
TESTS : formulaire conditionnel, gardes 422, API+snapshot, code auto,
non-régression Catalogue intégrale.
```

### P0-B — Catalogue : compte comptable (PRQ-03)

```
[PRÉAMBULE — module CATALOGUE]
OBJECTIF : le champ d'imputation qui débloquera l'état par imputation d'Achat.
À FAIRE : migration catalogue_articles.compte_comptable (string nullable,
format libre v1) ; champ au formulaire (aide : "plan comptable de
l'établissement") ; colonne dans la fiche et l'export ; exposition dans
l'API fiche compacte (§2.2) + snapshot ; reprise facultative : commande
artisan catalogue:importer-comptes {csv} (code article ; compte).
CRITÈRES : nullable partout (aucun blocage de flux existant) ; l'API l'expose.
TESTS : CRUD, API+snapshot, import CSV (doublons, codes inconnus), non-régression.
```

### P0-C — Catalogue : endpoint journal des prix (§2.4 du contrat)

```
[PRÉAMBULE — module CATALOGUE]
OBJECTIF : GET /catalogue/api/articles/{id}/journal-prix (API_Inter_Modules §2.4).
À FAIRE : lecture du journal d'activité Catalogue filtré sur les modifications
de prix_indicatif de l'article, 12 derniers mois, borné 50 ; réponse
{date, ancien, nouveau, par} ; permission catalogue.api.view ; snapshot.
CRITÈRES : alimente PO-01 et le signal "réf. modifiée < 30 j" d'Achat (A14)
sans exposer le reste du journal.
TESTS : snapshot, bornage, 403, article sans historique (tableau vide).
```

### P0-D — Composants partagés : extraction paramétrable (⚠ PATTERNS.md)

```
[PRÉAMBULE — modules CATALOGUE et STOCK ; refactor SANS changement visuel]
OBJECTIF : rendre réutilisables par Achat les 3 composants identifiés en
session UX n°2, sans dupliquer le code.
À FAIRE :
1) MODALE DE SÉLECTION (actuelle "Sélectionner un article" de Stock) :
   options de configuration — mode: 'radio'|'multi', colonnes additionnelles
   (ex. TVA, ★ fournisseur préféré), tri paramétrable, texte du pied,
   persistance de sélection entre recherches (mode multi). Stock reste en
   radio À L'IDENTIQUE (non-régression pixel).
2) WIZARD DE RÉFÉRENCEMENT (étape ② des entrées Stock) : configuration de
   colonnes [{champ, libellé, type, requis, unique}], champ commun
   "appliquer à toutes" optionnel, import/collage avec rapport, tampon
   injectable (table fournie par le module hôte). Stock inchangé.
3) RÉCAPITULATIF DE DOCUMENT : partiel générique (en-tête + lignes + totaux
   + encarts) utilisé par la validation Stock ; paramétrable pour l'étape ②
   et le visa d'Achat.
- Documenter les 3 composants dans PATTERNS.md (signature, options, exemples).
CRITÈRES : suites Stock et Catalogue vertes SANS modification des assertions ;
captures avant/après identiques.
TESTS : non-régression totale + tests unitaires des options de configuration.
```

### P0-E — Documents transverses : DESIGN.md et SFD Stock

```
[PRÉAMBULE — documentation uniquement, aucun code]
OBJECTIF : consigner les conventions nées du chantier Achat.
À FAIRE :
- DESIGN.md : sémantique du JAUNE (« verrouillé, en cours d'officialisation »),
  code des alertes (rouge action / orange vigilance / orange hachuré dette),
  pied de page à compteurs (doctrine), 403 nominatives (gabarit), pilules de
  statut Achat.
- SFD_Stock.md : consigner l'écart D10 constaté (le tampon porte l'ÉTAT par
  unité — §6.2 à mettre à jour) ; référencer RACCORDEMENT_Achat_Stock.md et
  API_Inter_Modules.md comme amendements liés.
CRITÈRES : relecture croisée équipes Stock/Catalogue ; versions committées.
```

> **Note** : la carte de rapport Stock « réceptions vs ajustements » (UX4-06) et le lien ParcInfo « commande d'origine » dépendent de données créées par D-11/D-12/D-15 — ils restent planifiés là-bas, pas en préalable.

---

# VOLUME 3 — Backlog v2 (après D-20, ordre de valeur décroissante)

### D-21 — « Commander » depuis l'alerte de seuil Stock

```
[PRÉAMBULE — modules STOCK et ACHAT ; contrat : API_Inter_Modules §8]
OBJECTIF : fermer la boucle rupture → commande (la fin du WhatsApp de Salamata).
À FAIRE : sur l'alerte de seuil Stock (dashboard + état des stocks), bouton
"Commander" (visible si permission achat.bons_commande.store) → POST
/achat/api/bons-commande/brouillon-depuis-articles {article_ids[]} → crée un
BROUILLON pré-rempli (fournisseur préféré majoritaire, quantités = seuil×2
paramétrable, prix pré-remplis) → redirection A-03 étape ①. Nouveau contrat
§4.7 à ajouter à API_Inter_Modules.md + snapshot. Journal : "créé depuis
l'alerte de seuil (article X)".
TESTS : création pré-remplie, permissions croisées, articles multi-fournisseurs
(choix demandé), snapshot.
```

### D-22 — Notifications (visa, renvoi, réception)

```
[PRÉAMBULE]
OBJECTIF : les acteurs n'ont plus à guetter.
À FAIRE : notifications Laravel (mail + cloche Core si disponible) :
BC soumis → validateurs ; renvoyé → auteur (avec motif) ; réception intégrée
→ auteur du BC ; reliquat dépassant le délai → auteur + validateur (digest
hebdomadaire, pas un mail par reliquat). Préférences simples par utilisateur
(on/off par type) dans A-08 ou le profil Core. AUCUNE logique métier dans les
notifications (lecture seule des états).
TESTS : déclenchements, destinataires par permission, digest, opt-out.
```

### D-23 — Duplication de BC et commandes récurrentes

```
[PRÉAMBULE]
OBJECTIF : le trimestre de consommables en deux clics.
À FAIRE : action "Dupliquer" (fiche + liste, tout statut sauf ANNULE) →
nouveau BROUILLON : lignes copiées MAIS valeurs re-figées AU JOUR DE LA
DUPLICATION (nouveau prix indicatif proposé, ancien prix négocié affiché en
référence PO-01), fournisseur re-vérifié actif, aucune donnée de livraison.
Chronologie : "dupliqué depuis BC-2026-0041".
TESTS : re-figeage (IA-2 étendu), articles désactivés (ligne marquée),
permissions.
```

### D-24 — État par imputation comptable (activation post-PRQ-03)

```
[PRÉAMBULE]
OBJECTIF : lever la carte grisée de A-07.
À FAIRE : dès que compte_comptable est peuplé au Catalogue : figer le compte
sur la ligne À LA SAISIE (même doctrine que prix/TVA — migration
achat_lignes_commande.compte_comptable) ; carte "Dépenses par imputation"
(agrégats, période, exports) ; les lignes sans compte apparaissent en
"Non imputé" (jamais masquées).
TESTS : figeage, agrégats, "non imputé", exports.
```

### D-25 — Cadrage du portail de demande (Dr Zongo) — prompt d'étude, pas de code

```
[PRÉAMBULE — livrable : CADRAGE_Demandes_Achat.md]
OBJECTIF : instruire le circuit de demande v2 sans l'implémenter.
À FAIRE : sur la base de A13 (service demandeur déjà tracé) : périmètre
(demande → arbitrage → BC lié), acteurs (demandeur SANS accès aux dépenses
globales — reprendre ENF-SEC-04), impacts sur les statuts et le visa
(D3 : le second visa reste-t-il exclu ?), écrans candidats (portail minimal),
re-répartition des exigences W du CDC. Format : décisions à trancher
(Z1, Z2…) comme les A1-A16.
```

### D-26 — Rapprochement BC ↔ factures — prompt d'étude

```
[PRÉAMBULE — livrable : CADRAGE_Rapprochement_Comptable.md]
OBJECTIF : préparer l'interface avec le système comptable (3-way match :
commandé / reçu / facturé). Étudier : point d'entrée (import ? API ?),
rapprochement par numéro de BC, écarts tolérés, restitution (carte de rapport,
signaux). Aucun code.
```

---

# Conduite de chantier — règles communes aux volumes

### Definition of done d'un prompt (à vérifier avant de passer au suivant)

1. Tous les tests du prompt verts, **sur SQLite ET PostgreSQL** ;
2. Non-régression : suites complètes du module hôte (et de Stock/Catalogue si touchés) vertes ;
3. Aucune permission déclarée sans fonctionnalité, aucune fonctionnalité sans permission (script de contrôle) ;
4. Textes conformes à `SPEC_UX_Achat.md` §15 (ou amendement de la SPEC proposé) ;
5. Snapshots API figés/mis à jour + `API_Inter_Modules.md` amendé si contrat touché ;
6. Note de livraison : *fait / écarts vs SFD (avec proposition d'arbitrage) / dette laissée (avec ticket)*.

### Gestion des écarts

Un écart découvert en développement ne se code jamais silencieusement : il produit une **proposition d'amendement** (SFD, SPEC ou contrat API) arbitrée avant merge. Le document s'amende, il ne se contourne pas — c'est la règle qui a manqué au module v1.

### Ordre global consolidé

```
P0-A → P0-B → P0-C → P0-D → P0-E   (préalables, parallélisables sauf P0-D)
   └──▶ D-01 → … → D-09            (phase A — Achat autonome)
             └──▶ D-10 → D-11 → D-12   (phase B — conjoint Stock, gel de version)
                        └──▶ D-13 → D-14 → D-15 → D-16 → D-17 → D-18
                                   └──▶ D-19 → D-20   (livraison)
                                              └──▶ D-21 → D-22 → D-23 → D-24 → D-25 → D-26 (v2)
```

---

*Volumes 2 et 3 — 08/08/2026. Le recueil couvre désormais : préalables (P0-A→E), construction (D-01→D-20), backlog v2 (D-21→D-26), et la conduite de chantier.*

---
---

# VOLUME 4 — Extension : les bordereaux de réception (BR-01 → BR-05)

> **Objet** : donner une existence documentaire complète à la réception — le **BL fournisseur** (papier du livreur, numérisé) et le **bordereau de réception** (PDF généré par Stock à la validation, contre-signé), visibles depuis Stock ET depuis la fiche BC d'Achat.
> **Placement dans le séquencement** : BR-01 et BR-02 s'exécutent dans le module **Stock**, après D-11 (le lien BC existe) ; BR-03 côté Achat après D-12 ; BR-04/BR-05 ferment. Idéalement : `… D-12 → BR-01 → BR-02 → BR-03 → D-13 …`, ou en lot séparé avant D-19.
> **Répercussions documentaires** (à faire dans BR-05) : `API_Inter_Modules.md` §3.2/§4.6, `SPEC_UX_Achat.md` (onglet Réceptions), `SFD_Stock.md` (documents d'entrée + gabarit PDF), `RACCORDEMENT_Achat_Stock.md` §4.

### BR-01 — Stock : le BL fournisseur numérisé sur le bon d'entrée

```
[PRÉAMBULE — module STOCK ; réutiliser les patterns documents d'Achat D-09
(stockage hors racine, MIME serveur, permissions dédiées) — si D-09 n'est pas
encore livré, extraire d'abord le service de stockage en composant partagé]
OBJECTIF : attacher le bordereau papier du livreur au bon d'entrée.
À FAIRE :
- Migration stock_documents_entree : entree_id FK cascade (brouillon) /
  protection applicative après validation ; type enum('bl_fournisseur',
  'photo_livraison','autre') ; chemin, nom_original, mime, taille, created_by ;
  pierre tombale (est_supprime, motif, par, le) — même doctrine qu'Achat A16.
- Étape ① du bon d'entrée : zone "📎 Bordereau du fournisseur" à côté de
  Référence externe — dépôt PDF/image, CAMÉRA sur mobile (le magasinier
  photographie le BL au comptoir) ; plusieurs pièces possibles ; vignette +
  suppression libre en brouillon.
- Après validation : consultation sous permission stock.documents.view ;
  suppression via modale à motif → pierre tombale.
- Lien fort avec la référence : si un BL est joint et reference_externe vide,
  suggestion douce "Reportez le n° du BL dans Référence externe" (jamais bloquant
  par défaut ; paramètre Stock bl_obligatoire_si_commande=false en v1 —
  s'il passe à true : 422 à la validation d'une entrée LIÉE À UN BC sans pièce
  bl_fournisseur, message : "Joignez le bordereau du fournisseur (paramètre
  de l'établissement)").
CRITÈRES : uploads sécurisés (types/taille/MIME) ; URL directe inaccessible
sans permission ; pierre tombale post-validation ; paramètre testé dans les
deux positions.
TESTS : dépôt (formats, taille), 403 téléchargement, tombstone, garde
paramétrable, non-régression entrées.
```

### BR-02 — Stock : le PDF « Bordereau de réception » généré à la validation

```
[PRÉAMBULE — module STOCK ; gabarit : même charte que le PDF de BC (SPEC_UX §17)]
OBJECTIF : la preuve de réception imprimable et signable — le pendant "entrée"
du bon de commande.
À FAIRE :
- GET /stock/entrees/{id}/bordereau-reception (permission stock.entrees.index,
  disponible uniquement statut VALIDÉ) : PDF A4 —
  en-tête "Bordereau de réception N° ENT-2026-0034" + QR du numéro ;
  bloc références : date de livraison, magasin, fournisseur,
  **BC lié "BC-2026-0041"** (si raccordé) + n° de BL fournisseur
  (reference_externe) ; tableau des lignes REÇUES (article, qté, état pour les
  équipements, n° de série listés en annexe si nature E) ; observation typée
  ("Livraison conforme" / "Écart BL — réclamation" + texte) ;
  DEUX cadres de signature : "Le Magasinier" (nom pré-imprimé) / "Le Livreur" ;
  pied paginé.
- Filigrane "CONTRE-PASSÉ PARTIELLEMENT" si un contre-mouvement existe sur
  l'entrée (avec renvoi au n° du contre-mouvement).
- Bouton "🖨 Bordereau de réception" sur la fiche de l'entrée validée et dans
  la liste (actions des validées).
CRITÈRES : montants ABSENTS du bordereau par défaut (document de quai, pas de
prix sous les yeux du livreur) — paramètre afficher_couts_bordereau=false ;
annexe des n° de série exacte ; QR lisible.
TESTS : contenus (lignes, séries, BC lié, observation), filigrane
contre-passation, paramètre coûts, 403, statut non validé → 404.
```

### BR-03 — Achat : les bordereaux visibles depuis la fiche BC

```
[PRÉAMBULE — module ACHAT ; dépend de D-12, BR-01, BR-02]
OBJECTIF : l'onglet Réceptions devient le dossier documentaire complet de la
livraison, sans quitter Achat.
À FAIRE :
- API_Inter_Modules §3.2 étendu (côté Stock) : chaque entrée liée expose
  documents: [{id, type, nom_original}] et bordereau_pdf: url|null ;
  §4.6 (côté Achat) reflète l'information ; snapshots mis à jour.
- Onglet Réceptions (A-04), cartes "Intégrées" : deux liens s'ajoutent —
  "🖨 Bordereau de réception" (ouvre le PDF BR-02) et "📎 BL fournisseur (2)"
  (liste déroulante des pièces, téléchargement via une route proxy Achat
  GET /achat/bons-commande/{id}/receptions/{entree}/documents/{doc} qui
  VÉRIFIE achat.documents.view PUIS relaie le fichier Stock — jamais d'URL
  Stock exposée à un utilisateur sans droits Stock).
- Section "En cours côté magasin" : indicateur "📎 BL joint" (oui/non) —
  informatif.
- Chronologie du BC : événement "BL fournisseur joint à ENT-2026-0034" relayé.
CRITÈRES : un profil Achat sans AUCUN rôle Stock consulte les bordereaux de
SES commandes (c'est le but) ; un profil sans achat.documents.view ne voit
rien ; dégradation partielle si l'API Stock est coupée.
TESTS : proxy (200/403 selon les DEUX permissions), snapshots, dégradation,
chronologie.
```

### BR-04 — Le rapprochement BL ↔ saisie (écarts de livraison)

```
[PRÉAMBULE — modules STOCK (saisie) et ACHAT (restitution)]
OBJECTIF : tracer l'écart entre ce que le BL papier annonce et ce qui est
réellement compté — aujourd'hui perdu dans un texte libre.
À FAIRE :
- Étape ① Stock, si observation = "Écart BL — réclamation" : mini-tableau
  structuré facultatif par ligne concernée : quantite_annoncee_bl (saisie),
  quantite_comptee (celle du bon), motif court (pilules : "Manquant" /
  "Endommagé — refusé" / "Excédent refusé") — stocké en JSON
  stock_entrees.ecarts_bl.
- Bordereau PDF (BR-02) : bloc "Écarts constatés" listant ces lignes —
  c'est la pièce de réclamation à faire signer AU LIVREUR.
- Côté Achat : la carte de réception affiche la pilule rouge "Écart BL" +
  détail au dépliage ; NOUVELLE donnée du rapport Signaux (D-16) :
  "écarts BL par fournisseur" (taux de livraisons avec écart) — le
  fournisseur qui annonce 10 et livre 8 se lit sur une ligne.
- La chronologie du BC porte l'écart ("Réception ENT-… intégrée — écart BL
  déclaré : 2 manquants").
CRITÈRES : structuré mais FACULTATIF (jamais un frein au quai) ; l'écart
n'affecte AUCUN compteur (les reliquats ne connaissent que le compté —
RGC-02 inchangé) ; il documente et signale, c'est tout.
TESTS : saisie JSON, PDF, remontée Achat, agrégat Signaux, neutralité des
compteurs.
```

### BR-05 — Durcissement, contrats et documents

```
[PRÉAMBULE — transverse]
OBJECTIF : fermer proprement l'extension.
À FAIRE :
- Amendements : API_Inter_Modules.md (§3.2, §4.6, snapshots, note proxy) ;
  SPEC_UX_Achat.md (onglet Réceptions : liens bordereaux, pilule Écart BL ;
  §15 : textes des nouveaux messages) ; SFD_Stock.md (documents d'entrée,
  gabarit bordereau, paramètres bl_obligatoire / afficher_couts) ;
  RACCORDEMENT_Achat_Stock.md §4 ; SFD_Achat.md §7.7 (9e signal : écarts BL).
- Tests transverses : matrice permissions étendue (proxy croisé), invariant
  nouveau IA-16 "un document de réception n'est jamais accessible sans la
  permission du module consulté", suites bi-SGBD.
- Recette : 3 scénarios ajoutés au cahier — dépôt du BL au comptoir (mobile),
  bordereau signé livreur avec écarts, consultation croisée depuis Achat.
CRITÈRES : zéro divergence document/code ; CI verte.
```

### Récapitulatif du volume

| Prompt | Module | Apporte |
|---|---|---|
| BR-01 | Stock | BL fournisseur numérisé (caméra au comptoir), pierre tombale, garde paramétrable |
| BR-02 | Stock | PDF « Bordereau de réception » signable livreur/magasinier, sans coûts, annexe des séries |
| BR-03 | Achat | Consultation croisée depuis la fiche BC (proxy sécurisé, dégradation partielle) |
| BR-04 | Les deux | Écarts BL structurés → pièce de réclamation + 9e signal d'intégrité |
| BR-05 | Transverse | Contrats, docs, IA-16, recette |

---

*Volume 4 — 08/08/2026. Insertion recommandée : après D-12 (ou en lot avant D-19). Le bordereau devient le troisième document de la chaîne : BC (engagement) → bordereau de réception (preuve) → dossier BC complet (audit).*
