@extends('stock::layouts.master')

@section('title', 'Rapports - Stocks')
@section('header', 'Rapports de stock')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock.dashboard.index') }}">Stocks</a></li>
    <li class="breadcrumb-item active">Rapports</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')

{{-- ── CHOIX DU RAPPORT + FILTRES ──────────────────────────────────────── --}}
<div class="card border-1 rounded-1 mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="rapport-type">Rapport</label>
                <select class="form-select form-select-sm" id="rapport-type">
                    <option value="entrees">Entrées de stock</option>
                    <option value="sorties">Sorties de stock</option>
                    <option value="transferts">Transferts inter-magasins</option>
                    <option value="stock">Stock par magasin</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-magasin">Magasin</label>
                <select class="form-select form-select-sm" id="filter-magasin">
                    <option value="">Tous les magasins</option>
                    @foreach($magasins as $magasin)
                        <option value="{{ $magasin->id }}">{{ $magasin->code }} — {{ $magasin->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2" id="groupe-statut" hidden>
                <label class="form-label small fw-semibold mb-1" for="filter-statut">Statut</label>
                <select class="form-select form-select-sm" id="filter-statut">
                    <option value="">Tous</option>
                    @foreach($statutsTransfert as $code => $statut)
                        <option value="{{ $code }}">{{ $statut['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2" id="groupe-alerte" hidden>
                <label class="form-label small fw-semibold mb-1" for="filter-alerte">Alerte</label>
                <select class="form-select form-select-sm" id="filter-alerte">
                    <option value="">Tous</option>
                    @foreach($statutsAlerte as $code => $statut)
                        <option value="{{ $code }}">{{ $statut['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2" id="groupe-date-debut">
                <label class="form-label small fw-semibold mb-1" for="filter-date-debut">Du</label>
                <input type="date" class="form-control form-control-sm" id="filter-date-debut">
            </div>
            <div class="col-md-2" id="groupe-date-fin">
                <label class="form-label small fw-semibold mb-1" for="filter-date-fin">Au</label>
                <input type="date" class="form-control form-control-sm" id="filter-date-fin">
            </div>
        </div>
    </div>
</div>

{{-- ── RÉSULTAT ────────────────────────────────────────────────────────── --}}
<div class="card border-1 rounded-1">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2 text-primary"></i><span id="rapport-titre">Entrées de stock</span></h6>
        <a href="#" id="btn-pdf" target="_blank" class="btn btn-sm btn-secondary rounded-1"
           data-bs-toggle="tooltip" title="Exporter en PDF">
            <i class="fas fa-file-pdf"></i>
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light" id="rapport-entetes"></thead>
                <tbody id="rapport-lignes">
                    <tr><td class="text-center text-muted py-4">Chargement…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('js')
<script src="{{ asset('js/modules/stock/rapports/index.js') }}?v={{ time() }}"></script>
@endpush
