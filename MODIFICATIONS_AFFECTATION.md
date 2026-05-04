# Modifications de l'étape Affectation - Modale Ordinateur

## Vue d'ensemble

L'étape "Affectation" de la modale d'ajout d'ordinateur a été modifiée pour améliorer l'expérience utilisateur. Au lieu d'afficher des formulaires de saisie, le système ouvre maintenant des modales de sélection dédiées avec filtres et recherche.

## Fichiers créés

### 1. `/Modules/ParcInfo/resources/views/informatique/ordinateurs/_selection_modals.blade.php`
Contient les trois modales de sélection :
- **Modale Employé** : Sélection d'un employé avec filtres (Direction, Service, Statut) et recherche
- **Modale Poste** : Sélection d'un poste de travail avec filtres (Direction, Service, Statut) et recherche
- **Modale Local** : Sélection d'un local avec filtres (Site, Bâtiment, Étage) et recherche

Chaque modale inclut :
- Filtres contextuels
- Champ de recherche avec debounce
- Tableau avec colonnes pertinentes
- Skeleton loader pendant le chargement
- Boutons radio pour la sélection
- Bouton "Confirmer" (désactivé tant qu'aucune sélection)

### 2. `/Modules/ParcInfo/resources/views/informatique/ordinateurs/_selection_modals.js`
Script JavaScript gérant :
- L'ouverture des modales au clic sur les cartes d'affectation
- Le chargement des données via AJAX avec filtres
- La sélection d'éléments (employé/poste/local)
- L'affichage des cartes récapitulatives après confirmation
- Le bouton "Modifier" pour rouvrir la modale avec l'élément pré-sélectionné
- Les dépendances hiérarchiques (Direction → Services, Site → Bâtiments → Étages)

## Fichiers modifiés

### 1. `/Modules/ParcInfo/resources/views/informatique/ordinateurs/_wizard.blade.php`
**Modifications dans l'étape 3 (Affectation) :**
- Suppression des formulaires de détails (aff-employe, aff-poste, aff-local)
- Ajout de cartes récapitulatives pour chaque type d'affectation :
  - `#aff-employe-summary` : Affiche nom, matricule, poste, rattachement
  - `#aff-poste-summary` : Affiche code, libellé, emplacement
  - `#aff-local-summary` : Affiche code, libellé, type, étage, bâtiment
- Chaque carte inclut un bouton "Modifier" pour rouvrir la modale

### 2. `/Modules/ParcInfo/resources/views/informatique/ordinateurs/index.blade.php`
**Ajouts :**
- Inclusion de `_selection_modals.blade.php`
- Chargement de lodash.js (pour le debounce)
- Chargement de `_selection_modals.js`

### 3. `/Modules/Grh/routes/web.php`
**Ajout :**
- Route `GET /grh/employes/api` → `EmployeController@getApiData`

### 4. `/Modules/Organisation/routes/web.php`
**Ajouts :**
- Route `GET /organisation/locaux/api` → `LocalController@getApiData`
- Route `GET /organisation/postes-travail/api` → `PosteTravailController@getApiData`
- Route `GET /organisation/directions/{id}/services` → `DirectionController@getServices`
- Route `GET /organisation/sites/{id}/batiments` → `SiteController@getBatiments`
- Route `GET /organisation/batiments/{id}/etages` → `BatimentController@getEtages`

## Comportement attendu

### Affectation à un employé
1. L'utilisateur clique sur la carte "Affecter à un employé"
2. La modale `#employeSelectionModal` s'ouvre immédiatement
3. Un skeleton s'affiche pendant le chargement
4. La liste des employés apparaît avec les colonnes : Matricule, Nom Complet, Poste, Niveau, Rattachement, Statut
5. L'utilisateur peut filtrer par Direction, Service, Statut ou rechercher par nom/matricule
6. L'utilisateur sélectionne un employé via le radio button
7. Le bouton "Confirmer" devient actif
8. Après confirmation, la modale se ferme et une carte récapitulative s'affiche
9. Le bouton "Modifier" permet de rouvrir la modale avec l'employé pré-sélectionné

### Affectation à un poste
1. L'utilisateur clique sur la carte "Affecter à un poste"
2. La modale `#posteSelectionModal` s'ouvre immédiatement
3. La liste des postes apparaît avec les colonnes : Code, Poste, Direction, Service, Emplacement, Occupant, Statut
4. Filtres disponibles : Direction, Service, Statut
5. Recherche par code ou libellé
6. Sélection et confirmation identiques au processus employé

### Affectation à un local
1. L'utilisateur clique sur la carte "Affecter à un local"
2. La modale `#localSelectionModal` s'ouvre immédiatement
3. La liste des locaux apparaît avec les colonnes : Code, Libellé, Type, Superficie, Étage, Bâtiment, Site, Statut
4. Filtres disponibles : Site, Bâtiment, Étage
5. Recherche par code ou libellé
6. Sélection et confirmation identiques au processus employé

## Données backend

### Champs automatiques (non affichés dans l'interface)
- **date_affectation** : Renseignée automatiquement avec la date du jour lors de l'enregistrement
- **type_affectation** : Définie automatiquement à "Permanent" lors de l'enregistrement

Ces champs sont envoyés au backend mais ne sont pas visibles dans l'interface utilisateur.

## Méthodes de contrôleur à implémenter

Les contrôleurs suivants doivent implémenter les méthodes `getApiData()` :

### EmployeController (Grh)
```php
public function getApiData(Request $request)
{
    $query = Employe::query()->with(['direction', 'service', 'unite']);
    
    if ($request->filled('direction_id')) {
        $query->where('direction_id', $request->direction_id);
    }
    if ($request->filled('service_id')) {
        $query->where('service_id', $request->service_id);
    }
    if ($request->filled('statut')) {
        $query->where('statut', $request->statut);
    }
    if ($request->filled('search')) {
        $s = $request->search;
        $query->where(function($q) use ($s) {
            $q->where('matricule', 'ilike', "%{$s}%")
              ->orWhere('nom', 'ilike', "%{$s}%")
              ->orWhere('prenom', 'ilike', "%{$s}%");
        });
    }
    
    return response()->json($query->get()->map(function($emp) {
        return [
            'id' => $emp->id,
            'matricule' => $emp->matricule,
            'nom_complet' => $emp->nom_complet,
            'poste' => $emp->poste,
            'niveau' => $emp->niveau,
            'rattachement' => $emp->rattachement_complet,
            'statut' => $emp->statut
        ];
    }));
}
```

### PosteTravailController (Organisation)
```php
public function getApiData(Request $request)
{
    $query = PosteTravail::query()->with(['direction', 'service', 'local', 'employe']);
    
    // Filtres similaires
    
    return response()->json($query->get()->map(function($poste) {
        return [
            'id' => $poste->id,
            'code' => $poste->code,
            'libelle' => $poste->libelle,
            'direction' => $poste->direction?->libelle,
            'service' => $poste->service?->libelle,
            'emplacement' => $poste->emplacement_complet,
            'occupant' => $poste->employe?->nom_complet,
            'statut' => $poste->statut
        ];
    }));
}
```

### LocalController (Organisation)
```php
public function getApiData(Request $request)
{
    $query = Local::query()->with(['etage.batiment.site', 'typeLocal']);
    
    // Filtres similaires
    
    return response()->json($query->get()->map(function($local) {
        return [
            'id' => $local->id,
            'code' => $local->code,
            'libelle' => $local->libelle,
            'type' => $local->typeLocal?->libelle,
            'superficie' => $local->superficie,
            'etage' => $local->etage?->libelle,
            'batiment' => $local->etage?->batiment?->libelle,
            'site' => $local->etage?->batiment?->site?->libelle,
            'statut' => $local->statut
        ];
    }));
}
```

### Méthodes de dépendances hiérarchiques

**DirectionController::getServices($id)**
```php
public function getServices($id)
{
    return response()->json(
        Service::where('direction_id', $id)
               ->where('actif', true)
               ->orderBy('libelle')
               ->get(['id', 'libelle'])
    );
}
```

**SiteController::getBatiments($id)**
```php
public function getBatiments($id)
{
    return response()->json(
        Batiment::where('site_id', $id)
                ->where('actif', true)
                ->orderBy('libelle')
                ->get(['id', 'libelle'])
    );
}
```

**BatimentController::getEtages($id)**
```php
public function getEtages($id)
{
    return response()->json(
        Etage::where('batiment_id', $id)
             ->where('actif', true)
             ->orderBy('numero')
             ->get(['id', 'libelle'])
    );
}
```

## Règles communes

1. Le bouton "Confirmer" reste désactivé tant qu'aucun élément n'est sélectionné
2. Les modales de sélection s'affichent par-dessus `ordinateurModal` sans la fermer
3. Le bouton "Modifier" dans la carte récapitulative rouvre la modale avec l'élément précédemment sélectionné déjà coché
4. Les filtres se rafraîchissent automatiquement avec debounce (300ms) pour la recherche
5. Un skeleton loader s'affiche pendant le chargement des données

## Dépendances

- **Bootstrap 5** : Pour les modales et les composants UI
- **jQuery** : Pour les requêtes AJAX et la manipulation DOM
- **Lodash** : Pour la fonction `debounce` sur la recherche

## Notes importantes

- Les routes API doivent retourner des données au format JSON
- Les filtres hiérarchiques (Direction → Services, Site → Bâtiments → Étages) doivent être implémentés
- La validation côté serveur doit être maintenue pour les affectations
- Les champs `date_affectation` et `type_affectation` sont gérés automatiquement côté backend
