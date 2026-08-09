# Spécification UX détaillée — Module Achat

> **Version** : 1.1 — 08/08/2026 (Partie 1 : écrans et composants · Partie 2 : textes, responsive, PDF, accessibilité, performance, wireframes) · **Statut** : projet, à valider avant maquettes
> **Sources consolidées** : `CDC_Achat_v2.md` (+ amendements `soumis`, service demandeur, extinction régularisation, pierre tombale) · `RACCORDEMENT_Achat_Stock.md` · sessions de brainstorming UX n°1 à n°4 (décisions UX-xx, UX2-xx, UX3-xx, UX4-xx) · captures réelles du module Stock (gabarits de référence).
> **Système d'identifiants** : écrans **A-xx** · modales **M-xx** · confirmations SweetAlert **SW-xx** · popovers **PO-xx** · états vides **EV-xx** · boutons désignés `[Libellé]`.
> **Périmètre de ce document** : structure, composants, boutons, modales, popovers, états et interactions de chaque écran. Hors périmètre (à produire ensuite) : maquettes graphiques, textes définitifs de tous les messages, spécification responsive par point de rupture, gabarit PDF.

---

## 0. Conventions transverses (s'appliquent à tous les écrans)

### 0.1 Chrome (identique au module Stock — UX2-02)

| Élément | Spécification |
|---|---|
| **Topbar** | Logo `CHU-YO \| ACHAT` · burger de repli sidebar · 2 boutons d'accès rapide : `[🏠 TABLEAU DE BORD]` `[📋 BONS DE COMMANDE]` (ce dernier porte le badge rouge « à valider » si l'utilisateur a la permission `valider` et qu'il existe des BC `soumis`) · avatar + nom utilisateur |
| **Sidebar** | Section principale : Tableau de bord / Bons de commande (badge 🔴N) / Reliquats / Rapports · Section RÉFÉRENTIELS : Administration · Section AUTRES MODULES : Catalogue, Stock, Gestion RH, Organisation, Parc Informatique · lien `ACCUEIL GÉNÉRAL` |
| **Fil d'Ariane** | `Accueil / Achat / {Page} / {Objet}` — chaque segment cliquable |
| **Pied de page** | Copyright à gauche · `CHU-YO \| Module Achat` à droite |

### 0.2 Sémantique des statuts et couleurs (UX2-06, ⚠️ DESIGN.md)

| Pilule | Couleur | Sens projet |
|---|---|---|
| `Brouillon #id` | Gris | Modifiable, non engageant, sans numéro |
| `Soumis` | **Jaune** | **Verrouillé, en cours d'officialisation** (même sens que `Référencement`/`Pointage` de Stock) |
| `Validé` | Bleu | Engageant, immuable, réceptions ouvertes |
| `Partiel` | Orange | Partiellement livré, reliquat ouvert |
| `Livré` | Vert | Soldé |
| `Clôturé` | Noir | Reliquat abandonné volontairement (motif journalisé) |
| `Annulé` | Rouge | Sans effet |
| Bandeau `RÉGULARISATION` | Orange hachuré | BC d'intérim, hors workflow de réception |

Alertes des KPI : rouge = action attendue de l'utilisateur courant · orange = vigilance · orange hachuré = dette de régularisation.

### 0.3 Doctrine des actions (UX-06)

- Action **interdite par les droits** → **absente** de l'écran (jamais grisée).
- Action **impossible par l'état** → **grisée + infobulle-diagnostic** obligatoire : `Valider (2 lignes sans prix)`, `Soumettre (aucune ligne)`, `Modifier — bon validé : utilisez l'annulation ou la clôture` (UX3-03).
- Toute action irréversible passe par un **SweetAlert récapitulatif chiffré** mentionnant l'irréversibilité.
- Anti-double-soumission systématique ; toute attente serveur > 1 s : bouton en état `spinner + libellé en cours` (« Validation en cours… »).

### 0.4 Compteurs (UX2-05, UX2-13)

Pied de page à compteurs sur tout écran de saisie (`3 ligne(s) · 12 unité(s) · Total : 4 260 000 FCFA HT · 5 026 800 FCFA TTC`) ; total filtré en pied de chaque liste Bootstrap Table ; bandeaux de progression sur les wizards (« 12/25 »). Tout montant est qualifié **HT** ou **TTC**, sans exception, écrans, exports et PDF compris.

### 0.5 États transverses

| Cas | Comportement |
|---|---|
| **403** | Page 403 du projet affichant le libellé **et le nom technique** de la permission manquante (`achat.bons_commande.valider`) (UX3-03, ⚠️ convention commune) |
| **409 (concurrence)** | « Ce brouillon a été modifié depuis votre ouverture (par {user}, {heure}). `[Recharger]` `[Écraser]` » — jamais de fusion silencieuse (UX3-05) |
| **422** | Erreurs mappées champ par champ, y compris lignes dynamiques, avec bascule automatique vers l'onglet/l'étape fautive |
| **Référentiel dégradé** | Fournisseur ou article désactivé au Catalogue : marquage bandeau/ligne orange, jamais de destruction de brouillon (UX3-05) |
| **Premier lancement** | Encart d'accueil refermable « Le circuit en une phrase : Catalogue → BC → visa → réception magasin → parc » + mini-schéma (UX3-02) |

---

## A-01 — Tableau de bord · `/achat` · permission `achat.dashboard.view`

**Dérivé de** : dashboard Stock §3.1. **Objet** : point d'entrée quotidien ; chaque chiffre est cliquable vers la liste filtrée qui le justifie (UX-03) — un KPI non justifiable est retiré.

### Zones et composants

**Z1 — Rangée de cartes KPI** (6 cartes, ordre fixe) :

| Carte | Valeur | Couleur | Clic → | Visible si |
|---|---|---|---|---|
| **À valider** | Nombre de BC `soumis` | Rouge si > 0 | A-02 filtrée `statut=soumis` | permission `valider` (sinon carte masquée) |
| Engagé du mois | Montant, libellé `HT` + bascule | Neutre | A-07 (état des BC, mois courant) | tous |
| BC ouverts | `validé` + `partiel` | Neutre | A-02 filtrée | tous |
| **Reliquats > N j** | Nombre (N = paramètre EF-ADM) | Orange si > 0 | A-06 filtrée `> N j` | tous |
| En cours de réception | X bons d'entrée liés non validés | Bleu info | Liste des entrées **Stock** filtrée (lien croisé) | tous |
| **Dette d'intérim** | Équipements non rattachés | Orange hachuré si > 0 ; carte **masquée à zéro** | Rapport de régularisation | tous (UX2-12) |

**Z2 — Graphique** : « Dépenses engagées — 12 mois glissants » ; barres mensuelles, **mois vides inclus, ordre chronologique** (leçon AN-07) ; sélecteur `HT \| TTC` (défaut HT) ; infobulle par barre : mois, montant, nb de BC ; clic sur une barre → A-07 filtré sur le mois.

**Z3 — Tableau « Reliquats les plus anciens »** (5 lignes) : Article · Fournisseur · Reste · Âge (badge) · lien fiche BC. Lien `[Voir tous les reliquats →]` → A-06.

**Z4 — Fil « 10 derniers événements »** : issu du journal (validations, réceptions intégrées, clôtures, renvois) ; format « {icône} {phrase au passé} — {auteur}, {date} » ; clic → fiche concernée.

**Z5 — Actions rapides** : `[+ Nouveau bon de commande]` (permission `store`) · `[Mes brouillons]` (A-02 filtrée `créé par moi + brouillon`) · `[Rapports]`.

### États

- **EV-01** (module vide) : Z2–Z4 remplacées par « Aucun bon de commande pour l'instant. `[Créer le premier BC]` — ou explorez le `[Catalogue]` pour préparer vos articles. »
- Chargement : squelettes de cartes ; les KPI apparaissent progressivement.

---

## A-02 — Bons de commande, liste · `/achat/bons-commande` · permission `achat.bons_commande.index`

**Dérivé de** : liste des entrées Stock §3.4 (Bootstrap Table serveur, actions selon statut).

### Zone de filtres (au-dessus du tableau)

| Composant | Détail |
|---|---|
| Recherche libre | **Accepte tous les formats de numéros** : `BC-2026-0041`, `ENT-2026-0034` (redirige vers le BC lié), n° de série (redirige via la chaîne), texte libre (UX3-01) |
| Filtres | Statut (pilules multi : Brouillon/Soumis/Validé/Partiel/Livré/Clôturé/Annulé) · Fournisseur (Select) · Période (du/au) · case `Régularisations uniquement` |
| Bouton | `[+ Nouveau bon de commande]` (permission `store`) · menu `[⋮]` → `Nouveau BC de régularisation` (permission `regulariser`, **visible seulement si dette d'intérim > 0** — extinction UX4-05) |

### Tableau (pagination serveur, tri par colonne)

| Colonne | Contenu |
|---|---|
| Numéro | `BC-2026-0041` ou `Brouillon #12` (gris) ; pictogramme 🔶 hachuré si régularisation |
| Fournisseur | Libellé (dénormalisé si supprimé au Catalogue) |
| Date | Date du document |
| Service demandeur | Libellé ou `—` (UX3-04) |
| Lignes | Nombre |
| Montant TTC | Aligné à droite, `FCFA` |
| Livraison | Mini-barre de progression `{livré}/{commandé}` unités (masquée avant validation) |
| Statut | Pilule §0.2 |
| Créé par | Nom |
| Actions | Selon statut et droits — voir grille ci-dessous |

**Grille actions × statut** (rappel session 2 ; une action sans droit est absente, une action bloquée par l'état est grisée + infobulle) :

| Statut | Actions affichées |
|---|---|
| Brouillon | `[👁 Voir]` `[✏ Modifier]` `[🗑 Supprimer]` (Swal simple) `[Soumettre]` |
| Soumis | `[👁 Voir]` — et pour le validateur : `[✔ Valider]` `[↩ Renvoyer]` (ouvre M-06) ; pour l'auteur : `[↩ Reprendre]` (renvoi à soi-même, tracé) |
| Validé / Partiel | `[👁 Voir]` `[🖨 PDF]` ; `[✏]`/`[🗑]` grisés → infobulle « Bon validé — utilisez l'annulation ou la clôture » |
| Livré / Clôturé / Annulé | `[👁 Voir]` `[🖨 PDF]` (sauf Annulé : pas de PDF) |

Pied de tableau : `{n} bons · Total affiché : {X} FCFA TTC` (compteur du filtre courant). **EV-02** : « Aucun BC ne correspond à ces filtres. `[Réinitialiser]` · `[Nouveau BC]` ».

---

## A-03 — Bon de commande, création/édition · `/achat/bons-commande/create`, `/{id}/edit` · permission `store`/`update`

**Dérivé de** : « Nouveau bon d'entrée » Stock (page à stepper). **Stepper 2 étapes** (UX2-03) : `① Lignes & montants` — `② Récapitulatif & soumission`. Accessible uniquement en statut `brouillon` (sinon redirection fiche + toast explicatif).

### Étape ① — Lignes & montants

**Bandeau permanent** : `Brouillon #12 — modifiable, non engageant, sans numéro` (gris). Si mode régularisation : bandeau supplémentaire orange hachuré `RÉGULARISATION — acquisition de la période d'intérim, hors workflow de réception`.

**Carte « En-tête »** :

| Champ | Type | Règles / interactions |
|---|---|---|
| Fournisseur * | Select (recherche) — mention sous le champ : « Référentiel du module Catalogue » | Verrouillé 🔒 dès qu'une ligne existe (infobulle « Videz les lignes pour changer de fournisseur ») — évite les lignes orphelines d'un autre fournisseur préféré |
| Date * | Datepicker, défaut aujourd'hui | Mode régularisation : bornée à la période d'intérim (27/07/2026 → mise en service), infobulle si hors bornes (UX4-05) |
| Service demandeur | Select Organisation (facultatif) + champ `Réf. demande papier` | Imprimés sur le PDF ; filtrables (UX3-04) |
| Observation | **Pilules à choix rapide** : `Urgent` · `Renouvellement périodique` · `Sur demande de service` · `Autre` (déplie un texte requis) | La pilule « Sur demande de service » pré-ouvre les champs Service demandeur |

**Carte « Lignes du bon (N) »** — tableau de lignes dynamiques :

| Colonne | Détail |
|---|---|
| Nature | Badge C/E/P/L/S (formatters.js du Catalogue) |
| Article | `CODE — Désignation` ; sous-libellé pour les licences : « Logiciel : {nom} » ou ⚠ rouge « Aucun logiciel rattaché — la réception sera impossible `[Corriger au Catalogue →]` » (contrôle préventif, UX2-10) |
| Qté * | Numérique > 0 |
| Prix négocié * (FCFA HT) | Pré-rempli du prix indicatif ; sous le champ, en petit gris : `réf. 650 000` + **PO-01** ; pilule d'écart `+28 % vs dernier payé` (orange, informative) si écart > seuil config — la référence est le **dernier prix payé**, à défaut le prix indicatif (UX4-02) |
| TVA % | **Pilule cliquable** `18 %` (pré-remplie du taux article) → clic = mini-champ d'édition ; retour pilule au blur (UX-04) |
| Sous-total HT | Calculé, lecture seule |
| 🗑 | Supprime la ligne (sans Swal — c'est un brouillon) |

Sous le tableau : zone pointillée `[+ Ajouter des articles]` → ouvre **M-01**. Dès 10 lignes : champ de filtre interne au tableau + sous-totaux par nature (UX3-05).

**Pied de page collant** : compteurs `3 ligne(s) · 12 unité(s) · Total : 4 260 000 FCFA HT · 5 026 800 FCFA TTC` · `[💾 Enregistrer le brouillon]` · `[Continuer → Récapitulatif]` (grisé + diagnostic si 0 ligne ou champs requis manquants).

**Popovers de l'étape ①** :

- **PO-01 « Décomposition du prix »** (clic sur `réf.` ou sur la pilule d'écart) : prix indicatif Catalogue · **dernier prix payé** (BC-2026-0033, 12/06/2026) · moyenne des 3 derniers BC · mention `réf. modifiée au Catalogue le {date} ({ancien} → {nouveau})` si < 30 j (UX4-02) ;
- **PO-02 « Décomposition des totaux »** (clic sur le total du pied) : somme HT par taux de TVA → TVA par taux → TTC (l'outil de diagnostic de Fatou, UX3-03).

**Garde de sortie** : navigation avec modifications non enregistrées → confirmation native « Quitter sans enregistrer ? ».

### Étape ② — Récapitulatif & soumission

Lecture seule (composant **« Récapitulatif de BC »**, partagé avec le visa — UX2-03) : en-tête, tableau des lignes figé, totaux en grande taille, encarts d'avertissement empilés le cas échéant :

- ⚠ orange « 2 lignes s'écartent de plus de 20 % du dernier prix payé » (dépliable ligne à ligne) ;
- ⚠ rouge « 1 ligne licence sans logiciel rattaché » (bloque la soumission, diagnostic sur le bouton) ;
- ℹ bleu (régularisation) « Ce bon documentera une acquisition passée ; il sera exclu des statistiques de dépense ».

Barre collante : `[← Retour aux lignes]` · `[Soumettre au visa]` → **SW-01**.

**SW-01 — Soumission** : « Le bon sera **verrouillé** et transmis au visa. Il recevra son numéro définitif à la validation. — 3 lignes · 5 026 800 FCFA TTC · Sonabel-Info. `[Annuler]` `[Soumettre]` ». Succès : toast 2 s + redirection fiche A-04 (pilule `Soumis` jaune).

### Réouverture d'un brouillon renvoyé

Encart jaune en tête d'étape ① : « **Renvoyé par M. Ouédraogo le {date}** — motif : "{motif}" » (UX2-07), refermable, ré-affichable depuis la chronologie.

---

## A-04 — Bon de commande, fiche · `/achat/bons-commande/{id}` · permission `index` (+ permissions d'action)

**Dérivé de** : fiche validée Stock + fiche magasin à onglets. **L'écran le plus partagé du module** (5 publics).

### Bandeau d'état (toujours visible)

`BC-2026-0041` (ou `Brouillon #12`) · pilule de statut · fournisseur (lien Catalogue) · date · service demandeur · montant TTC en grand · badges contextuels : 🔶 `RÉGULARISATION` · ⚠ `Fournisseur créé il y a 6 j — premier BC` (UX4-03) · `saisi et validé par la même personne` (post-validation, UX4-07).

### Barre d'actions contextuelle (règle §0.3)

| Statut | Boutons (si droits) |
|---|---|
| Brouillon | `[✏ Modifier]` `[Soumettre]` `[🗑 Supprimer]` |
| Soumis | Validateur : `[✔ Valider — 5 026 800 FCFA TTC]` (SW-02) `[↩ Renvoyer]` (M-06) · Auteur : `[↩ Reprendre]` |
| Validé | `[🖨 PDF]` `[✖ Annuler]` (M-07 — présent seulement si aucune réception) `[📎 Ajouter une pièce]` (M-05) |
| Partiel | `[🖨 PDF]` `[🔒 Clôturer le reliquat]` (M-03) `[📎]` |
| Livré / Clôturé | `[🖨 PDF]` `[📎]` |
| Annulé | — (lecture seule) |

Sur mobile : barre **collante** en bas, boutons pleine largeur — c'est LE parcours mobile de première classe (UX3-06).

**SW-02 — Validation (le Swal enrichi, UX2-08 + UX4-07)** :
« **Valider le bon BC-2026-0041 ?**
3 lignes · 5 026 800 FCFA TTC · Sonabel-Info
📊 3ᵉ BC de ce fournisseur ce mois — cumul : 12,4 M FCFA
⚠ Ligne 2 : +29 % vs dernier payé · ⚠ Fournisseur créé il y a 6 jours
Le bon recevra le n° BC-2026-0041 et **ne sera plus modifiable**. `[Annuler]` `[Valider]` »
(Les lignes ⚠ n'apparaissent que si les signaux existent ; leur présence ne bloque jamais.)

### Onglet « Lignes »

Tableau : Nature · Article · Qté commandée · **Qté livrée** · **Reste** · Prix figé HT · TVA · Montant HT · barre de progression par ligne (`6/10`, verte à 100 %). Pied : totaux HT/TVA/TTC + PO-02. Avant validation : colonnes livraison masquées.

### Onglet « Réceptions » (UX2-09, UX-12)

- **Section « Intégrées »** (fait foi) : cartes par bon d'entrée — `ENT-2026-0034 · 20/08 · Magasin CHU-YO · 6 unités · observation : Livraison conforme · [Voir le bon d'entrée →]` ; dépliable : lignes/quantités, équipements sérialisés (liens fiches ParcInfo) ;
- **Section « En cours côté magasin »** (informatif, lecture API Stock) : `Brouillon #58 — créé ce matin par Salamata · 10 unités en référencement (7/10 saisies)` + badge gris `non intégré — sans effet sur les reliquats` + indicateur `📎 BL joint` / `BL non joint` (BR-03, informatif) ;
- **EV-03** (vide) : « Rien n'a encore été livré sur cette commande. Les réceptions se saisissent au magasin (module Stock) et apparaîtront ici. » — la phrase enseigne la frontière (UX3-02).

#### Le dossier documentaire de la livraison (BR-03/BR-04)

Sous chaque carte **Intégrée**, une barre de pièces séparée par un filet :

- `[🖨 Bordereau de réception]` → le PDF de BR-02 dans la **modale d'impression** (jamais un onglet, convention du projet). Absent sur une contre-passation : elle défait une livraison, elle n'en atteste pas ;
- `[📎 Bordereau du fournisseur · 240 Ko]` — une pastille par pièce, libellée par son TYPE et non par son nom de fichier. Sans `documents.view` : pastille grise cadenassée « Consultation réservée » ; pièce supprimée : pastille barrée (pierre tombale) ;
- `[⚠ Écart BL ②]` (rouge, dépliable) → tableau `Désignation · Annoncé au BL · Compté reçu · Écart · Motif`, suivi de la phrase qui évite le contresens : « Seules les quantités comptées sont entrées en stock et déduites du reste à livrer : l'écart déclaré ne modifie aucun compteur. »

Tous ces liens sont des **routes d'Achat** : un acheteur sans aucun droit Stock consulte les pièces de SES commandes, et aucune URL du magasin n'apparaît dans la page (IA-16).

### Onglet « Licences » (visible si ≥ 1 ligne licence ou prestation)

Par ligne : logiciel · reçues/restantes (barre) · `[Réceptionner…]` → A-05 (grisé + diagnostic si BC non validé ou reste = 0) · pour les prestations : `[Constater le service fait]` → M-04. Liste des licences créées (liens ParcInfo).

### Onglet « Documents »

Tableau : Type (pilule : BC signé / Bordereau fournisseur / Facture pro forma / Autre) · Fichier · Déposé par · Date · Actions `[⬇ Télécharger]` (permission `documents.view`) `[🗑]` (permission `documents.delete`).
- Dépôt : `[📎 Ajouter une pièce]` → **M-05** ;
- Suppression **avant** validation du BC : Swal simple ;
- Suppression **après** validation : **M-08** (motif obligatoire) → la ligne devient une **pierre tombale** grisée : `🪦 Pièce supprimée par {user} le {date} — motif : "{motif}"` (UX4-08).

### Onglet « Chronologie »

Timeline verticale du journal réel (UX-07) : création · soumission · **renvois avec motifs** · validation · réceptions intégrées (`ENT-… 6/10`) · **rejets de notification en rouge** (dépassement) · contre-mouvements · clôture · suppressions de pièces. Format : icône + phrase au passé + auteur + horodatage. Aucun événement reconstruit.

---

## A-05 — Réception de licences (wizard) · `/achat/bons-commande/{id}/licences/{ligne}` · permission `receptionner-licences`

**Dérivé de** : étape ② « N° de série » du bon d'entrée Stock — **même composant wizard, colonnes paramétrées** (UX2-10).

### Pré-écran (dans une modale légère)

« Réceptionner combien d'unités ? » — champ quantité, max = reste à livrer, défaut = reste. `[Commencer la saisie]`.
**Contrôle d'ouverture** : si l'article n'a pas de logiciel rattaché (C11) → écran bloqué : « Impossible de réceptionner : l'article {code} n'a pas de logiciel rattaché. `[Corriger la fiche article →]` » (jamais de blocage à la 25ᵉ clé).

### Écran wizard (plein écran)

| Zone | Détail |
|---|---|
| Bandeau | `Réception de licences — BC-2026-0041 · Kaspersky Endpoint` · progression `12/25` |
| Champ commun | « Date d'activation — `[Appliquer à toutes]` » (pré-remplit, modifiable ligne à ligne) |
| Lignes (une par unité) | **Clé de licence*** (autofocus, `Entrée` = suivante) · Date d'activation* · Date d'expiration (facultative) · indicateur ✔/✖ (doublon détecté en direct : champ rouge + « clé déjà saisie ligne 4 ») |
| Import en masse | `[📋 Coller / importer CSV…]` : zone de collage (une clé par ligne) → **rapport** : `22 acceptées · 2 doublons ignorés · 1 vide` |
| Boutons | `[💾 Enregistrer et continuer plus tard]` (tampon sauvegardé **à chaque Entrée** — UX3-05) · `[← Revenir en arrière]` (Swal : « Les 12 clés saisies seront perdues » — purge du tampon, tracé) · `[Finaliser]` (grisé + diagnostic « 13 clés manquantes ») |

**SW-03 — Finalisation** : « **25 licences** seront créées dans le Parc Informatique, rattachées à **Kaspersky Endpoint**, au coût unitaire de **8 500 FCFA HT** (prix de la ligne). **Action définitive.** `[Annuler]` `[Créer les licences]` ». Succès : écran de compte-rendu — `✔ 25 licences créées` + `[Voir dans ParcInfo]` `[Retour à la fiche BC]`. Échec : rollback complet + 422, **jamais de succès affiché** (ENF-FIA-04).

---

## A-06 — Reliquats · `/achat/reliquats` · permission `reliquats.index`

**Dérivé de** : État des stocks Stock §3.3 (lecture seule, filtres, badges).

| Zone | Détail |
|---|---|
| Filtres | Pilules d'âge : `Tous` · `> 30 j` · `> 60 j` · `> N j (param.)` · Select fournisseur · recherche |
| Tableau | BC (lien) · Fournisseur · Article · Commandée · Livrée · **Reste** · **Âge** (badge vert/orange/rouge selon seuils EF-ADM) · Dernière réception · Actions |
| Actions par ligne | `[Voir le BC]` · `[🔒 Clôturer…]` (M-03, permission `cloturer` — au niveau du **BC**, la modale liste toutes ses lignes à reste) · 🔮 `[Relancer]` (backlog v2) |
| Pied | **`Engagé non livré : 9 340 000 FCFA TTC`** (le chiffre d'Issouf — total du filtre courant) |
| **EV-04** | « Aucun reliquat — toutes les commandes validées sont soldées ✔ » (l'état vide est une bonne nouvelle, on le dit) |

---

## A-07 — Rapports · `/achat/rapports` · permission `rapports.view` (+ `rapports.signaux`)

**Dérivé de** : page Rapports Stock §3.9 — grille de cartes, aperçu, exports.

| Carte | Contenu | Exports |
|---|---|---|
| État des bons de commande | Filtres statut/fournisseur/période ; option `Inclure les régularisations` (décochée par défaut, UX-17) | CSV · XLSX · PDF |
| Dépenses par fournisseur | Période paramétrable, HT/TTC | CSV · XLSX · PDF |
| Dépenses par catégorie | Catégories Catalogue | CSV · XLSX · PDF |
| Évolution 12 mois | Le graphique du dashboard, exportable | XLSX · PDF |
| Reliquats | L'état A-06 imprimable | CSV · XLSX · PDF |
| Régularisation de l'intérim | BC de régularisation + **compteur « X équipements non rattachés »** + liste à rattacher | XLSX · PDF |
| **Imputation comptable** | **Carte grisée « Bientôt disponible — en attente du champ compte comptable au Catalogue (PRQ-03) »** (UX-18 : l'attente reste visible) | — |
| **Signaux** 🔒 | Permission dédiée `rapports.signaux` (UX4-09) — 8 indicateurs : écarts au dernier prix payé · prix indicatifs modifiés < 30 j suivis d'un BC · fournisseurs récents à premier BC · BC rapprochés · montants clôturés non livrés (par fournisseur et par auteur) · auto-validations · délai médian de visa par validateur · pierres tombales de pièces | XLSX · PDF |

Règles : chaque PDF porte période, filtres, date d'édition et la mention **HT ou TTC dans le titre** ; cohérence contractuelle dashboard = rapport = export (même source, même arrondi, même périmètre — recettée, UX-18/UX2-05).

---

## A-08 — Administration · `/achat/administration` · permission `administration.manage`

Page simple à cartes de paramètres (chaque modification = toast + journal) :

| Paramètre | Composant |
|---|---|
| Préfixe de numérotation (`BC`) | Champ court + aperçu `BC-2026-0042` |
| Délai d'alerte des reliquats (N jours) | Numérique — pilote badges A-06 et KPI A-01 |
| Seuil d'écart de prix (%) | Numérique (défaut 20) — pilote pilules et signaux |
| Taille max des pièces (Mo) | Numérique |
| **Régularisation** | Interrupteur — **auto-désactivé quand la dette atteint zéro** ; réactivation = Swal d'avertissement + journalisation (UX4-05) ; affiche la dette courante |
| Motifs d'observation (pilules) | Liste éditable (v1 : config simple) |

---

## Répertoire des modales

| ID | Titre | Ouverte depuis | Contenu et boutons |
|---|---|---|---|
| **M-01** | Sélectionner des articles | A-03 ét. ① | **La modale article Stock en mode multi** (UX2-04) : filtre nature · recherche · tableau à **cases à cocher** (Nature badge · Code · Nom · Unité · Prix indicatif · TVA · ★ fournisseur préféré) ; tri « fournisseur de la commande d'abord » ; sélection **persistante entre recherches** ; pied : `4 article(s) sélectionné(s)` · lien D7 « L'article n'existe pas ? `Créez-le dans le module Catalogue →` (nouvel onglet, le brouillon attend) » · `[Annuler]` `[Ajouter au bon]` |
| **M-02** | Sélectionner une commande | Bon d'entrée **Stock** (raccordement §2.2) | Référencée ici pour mémoire — spécifiée dans `RACCORDEMENT_Achat_Stock.md` |
| **M-03** | Clôturer le reliquat | A-04, A-06 | Liste des lignes à reste (cochées, décochables) · **motif*** · Swal intégré : « 6 unités reçues restent au parc · **4 abandonnées (1 660 000 FCFA TTC)** · le bon passera à Clôturé » · `[Annuler]` `[Clôturer]` |
| **M-04** | Constater le service fait | A-04 onglet Licences | Date (défaut aujourd'hui) · commentaire · « La ligne sera marquée réalisée » · `[Annuler]` `[Constater]` — objectif : 10 secondes (UX-15) |
| **M-05** | Ajouter une pièce | A-04 onglet Documents | Type* (pilules) · fichier (glisser-déposer, **caméra acceptée sur mobile** — UX3-06) · contrôle type/taille à la sélection · `[Annuler]` `[Déposer]` |
| **M-06** | Renvoyer en brouillon | A-04, A-02 (statut Soumis) | **Motif*** · « Le bon repassera en brouillon chez {auteur} » · `[Annuler]` `[Renvoyer]` → motif en chronologie + encart jaune (UX2-07) |
| **M-07** | Annuler le bon | A-04 (Validé sans réception) | Motif* · « Le bon sera annulé sans effet — action définitive » · `[Retour]` `[Annuler le bon]` |
| **M-08** | Supprimer une pièce (post-validation) | A-04 onglet Documents | Motif* · « Le fichier sera supprimé ; **la trace de la suppression restera visible** » · `[Annuler]` `[Supprimer]` → pierre tombale (UX4-08) |
| **M-09** | Rattacher des équipements (régularisation) | A-04 (BC de régularisation), rapport Régularisation | Recherche des équipements ParcInfo **sans commande d'origine** · cases à cocher · compteur · `[Rattacher (3)]` → chronologie + décrément de la dette (UX-17) |

## Répertoire des confirmations SweetAlert

| ID | Déclencheur | Message type |
|---|---|---|
| SW-01 | Soumettre | Verrouillage + numéro à la validation + chiffres |
| SW-02 | Valider | Chiffres + **contexte de dépense** + signaux ⚠ + immutabilité |
| SW-03 | Finaliser licences | N licences, logiciel, coût, « action définitive » |
| SW-04 | Supprimer un brouillon | « Brouillon #12 et ses 3 lignes seront supprimés » |
| SW-05 | Revenir en arrière (wizard) | « Les N clés saisies seront perdues » (purge tampon, tracé) |
| SW-06 | Réactiver la régularisation (A-08) | Avertissement + journalisation |

## Répertoire des popovers et infobulles normées

| ID | Emplacement | Contenu |
|---|---|---|
| PO-01 | Prix de ligne (A-03) | Indicatif · dernier payé · moyenne 3 BC · modification récente du Catalogue |
| PO-02 | Totaux (A-03 pied, A-04 Lignes) | Décomposition HT par taux → TVA → TTC |
| PO-03 | Badge d'âge (A-06, A-01) | Seuils configurés et date de dernière réception |
| Infobulles-diagnostic | Tout bouton grisé | Cause exacte, actionnable (« 2 lignes sans prix ») — obligatoires (§0.3) |

## Matrice écrans × permissions (synthèse)

| Écran/Action | Permission |
|---|---|
| A-01 | `achat.dashboard.view` |
| A-02 / A-04 lecture | `achat.bons_commande.index` |
| A-03 création/édition, soumission | `store` / `update` / `soumettre` |
| Valider · Renvoyer | `valider` |
| Annuler · Clôturer | `annuler` · `cloturer` |
| A-05 | `receptionner-licences` |
| Régularisation (création, M-09) | `regulariser` |
| Documents (voir/déposer/supprimer) | `documents.view` / `documents.store` / `documents.delete` |
| A-06 | `reliquats.index` |
| A-07 (+ carte Signaux) | `rapports.view` (+ `rapports.signaux`) · exports : `rapports.export` |
| A-08 | `administration.manage` |
| API inter-modules | `achat.api.view` |

---

## Ce qui n'est PAS encore couvert par cette version (travail restant)

Ce document est **complet sur la structure, les composants et les interactions** des 8 écrans, 9 modales, 6 Swals et 3 popovers. Il n'est **pas fini** sur les points suivants, à produire dans les prochaines itérations :

1. **Textes définitifs** de tous les messages (Swals, 422, états vides) — les formulations ci-dessus sont des « textes types » à faire valider par la MOA ;
2. **Spécification responsive détaillée** : seuls les principes sont posés (parcours visa mobile, barres collantes) ; le comportement par point de rupture (colonnes masquées dans les tableaux, empilement des cartes) reste à écrire ;
3. **Gabarit PDF** du BC (structure, QR code, filigranes) — porté par LIV-04, non détaillé ici ;
4. **Accessibilité** : navigation clavier complète et ordres de tabulation par écran, annonces lecteur d'écran des changements d'état (stepper, wizard) — non traités ;
5. **Spécification fine des états de chargement** (squelettes par zone) et des temps de réponse cibles par interaction ;
6. **Les écrans côté Stock modifiés par le raccordement** (encart de liaison, M-02, plafonds) — spécifiés dans `RACCORDEMENT_Achat_Stock.md`, à fusionner lors de l'amendement du SFD Stock ;
7. **Wireframes/maquettes** : ce document est la spécification textuelle qui les précède (ordre recommandé : A-03, M-01, A-04, A-06, A-01).

---

*Fin de la spécification UX v1.0 — 07/08/2026. Prochaine étape : validation MOA, puis maquettes dans l'ordre recommandé, puis intégration au SFD Achat.*

---
---

# PARTIE 2 — Compléments v1.1 (08/08/2026)

> Cette partie couvre les points listés « non finis » en v1.0 : catalogue des textes définitifs (§15), spécification responsive (§16), gabarit PDF (§17), accessibilité (§18), états de chargement et cibles de performance (§19), wireframes textuels des écrans critiques (§20). La liste des restes est mise à jour en fin de document.

---

## 15. Catalogue des textes (proposés pour validation MOA)

Règles d'écriture : voix active, vouvoiement, chiffres avant les mots, jamais de jargon technique côté utilisateur (le technique vit dans les 403 et les logs), les montants toujours suivis de `FCFA` et qualifiés `HT`/`TTC`, les conséquences irréversibles **en gras**.

### 15.1 Confirmations (textes définitifs)

**SW-01 — Soumission**
> **Soumettre le bon au visa ?**
> Brouillon #12 · 3 lignes · 5 026 800 FCFA TTC · Sonabel-Info
> Le bon sera **verrouillé** pendant l'examen. Il recevra son numéro définitif à la validation.
> `[Annuler]` `[Soumettre au visa]`

**SW-02 — Validation**
> **Valider le bon de commande ?**
> 3 lignes · 5 026 800 FCFA TTC · Sonabel-Info
> 📊 3ᵉ bon de ce fournisseur ce mois-ci — cumul : 12 400 000 FCFA TTC
> ⚠ Ligne 2 : prix supérieur de 29 % au dernier prix payé (645 000 FCFA HT le 12/06/2026)
> ⚠ Fournisseur créé au Catalogue il y a 6 jours — premier bon de commande
> Le bon recevra le numéro **BC-2026-0041** et **ne pourra plus être modifié**.
> `[Annuler]` `[Valider le bon]`

**SW-03 — Finalisation des licences**
> **Créer les licences ?**
> 25 licences **Kaspersky Endpoint** seront créées dans le Parc Informatique, au coût unitaire de 8 500 FCFA HT (prix de la commande).
> **Cette action est définitive.**
> `[Annuler]` `[Créer les 25 licences]`

**SW-04 — Suppression d'un brouillon**
> **Supprimer le brouillon #12 ?**
> Ses 3 lignes et sa pièce jointe seront supprimées. Cette action ne laisse pas de trace : un brouillon n'engage rien.
> `[Annuler]` `[Supprimer]`

**SW-05 — Retour en arrière du wizard**
> **Revenir en arrière ?**
> Les **12 clés déjà saisies seront perdues**. L'action sera consignée dans la chronologie.
> `[Continuer la saisie]` `[Revenir en arrière]`

**SW-06 — Réactivation de la régularisation**
> **Rouvrir la saisie de régularisations ?**
> La dette de l'intérim est à zéro depuis le {date}. La réouverture sera consignée au journal avec votre identité.
> `[Annuler]` `[Rouvrir]`

**M-03 — Clôture (corps de modale, après choix des lignes)**
> 6 unités reçues **restent au parc**. 4 unités (1 660 000 FCFA TTC) seront **définitivement abandonnées**. Le bon passera au statut Clôturé.
> Motif de la clôture * : `[………]`
> `[Annuler]` `[Clôturer le reliquat]`

**M-06 — Renvoi en brouillon**
> Le bon repassera en brouillon chez **Awa Traoré**, qui pourra le corriger et le soumettre à nouveau.
> Motif du renvoi * : `[………]`
> `[Annuler]` `[Renvoyer en brouillon]`

**M-07 — Annulation**
> Le bon **BC-2026-0041** sera annulé **sans aucun effet** : aucune réception n'a été enregistrée.
> Motif de l'annulation * : `[………]`
> `[Retour]` `[Annuler le bon]`

**M-08 — Suppression de pièce (post-validation)**
> Le fichier « devis-sonabel-mars.pdf » sera supprimé. **La trace de cette suppression restera visible** dans l'onglet Documents et la chronologie.
> Motif * : `[………]`
> `[Annuler]` `[Supprimer la pièce]`

### 15.2 Messages d'erreur normalisés (422)

| Code situation | Message affiché (au champ fautif) |
|---|---|
| Ligne sans prix | « Indiquez le prix négocié (le prix de référence est de 650 000 FCFA HT). » |
| Quantité nulle/négative | « La quantité doit être supérieure à zéro. » |
| Aucune ligne à la soumission | Bouton : `Soumettre (aucune ligne)` ; si contourné : « Ajoutez au moins une ligne avant de soumettre. » |
| Licence sans logiciel (ouverture wizard) | « La ligne licence « {code} » n'a pas de logiciel rattaché au Catalogue : la réception serait impossible. » — *texte livré, qui nomme la ligne fautive plutôt que l'article* |
| Clé en doublon (wizard) | « La clé « {clé} » est déjà saisie dans cette réception. » — *texte livré : la clé est citée, ce qui vaut mieux qu'un numéro de ligne dans une grille défilante* |
| Quantité de clés ≠ quantité réceptionnée | Bouton : `Finaliser (13 clés manquantes)` |
| Date hors intérim (régularisation) | « La date doit être comprise entre le 27/07/2026 et le {date de mise en service}. » |
| Dépassement du reste (notification Stock, côté magasin) | « Ligne {désignation} : reste à livrer {reste}, or {annoncé} sont annoncés — pour l'excédent, créez un bon de commande complémentaire ou refusez à la livraison. » — *texte livré, qui rappelle aussi la quantité annoncée* |
| Fichier trop lourd (M-05) | « Le fichier dépasse {N} Mo (taille actuelle : 18 Mo). Compressez-le ou déposez-le en plusieurs parties. » |
| Type de fichier refusé (M-05) | « Format non accepté : joignez un PDF, une image ou un document bureautique. » — *texte livré, aligné sur les extensions réellement admises* |
| Conflit d'édition (409) | « Ce brouillon a été modifié depuis votre ouverture (par vous-même, à 14 h 02, dans un autre onglet). `[Recharger]` `[Écraser avec ma version]` » |
| BL manquant sur une entrée liée (BR-01, si `bl_obligatoire_si_commande`) | « Joignez le bordereau du fournisseur (paramètre de l'établissement). » — au magasin, à la validation du bon d'entrée |
| Écarts déclarés sans le bon motif (BR-04) | « Choisissez le motif "Écart BL — réclamation" pour déclarer des écarts de livraison. » |
| Écart sur un article absent du bon (BR-04) | « Cet article n'est pas sur le bon d'entrée : un écart porte sur une ligne reçue. » |
| Écart sans écart (BR-04) | « Annoncé et compté sont identiques : il n'y a pas d'écart à déclarer. » |
| Pièce d'un autre dossier (BR-03, 404) | « Cette pièce n'appartient pas au dossier de ce bon de commande. » |
| Pièce supprimée (BR-03, 410) | « Cette pièce a été supprimée du dossier de réception. » |

### 15.3 États vides (textes définitifs)

| ID | Écran | Texte |
|---|---|---|
| EV-01 | A-01 | « Aucun bon de commande pour l'instant. `[Créer le premier bon]` — ou explorez le `[Catalogue]` pour préparer vos articles. » |
| EV-02 | A-02 | « Aucun bon de commande ne correspond à ces filtres. » + `[Réinitialiser les filtres]` |
| EV-03 | A-04 Réceptions | « Rien n'a encore été livré sur cette commande. Les réceptions se saisissent au magasin (module Stock) et apparaîtront ici automatiquement. » |
| EV-04 | A-06 | « Aucun reliquat — toutes les commandes validées sont soldées ✔ » |
| EV-05 | A-04 Documents | « Aucune pièce au dossier. Le BC signé, le bordereau du fournisseur ou la facture pro forma se déposent ici pour ne plus dormir dans un classeur. » + `[📎 Ajouter une pièce]` |
| EV-06 | A-04 Chronologie | *(impossible par construction : la création est toujours journalisée)* |
| EV-07 | A-07 carte Signaux | « ✔ Aucun signal sur ce périmètre. » — affiché **par indicateur**, la carte en présentant neuf : un état vide global masquerait les huit autres |

### 15.4 Toasts de succès (2 s, coin haut droit)

`Brouillon enregistré` · `Bon soumis au visa` · `Bon BC-2026-0041 validé` · `Bon renvoyé à Awa Traoré` · `Reliquat clôturé` · `25 licences créées ✔` · `Pièce déposée` · `Paramètre enregistré` · `3 équipements rattachés — dette restante : 9`.

---

## 16. Spécification responsive

Points de rupture du projet (Bootstrap 5) : **XS** < 576 · **SM/MD** 576–991 · **LG+** ≥ 992. Doctrine (UX3-06) : un parcours mobile de première classe (le visa), consultation correcte partout, saisie lourde assumée au poste.

### 16.1 Comportements par écran

| Écran | LG+ (référence) | SM/MD | XS (téléphone) |
|---|---|---|---|
| Chrome | Sidebar fixe | Sidebar repliée (burger) | Sidebar en tiroir ; topbar : logo + burger + badge 🔴 + avatar (les 2 accès rapides passent dans le tiroir) |
| A-01 | 6 KPI sur une rangée | 3 × 2 | Cartes empilées, ordre : À valider → Reliquats → Dette → Engagé → BC ouverts → En cours ; graphique : 6 derniers mois avec balayage horizontal |
| A-02 | Toutes colonnes | Masque : Service demandeur, Créé par | Vue « cartes-lignes » : Numéro + pilule / Fournisseur / Montant TTC / progression ; tap = fiche ; actions dans la fiche uniquement |
| A-03 | Tableau de lignes complet | Masque : Sous-total (visible au tap) | **Saisie déconseillée mais possible** : lignes en cartes empilées ; pied à compteurs réduit à `3 lignes · 5 026 800 TTC` + `[💾]` collant. Bandeau une fois par session : « La saisie est plus confortable sur un poste de travail. » |
| A-04 | Onglets horizontaux | Idem | **Parcours de première classe** : bandeau compacté (numéro, pilule, montant), onglets balayables, **barre d'actions collante en bas, boutons pleine largeur** ; SW-02 plein écran |
| A-05 | Wizard 2 colonnes (liste + saisie) | 1 colonne | Utilisable (clé au clavier), mais collage en masse recommandé ; boutons collants |
| A-06 | Tableau complet | Masque : Dernière réception | Cartes-lignes : Article / Reste / badge d'âge / lien BC ; total engagé non livré collant en bas |
| A-07 | Grille 2 colonnes de cartes | 1 colonne | 1 colonne ; aperçus remplacés par le bouton d'export direct |
| A-08 | Cartes | 1 colonne | Lecture seule recommandée (bandeau) |
| Modales | Centrées | Centrées | **Plein écran** (M-01, M-03, M-05, M-09) ; M-04/M-06/M-07/M-08 restent en feuille basse (bottom sheet) |

### 16.2 Règles transverses mobiles

Zones tactiles ≥ 44 px · barres d'action toujours collantes · datepickers natifs · M-05 propose `[📷 Prendre une photo]` en premier sur XS · aucune interaction au survol obligatoire : tout popover (PO-01/02/03) s'ouvre aussi au tap et se ferme au tap extérieur.

---

## 17. Gabarit PDF du bon de commande (spécification LIV-04)

Format A4 portrait, marges 15 mm, police du projet, généré par dompdf. **Le logiciel produit le papier** (CTR-07) : le PDF est l'objet juridique signé.

| Zone | Contenu |
|---|---|
| **En-tête** (bandeau) | Logo + « CHU-YO — Bon de commande » · **N° BC-2026-0041** en gros · **QR code** (contenu : le numéro seul, `BC-2026-0041`) en haut à droite, 22 × 22 mm, lisible douchette (UX-13) |
| Bloc parties | Gauche : émetteur (CHU-YO, service approvisionnement, acheteur) · Droite : fournisseur (raison sociale, contacts — libellés figés à la validation) |
| Bloc références | Date de validation · Service demandeur + réf. demande (si renseignés) · Observation typée |
| **Tableau des lignes** | N° · Code article · Désignation · Qté · PU HT · TVA % · Montant HT — répétition de l'en-tête de tableau à chaque page |
| **Totaux** (encadré) | Total HT · TVA (détail par taux) · **Total TTC en gras** · montant TTC **en toutes lettres** (« cinq millions vingt-six mille huit cents francs CFA ») |
| Cadres de signature | Gauche : « L'Acheteur » · Droite : « Le Validateur » (nom + date pré-imprimés, espace signature) — un seul cadre validateur (D3) |
| Pied de page | `BC-2026-0041 · page 1/2 · édité le {date} par {user} · CHU-YO Module Achat` |
| **Filigranes** (diagonale, gris 15 %) | `BROUILLON — SANS VALEUR D'ENGAGEMENT` (tout statut < validé — le PDF de brouillon n'est accessible que par prévisualisation, jamais listé) · `RÉGULARISATION` (BC d'intérim, cumulable avec rien d'autre) · `ANNULÉ` (statut annulé) |

Variante « états » (A-07) : en-tête commun, titre portant la qualification **HT/TTC**, période et filtres imprimés sous le titre, date d'édition.

---

## 18. Accessibilité

Cible : utilisable **entièrement au clavier**, lisible par lecteur d'écran, conforme à l'esprit RGAA niveau AA sur les parcours critiques (saisie, visa, wizard).

### 18.1 Clavier

| Contexte | Comportement |
|---|---|
| Ordre de tabulation A-03 ét. ① | En-tête (fournisseur → date → service → pilules observation) → lignes (article → qté → prix → TVA → suppr) ligne par ligne → `[+ Ajouter]` → pied (`[💾]` → `[Continuer]`) |
| Tableau de lignes | `Tab` circule dans la ligne ; `Entrée` sur `[+ Ajouter des articles]` ouvre M-01 ; `Suppr` sur le bouton 🗑 focalisé supprime |
| M-01 | Focus initial : champ de recherche ; `↑/↓` navigue la liste, `Espace` coche, `Entrée` = `[Ajouter au bon]` ; `Échap` ferme (sélection conservée en mémoire de session de modale) |
| Wizard A-05 | Focus initial : première clé vide ; **`Entrée` = clé suivante** (le geste douchette) ; `Ctrl+Entrée` = `[💾 Enregistrer]` |
| Swals | Focus initial sur le bouton **le moins destructeur** (`[Annuler]`) ; `Échap` = annuler ; jamais de validation par `Entrée` seule sur SW-02/SW-03 (actions définitives) |
| Pilules (statuts de filtre, TVA, observation) | Focusables, `Espace` bascule, groupe navigable aux flèches |

### 18.2 Lecteur d'écran et sémantique

- Pilules de statut : `aria-label` complet (« Statut : soumis, en attente de visa ») — jamais la couleur seule (§0.2 double toujours couleur + libellé) ;
- Stepper : `aria-current="step"` ; changement d'étape annoncé (« Étape 2 sur 2 : récapitulatif et soumission ») ;
- Progression du wizard : `aria-live="polite"` sur le bandeau (« 13 clés sur 25 saisies ») ;
- Boutons grisés : l'infobulle-diagnostic est aussi `aria-describedby` (le lecteur d'écran lit la cause) ;
- Toasts : `role="status"` ; erreurs 422 : focus déplacé sur le premier champ fautif + `aria-invalid` ;
- Contrastes : les badges vert/orange/rouge portent aussi un pictogramme (✔/⚠/⛔) — jamais d'information par la couleur seule ;
- Modales : piège à focus, retour du focus à l'élément déclencheur à la fermeture.

---

## 19. États de chargement et cibles de performance

### 19.1 Squelettes et progressivité

| Écran | Stratégie |
|---|---|
| A-01 | Squelettes des 6 cartes ; KPI affichés au fil de l'eau ; le graphique charge en dernier |
| A-02 / A-06 | Bootstrap Table : spinner intégré + conservation des filtres pendant le rechargement |
| A-04 | Bandeau + onglet Lignes immédiats (une requête) ; Réceptions/Chronologie chargés à l'ouverture de l'onglet (badge compteur pré-chargé) ; section « En cours côté magasin » : spinner dédié + repli silencieux avec message « Le module Stock ne répond pas — les réceptions intégrées restent exactes » si l'API Stock échoue (**dégradation partielle, jamais de page blanche**) |
| M-01 | Recherche à la frappe (debounce 300 ms), spinner dans le champ, résultats bornés à 50 + « affinez la recherche » |
| A-05 | Sauvegarde du tampon à chaque `Entrée` : indicateur discret ✓ « enregistré » par ligne ; en cas d'échec réseau : la ligne passe en ⟳ « en attente de connexion », ressaisie protégée |

### 19.2 Cibles (volumétrie de référence CDC §4.3)

| Interaction | Cible |
|---|---|
| Affichage d'une liste filtrée | < 2 s |
| Recherche M-01 | < 500 ms après debounce |
| Enregistrement de brouillon | < 1 s |
| Validation (SW-02 → toast) | < 2 s |
| Finalisation de 25 licences | < 4 s (transaction atomique) |
| Génération PDF | < 3 s (au-delà : « Le PDF se prépare… » + téléchargement différé) |

---

## 20. Wireframes textuels des écrans critiques

### 20.1 — A-03, étape ① (poste de travail)

```
┌────────────────────────────────────────────────────────────────────────────┐
│ CHU-YO | ACHAT   [🏠 TABLEAU DE BORD] [📋 BONS DE COMMANDE 🔴3]    ○ awa   │
├──────────┬─────────────────────────────────────────────────────────────────┤
│ Tableau  │  Nouveau bon de commande            Accueil / Achat / BC / Nouveau
│ de bord  │  ●① Lignes & montants ─────────────── ○② Récapitulatif & soumission
│ ▸ Bons   │ ┌─────────────────────────────────────────────────────────────┐ │
│   de cde │ │ Brouillon #12 — modifiable, non engageant, sans numéro      │ │
│ Reliquats│ ├─── En-tête ─────────────────────────────────────────────────┤ │
│ Rapports │ │ Fournisseur *        Date *       Service demandeur         │ │
│ ─────────│ │ [Sonabel-Info 🔒▾]  [08/08/2026]  [Radiologie ▾] [réf: 24-A]│ │
│ RÉFÉRENT.│ │  Référentiel du module Catalogue                            │ │
│ Admin.   │ │ Observation ⓘ (Urgent)(Renouv. périodique)(Sur demande)(Autre)│
│ ─────────│ ├─── Lignes du bon (2) ───────────────────────────────────────┤ │
│ AUTRES   │ │ Nat  Article                Qté  Prix nég. HT   TVA   S/T HT│ │
│ MODULES  │ │ [E] EQP-00001 Dell Latitude [10] [830 000]      (18%) 8,3 M │ │
│ Catalogue│ │      réf. 650 000 ⓘ  ⚠ +29% vs dernier payé            🗑  │ │
│ Stock    │ │ [C] CONS-00001 Toner HP 85A [20] [45 000]       (18%) 900 k │ │
│ …        │ │ ┌ - - - - - - [＋ Ajouter des articles] - - - - - - - - - ┐ │ │
│          │ │ └ - - - - - - - - - - - - - - - - - - - - - - - - - - - -┘ │ │
│          │ └─────────────────────────────────────────────────────────────┘ │
│          │ ┌─ 2 ligne(s) · 30 unité(s) · Total : 9 200 000 FCFA HT ·      │
│          │ │  10 856 000 FCFA TTC ⓘ     [💾 Enregistrer]  [Continuer →]  │
└──────────┴─────────────────────────────────────────────────────────────────┘
```

### 20.2 — A-04, fiche (statut Partiel, poste de travail)

```
┌────────────────────────────────────────────────────────────────────────────┐
│ BC-2026-0041   (Partiel)   Sonabel-Info   13/08/2026   Radiologie          │
│ 10 856 000 FCFA TTC                     [🖨 PDF] [🔒 Clôturer…] [📎 Pièce] │
├────────────────────────────────────────────────────────────────────────────┤
│ [Lignes] [Réceptions ②] [Licences] [Documents ①] [Chronologie]             │
├────────────────────────────────────────────────────────────────────────────┤
│ ▼ Intégrées (fait foi)                                                     │
│ ┌ ENT-2026-0034 · 20/08 · Magasin CHU-YO · 6 unités · Livraison conforme ┐ │
│ │   ▸ Dell Latitude ×6 → 6 fiches parc [voir]      [Voir le bon d'entrée →]│
│ └──────────────────────────────────────────────────────────────────────── ┘ │
│ ▼ En cours côté magasin (informatif — sans effet sur les reliquats)        │
│ ┌ Brouillon #58 · créé ce matin par Salamata · 4 unités en référencement  ┐│
│ │   (2/4 saisies)                                  [Voir le bon d'entrée →]││
│ └───────────────────────────────────────────────────────────────────────── ┘│
└────────────────────────────────────────────────────────────────────────────┘
```

### 20.3 — M-01 (modale de sélection, mode multi)

```
┌─ Sélectionner des articles ────────────────────────────────────── ✕ ─┐
│ [Toutes les natures ▾]  [ Code ou nom d’article…            🔍 ]     │
│ ☐  Nat  Code        Nom                    Unité  Prix ind.  TVA  ★  │
│ ☑  [E]  EQP-00001   Ordinateur Dell L3540  unité  650 000    18%  ★  │
│ ☐  [E]  EQP-00003   Imprimante LaserJet    unité  320 000    18%     │
│ ☑  [C]  CONS-00001  Toner HP 85A noir      cart.   45 000    18%  ★  │
│ ☐  [P]  PIE-00001   Disque SSD 512 Go      unité   55 000    18%     │
│ ⓘ L’article n’existe pas ? Créez-le dans le module Catalogue →       │
│    (le brouillon attend sans rien perdre)                            │
│                    2 article(s) sélectionné(s)   [Annuler] [Ajouter] │
└──────────────────────────────────────────────────────────────────────┘
   ★ = fournisseur préféré : Sonabel-Info (tri : ses articles d’abord)
```

---

## Restes à produire (mise à jour v1.1)

Couvert depuis la v1.0 : textes définitifs (§15), responsive (§16), gabarit PDF (§17), accessibilité (§18), chargements et cibles (§19), wireframes des 3 écrans critiques (§20). Restent :

1. **Maquettes graphiques haute fidélité** (à partir des wireframes §20 — ordre : A-03, M-01, A-04, A-06, A-01) ;
2. Wireframes des écrans secondaires (A-01, A-02, A-05, A-06, A-07, A-08) si souhaités avant maquettage ;
3. **Fusion des écrans Stock modifiés** (`RACCORDEMENT_Achat_Stock.md`) dans l'amendement du SFD Stock ;
4. Validation MOA des textes du §15, puis gel du catalogue de messages.

---

*Fin de la spécification UX v1.1 — 08/08/2026.*
