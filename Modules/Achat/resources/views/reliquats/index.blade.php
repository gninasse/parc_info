@extends('achat::layouts.master')

@section('header', 'Reliquats')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard') }}">Achat</a></li>
    <li class="breadcrumb-item active" aria-current="page">Reliquats</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
<style>
    .pilule-age .btn { border-radius: 999px; }
    #reliquats-table td { vertical-align: middle; }
</style>
@endpush

@section('content')

{{-- ── Filtres (SPEC_UX A-06) ──────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-lg-5">
                <label class="form-label small text-uppercase text-muted" for="filter-recherche">Recherche</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" id="filter-recherche" class="form-control"
                           placeholder="N° de BC, article, fournisseur…">
                </div>
            </div>

            <div class="col-lg-3">
                <label class="form-label small text-uppercase text-muted" for="filter-fournisseur">Fournisseur</label>
                <select id="filter-fournisseur" class="form-select form-select-sm">
                    <option value="">Tous les fournisseurs</option>
                    @foreach($fournisseurs as $fournisseur)
                        <option value="{{ $fournisseur->id }}">{{ $fournisseur->raison_sociale }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-4">
                <label class="form-label small text-uppercase text-muted d-block">Ancienneté</label>
                {{-- Le dernier seuil vient des PARAMÈTRES (A-08) : il porte la
                     valeur de l'établissement, pas une constante. --}}
                <div class="btn-group btn-group-sm pilule-age" role="group" id="filter-age">
                    <input type="radio" class="btn-check" name="age" id="age-tous" value="" checked autocomplete="off">
                    <label class="btn btn-outline-secondary" for="age-tous">Tous</label>

                    <input type="radio" class="btn-check" name="age" id="age-30" value="30" autocomplete="off">
                    <label class="btn btn-outline-warning" for="age-30">&gt; 30 j</label>

                    <input type="radio" class="btn-check" name="age" id="age-60" value="60" autocomplete="off">
                    <label class="btn btn-outline-danger" for="age-60">&gt; 60 j</label>

                    @if(! in_array($seuilAlerte, [30, 60], true))
                        <input type="radio" class="btn-check" name="age" id="age-param" value="{{ $seuilAlerte }}" autocomplete="off">
                        <label class="btn btn-outline-danger" for="age-param">&gt; {{ $seuilAlerte }} j</label>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-hourglass-split me-2 text-primary"></i>Reste à livrer
        </h6>
    </div>

    <div class="card-body p-0">
        {{-- Toolbar (pattern du projet) : la sélection d'une ligne active
             « Voir le BC » et la clôture du reliquat. --}}
        <div id="toolbar">
            <button id="btn-voir-bc" class="btn btn-secondary btn-sm" disabled
                    data-bs-toggle="tooltip" title="Voir le bon de commande">
                <i class="fas fa-eye"></i>
            </button>
            @can('achat.bons_commande.cloturer')
                <button id="btn-cloturer" class="btn btn-dark btn-sm" disabled
                        data-bs-toggle="tooltip" title="Clôturer le reliquat">
                    <i class="bi bi-lock"></i>
                </button>
            @endcan
            <a id="btn-export" class="btn btn-outline-success btn-sm"
               href="{{ route('achat.reliquats.export') }}"
               data-bs-toggle="tooltip" title="Exporter le tableau filtré (CSV)">
                <i class="fas fa-file-csv"></i>
            </a>
        </div>

        <table id="reliquats-table"
               data-toggle="table"
               data-toolbar="#toolbar"
               data-url="{{ route('achat.reliquats.data') }}"
               data-side-pagination="server"
               data-pagination="true"
               data-page-size="25"
               data-page-list="[10, 25, 50, 100]"
               data-sort-name="age_jours"
               data-sort-order="desc"
               data-click-to-select="true"
               data-single-select="true"
               data-locale="fr-FR"
               data-classes="table table-hover align-middle"
               class="table">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="numero" data-sortable="true" data-formatter="reliquatNumeroFormatter">Bon</th>
                    <th data-field="fournisseur" data-sortable="true">Fournisseur</th>
                    <th data-field="article" data-sortable="true">Article</th>
                    <th data-field="quantite" data-align="right">Commandée</th>
                    <th data-field="quantite_livree" data-align="right">Livrée</th>
                    <th data-field="reste" data-sortable="true" data-align="right" data-formatter="reliquatResteFormatter">Reste</th>
                    <th data-field="age_jours" data-sortable="true" data-formatter="reliquatAgeFormatter">Âge</th>
                    <th data-field="derniere_reception" data-formatter="reliquatDateFormatter">Dernière réception</th>
                </tr>
            </thead>
        </table>
    </div>

    {{-- Pied : LE chiffre de gestion (total du filtre courant) --}}
    <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center">
        <span class="text-muted small" id="pied-compteur">—</span>
        <span class="fw-bold" id="pied-engage">Engagé non livré : —</span>
    </div>
</div>

{{-- EV-04 : l'état vide est une BONNE nouvelle, on le dit --}}
<div class="text-center text-muted py-5 d-none" id="etat-vide">
    <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
    Aucun reliquat — toutes les commandes validées sont soldées ✔
</div>

@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/achat/reliquats/index.js') }}?v={{ time() }}"></script>
@endpush
