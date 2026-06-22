@extends('parcinfo::layouts.master')

@section('header')
    Configuration de la catégorie : {{ $category->libelle }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item">Référentiels</li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.referentiels.categories.index') }}">Catégories</a></li>
    <li class="breadcrumb-item active">{{ $category->libelle }}</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')
<div class="row g-4">
    <!-- Category Card Info -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-4">
                <div class="badge bg-light text-dark p-3 border mb-3">
                    <i class="bi {{ $category->icone }} fs-1"></i>
                </div>
                <h5 class="fw-bold mb-1">{{ $category->libelle }}</h5>
                <p class="text-muted small mb-3">Code technique : <code>{{ $category->code }}</code></p>
                <div class="d-flex justify-content-center gap-2">
                    <button class="btn btn-outline-primary btn-sm" id="btn-edit-category">
                        <i class="fas fa-edit me-1"></i> Modifier
                    </button>
                    <a href="{{ route('parc-info.referentiels.categories.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Retour
                    </a>
                </div>
            </div>
        </div>

        <!-- Help/Info Card -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-info-circle text-info me-2"></i>Aide à la configuration</h6>
            </div>
            <div class="card-body pt-0 small">
                <p>Les champs configurés définissent dynamiquement la structure des données de cette catégorie :</p>
                <ul>
                    <li><strong>Nom Panel</strong> regroupe les champs dans des onglets/sections distincts dans la modale d'ajout/modification.</li>
                    <li><strong>Type champ</strong> définit le composant graphique rendu (champs texte, sélecteur dropdown, date, etc.).</li>
                    <li>Pour le type <strong>Sélecteur (select)</strong>, vous devez définir la source d'options :
                        <ul>
                            <li>Soit un dictionnaire de valeurs (préfixe <code>DICT:code_du_dictionnaire</code>).</li>
                            <li>Soit un tableau JSON direct (ex: <code>["Valeur 1", "Valeur 2"]</code>).</li>
                        </ul>
                    </li>
                    <li>Les <strong>Règles de validation</strong> suivent les règles standards de Laravel (ex: <code>required|integer|min:1</code>).</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Fields CRUD Table -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold">Champs Dynamiques Configurés</h6>
            </div>
            <div class="card-body p-0">
                <div id="toolbar-fields">
                    <button id="btn-add-field" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Ajouter un champ
                    </button>
                    <button id="btn-edit-field" class="btn btn-info" disabled>
                        <i class="fas fa-edit"></i>
                    </button>
                    <button id="btn-delete-field" class="btn btn-danger" disabled>
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <table id="fields-table"
                       data-toggle="table"
                       data-url="{{ route('parc-info.referentiels.categories.fields.data', $category->id) }}"
                       data-pagination="true"
                       data-side-pagination="server"
                       data-search="true"
                       data-show-refresh="true"
                       data-toolbar="#toolbar-fields"
                       data-click-to-select="true"
                       data-single-select="true"
                       data-id-field="id"
                       class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th data-field="state" data-radio="true"></th>
                            <th data-field="ordre_affichage" data-sortable="true">Ordre</th>
                            <th data-field="libelle" data-sortable="true">Libellé</th>
                            <th data-field="code">Code</th>
                            <th data-field="type_champ" data-formatter="typeFieldFormatter">Type</th>
                            <th data-field="nom_panel" data-sortable="true">Onglet / Groupe</th>
                            <th data-field="afficher_dans_liste" data-formatter="booleanFormatter">Tableau</th>
                            <th data-field="afficher_dans_modal" data-formatter="booleanFormatter">Formulaire</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
@include('parcinfo::referentiels.categories._modal')

<!-- Fields Modal -->
@include('parcinfo::referentiels.categories._modal_field')
@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    window.typeFieldFormatter = function (value) {
        const types = {
            'text': '<span class="badge bg-light text-dark border">Texte</span>',
            'number': '<span class="badge bg-light text-info border">Nombre</span>',
            'select': '<span class="badge bg-light text-primary border">Sélecteur</span>',
            'boolean': '<span class="badge bg-light text-warning border">Oui / Non</span>',
            'date': '<span class="badge bg-light text-success border">Date</span>',
        };
        return types[value] || value;
    };

    window.booleanFormatter = function (value) {
        return value 
            ? '<span class="text-success"><i class="fas fa-check-circle fs-5"></i></span>'
            : '<span class="text-muted"><i class="fas fa-minus-circle fs-5"></i></span>';
    };
    
    window.dateFormatter = function (value) {
        if (!value) return '-';
        return new Date(value).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    };
</script>
<script type="module" src="{{ asset('js/modules/parc-info/referentiels/categories-show.js') }}?v={{ time() }}"></script>
@endpush
