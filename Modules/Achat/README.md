# Module Achat — CHU-YO

Chaîne d'engagement fournisseur : bons de commande, visa, réceptions et reliquats.

> **Documents contractuels** : `SFD_Achat.md` (le SFD fait foi) · `SPEC_UX_Achat.md` v1.1 (annexe normative)
> **Conventions transverses** : `PATTERNS.md`, `DESIGN.md`

---

## État d'avancement

Ce module est en construction. Le **socle** est livré et vérifié ; les écrans
métier restent à développer.

| Livré | Contenu |
|---|---|
| Structure | `module.json` (requires Core, Catalogue, Stock, ParcInfo, Organisation), providers, layout, sidebar, navbar |
| Modèle de données | Les 8 tables du SFD §6.2, avec leurs contraintes `CHECK` actives sur SQLite **et** PostgreSQL |
| Permissions | Les 20 permissions du SFD §5 + les 3 rôles seedés, intégrés aux écrans de Core |
| Services fondateurs | `NumerotationService` (IA-3), `CalculMontantsService` (IA-1) |
| Écran | A-01 — tableau de bord (état vide EV-01) |
| Chrome | Sidebar « CHU-YO \| ACHAT », topbar à accès rapides, fil d'Ariane (SPEC_UX §0.1) |
| **Raccordement Stock (PRQ-05)** | `stock_entrees.bon_commande_id`, les 6 endpoints d'API (§4) et le service d'intégration `AchatReceptionService` (§5.1/5.2) |
| Tests | 126 tests, joués sur les deux SGBD |

À développer : A-02 à A-08, les 9 modales, le wizard de licences, les exports et
le PDF. Côté Stock, le mode « Livraison sur commande » (modale M-02, scan QR,
pré-remplissage, appel du service à la validation) reste à câbler dans ses
écrans : le contrat serveur, lui, est livré et testé.

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
`config/` (leçon AN-13/14) : préfixe de numérotation, délai d'alerte des
reliquats, seuil d'écart de prix, taille maximale des pièces, activation de la
régularisation et bornes d'intérim.

`regularisation_active` est semé à **faux** : la porte ne s'ouvre que par un acte
d'administration explicite (A15/IA-11).

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

## Tests

```bash
php artisan test Modules/Achat          # SQLite (suite par défaut)
Modules/Achat/tests/postgres.sh         # PostgreSQL réel, schéma isolé
```

La convention 6 exige que les suites passent sur **les deux** SGBD. Les `CHECK`
ne se posent pas de la même façon (SQLite ne sait pas les ajouter après coup,
d'où la recréation de table par `SchemaChecks`) : réussir sur l'un ne prouve
rien pour l'autre. Le script PostgreSQL migre dans un schéma isolé et le
supprime ensuite ; vos données ne sont jamais touchées.

---

## Écarts signalés (SFD)

| # | Écart | Traitement retenu |
|---|---|---|
| 1 | ~~`RACCORDEMENT_Achat_Stock.md` absent~~ — **levé le 05/08/2026** | Document fourni ; le raccordement est implémenté côté serveur : colonne de liaison, 6 endpoints, service d'intégration avec plafond sous verrou, idempotence et contre-passation. Reste à câbler dans les **écrans** Stock (modale M-02, scan QR, pré-remplissage). |
| 2 | **Contradiction interne** : §1.4 annonce « `ANNULE` — numéro jamais attribué », mais §7.5 n'autorise l'annulation que depuis `VALIDE`, qui porte déjà un numéro | Le numéro est **conservé** à l'annulation : le retirer creuserait un trou dans la séquence (contraire à IA-3) et effacerait la trace d'un document ayant pu circuler. Documenté par un test ; à trancher par la MOA. |
| 3 | §6.2 prévoit un `CHECK` « `est_regularisation = false OR date_document BETWEEN bornes d'intérim` » | **Impossible en `CHECK` statique** : les bornes sont des paramètres modifiables (`achat_parametres`). La garde est applicative, à poser à la création du BC de régularisation. |
| 4 | La nature `prestation` (PRQ-02) et le compte comptable (PRQ-03) sont des amendements **Catalogue** non livrés | Le modèle les accepte (`nature` figée en chaîne, `service_fait_*` présents) sans les exiger. |
| 5 | Le jalon d'installation annonce « **17 permissions** » | Le SFD §5 en compte **20 distinctes** : son tableau tient sur 14 lignes, dont 4 regroupent plusieurs permissions (`store` / `update` / `destroy`, `annuler` / `cloturer`, `documents.view` / `store` / `delete`, `rapports.view` / `export`). Le SFD faisant foi, les 20 sont posées — ni manquante, ni surnuméraire (vérifié par test). |
