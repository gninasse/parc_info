@extends('achat::layouts.master')

@section('title', 'Bons de commande - Achat')
@section('header', 'Bons de commande')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item active">Bons de commande</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')

{{-- ── FILTRES ─────────────────────────────────────────────────────────── --}}
<div class="card border-1 rounded-1 mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-fournisseur">Fournisseur</label>
                <select class="form-select form-select-sm" id="filter-fournisseur">
                    <option value="">Tous les fournisseurs</option>
                    @foreach($fournisseurs as $fournisseur)
                        <option value="{{ $fournisseur->id }}">{{ $fournisseur->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-statut">Statut</label>
                <select class="form-select form-select-sm" id="filter-statut">
                    <option value="">Tous les statuts</option>
                    @foreach($statuts as $code => $statut)
                        <option value="{{ $code }}">{{ $statut['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-date-debut">Commandé à partir du</label>
                <input type="date" class="form-control form-control-sm" id="filter-date-debut">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-date-fin">Jusqu'au</label>
                <input type="date" class="form-control form-control-sm" id="filter-date-fin">
            </div>
        </div>
    </div>
</div>

{{-- ── TABLE ───────────────────────────────────────────────────────────── --}}
<div class="card border-1 rounded-1">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Liste des bons de commande</h6>
    </div>
    <div class="card-body p-0">

        <div id="toolbar" class="d-flex gap-1">
            @can('achat.bons_commande.create')
            <a href="{{ route('achat.bons-commande.create') }}" class="btn btn-sm btn-primary rounded-1"
               data-bs-toggle="tooltip" title="Créer un bon de commande">
                <i class="fas fa-plus"></i>
            </a>
            @endcan
            <button id="btn-show" class="btn btn-sm btn-info text-white rounded-1" disabled
                    data-bs-toggle="tooltip" title="Ouvrir la fiche">
                <i class="fas fa-eye"></i>
            </button>
            <button id="btn-print" class="btn btn-sm btn-success text-white rounded-1" disabled
                    data-bs-toggle="tooltip" title="Imprimer le bon de commande">
                <i class="fas fa-print"></i>
            </button>
            @can('achat.bons_commande.annuler')
            <button id="btn-annuler" class="btn btn-sm btn-warning text-dark rounded-1" disabled
                    data-bs-toggle="tooltip" title="Annuler (avant toute livraison)">
                <i class="fas fa-ban"></i>
            </button>
            @endcan
            @can('achat.bons_commande.delete')
            <button id="btn-delete" class="btn btn-sm btn-danger rounded-1" disabled
                    data-bs-toggle="tooltip" title="Supprimer (brouillon uniquement)">
                <i class="fas fa-trash"></i>
            </button>
            @endcan
        </div>

        <table id="items-table"
               data-toggle="table"
               data-url="{{ route('achat.bons-commande.data') }}"
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
                    <th data-field="numero_commande" data-sortable="true" class="fw-semibold">N° commande</th>
                    <th data-field="fournisseur" data-sortable="true">Fournisseur</th>
                    <th data-field="date_commande" data-sortable="true" data-formatter="dateFormatter">Date</th>
                    <th data-field="montant_ht" data-sortable="true" data-formatter="prixFormatter" class="text-end">Montant HT</th>
                    <th data-field="montant_ttc" data-sortable="true" data-formatter="prixFormatter" class="text-end">Montant TTC</th>
                    <th data-field="statut" data-sortable="true" data-formatter="statutBcFormatter" class="text-center">Statut</th>
                    <th data-field="created_at" data-sortable="true" data-formatter="dateHeureFormatter">Créé le</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

{{-- Aperçu PDF (M-06) --}}
@include('achat::shared._modal_pdf', [
    'id' => 'modal-pdf',
    'titre' => 'Impression du bon de commande',
])

@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('js/modules/achat/bons-commande/index.js') }}?v={{ time() }}"></script>
@endpush
