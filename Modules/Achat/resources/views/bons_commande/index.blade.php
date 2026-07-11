@extends('achat::layouts.master')

@section('title', 'Bons de Commande - Achat')
@section('header', 'Bons de Commande')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item active">Bons de Commande</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
<style>
    .card-filter {
        border: 1px solid var(--bs-border-color);
        background-color: var(--bs-body-bg);
    }
</style>
@endpush

@section('content')

{{-- ── CARD DE FILTRES RECHERCHE ── --}}
<div class="card card-filter mb-3 rounded-1">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1" for="filter-fournisseur">Fournisseur</label>
                <select class="form-select form-select-sm" id="filter-fournisseur">
                    <option value="">Tous les fournisseurs</option>
                    @foreach($fournisseurs as $f)
                        <option value="{{ $f->id }}">{{ $f->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1" for="filter-statut">Statut</label>
                <select class="form-select form-select-sm" id="filter-statut">
                    <option value="">Tous les statuts</option>
                    <option value="brouillon">Brouillon</option>
                    <option value="valide">Validé</option>
                    <option value="partiel">Livré Partiel</option>
                    <option value="livre">Livré Complet</option>
                    <option value="annule">Annulé</option>
                </select>
            </div>
        </div>
    </div>
</div>

{{-- ── COMPONENT: TABLE CARD ── --}}
<div class="card border-1 rounded-1">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Liste des Bons de Commande</h6>
    </div>
    <div class="card-body p-0">
        {{-- Toolbar avec boutons d'actions icon-only selon DESIGN.md ── --}}
        <div id="toolbar" class="d-flex gap-1">
            @can('achat.bons_commande.create')
            <a href="{{ route('achat.bons-commande.create') }}" class="btn btn-sm btn-primary rounded-1" data-bs-toggle="tooltip" title="Créer un nouveau bon de commande">
                <i class="fas fa-plus"></i>
            </a>
            @endcan
            <button id="btn-show" class="btn btn-sm btn-info text-white rounded-1" disabled data-bs-toggle="tooltip" title="Voir les détails / Modifier">
                <i class="fas fa-eye"></i>
            </button>
            <button id="btn-print" class="btn btn-sm btn-success text-white rounded-1" disabled data-bs-toggle="tooltip" title="Imprimer le bon de commande">
                <i class="fas fa-print"></i>
            </button>
            @can('achat.bons_commande.edit')
            <button id="btn-annuler" class="btn btn-sm btn-warning text-white rounded-1" disabled data-bs-toggle="tooltip" title="Annuler le bon de commande">
                <i class="fas fa-ban"></i>
            </button>
            @endcan
            @can('achat.bons_commande.delete')
            <button id="btn-delete" class="btn btn-sm btn-danger rounded-1" disabled data-bs-toggle="tooltip" title="Supprimer (Brouillon uniquement)">
                <i class="fas fa-trash"></i>
            </button>
            @endcan
        </div>

        <table id="bons-commande-table"
               data-toggle="table"
               data-url="{{ route('achat.bons-commande.index') }}"
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
               data-page-size="25"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="numero_commande" data-sortable="true" class="fw-semibold">N° Commande</th>
                    <th data-field="fournisseur" data-sortable="true">Fournisseur</th>
                    <th data-field="date_commande" data-sortable="true" data-formatter="dateFormatter">Date Commande</th>
                    <th data-field="montant_total" data-sortable="true" data-formatter="priceFormatter" class="text-end">Montant Total</th>
                    <th data-field="statut" data-sortable="true" data-formatter="bcStatusFormatter" class="text-center">Statut</th>
                    <th data-field="created_at" data-sortable="true" data-formatter="dateTimeFormatter">Créé le</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

{{-- Modal d'impression uniforme --}}
<div class="modal fade shadow" id="printBcModal" tabindex="-1" aria-labelledby="printBcModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary border-0 text-primary py-3">
                <h5 class="modal-title fw-bold" id="printBcModalLabel">
                    <i class="fas fa-file-pdf me-2 text-danger"></i>Impression du Bon de Commande
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="print-bc-iframe" class="w-100" style="height: 70vh; border: none; border-radius: 4px;" src=""></iframe>
            </div>
            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-sm btn-secondary rounded-1 px-3" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script>
    // Formatters globaux pour cette vue
    window.dateFormatter = function (value) {
        if (!value) return '-';
        const parts = value.split('-');
        if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
        return value;
    };

    window.dateTimeFormatter = function (value) {
        if (!value) return '-';
        return new Date(value).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    window.priceFormatter = function (value) {
        if (value === null || value === undefined) return '-';
        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(value);
    };

    window.bcStatusFormatter = function (value) {
        const badges = {
            'brouillon': '<span class="badge bg-secondary"><i class="fas fa-edit me-1"></i>Brouillon</span>',
            'valide': '<span class="badge bg-primary"><i class="fas fa-check-circle me-1"></i>Validé</span>',
            'partiel': '<span class="badge bg-info text-white"><i class="fas fa-truck-loading me-1"></i>Partiel</span>',
            'livre': '<span class="badge bg-success"><i class="fas fa-truck me-1"></i>Livré</span>',
            'annule': '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Annulé</span>'
        };
        return badges[value] || value;
    };
</script>
<script src="{{ asset('js/modules/achat/bons-commande/index.js') }}?v={{ time() }}"></script>
@endpush
