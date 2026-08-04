# Retour d'expérience — construction du module Stock

> **Date** : 04/08/2026 · **Branche** : `refactor/stock-rebuild` · **Périmètre** : 23 commits, de `d38cb40` (socle) à `5a57897` (filtres des modales)
> **Objet** : ce que la construction complète du module Stock a appris — sur l'architecture du projet, sur les décisions qui se sont révélées justes, et surtout sur les pièges concrets qui ont coûté du temps. Document destiné à quiconque reprendra ce module ou en construira un autre dans ce dépôt.

---

## 1. Ce qui a été construit

| Élément | Volume |
|---|---|
| Migrations | 19 (13 tables du SFD §6 + séquences + 5 évolutions) |
| Services métier | 10 |
| Fichiers PHP / Blade du module | 182 |
| Modules JS | 23 (dont 9 partagés) |
| Tests | 23 classes — **288 verts sur PostgreSQL**, 286 + 2 skips sur SQLite, 1 165 assertions |

Fonctionnellement : magasins, état des stocks, les trois triptyques (entrées, sorties, transferts) avec leurs wizards, le tableau de bord, l'impression, les pièces jointes. **Non réalisés** : inventaires, historique des mouvements avec contre-mouvement (l'écran ; le service existe), rapports, API inter-modules.

---

## 2. Architecture du projet — ce qu'il faut savoir avant de coder

### Modules et dépendances
Laravel 12 / PHP 8.2, `nwidart/laravel-modules`. Modules actifs : **Core** (socle, users, permissions), **Organisation** (sites, directions, services, unités, bâtiments/étages/locaux, postes), **Grh** (employés), **ParcInfo** (équipements, affectations), **Catalogue** (articles, catégories, fournisseurs), **Stock**. Le `requires` de `module.json` déclare la chaîne — Stock dépend des cinq autres.

### Permissions
Chaque module déclare ses permissions dans `config/permissions.php` (tableau `nom => libellé`), synchronisées par `php artisan cores:sync-permissions {module}` ou globalement par `cores:sync`. Un seeder `{Module}PermissionsSeeder` crée les rôles et **conserve les permissions des autres modules** lors du `syncPermissions` (motif à reprendre tel quel).

Les contrôleurs portent les permissions via `HasMiddleware` + `Middleware('permission:x', only: [...])` — **jamais** dans les routes.

### Navigation
`config/config.php` de chaque module expose une clé `navigation` ; `HasModulePermissions::getModuleNavigation()` filtre par permission **et** par `Route::has()`. Le partiel `core::partials.sidebar-modules` affiche les autres modules. Chaque module a son propre layout (`{module}::layouts.master` + `partials/navbar` + `partials/sidebar`), copié depuis Grh.

### Journalisation
Trait `Modules\Core\Traits\LogsActivityWithModule` avec `static::$activityModule = 'stock'` positionné dans le `boot`. Le module Stock l'a enveloppé dans un concern `JournaliseActiviteStock` pour ne pas répéter cette ligne dans douze modèles.

### Conventions front (issues de PATTERNS.md, confirmées à l'usage)
- Tables : bootstrap-table côté serveur, réponse **`{total, rows}`**, routes `.data`.
- JS : un dossier par écran, trio `index.js` / `{Entité}Form.js` / `{Entité}Actions.js`, modules ES importés en `type="module"`.
- Modales duales création/édition, Swal pour le destructif et les erreurs, toasts pour les succès.
- Routes littérales **avant** `/{id}` (sinon `data`, `create`, `export` sont capturés comme des identifiants).

---

## 3. Décisions structurantes qui ont bien vieilli

**Un point d'écriture unique.** `MouvementService` est le seul endroit qui écrit dans `stock_mouvements` et `stock_niveaux` : transaction, `lockForUpdate`, gardes transverses, exceptions typées. Tous les services de validation passent par lui. Résultat : l'invariant « niveau = Σ mouvements » n'a jamais dérivé, et la commande `stock:controle-coherence` sort à 0 écart y compris après une séquence aléatoire de 80 opérations.

**Contrat plutôt que copie.** `DocumentAPointage` (implémenté par `Sortie` et `Transfert`) permet à `PointageService`, à l'écran de pointage et au trait `GerePointageHttp` de servir les deux documents sans une ligne dupliquée. Quand les transferts sont arrivés, il n'y a eu qu'à écrire leur spécificité (source ≠ cible, paire atomique). **C'est le pattern à reproduire** dès qu'un deuxième document ressemble à un premier.

**Partiels partagés côté vue et JS.** Neuf composants Blade et neuf modules JS mutualisés (stepper, champ scan, sélecteurs d'article/unités/bénéficiaires, modale PDF, pièces jointes, écran de pointage, validation générique, formatters). Les gabarits PDF partagent en-tête, styles et cartouche. Le coût d'un troisième document tend vers zéro.

**Idempotence par jeton.** Colonne `jeton_validation` sur les trois documents : rejouer la même validation renvoie le même récapitulatif sans réécrire, un jeton différent sur un bon validé donne 409. Simple et suffisant.

**Table de préférences dans le module** plutôt qu'une colonne ajoutée à `users` du Core : `stock_preferences` reste supprimable avec le module.

**Disque privé pour les pièces jointes.** Route de téléchargement contrôlée, pas de symlink public — ce qui évite le problème connu des `storage/app/public/achat_documents` appartenant à `www-data` et impossibles à purger sans sudo.

---

## 4. Catalogue des pièges rencontrés

> La section la plus utile. Chaque entrée : symptôme → cause → remède.

### 4.1 Blade et Laravel

**`@json()` sur une expression multi-ligne** → `Unclosed '[' on line X does not match ')'`. La directive ne parse pas les expressions sur plusieurs lignes. Préparer la valeur dans un bloc `@php` puis `@json($variable)`.

**`assertSee()` sur une URL avec paramètres** échoue : `&` est échappé en `&amp;` dans les attributs `href`. Asserter sur la forme échappée ou sur des fragments.

**`assertSee()` sur du texte accentué injecté par `@json`** échoue : `json_encode` échappe en `à`. Asserter sur un code ASCII (le code article) ou sur `viewData()`.

**`TrimStrings` + `ConvertEmptyStringsToNull`** transforment une textarea ne contenant que des blancs en `null` → une règle `required_without` se déclenche et renvoie 422 au lieu d'un traitement à vide. En tenir compte dans les tests d'import.

**Défauts de colonne et modèles non persistés.** Un `statut` avec `default('BROUILLON')` en base vaut `null` sur une instance non sauvegardée : les machines à états comparaient contre `null`. Refléter les défauts dans `protected $attributes` du modèle.

**`php artisan` depuis un sous-dossier** → `Could not open input file: artisan`. Le répertoire de travail de l'outil Bash persiste entre les appels : toujours revenir à la racine du projet.

**Classe anonyme de test étendant un service** : si la signature du parent change (ajout d'un paramètre), la surcharge casse au chargement avec une erreur de compatibilité. Penser à la mettre à jour en même temps.

### 4.2 Portabilité SQLite / PostgreSQL

**SQLite refuse `ALTER TABLE … ADD CONSTRAINT CHECK`.** Solution retenue (`Modules/Stock/app/Support/SchemaChecks.php`) : sur SQLite, relire le DDL dans `sqlite_master`, réinjecter les `CONSTRAINT … CHECK (…)` dans le `CREATE TABLE`, recréer la table (encore vide, on est dans sa propre migration) puis **rejouer les index**, qui sont des entrées distinctes de `sqlite_master` et disparaissent au `DROP`.

**Écrire des expressions CHECK portables** : pas de `num_nonnulls()` (PostgreSQL seulement) — utiliser `(CASE WHEN col IS NULL THEN 0 ELSE 1 END) + … = 1`.

**Une FK ajoutée après coup** (`stock_lignes_inventaire.mouvement_id` référençant une table créée plus tard) n'est possible que sur PostgreSQL ; sur SQLite l'intégrité reste applicative. À documenter dans la migration.

### 4.3 Modules amont — colonnes obligatoires non évidentes

Ces contraintes ont cassé des tests parfaitement valides côté Stock :

| Table | Colonnes requises non devinables |
|---|---|
| `organisation_services` | `direction_id`, `site_id`, **`type_service`** (`administratif`\|`clinique`\|`medico-technique`) |
| `organisation_directions` | `site_id` |
| `parc_info_affectation_equipements` | **`code`** (aucun défaut) |
| `parc_info_equipements` | `code_inventaire`, `numero_serie`, `modele`, `statut`, `etat` |

**ParcInfo n'a aucune factory.** Un helper `Modules\Stock\Database\Factories\ParcInfoDeTest::equipement()` a été créé pour les tests du module. À étendre plutôt qu'à dupliquer.

**Statuts d'équipement ParcInfo** : `en_stock`, `en_stock_magasin`, `en_stock_dsi`, `en_service`, `en_reparation`, `perdu`, `reforme`. Le test « est en stock » se fait donc par préfixe (`str_starts_with($statut, 'en_stock')`), jamais par égalité.
**États** : `bon`, `passable`, `mauvais`, `avarie` — **il n'existe pas d'état « Neuf »**.

**Routes dynamiques par catégorie ParcInfo** : elles sont nommées `parc-info.{Str::plural(code)}.show`. Un `'s'` naïf fonctionne pour les codes minuscules et casse pour les majuscules (`ORDI` → `ORDIs` ≠ `ORDIS`) → `RouteNotFoundException`. Bug préexistant corrigé dans `informatique/show.blade.php`, l'étiquette QR et les trois contrôleurs Stock (commit `780021a`). **Toujours passer par `Str::plural`.**

### 4.4 Eloquent

**Eager loading avec liste de colonnes.** `with('tampons.equipement:id,code_inventaire,numero_serie,modele')` omettait `statut` et `categorie_id`, que les gardes de pointage lisent : elles voyaient `null` et refusaient l'unité avec un message trompeur (« Unité non “en stock” ») **à la validation seulement**, alors que le pointage était passé. Lister toutes les colonnes dont dépend la logique, ou ne pas restreindre.

### 4.5 Tests

**Matrice de permissions avec `@dataProvider`** : l'assertion « invité → redirigé vers login » échoue à partir du deuxième jeu de données parce que l'authentification du jeu précédent persiste. Appeler `$this->app['auth']->forgetGuards();` avant.

**Tester un rollback** exige un échec **réel** en pleine transaction, pas un mock complaisant : pour I11, une classe anonyme qui lève à la 3ᵉ création de fiche sur 15 ; pour I4, désactiver le magasin cible entre le brouillon et la validation, ce qui fait frapper la garde côté ENTREE **après** que le côté SORTIE a décrémenté.

---

## 5. Procédure de test PostgreSQL (validée avec Ibrahim)

Il n'existe **pas** de base de test séparée : l'utilisateur PostgreSQL `parc_info` n'a pas le droit `CREATEDB`, et Ibrahim a arbitré qu'on utilise la base de développement. La base contient ses **vraies données** (bons validés, articles, utilisateurs) : la séquence complète est obligatoire.

```bash
# 1. Sauvegarde
PGPASSWORD=parc_info pg_dump -h 127.0.0.1 -U parc_info -Fc parc_info -f /chemin/avant.dump

# 2. Tests (les variables du shell priment sur le sqlite de phpunit.xml)
DB_CONNECTION=pgsql DB_DATABASE=parc_info ACTIVITY_LOGGER_DB_CONNECTION=pgsql \
  php artisan test Modules/Stock/tests

# 3. Restauration — DROP SCHEMA d'abord :
#    pg_restore --clean seul échoue si le schéma courant porte des tables
#    inconnues du dump (dépendances de contraintes)
PGPASSWORD=parc_info psql -h 127.0.0.1 -U parc_info -d parc_info \
  -c "DROP SCHEMA public CASCADE; CREATE SCHEMA public;"
PGPASSWORD=parc_info pg_restore -h 127.0.0.1 -U parc_info -d parc_info /chemin/avant.dump
#    → 2 erreurs « permission denied to change default privileges » : cosmétiques

# 4. Delta (nouvelles migrations depuis le dump)
php artisan migrate --force && php artisan view:cache
```

`ACTIVITY_LOGGER_DB_CONNECTION` est indispensable : sans elle, spatie/activitylog reste sur pgsql pendant les tests SQLite et fait échouer la première migration.

**Ne jamais reconstruire par seeders seuls** : les seeders ne recréent pas les données saisies à la main (utilisateurs, articles, catégories, séquences de codes). Toujours restaurer le dump.

---

## 6. Préférences produit d'Ibrahim (dérogent aux specs)

Ces arbitrages priment sur `UX_Stock.md` et doivent être reconduits sur les écrans à venir :

1. **Lignes regroupées** : une seule table par bon, articles quantitatifs et « modèle × N » ensemble, distingués par le badge de nature — au lieu des deux onglets Articles/Équipements de la spec §3.2.
2. **Sélecteur d'article en modale** (recherche, filtre de nature, prix indicatif, double-clic) au lieu d'un Select2 par ligne.
3. **Deux modèles d'impression par document** : un « bon » (libellés, quantités, coûts) et une « fiche des équipements » (code d'inventaire, modèle, n° de série), via `?modele=`, avec menu déroulant sur la fiche.
4. **Impression en modale iframe** plutôt qu'un nouvel onglet, y compris depuis les listes.
5. **État de l'unité saisi au wizard**, rangée par rangée, et hérité par la fiche ParcInfo créée — ce qui a réglé la question de l'état « Neuf » inexistant.

Ces préférences sont consignées dans la mémoire de session (`preferences-ux-ibrahim`).

---

## 7. Méthode de travail — ce qui a fonctionné

**Découper en lots commitables.** Le prompt des entrées imposait 6 commits (A→F) ; le résultat est lisible et bisectable. Reconduit spontanément ensuite : 5 lots pour les 6 diligences finales, chacun testé et commité.

**Écrire le test de l'invariant avant l'implémentation.** Les invariants I1–I17 de `TESTS_Stock.md` ont servi de spécification exécutable. Ceux qui portent sur la concurrence et l'atomicité ont trouvé de vrais défauts d'ordre (transition vérifiée après la règle métier au lieu d'avant).

**Interroger un prompt qui présuppose l'inexistant.** Le prompt des transferts demandait de réutiliser « le composant de pointage du prompt 5 » — or le lot sorties n'avait jamais été demandé. Poser la question a évité de construire les transferts sur du vide puis de tout refaire.

**Demander avant le destructif.** `RefreshDatabase` sur la base de développement efface tout : la question posée a permis d'arbitrer (sauvegarde + restauration) au lieu de perdre les données.

**Vérifier plutôt qu'affirmer.** Deux prompts portaient sur du travail déjà fait (écrans référentiels, modèles PDF des sorties) : la vérification sur pièces (fichiers, routes, tests exécutés) a évité de refaire à l'identique.

---

## 8. État à date et suites

**Fait** : squelette, couche données complète (13 tables + invariants), magasins, état des stocks, entrées/sorties/transferts avec triptyques complets, tableau de bord, impression 2 modèles + modale, pièces jointes, magasin par défaut, filtres des modales.

**Reste à faire** (par ordre logique) :
1. **Inventaires** (SFD §7.5) — feuille de comptage, comptage à l'aveugle, ajustements motivés, blocage I10 (le flag `sous_inventaire` de l'écran des niveaux l'anticipe déjà).
2. **Historique des mouvements** — écran + contre-mouvement (`MouvementService::contreMouvement()` est écrit et testé, il manque l'interface).
3. **Rapports** (5 rapports + exports) et **API inter-modules** (`/stock/api/*`, snapshots contractuels).
4. Compléments possibles : filtres du sélecteur d'unités (aujourd'hui recherche seule), import CSV avec état (`numéro;état`), carte du module Catalogue sur la page d'accueil.

**Points en suspens à arbitrer** :
- Les cascades internes des modales de bénéficiaires sont désormais fonctionnelles, mais les modales **copiées de ParcInfo** (`shared/_selection_modals/`) restent un doublon du module d'origine : à mutualiser si ParcInfo évolue.
- Aucun test d'intégration croisé n'existe encore côté ParcInfo pour vérifier que les fiches créées par la sérialisation D10 s'affichent correctement dans ses écrans.
