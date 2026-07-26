@extends('achat::layouts.master')

@section('title', 'Bordereaux de livraison - Achat')
@section('header', 'Bordereaux de livraison')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item active">Bordereaux de livraison</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')

{{-- ── FILTRES ─────────────────────────────────────────────────────────── --}}
<div class="card border-1 rounded-1 mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1" for="filter-bc">Bon de commande</label>
                <select class="form-select form-select-sm" id="filter-bc">
                    <option value="">Tous les bons de commande</option>
                    @foreach($bonsCommande as $bonCommande)
                        <option value="{{ $bonCommande->id }}">{{ $bonCommande->numero_commande }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1" for="filter-statut">Statut</label>
                <select class="form-select form-select-sm" id="filter-statut">
                    <option value="">Tous les statuts</option>
                    @foreach($statuts as $code => $statut)
                        <option value="{{ $code }}">{{ $statut['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

{{-- ── TABLE ───────────────────────────────────────────────────────────── --}}
<div class="card border-1 rounded-1">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-shipping-fast me-2 text-primary"></i>Liste des bordereaux de livraison</h6>
    </div>
    <div class="card-body p-0">

        <div id="toolbar" class="d-flex gap-1">
            @can('achat.bordereaux.create')
            <a href="{{ route('achat.bordereaux.create') }}" class="btn btn-sm btn-primary rounded-1"
               data-bs-toggle="tooltip" title="Enregistrer une réception">
                <i class="fas fa-plus"></i>
            </a>
            @endcan
            <button id="btn-show" class="btn btn-sm btn-info text-white rounded-1" disabled
                    data-bs-toggle="tooltip" title="Ouvrir la fiche">
                <i class="fas fa-eye"></i>
            </button>
            <button id="btn-print" class="btn btn-sm btn-secondary rounded-1" disabled
                    data-bs-toggle="tooltip" title="Imprimer le bordereau">
                <i class="fas fa-print"></i>
            </button>
            @can('achat.bordereaux.valider')
            <button id="btn-wizard" class="btn btn-sm btn-success text-white rounded-1" disabled
                    data-bs-toggle="tooltip" title="Assistant d'intégration au parc">
                <i class="fas fa-magic"></i>
            </button>
            @endcan
            @can('achat.bordereaux.delete')
            <button id="btn-delete" class="btn btn-sm btn-danger rounded-1" disabled
                    data-bs-toggle="tooltip" title="Supprimer (brouillon uniquement)">
                <i class="fas fa-trash"></i>
            </button>
            @endcan
        </div>

        <table id="items-table"
               data-toggle="table"
               data-url="{{ route('achat.bordereaux.data') }}"
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
               data-page-size="25"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="numero_livraison" data-sortable="true" class="fw-semibold">N° livraison</th>
                    <th data-field="numero_commande" data-sortable="true">Bon de commande</th>
                    <th data-field="ref_bordereau_physique" data-sortable="true">Réf. bordereau physique</th>
                    <th data-field="date_livraison" data-sortable="true" data-formatter="dateFormatter">Date de livraison</th>
                    <th data-field="statut" data-sortable="true" data-formatter="statutBlFormatter" class="text-center">Statut</th>
                    <th data-field="created_at" data-sortable="true" data-formatter="dateHeureFormatter">Créé le</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('achat::shared._modal_pdf', [
    'id' => 'modal-pdf',
    'titre' => 'Impression du bordereau de livraison',
])

@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('js/modules/achat/bordereaux/index.js') }}?v={{ time() }}"></script>
@endpush
