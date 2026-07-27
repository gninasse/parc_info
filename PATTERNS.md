# PATTERNS DE DÉVELOPPEMENT — parc_info

> Référence consolidée des conventions et patterns utilisés dans ce projet.
> À lire **avant** de créer ou modifier un module.

---

## Table des matières

1. [Stack & librairies](#1-stack--librairies)
2. [Architecture modulaire](#2-architecture-modulaire)
3. [Conventions de nommage](#3-conventions-de-nommage)
4. [Pattern CRUD complet](#4-pattern-crud-complet)
   - 4.1 [Controller](#41-controller)
   - 4.2 [Form Requests](#42-form-requests)
   - 4.3 [Routes](#43-routes)
   - 4.4 [Vue Blade index](#44-vue-blade-index)
   - 4.5 [Vue Blade modal](#45-vue-blade-modal)
   - 4.6 [JavaScript — Variante A (ES Modules / classes)](#46-javascript--variante-a-es-modules--classes)
   - 4.7 [JavaScript — Variante B (procédural / fichier unique)](#47-javascript--variante-b-procédural--fichier-unique)
5. [Réponses JSON standardisées](#5-réponses-json-standardisées)
6. [Service Layer](#6-service-layer)
7. [Traits Eloquent transversaux](#7-traits-eloquent-transversaux)
8. [Permissions & autorisation](#8-permissions--autorisation)
9. [Frontend détaillé](#9-frontend-détaillé)
   - 9.1 [Bootstrap Table](#91-bootstrap-table)
   - 9.2 [Toolbar + sélection radio](#92-toolbar--sélection-radio)
   - 9.3 [Modal Bootstrap 5](#93-modal-bootstrap-5)
   - 9.4 [Offcanvas & Popover](#94-offcanvas--popover)
   - 9.5 [SweetAlert2](#95-sweetalert2)
   - 9.6 [Erreurs de validation inline](#96-erreurs-de-validation-inline)
10. [Events & Listeners](#10-events--listeners)
11. [Inter-modules](#11-inter-modules)
12. [Migrations & modèles](#12-migrations--modèles)
13. [Tests](#13-tests)
14. [Checklist nouveau module](#14-checklist-nouveau-module)

---

## 1. Stack & librairies

### Backend
| Élément | Valeur |
|---------|--------|
| Framework | Laravel 12, PHP 8.2+ |
| Modules | `nwidart/laravel-modules` |
| Auth | Laravel Sanctum (API) + session (web) |
| Permissions | `spatie/laravel-permission` v6 |
| Audit trail | `spatie/laravel-activitylog` |
| PDF | `barryvdh/laravel-dompdf` |

### Frontend — librairies disponibles dans `/public/plugins/`
| Lib | Usage |
|-----|-------|
| **Bootstrap 5** `bootstrap.bundle.min.js` | Layout, modals, offcanvas, popovers, tooltips |
| **jQuery** 3.7.1 | AJAX, DOM |
| **Bootstrap Table** | Tableaux paginés server-side |
| **SweetAlert2** | Confirmations, toasts, alertes |
| **Select2** | Selects enrichis avec recherche |
| **Ziggy** | Helper `route()` en JavaScript |
| **FontAwesome** + **Bootstrap Icons** | Icônes |
| **ApexCharts** / **Chart.js** | Graphiques |
| **SortableJS** | Drag & drop |
| **Moment.js** | Dates JS |
| **iCheck Bootstrap** | Checkboxes stylisées |
| **Bootstrap Toggle** | Switches on/off |

### Chargement global (dans `master.blade.php`, disponible partout)
```
jQuery → $.ajaxSetup(CSRF) → Popper → Bootstrap → AdminLTE → SweetAlert2 → Select2 → Ziggy (@routes)
```
**Bootstrap Table, Bootstrap Toggle, iCheck** se chargent à la demande via `@push('js')`.

---

## 2. Architecture modulaire

```
Modules/{NomModule}/
├── app/
│   ├── Actions/              # Classes action (opérations atomiques complexes)
│   ├── Contracts/            # Interfaces pour le découplage inter-modules
│   ├── Events/               # Événements Laravel
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Requests/         # Form Requests (authorize + validate)
│   ├── Listeners/
│   ├── Models/
│   ├── Providers/
│   │   ├── {Module}ServiceProvider.php
│   │   ├── EventServiceProvider.php
│   │   └── RouteServiceProvider.php
│   ├── Services/             # Logique métier
│   └── Traits/
├── config/
│   ├── config.php            # Config module
│   └── permissions.php       # Déclaration des permissions
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/views/
│   ├── layouts/
│   │   ├── master.blade.php
│   │   └── partials/
│   ├── {ressource}/
│   │   ├── index.blade.php
│   │   ├── show.blade.php    # (si page dédiée)
│   │   ├── create.blade.php  # (si formulaire long / page dédiée)
│   │   └── _modal.blade.php  # (si CRUD en modal)
│   └── components/
│       └── shared/
├── routes/
│   ├── web.php
│   └── api.php
└── tests/Feature/
```

```
public/js/modules/{module-slug}/
├── {ressource}/
│   ├── index.js        # point d'entrée
│   ├── {Res}Form.js    # (variante A uniquement)
│   └── {Res}Actions.js # (variante A uniquement)
└── referentiels/
    └── {ressource}.js  # variante B (fichier unique)
```

---

## 3. Conventions de nommage

| Élément | Convention | Exemple |
|---------|-----------|---------|
| **Table DB** | `{module}_{ressource}` | `stock_magasins` |
| **Route name** | `{module}.{ressource}.{action}` | `stock.magasins.store` |
| **Route data** | `{module}.{ressource}.data` | `stock.magasins.data` |
| **Permission** | `{module}.{ressource}.{action}` | `stock.magasins.create` |
| **Vue namespace** | `{module}::{ressource}.{action}` | `stock::magasins.index` |
| **Controller** | `{Ressource}Controller` | `MagasinController` |
| **Service** | `{Ressource}Service` | `MagasinService` |
| **Action** | `{Operation}Action` | `StockExitAction` |
| **Form Request** | `{Store\|Update}{Ressource}Request` | `StoreMagasinRequest` |
| **Event** | `{Ressource}{ActionPassée}` | `BonEntreeValide` |
| **Interface** | `{Module}IntegrationInterface` | `ParcInfoIntegrationInterface` |
| **JS fichier** | camelCase ou kebab-case selon contexte | `MagasinForm.js`, `bons-entree.js` |
| **ID champ caché** | `{ressource}-id` ou `item-id` | `magasin-id` |
| **ID formulaire** | `{ressource}-form` ou `item-form` | `magasin-form` |
| **ID modal** | `{ressource}Modal` ou `item-modal` | `magasinModal` |
| **ID table** | `{ressource}s-table` ou `items-table` | `magasins-table` |

**Slugs de modules dans les routes** (kebab-case) :
- `Core` → `cores`
- `ParcInfo` → `parc-info`
- `Grh` → `grh`
- `Organisation` → `organisation`

---

## 4. Pattern CRUD complet

### 4.1 Controller

```php
<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Stock\Http\Requests\StoreMagasinRequest;
use Modules\Stock\Http\Requests\UpdateMagasinRequest;
use Modules\Stock\Models\Magasin;

class MagasinController extends Controller
{
    use AuthorizesRequests;

    // ── Vue liste (retourne uniquement la vue) ──
    public function index(): \Illuminate\View\View
    {
        $this->authorize('stock.magasins.view');

        return view('stock::magasins.index');
    }

    // ── Données pour Bootstrap Table (server-side) ──
    public function getData(Request $request): JsonResponse
    {
        $this->authorize('stock.magasins.view');

        $query = Magasin::query();

        // Recherche
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('nom', 'like', "%{$search}%");
            });
        }

        // Filtres additionnels
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Tri (whitelist impérative)
        $sortField = $request->input('sort', 'created_at');
        $sortOrder = $request->input('order', 'desc');
        $allowed   = ['code', 'nom', 'type', 'est_actif', 'created_at'];
        if (! in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }
        $query->orderBy($sortField, $sortOrder);

        $total = $query->count();
        $rows  = $query
            ->offset((int) $request->input('offset', 0))
            ->limit((int)  $request->input('limit', 10))
            ->get()
            ->map(fn (Magasin $m) => [
                'id'         => $m->id,
                'code'       => $m->code,
                'nom'        => $m->nom,
                'type'       => $m->type,
                'type_label' => config("stock.types_magasin.{$m->type}", $m->type),
                'est_actif'  => $m->est_actif,
                'created_at' => $m->created_at?->toDateTimeString(),
            ]);

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    // ── Pré-remplissage Edit (appelé par le JS avant d'ouvrir le modal) ──
    public function show(Magasin $magasin): JsonResponse
    {
        $this->authorize('stock.magasins.view');

        return response()->json([
            'success' => true,
            'data'    => $magasin->only(['id', 'code', 'nom', 'type', 'description', 'est_actif']),
        ]);
    }

    public function store(StoreMagasinRequest $request): JsonResponse
    {
        try {
            $magasin = Magasin::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Le magasin « {$magasin->nom} » a été créé avec succès.",
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateMagasinRequest $request, Magasin $magasin): JsonResponse
    {
        try {
            $magasin->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Le magasin « {$magasin->nom} » a été modifié avec succès.",
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Magasin $magasin): JsonResponse
    {
        $this->authorize('stock.magasins.delete');

        try {
            // Règle métier avant suppression
            if (! $magasin->estSupprimable()) {
                throw new Exception('Ce magasin ne peut pas être supprimé car il contient des données.');
            }

            $magasin->delete();

            return response()->json(['success' => true, 'message' => 'Magasin supprimé avec succès.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
```

**Points clés :**
- `index()` → **vue uniquement**, plus de double-rôle
- `getData()` → **JSON Bootstrap Table** (`{total, rows}`)
- `show()` → **JSON** pour pré-remplir le modal Edit
- `$request->validated()` dans store/update (pas `safe()->all()`)
- `try/catch` sur toutes les mutations, `500` pour erreurs serveur, `422` pour règles métier
- Pas d'injection de Service dans le constructeur pour les CRUDs simples (Eloquent direct)
- Avec Service : `public function __construct(protected MagasinService $service) {}`

---

### 4.2 Form Requests

```php
class StoreMagasinRequest extends FormRequest
{
    // Toujours vérifier la permission ici (pas return true)
    public function authorize(): bool
    {
        return $this->user()->can('stock.magasins.create');
    }

    public function rules(): array
    {
        return [
            'code'        => ['required', 'string', 'max:30', 'unique:stock_magasins,code'],
            'nom'         => ['required', 'string', 'max:255'],
            'type'        => ['required', 'in:TECHNIQUE,CONSOMMABLE,REBUT'],
            'description' => ['nullable', 'string'],
            'est_actif'   => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique'   => 'Ce code existe déjà.',
            'code.required' => 'Le code est obligatoire.',
            'nom.required'  => 'Le nom est obligatoire.',
        ];
    }
}

class UpdateMagasinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.magasins.edit');
    }

    public function rules(): array
    {
        // Récupérer l'ID de la route model binding
        $id = $this->route('magasin') instanceof Magasin
            ? $this->route('magasin')->id
            : $this->route('magasin');

        return [
            'code' => ['required', 'string', 'max:30', 'unique:stock_magasins,code,'.$id],
            'nom'  => ['required', 'string', 'max:255'],
            // ...
        ];
    }
}
```

---

### 4.3 Routes

```php
// routes/web.php
Route::middleware(['auth', 'verified'])->prefix('{module-slug}')->name('{module}.')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');

    // CRUD simple en modal (pas de page create/edit/show dédiée)
    Route::get('magasins',          [MagasinController::class, 'index'])  ->name('magasins.index');
    Route::get('magasins/data',     [MagasinController::class, 'getData'])->name('magasins.data');
    Route::get('magasins/{magasin}',[MagasinController::class, 'show'])   ->name('magasins.show');
    Route::post('magasins',         [MagasinController::class, 'store'])  ->name('magasins.store');
    Route::put('magasins/{magasin}',[MagasinController::class, 'update']) ->name('magasins.update');
    Route::delete('magasins/{magasin}', [MagasinController::class, 'destroy'])->name('magasins.destroy');

    // Ressource avec page dédiée (create/show)
    Route::get('bons-entree/create',        [BonEntreeController::class, 'create'])  ->name('bons-entree.create');
    Route::get('bons-entree/data',          [BonEntreeController::class, 'getData']) ->name('bons-entree.data');
    Route::get('bons-entree/{bon_entree}',  [BonEntreeController::class, 'show'])    ->name('bons-entree.show');
    Route::post('bons-entree/{bon_entree}/valider', [BonEntreeController::class, 'valider'])->name('bons-entree.valider');
    Route::resource('bons-entree', BonEntreeController::class)
        ->except(['create', 'show', 'edit'])->names('bons-entree');
});
```

**Règle :** La route `data` doit être déclarée **avant** le `resource` ou avant les routes avec paramètre, sinon Laravel interprète `data` comme un ID.

---

### 4.4 Vue Blade index

```blade
@extends('{module}::layouts.master')

@section('header', 'Magasins')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('{module}.dashboard.index') }}">Module</a></li>
    <li class="breadcrumb-item active">Magasins</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')

{{-- Filtres externes (si nécessaire) --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Type</label>
                <select class="form-select form-select-sm" id="filter-type">
                    <option value="">Tous</option>
                    <option value="TECHNIQUE">Technique</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-warehouse me-2 text-primary"></i>Liste des magasins</h6>
    </div>
    <div class="card-body p-0">

        {{-- Toolbar : boutons DÉSACTIVÉS par défaut, activés à la sélection --}}
        <div id="toolbar">
            @can('{module}.magasins.create')
            <button id="btn-add" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="Ajouter">
                <i class="fas fa-plus"></i>
            </button>
            @endcan
            @can('{module}.magasins.edit')
            <button id="btn-edit" class="btn btn-info btn-sm" disabled data-bs-toggle="tooltip" title="Modifier">
                <i class="fas fa-edit"></i>
            </button>
            @endcan
            @can('{module}.magasins.delete')
            <button id="btn-delete" class="btn btn-danger btn-sm" disabled data-bs-toggle="tooltip" title="Supprimer">
                <i class="fas fa-trash"></i>
            </button>
            @endcan
        </div>

        <table id="magasins-table"
               data-toggle="table"
               data-url="{{ route('{module}.magasins.data') }}"
               data-pagination="true"
               data-side-pagination="server"
               data-search="true"
               data-show-refresh="true"
               data-show-columns="true"
               data-toolbar="#toolbar"
               data-click-to-select="true"
               data-single-select="true"
               data-id-field="id"
               data-page-list="[10, 25, 50, 100]"
               data-page-size="10"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="id" data-sortable="true">ID</th>
                    <th data-field="code" data-sortable="true">Code</th>
                    <th data-field="nom" data-sortable="true">Nom</th>
                    <th data-field="type_label">Type</th>
                    <th data-field="est_actif" data-formatter="statutFormatter">Actif</th>
                    <th data-field="created_at" data-sortable="true" data-formatter="dateFormatter">Créé le</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('{module}::magasins._modal')
@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/{module}/magasins/index.js') }}?v={{ time() }}"></script>
@endpush
```

**Points clés :**
- `data-url` → route `.data` (jamais `.index`)
- `data-search="true"` : si la recherche est gérée par Bootstrap Table (recommandé)
- Filtres externes (`#filter-type`) : injectés via `queryParams` dans le JS
- Pas de colonne `actions` inline dans le `<thead>` — tout passe par les boutons toolbar
- `data-single-select="true"` + `data-radio="true"` → sélection d'une ligne à la fois

---

### 4.5 Vue Blade modal

```blade
{{-- _modal.blade.php --}}
<div class="modal fade" id="magasinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="magasinModalLabel">
                    <i class="fas fa-warehouse me-2"></i><span id="modal-title-text">Nouveau magasin</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            {{-- Le <form> EST DANS modal-content (pas à l'extérieur) --}}
            <form id="magasin-form" novalidate>
                @csrf
                <input type="hidden" id="magasin-id" name="id">

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="f-code" class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="f-code" name="code" required>
                        </div>
                        <div class="col-md-8">
                            <label for="f-nom" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="f-nom" name="nom" required>
                        </div>
                        <div class="col-md-6">
                            <label for="f-type" class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="f-type" name="type" required>
                                <option value="TECHNIQUE">Technique</option>
                                <option value="CONSOMMABLE">Consommable</option>
                                <option value="REBUT">Rebut</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="f-actif" name="est_actif" value="1" checked>
                                <label class="form-check-label" for="f-actif">Actif</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="f-description" class="form-label">Description</label>
                            <textarea class="form-control" id="f-description" name="description" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-save">
                        <i class="fas fa-save me-1"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
```

**Règles obligatoires :**
- Tous les inputs ont un attribut `name` (pour `$form.serialize()`)
- `@csrf` est **à l'intérieur** du `<form>`, pas dans `modal-body`
- Champ caché `id` nommé `{ressource}-id` (sans attribut `name`, ou avec `name="id"`)
- `novalidate` sur le form (on gère la validation côté JS/PHP)
- Checkbox booléenne : `value="1"` (Laravel reçoit `"1"` ou absent → cast `boolean`)

---

### 4.6 JavaScript — Variante A (ES Modules / classes)

Utiliser quand le CRUD a des **actions secondaires complexes** (ex : gestion de permissions, wizard multi-étapes).

**Structure fichiers :**
```
public/js/modules/{module}/{ressource}/
├── index.js          ← point d'entrée, init table + instancie les classes
├── {Res}Form.js      ← classe : modal + validation + soumission AJAX
└── {Res}Actions.js   ← classe : boutons toolbar + actions secondaires
```

**`index.js`** :
```js
/**
 * index.js — point d'entrée {Ressource}
 */
import { MagasinForm }    from './MagasinForm.js';
import { MagasinActions } from './MagasinActions.js';

// Les formatters DOIVENT être en window.* et AVANT DOMContentLoaded
window.dateFormatter = function (value) {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('fr-FR', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit'
    });
};

window.statutFormatter = function (value) {
    return value
        ? '<span class="badge bg-success">Actif</span>'
        : '<span class="badge bg-secondary">Inactif</span>';
};

$(function () {
    const $table = $('#magasins-table');

    // Objet tableInstance partagé entre les classes
    const tableInstance = {
        refresh:       () => $table.bootstrapTable('refresh'),
        getSelectedId: () => {
            const sel = $table.bootstrapTable('getSelections');
            if (!sel.length) {
                Swal.fire({ icon: 'warning', title: 'Attention', text: 'Veuillez sélectionner une ligne.' });
                return null;
            }
            return sel[0].id;
        }
    };

    const form    = new MagasinForm('#magasinModal', '#magasin-form', tableInstance);
    const actions = new MagasinActions(tableInstance, form);

    // Activation/désactivation des boutons toolbar selon la sélection
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const sel = $table.bootstrapTable('getSelections');
        const one = sel.length === 1;
        $('#btn-edit').prop('disabled', !one);
        $('#btn-delete').prop('disabled', sel.length === 0);
    });

    // Injection des filtres externes dans les params Bootstrap Table
    $('#filter-type').on('change', () => $table.bootstrapTable('refresh'));
    $table.bootstrapTable('refreshOptions', {
        queryParams: function (params) {
            params.type = $('#filter-type').val();
            return params;
        }
    });
});
```

**`MagasinForm.js`** :
```js
export class MagasinForm {
    constructor(modalSelector, formSelector, tableInstance) {
        this.$modal = $(modalSelector);
        this.$form  = $(formSelector);
        this.table  = tableInstance;
        this._initSubmission();
        this._clearErrorsOnInput();
    }

    openForAdd() {
        this.$form[0].reset();
        this._clearErrors();
        $('#magasin-id').val('');
        $('#modal-title-text').text('Nouveau magasin');
        // Si un champ est désactivé en mode Edit, le réactiver
        $('#f-code').prop('disabled', false);
        this.$modal.modal('show');
    }

    openForEdit(data) {
        this.$form[0].reset();
        this._clearErrors();
        $('#magasin-id').val(data.id);
        $('#modal-title-text').text('Modifier le magasin');
        $('#f-code').val(data.code).prop('disabled', true); // code immuable
        $('#f-nom').val(data.nom);
        $('#f-type').val(data.type);
        $('#f-actif').prop('checked', data.est_actif);
        $('#f-description').val(data.description ?? '');
        this.$modal.modal('show');
    }

    _initSubmission() {
        this.$form.on('submit', (e) => {
            e.preventDefault();
            const id     = $('#magasin-id').val();
            const url    = id ? route('stock.magasins.update', id) : route('stock.magasins.store');
            const method = id ? 'PUT' : 'POST';

            const $btn = $('#btn-save');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

            $.ajax({
                url, method,
                data: this.$form.serialize(),
                success: (res) => {
                    if (res.success) {
                        this.$modal.modal('hide');
                        this.table.refresh();
                        Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 2000 });
                    }
                },
                error: (xhr) => {
                    if (xhr.status === 422) {
                        this._displayErrors(xhr.responseJSON.errors);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Une erreur est survenue.' });
                    }
                },
                complete: () => {
                    $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Enregistrer');
                }
            });
        });
    }

    _displayErrors(errors) {
        this._clearErrors();
        $.each(errors, (field, messages) => {
            const $field = this.$form.find(`[name="${field}"]`);
            $field.addClass('is-invalid');
            $field.after(`<div class="invalid-feedback d-block">${messages[0]}</div>`);
        });
    }

    _clearErrors() {
        this.$form.find('.is-invalid').removeClass('is-invalid');
        this.$form.find('.invalid-feedback').remove();
    }

    _clearErrorsOnInput() {
        this.$form.on('input change', '.is-invalid', function () {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        });
    }
}
```

**`MagasinActions.js`** :
```js
export class MagasinActions {
    constructor(tableInstance, formInstance) {
        this.table = tableInstance;
        this.form  = formInstance;
        this._initButtons();
    }

    _initButtons() {
        $('#btn-add').on('click', () => this.form.openForAdd());

        $('#btn-edit').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) this._loadAndEdit(id);
        });

        $('#btn-delete').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) this._confirmDelete(id);
        });
    }

    _loadAndEdit(id) {
        // GET show() → pré-remplir le modal
        $.ajax({
            url: route('stock.magasins.show', id),
            method: 'GET',
            success: (res) => {
                if (res.success) this.form.openForEdit(res.data);
            },
            error: () => Swal.fire({ icon: 'error', title: 'Erreur', text: 'Impossible de charger les données.' })
        });
    }

    _confirmDelete(id) {
        Swal.fire({
            title: 'Supprimer ce magasin ?',
            text: 'Cette action est irréversible.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor:  '#3085d6',
            confirmButtonText:  'Oui, supprimer',
            cancelButtonText:   'Annuler'
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: route('stock.magasins.destroy', id),
                method: 'DELETE',
                success: (res) => {
                    if (res.success) {
                        this.table.refresh();
                        Swal.fire({ icon: 'success', title: 'Supprimé', text: res.message, timer: 2000 });
                    }
                },
                error: (xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Suppression impossible.' })
            });
        });
    }
}
```

---

### 4.7 JavaScript — Variante B (procédural / fichier unique)

Utiliser pour les **CRUDs simples** (référentiels, 3 actions, pas d'action secondaire).

```js
/**
 * {ressource}.js — CRUD simple (variante procédurale)
 */

// Formatters : OBLIGATOIREMENT hors de DOMContentLoaded (scope global pour Bootstrap Table)
window.dateFormatter = function (value) {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('fr-FR', { year: 'numeric', month: '2-digit', day: '2-digit' });
};

window.statutFormatter = function (value) {
    return value ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>';
};

document.addEventListener('DOMContentLoaded', function () {
    const $table  = $('#items-table');
    const $modal  = new bootstrap.Modal('#item-modal'); // API Bootstrap 5 directe
    const $form   = $('#item-form');
    const $btnSave = $('#btn-save');

    // ── Sélection → active les boutons toolbar ──
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const sel = $table.bootstrapTable('getSelections');
        $('#btn-edit').prop('disabled', sel.length !== 1);
        $('#btn-delete').prop('disabled', sel.length === 0);
    });

    // ── Filtres externes ──
    $('#filter-type').on('change', () => $table.bootstrapTable('refresh'));
    $table.bootstrapTable('refreshOptions', {
        queryParams: (params) => {
            params.type = $('#filter-type').val();
            return params;
        }
    });

    // ── ADD ──
    $('#btn-add').on('click', function () {
        $form[0].reset();
        clearErrors();
        $('#item-id').val('');
        $('#modal-title-text').text('Nouveau');
        $modal.show();
    });

    // ── EDIT : GET show() → pré-remplir ──
    $('#btn-edit').on('click', function () {
        const row = $table.bootstrapTable('getSelections')[0];
        if (!row) return;
        $.ajax({
            url: route('{module}.{ressource}.show', row.id),
            method: 'GET',
            success: function (res) {
                if (!res.success) return;
                const d = res.data;
                $form[0].reset();
                clearErrors();
                $('#item-id').val(d.id);
                $('#modal-title-text').text('Modifier');
                $form.find('[name="libelle"]').val(d.libelle);
                // ... remplir les autres champs
                $modal.show();
            },
            error: () => Swal.fire('Erreur', 'Impossible de charger les données.', 'error')
        });
    });

    // ── SUBMIT (POST ou PUT selon id caché) ──
    $form.on('submit', function (e) {
        e.preventDefault();
        const id     = $('#item-id').val();
        const url    = id ? route('{module}.{ressource}.update', id) : route('{module}.{ressource}.store');
        const method = id ? 'PUT' : 'POST';

        $btnSave.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement...');

        $.ajax({
            url, method,
            data: $form.serialize(),
            success: function (res) {
                if (res.success) {
                    $modal.hide();
                    $table.bootstrapTable('refresh');
                    Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500 });
                }
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    displayErrors(xhr.responseJSON.errors);
                } else {
                    Swal.fire('Erreur', xhr.responseJSON?.message ?? 'Une erreur est survenue.', 'error');
                }
            },
            complete: () => $btnSave.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Enregistrer')
        });
    });

    // ── DELETE ──
    $('#btn-delete').on('click', function () {
        const row = $table.bootstrapTable('getSelections')[0];
        if (!row) return;
        Swal.fire({
            title: 'Supprimer cet élément ?',
            text: 'Cette action est définitive.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: route('{module}.{ressource}.destroy', row.id),
                method: 'DELETE',
                success: function (res) {
                    if (res.success) {
                        $table.bootstrapTable('refresh');
                        Swal.fire({ icon: 'success', title: 'Supprimé', text: res.message, timer: 1500 });
                    }
                },
                error: (xhr) => Swal.fire('Erreur', xhr.responseJSON?.message ?? 'Suppression impossible.', 'error')
            });
        });
    });

    // ── Helpers erreurs ──
    function displayErrors(errors) {
        clearErrors();
        $.each(errors, (field, messages) => {
            const $field = $form.find(`[name="${field}"]`);
            $field.addClass('is-invalid');
            $field.after(`<div class="invalid-feedback d-block">${messages[0]}</div>`);
        });
    }

    function clearErrors() {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
    }

    // Nettoyage auto à la saisie
    $form.on('input change', '.is-invalid', function () {
        $(this).removeClass('is-invalid');
        $(this).next('.invalid-feedback').remove();
    });
});
```

---

## 5. Réponses JSON standardisées

```php
// getData() — Bootstrap Table server-side (obligatoire)
['total' => int, 'rows' => array]

// show() — pré-remplissage Edit
['success' => true,  'data' => array]
['success' => false, 'message' => string]  // 404

// store() / update() — succès
['success' => true, 'message' => string]
['success' => true, 'message' => string, 'redirect' => url]  // si redirection post-create
['success' => true, 'message' => string, 'data' => model]    // si le JS a besoin de l'objet créé

// store() / update() — erreur serveur (catch Exception)
// HTTP 500
['success' => false, 'message' => string]

// Validation Laravel (Form Request) — automatique
// HTTP 422
['message' => string, 'errors' => ['field' => ['message']]]

// destroy() — succès
['success' => true, 'message' => string]

// Action métier refusée (règle métier, pas validation)
// HTTP 422 ou 403
['success' => false, 'message' => string]
```

---

## 6. Service Layer

### Règles
- **Pas de Repository pattern** : les Services accèdent directement à Eloquent
- Injection via **promoted properties PHP 8.1** dans le constructeur
- Services "lourds" enregistrés en **singleton** dans le ServiceProvider
- Toute opération multi-tables enveloppée dans `DB::transaction()`

```php
// StockServiceProvider::register()
$this->app->singleton(WizardService::class);
$this->app->bind(ParcInfoIntegrationInterface::class, ParcInfoIntegrationService::class);
```

```php
class BonEntreeController extends Controller
{
    public function __construct(
        protected WizardService      $wizardService,
        protected StockManagerService $stockManager
    ) {}
}
```

### Structure Service type

```php
class MagasinService
{
    public function creer(array $data): Magasin
    {
        return DB::transaction(function () use ($data) {
            $magasin = Magasin::create($data);
            // opérations liées...
            activity()->performedOn($magasin)->log('created');
            return $magasin;
        });
    }

    public function supprimer(Magasin $magasin): void
    {
        if (! $magasin->estSupprimable()) {
            throw new \Exception('Magasin non supprimable.');
        }
        DB::transaction(fn () => $magasin->delete());
    }
}
```

---

## 7. Traits Eloquent transversaux

### `HasAuditFields`
Injecte `created_by` / `updated_by` automatiquement via hooks Eloquent.
```php
use Modules\Stock\Traits\HasAuditFields;

class Magasin extends Model
{
    use HasAuditFields, SoftDeletes;
    // created_by, updated_by remplis automatiquement
    // Relations: creator(), updater()
}
```

### `GeneratesDocumentNumbers`
Génère des numéros séquentiels anti-collision avec `lockForUpdate()`.
```php
use Modules\Stock\Traits\GeneratesDocumentNumbers;

// Dans le contrôleur ou le service :
$numero = (new BonEntree)->genererNumero('bon_entree', config('stock.prefix_bon_entree', 'BE'));
// → "BE-2026-0001"

// Surcharger dans le modèle :
protected function numeroColonne(): string { return 'numero_be'; }
```

### `SoftDeletes`
Tous les modèles principaux utilisent `SoftDeletes`. Les tables ont `deleted_at`.

### `HasModulePermissions` (Core)
Extension Spatie pour la dimension module.
```php
$user->hasModuleAccess('stock');       // a au moins 1 permission dans le module
$user->getAccessibleModules();         // liste des modules accessibles
$user->canAccessModuleResource('stock', 'magasins', 'create');
```

---

## 8. Permissions & autorisation

### Convention de nommage
```
{module}.{ressource}.{action}
```
Actions standard : `view`, `create`, `edit`, `delete`, `valider`, `annuler`, `export`

### Déclaration `config/permissions.php`
```php
return [
    'stock.dashboard.view'       => 'Voir le tableau de bord Stock',
    'stock.magasins.view'        => 'Voir les magasins',
    'stock.magasins.create'      => 'Créer un magasin',
    'stock.magasins.edit'        => 'Modifier un magasin',
    'stock.magasins.delete'      => 'Supprimer un magasin',
    'stock.bons_entree.view'     => 'Voir les bons d\'entrée',
    'stock.bons_entree.create'   => 'Créer un bon d\'entrée',
    'stock.bons_entree.valider'  => 'Valider un bon d\'entrée',
];
```

Synchronisation : `php artisan cores:sync-permissions stock`

### Points de contrôle (3 niveaux)
```php
// 1. Form Request (HTTP, déclaratif)
public function authorize(): bool { return $this->user()->can('stock.magasins.create'); }

// 2. Contrôleur (impératif, pour les méthodes sans Form Request)
$this->authorize('stock.magasins.delete');

// 3. Blade (affichage conditionnel)
@can('stock.magasins.create') ... @endcan
```

### Gate super-admin (Core)
```php
// CoreServiceProvider : bypass total pour super-admin
Gate::before(fn ($user) => $user->hasRole('super-admin') ? true : null);
```

---

## 9. Frontend détaillé

### 9.1 Bootstrap Table

**Initialisation HTML (data-attributes)** :
```html
<table id="items-table"
       data-toggle="table"
       data-url="{{ route('{module}.{ressource}.data') }}"
       data-side-pagination="server"
       data-pagination="true"
       data-search="true"
       data-show-refresh="true"
       data-show-columns="true"
       data-toolbar="#toolbar"
       data-click-to-select="true"
       data-single-select="true"
       data-id-field="id"
       data-page-list="[10, 25, 50, 100]"
       data-page-size="10"
       class="table table-hover align-middle mb-0">
```

**Formatters** — toujours en `window.*`, déclarés **avant** `DOMContentLoaded` :
```js
window.dateFormatter    = (v) => v ? new Date(v).toLocaleDateString('fr-FR', {...}) : '-';
window.statutFormatter  = (v) => v ? '<span class="badge bg-success">Actif</span>' : '...';
window.prixFormatter    = (v) => v != null ? new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(v) : '-';
window.badgeFormatter   = (v, row) => `<span class="badge bg-${row.couleur}">${v}</span>`;
```

**Filtres externes** :
```js
// Injecter les filtres custom dans les paramètres AJAX
$table.bootstrapTable('refreshOptions', {
    queryParams: function (params) {
        params.statut = $('#filter-statut').val();
        params.type   = $('#filter-type').val();
        return params;
    }
});
$('#filter-statut, #filter-type').on('change', () => $table.bootstrapTable('refresh'));
```

**Rafraîchissement** :
```js
$table.bootstrapTable('refresh');                    // recharge les données
$table.bootstrapTable('refreshOptions', { ... });    // change les options puis recharge
```

---

### 9.2 Toolbar + sélection radio

```js
// Activer/désactiver les boutons selon la sélection
$table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
    const sel = $table.bootstrapTable('getSelections');
    $('#btn-edit').prop('disabled', sel.length !== 1);
    $('#btn-delete').prop('disabled', sel.length === 0);
    // Pour les boutons qui nécessitent exactement 1 sélection :
    $('#btn-permissions').prop('disabled', sel.length !== 1);
});

// Récupérer la ligne sélectionnée
const row = $table.bootstrapTable('getSelections')[0];
const id  = row?.id;
```

---

### 9.3 Modal Bootstrap 5

```js
// Instanciation (API Bootstrap 5 directe, recommandé en variante B)
const $modal = new bootstrap.Modal('#monModal');
$modal.show();
$modal.hide();

// Via jQuery Bootstrap plugin (recommandé en variante A, déjà inclus via bundle)
$('#monModal').modal('show');
$('#monModal').modal('hide');
```

**Offcanvas** (même API, préféré pour formulaires longs) :
```js
const offcanvas = new bootstrap.Offcanvas('#monOffcanvas');
offcanvas.show();
offcanvas.hide();
```

```html
<div class="offcanvas offcanvas-end" tabindex="-1" id="monOffcanvas" aria-labelledby="offcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasLabel">Titre</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <form id="item-form" novalidate>
            @csrf
            <input type="hidden" id="item-id" name="id">
            <!-- champs -->
            <button type="submit" class="btn btn-primary" id="btn-save">Enregistrer</button>
        </form>
    </div>
</div>
```

**Popover** (confirmation légère, remplace Swal sur des petits boutons inline) :
```js
// Confirmation delete inline via popover
const popover = new bootstrap.Popover('#btn-delete', {
    html: true,
    trigger: 'focus',
    content: `<div>Confirmer ?
        <button class="btn btn-sm btn-danger ms-2" id="confirm-delete">Oui</button>
        <button class="btn btn-sm btn-secondary ms-1" id="cancel-delete">Non</button>
    </div>`
});
$(document).on('click', '#confirm-delete', function() { /* DELETE AJAX */ });
$(document).on('click', '#cancel-delete',  function() { popover.hide(); });
```

---

### 9.4 Offcanvas & Popover

**Cas d'usage recommandés :**

| Composant | Quand l'utiliser |
|-----------|-----------------|
| **Modal** | CRUD standard (≤ 6 champs), confirmation complexe |
| **Offcanvas** | Formulaire long (> 6 champs), panneau de détails, filtres avancés |
| **Popover** | Confirmation inline sur un bouton, aperçu rapide d'info |
| **Toast Swal** | Feedback après action réussie (non bloquant, timer) |
| **Swal.fire** | Confirmation destructive (delete), erreur critique |

---

### 9.5 SweetAlert2

```js
// Toast non bloquant (succès après action)
const Toast = Swal.mixin({
    toast: true, position: 'top-end',
    showConfirmButton: false, timer: 2000, timerProgressBar: true
});
Toast.fire({ icon: 'success', title: res.message });

// Confirmation destructive (delete)
Swal.fire({
    title: 'Supprimer ?', text: 'Action irréversible.', icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
    confirmButtonText: 'Oui, supprimer', cancelButtonText: 'Annuler'
}).then((result) => { if (result.isConfirmed) { /* DELETE */ } });

// Alerte succès simple (avec fermeture auto)
Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 2000 });

// Erreur
Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Erreur.' });

// Warning sélection
Swal.fire({ icon: 'warning', title: 'Attention', text: 'Veuillez sélectionner une ligne.' });

// Confirmation bulk (> 5 éléments)
Swal.fire({
    title: 'Attention', icon: 'warning',
    text: `Vous allez affecter ${n} permissions. Continuer ?`,
    showCancelButton: true,
    confirmButtonText: 'Oui, continuer', cancelButtonText: 'Annuler'
});
```

---

### 9.6 Erreurs de validation inline

**Pattern recommandé (inline `.is-invalid`)** :
```js
function displayErrors(errors) {
    clearErrors();
    $.each(errors, (field, messages) => {
        // Chercher par name= (plus robuste que par id=)
        const $field = $form.find(`[name="${field}"]`);
        $field.addClass('is-invalid');
        // d-block nécessaire car Bootstrap masque .invalid-feedback sans parent .is-invalid direct
        $field.after(`<div class="invalid-feedback d-block">${messages[0]}</div>`);
    });
}

function clearErrors() {
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.invalid-feedback').remove();
}
```

**Quand utiliser Swal pour les erreurs :**
- Erreurs sur des champs sans `name` correspondant dans `errors`
- Erreur serveur 500 (pas 422)
- Erreurs de règles métier (pas validation formulaire)

---

## 10. Events & Listeners

```php
// EventServiceProvider.php du module
protected $listen = [
    BonEntreeValide::class => [
        NotifierResponsable::class,    // listener actif
        SynchroniserParcInfo::class,   // stub si logique inline dans le service
    ],
];

// Event
class BonEntreeValide
{
    public function __construct(public readonly BonEntree $bonEntree, public readonly int $userId) {}
}

// Déclencher
event(new BonEntreeValide($be, auth()->id()));
```

**Note importante :** Si la logique du listener nécessite la même transaction DB que l'action principale, la mettre **directement dans le Service** et laisser le listener en stub (avec commentaire `// Handled inline in XxxService`). Les listeners ne sont **pas** `ShouldQueue` dans ce projet (tout synchrone).

---

## 11. Inter-modules

Toujours passer par une **Interface + Service d'intégration** :

```php
// Modules/MonModule/app/Contracts/ParcInfoIntegrationInterface.php
interface ParcInfoIntegrationInterface
{
    public function createEquipementFromReception(array $data, int $userId): int;
    public function existsEquipementBySerial(string $serial): bool;
    public function existsEquipementByInventoryCode(string $code): bool;
}

// Modules/MonModule/app/Services/ParcInfoIntegrationService.php
class ParcInfoIntegrationService implements ParcInfoIntegrationInterface
{
    public function createEquipementFromReception(array $data, int $userId): int
    {
        // Appel direct au modèle ParcInfo ou à son service
        return \Modules\ParcInfo\Models\Equipement::create([...])->id;
    }
}

// MonModuleServiceProvider::register()
$this->app->bind(ParcInfoIntegrationInterface::class, ParcInfoIntegrationService::class);
```

**Dépendances inter-modules connues :**
```
Grh → Organisation (DossierEmploye → Employe)
```
(Les modules Achat et Stock, supprimés le 27/07/2026, suivaient ce même patron.)

---

## 12. Migrations & modèles

### Migration type

```php
Schema::create('stock_magasins', function (Blueprint $table) {
    $table->id();
    $table->string('code', 30)->unique();
    $table->string('nom', 255);
    $table->enum('type', ['TECHNIQUE', 'CONSOMMABLE', 'REBUT']);
    $table->text('description')->nullable();
    $table->boolean('est_actif')->default(true);
    $table->unsignedBigInteger('created_by')->nullable();
    $table->unsignedBigInteger('updated_by')->nullable();
    $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
    $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();
});
```

### Modèle type

```php
class Magasin extends Model
{
    use HasAuditFields, HasFactory, SoftDeletes;

    protected $table = 'stock_magasins';

    protected $fillable = ['code', 'nom', 'type', 'description', 'est_actif'];

    protected $casts = ['est_actif' => 'boolean'];

    // Relations
    public function bonsEntree(): HasMany { return $this->hasMany(BonEntree::class, 'magasin_id'); }
    public function inventaires(): HasMany { return $this->hasMany(StockInventaire::class, 'magasin_id'); }

    // Règles métier
    public function estSupprimable(): bool { return ! $this->bonsEntree()->exists(); }
    public function estModifiable(): bool  { return $this->est_actif; }

    // Accesseurs calculés
    public function getStockTotalAttribute(): int { return $this->inventaires()->sum('quantite_stock'); }
}
```

---

## 13. Tests

```php
class MagasinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Bypass toutes les vérifications de permission
        Gate::before(fn () => true);
    }

    public function test_index_returns_view(): void
    {
        $this->actingAs(User::factory()->create())
             ->get(route('stock.magasins.index'))
             ->assertOk()
             ->assertViewIs('stock::magasins.index');
    }

    public function test_getData_returns_json(): void
    {
        Magasin::factory()->count(3)->create();
        $this->actingAs(User::factory()->create())
             ->getJson(route('stock.magasins.data'))
             ->assertOk()
             ->assertJsonStructure(['total', 'rows']);
    }

    public function test_store_creates_magasin(): void
    {
        $this->actingAs(User::factory()->create())
             ->postJson(route('stock.magasins.store'), [
                 'code' => 'MAG-01', 'nom' => 'Magasin Test', 'type' => 'TECHNIQUE'
             ])
             ->assertOk()
             ->assertJson(['success' => true]);

        $this->assertDatabaseHas('stock_magasins', ['code' => 'MAG-01']);
    }
}
```

---

## 14. Checklist nouveau module

### Backend
- [ ] `module.json` — alias, priority, providers
- [ ] `StockServiceProvider` — config, views, migrations, blade components, bindings
- [ ] `EventServiceProvider` — `$listen` map
- [ ] `RouteServiceProvider` — prefix + name
- [ ] `config/config.php` — constantes, enums, labels
- [ ] `config/permissions.php` — toutes les permissions `{module}.{res}.{action}`
- [ ] Migrations — `{module}_{ressource}`, avec `created_by`, `updated_by`, `softDeletes`
- [ ] Modèles — `use HasAuditFields, SoftDeletes`, `$table`, `$fillable`, `$casts`, relations, méthodes métier
- [ ] Form Requests — `authorize()` vérifie la permission (pas `return true`)
- [ ] Controllers — `index()` vue seule + `getData()` JSON + `show()` JSON + CRUD
- [ ] Routes — `data` avant `resource`, nommage cohérent
- [ ] Seeder permissions + `DatabaseSeeder`

### Frontend
- [ ] Layout `master.blade.php` (copier depuis Core, adapter le nom du module)
- [ ] Vue `index.blade.php` — toolbar + table + `@push('js')`
- [ ] Vue `_modal.blade.php` — `@csrf` dans `<form>`, `name=` sur tous les inputs
- [ ] JS `index.js` — formatters en `window.*` hors DOMContentLoaded
- [ ] JS — toolbar buttons désactivés par défaut, activés à la sélection
- [ ] JS — Edit : GET `show()` avant `modal.show()`
- [ ] JS — Submit : gestion 422 avec `.is-invalid` inline
- [ ] JS — Delete : Swal confirm → DELETE → `table.refresh()`
- [ ] `data-url` pointe vers `.data` (jamais `.index`)
- [ ] Chargement BS Table + locale FR dans `@push('js')`

### Tests
- [ ] `RefreshDatabase` + `Gate::before(fn () => true)` dans `setUp()`
- [ ] Test vue index, getData, store, update, destroy (happy path)
