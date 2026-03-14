# Module Organisation - Copie depuis Inventis

## Ce qui a été copié

### 1. Modèles (7 fichiers)
- Site
- Direction  
- Service
- Unite (Unités cliniques)
- Batiment
- Etage
- Local

### 2. Contrôleurs (7 fichiers)
- SiteController
- DirectionController
- ServiceController
- UniteController
- BatimentController
- EtageController
- LocalController

### 3. Migrations (7 fichiers)
- 2026_02_17_120001_create_organisation_sites_table.php
- 2026_02_17_120002_create_organisation_directions_table.php
- 2026_02_17_120003_create_organisation_services_table.php
- 2026_02_17_120004_create_organisation_unites_table.php
- 2026_03_10_150000_create_organisation_batiments_table.php
- 2026_03_10_150001_create_organisation_etages_table.php
- 2026_03_10_150002_create_organisation_locaux_table.php

### 4. Vues
Toutes les vues du dossier `organisation` d'Inventis

### 5. Routes
Routes complètes pour tous les modules d'organisation avec :
- CRUD complet (index, show, store, update, destroy)
- Toggle status
- Routes AJAX helpers

## Structure des routes

```
/organisation/sites
/organisation/directions
/organisation/services
/organisation/unites
/organisation/batiments
/organisation/etages
/organisation/locaux
```

## Migrations effectuées

✅ Toutes les tables ont été créées avec succès dans la base `parc_info`

## Prochaines étapes

1. Adapter les vues pour utiliser le layout de parc_info
2. Vérifier les dépendances (traits, helpers, etc.)
3. Tester les fonctionnalités
4. Ajouter les permissions si nécessaire

## Commandes utilisées

```bash
# Créer le module
php artisan module:make Organisation

# Copier les fichiers
cp -r /path/to/Inventis/Models/* Modules/Organisation/app/Models/
cp -r /path/to/Inventis/Controllers/* Modules/Organisation/app/Http/Controllers/
cp -r /path/to/Inventis/migrations/* Modules/Organisation/database/migrations/
cp -r /path/to/Inventis/views/organisation Modules/Organisation/resources/views/

# Mettre à jour les namespaces
sed -i 's/Modules\\Core/Modules\\Organisation/g' fichiers

# Lancer les migrations
php artisan module:migrate Organisation

# Régénérer l'autoload
composer dump-autoload
```
