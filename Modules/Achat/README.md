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
| Écran | A-01 — tableau de bord |
| Tests | 80 tests, joués sur les deux SGBD |

À développer : A-02 à A-08, les 9 modales, le wizard de licences, les 6 endpoints
d'API, les exports et le PDF.

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
| 1 | `RACCORDEMENT_Achat_Stock.md` est **absent du dépôt** alors que le SFD s'y réfère (§2.3, §6.2) | Le raccordement Stock (`stock_entrees.bon_commande_id`, mode « livraison sur commande », plafonds) n'est **pas** implémenté ici : c'est un chantier du module Stock (SFD §9.1). À arbitrer avant la réception physique. |
| 2 | **Contradiction interne** : §1.4 annonce « `ANNULE` — numéro jamais attribué », mais §7.5 n'autorise l'annulation que depuis `VALIDE`, qui porte déjà un numéro | Le numéro est **conservé** à l'annulation : le retirer creuserait un trou dans la séquence (contraire à IA-3) et effacerait la trace d'un document ayant pu circuler. Documenté par un test ; à trancher par la MOA. |
| 3 | §6.2 prévoit un `CHECK` « `est_regularisation = false OR date_document BETWEEN bornes d'intérim` » | **Impossible en `CHECK` statique** : les bornes sont des paramètres modifiables (`achat_parametres`). La garde est applicative, à poser à la création du BC de régularisation. |
| 4 | La nature `prestation` (PRQ-02) et le compte comptable (PRQ-03) sont des amendements **Catalogue** non livrés | Le modèle les accepte (`nature` figée en chaîne, `service_fait_*` présents) sans les exiger. |
