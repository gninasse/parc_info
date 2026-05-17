# Plan de Refactorisation du Module Mobiles (Alignement sur Ordinateurs)

## 1. Objectif
Harmoniser l'interface et les fonctionnalités de gestion des terminaux mobiles avec celles des ordinateurs, en intégrant :
- Le lien cliquable sur le code inventaire.
- La redirection du bouton "Modifier" (index) vers la vue `show`.
- La structuration de la vue `show` en onglets standards (Fiche Technique, Affectation Actuelle, Historique Affectations, Journal des Changements).
- L'ajout des boutons d'actions (Changer statut, Affecter, Désaffecter).

## 2. Modifications prévues

### 2.1. Fichier `public/js/modules/parc-info/mobiles/index.js`
- **`codeFormatter`** : Utiliser `route('parc-info.mobiles.show', row.id)` pour générer l'URL du lien sur le code d'inventaire.
- **`#btn-edit`** : Modifier l'événement au clic pour rediriger vers la vue `show` (au lieu d'ouvrir une modale d'édition).

### 2.2. Fichier `Modules/ParcInfo/resources/views/informatique/mobiles/show.blade.php`
- Restructurer complètement la page pour adopter le "Pattern Vues Show" (identique à `ordinateurs/show.blade.php`).
- Ajouter les 4 onglets :
  - **Fiche Technique** : Intégrera les champs actuels de modification (modèle, imei, version OS, etc.).
  - **Affectation Actuelle** : Affichera les informations de l'employé/poste actuel, ou un message "En stock".
  - **Historique Affectations** : Affichera un tableau chronologique des affectations.
  - **Journal des Changements** : Affichera la `timeline` des modifications de l'équipement.
- Ajouter le bouton "Affecter" dans le Header Card et le menu déroulant de changement de "Statut".
- Ajouter les scripts JS nécessaires en bas de page pour gérer l'affectation, la désaffectation et la modification.

### 2.3. Fichier `Modules/ParcInfo/routes/web.php`
- Ajouter la route POST `store-affectation` pour permettre l'attribution d'un terminal mobile :
  `Route::post('/affectation', [MobileController::class, 'storeAffectation'])->name('store-affectation');`

### 2.4. Fichier `Modules/ParcInfo/app/Http/Controllers/MobileController.php`
- Ajouter la méthode `storeAffectation(Request $request)` pour gérer l'enregistrement d'une nouvelle affectation. Cette méthode suivra la même logique que celle présente dans `OrdinateurController`, en gérant l'historique et en clôturant l'affectation précédente le cas échéant.

## 3. Validation
Après l'application de ces changements, un contrôle `vendor/bin/pint` sera effectué pour s'assurer du bon formatage du code Laravel, suivi de vérifications fonctionnelles des URLs JS.