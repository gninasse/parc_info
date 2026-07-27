@extends('stock::layouts.master')

@section('title', 'Tableau de bord - Stocks')
@section('header', 'Tableau de bord des stocks')

@section('breadcrumb')
    <li class="breadcrumb-item active">Stocks</li>
@endsection

@section('content')

{{-- ── INDICATEURS ─────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-3">
    <div class="col-md-3 col-sm-6">
        <div class="card border-1 rounded-1 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 rounded-1 p-3">
                    <i class="fas fa-store text-primary fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small">Magasins actifs</div>
                    <div class="fs-4 fw-bold" id="indicateur-magasins">—</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-1 rounded-1 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-success bg-opacity-10 rounded-1 p-3">
                    <i class="fas fa-boxes text-success fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small">Articles en stock</div>
                    <div class="fs-4 fw-bold" id="indicateur-articles">—</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-1 rounded-1 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-warning bg-opacity-10 rounded-1 p-3">
                    <i class="fas fa-coins text-warning fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small">Valeur totale (FIFO)</div>
                    <div class="fs-5 fw-bold" id="indicateur-valeur">—</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-1 rounded-1 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-info bg-opacity-10 rounded-1 p-3">
                    <i class="fas fa-right-left text-info fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small">Mouvements du jour</div>
                    <div class="fs-4 fw-bold" id="indicateur-mouvements">—</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── DERNIERS MOUVEMENTS ─────────────────────────────────────────────── --}}
<div class="card border-1 rounded-1">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-clock-rotate-left me-2 text-primary"></i>Derniers mouvements</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Numéro</th>
                        <th>Type</th>
                        <th>Magasin</th>
                        <th class="text-end">Quantité</th>
                        <th class="text-end">Date</th>
                    </tr>
                </thead>
                <tbody id="derniers-mouvements">
                    <tr><td colspan="5" class="text-center text-muted py-4">Chargement…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('js')
<script src="{{ asset('js/modules/stock/dashboard.js') }}?v={{ time() }}"></script>
@endpush
