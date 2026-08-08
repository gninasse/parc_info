# Module Catalogue

Référentiel transverse des **articles** (4 natures : consommable, pièce, équipement, licence), **catégories** (2 niveaux) et **fournisseurs** du CHU-YO. Source de vérité consommée par ParcInfo et les futurs modules Stock/Achat via l'API inter-modules.

## Installation

```bash
php artisan module:enable Catalogue
php artisan module:migrate Catalogue        # tables catalogue_* (+ séquences de codes)
php artisan module:migrate ParcInfo         # re-routage affectations/licences (colonne article_id, FK)
php artisan cores:sync-permissions catalogue
php artisan module:seed Catalogue           # rôles + permissions (CataloguePermissionsSeeder)
php artisan cores:sync-modules              # registre des modules Core
```

Jeu de démonstration (hors production) : `php artisan module:seed Catalogue --class=CatalogueDemoSeeder`

## Commandes

| Commande | Rôle |
|---|---|
| `catalogue:migrate-parcinfo` | Migre les référentiels ParcInfo (types de consommables → catégories N1, consommables → articles, fournisseurs) et re-route `parc_info_affectations_consommables.article_id` et `parc_info_licences.fournisseur_id`. Transactionnelle, refuse les collisions de code (C5) et le rejeu. |
| `catalogue:migrate-parcinfo --dry-run` | Exécute la totalité de la migration puis annule la transaction : rapport identique, aucune écriture. |

## Permissions (guard `web`, module `catalogue`)

- `catalogue.{articles,categories,fournisseurs}.{index,store,update,destroy,toggle-status}` (15)
- `catalogue.api.view` — accès à l'API inter-modules

Rôles seedés : **Administrateur catalogue** (tout), **Gestionnaire catalogue** (tout sauf `destroy`), **Consultation catalogue** (`index` + API).

## API inter-modules (`/catalogue/api/*`, permission `catalogue.api.view`)

| Endpoint | Description |
|---|---|
| `GET /catalogue/api/articles` | Recherche bornée (`q`, `nature`, `categorie_id`, `est_actif` défaut `true`, `limit` défaut 50 / max 200) → `{data, total}` |
| `GET /catalogue/api/articles/{id}` | Fiche compacte ; 404 `{message}` |
| `GET /catalogue/api/categories` | Arbre 2 niveaux des catégories actives |
| `GET /catalogue/api/fournisseurs` | Fournisseurs actifs (`q`, `limit`) → `{data, total}` |

Les formats sont **contractuels** : verrouillés par `tests/Feature/ApiSnapshotTest.php`. Toute rupture doit être délibérée et coordonnée avec les modules consommateurs. Les décimaux sont renvoyés en chaînes (`"45000.00"`).

## Génération des codes

Séquences transactionnelles sous `lockForUpdate` (table `catalogue_sequences`) : `CAT-001` (catégories), `FOUR-XX001` (fournisseurs, 2 lettres de la raison sociale), `CONS-/PIE-/EQP-/LIC-00001` (articles, séquence par préfixe). La nature d'un article est immuable (C6) et `est_stockable` en est dérivé (faux pour les licences).

## Tests

```bash
vendor/bin/phpunit Modules/Catalogue/tests
```

`RouteSecurityTest` garantit qu'aucune route `catalogue.*` n'est servie sans middleware de permission.
