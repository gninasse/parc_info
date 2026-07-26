@extends('achat::layouts.master')

@section('title', 'États et statistiques - Achat')
@section('header', 'États et statistiques des achats')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item active">États et statistiques</li>
@endsection

@section('content')

{{-- ── INDICATEURS ─────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <x-achat-carte-indicateur
            libelle="Bons de commande"
            :valeur="$indicateurs['total_bc']"
            detail="Tous statuts confondus"
            icone="fa-file-invoice-dollar"
            couleur="primary" />
    </div>
    <div class="col-md-3">
        <x-achat-carte-indicateur
            libelle="Montant engagé TTC"
            :valeur="number_format($indicateurs['montant_engage'], 0, ',', ' ') . ' F'"
            detail="Commandes validées et livrées"
            icone="fa-coins"
            couleur="success" />
    </div>
    <div class="col-md-3">
        <x-achat-carte-indicateur
            libelle="Complétion des livraisons"
            :valeur="$indicateurs['taux_completion'] . ' %'"
            detail="Lignes entièrement livrées"
            icone="fa-truck"
            couleur="info" />
    </div>
    <div class="col-md-3">
        <x-achat-carte-indicateur
            libelle="Fournisseurs actifs"
            :valeur="$indicateurs['fournisseurs_actifs']"
            detail="Référencés dans ParcInfo"
            icone="fa-store"
            couleur="warning" />
    </div>
</div>

{{-- ── GRAPHIQUES ──────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-1 rounded-1 h-100">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold small">
                    <i class="fas fa-chart-line me-2 text-primary"></i>Dépense mensuelle
                </h6>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 250px;"><canvas id="chart-mensuel"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-1 rounded-1 h-100">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold small">
                    <i class="fas fa-chart-pie me-2 text-success"></i>Répartition par fournisseur
                </h6>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 250px;"><canvas id="chart-fournisseurs"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-1 rounded-1 h-100">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold small">
                    <i class="fas fa-boxes me-2 text-warning"></i>Catalogue par nature
                </h6>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 250px;"><canvas id="chart-types"></canvas></div>
            </div>
        </div>
    </div>
</div>

{{-- ── PARAMÈTRES DU RAPPORT ───────────────────────────────────────────── --}}
<div class="card border-1 rounded-1 mb-4">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold">
            <i class="fas fa-filter me-2 text-primary"></i>Sélection et paramètres du rapport
        </h6>
    </div>
    <div class="card-body">
        <form id="form-rapport">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold" for="report_type">Type de rapport</label>
                    <select name="report_type" id="report_type" class="form-select form-select-sm" required>
                        @foreach($rapports as $rapport)
                            <option value="{{ $rapport['cle'] }}" data-filtres="{{ implode(',', $rapport['filtres']) }}">
                                {{ $rapport['titre'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 groupe-filtre" data-filtre="fournisseur">
                    <label class="form-label small fw-semibold" for="fournisseur_id">Fournisseur</label>
                    <select name="fournisseur_id" id="fournisseur_id" class="form-select form-select-sm">
                        <option value="">Tous les fournisseurs</option>
                        @foreach($fournisseurs as $fournisseur)
                            <option value="{{ $fournisseur->id }}">{{ $fournisseur->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 groupe-filtre" data-filtre="statut">
                    <label class="form-label small fw-semibold" for="statut">Statut</label>
                    <select name="statut" id="statut" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach($statuts as $code => $statut)
                            <option value="{{ $code }}">{{ $statut['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 groupe-filtre" data-filtre="periode">
                    <label class="form-label small fw-semibold">Période</label>
                    <div class="input-group input-group-sm">
                        <input type="date" name="date_debut" id="date_debut" class="form-control" aria-label="Date de début">
                        <span class="input-group-text">au</span>
                        <input type="date" name="date_fin" id="date_fin" class="form-control" aria-label="Date de fin">
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" id="btn-rechercher" class="btn btn-sm btn-primary px-4 rounded-1">
                    <i class="fas fa-search me-1"></i> Rechercher
                </button>
                @can('achat.rapports.export')
                <button type="button" id="btn-exporter" class="btn btn-sm btn-outline-success px-4 rounded-1">
                    <i class="fas fa-file-pdf me-1"></i> Éditer le PDF
                </button>
                @endcan
            </div>
        </form>
    </div>
</div>

{{-- ── RÉSULTATS ───────────────────────────────────────────────────────── --}}
<div class="card border-1 rounded-1 mb-4 d-none" id="carte-resultats">
    <div class="card-header bg-light border-0 py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0" id="titre-rapport">Résultats</h6>
        <span class="badge bg-secondary" id="compteur-lignes">0 ligne</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small" id="table-rapport">
                <thead class="table-light"><tr id="entete-rapport"></tr></thead>
                <tbody id="corps-rapport"></tbody>
            </table>
        </div>
    </div>
</div>

@include('achat::shared._modal_pdf', ['id' => 'modal-pdf', 'titre' => 'Édition du rapport'])

@endsection

@push('js')
<script src="{{ asset('plugins/chartjs/chart.min.js') }}"></script>
<script>
    window.achatStatistiques = {
        depensesMensuelles: @json($depensesMensuelles),
        depensesParFournisseur: @json($depensesParFournisseur),
        articlesParType: @json($articlesParType),
        urls: {
            donnees: @json(route('achat.statistiques.data')),
            pdf: @json(route('achat.statistiques.pdf')),
        },
    };
</script>
<script src="{{ asset('js/modules/achat/statistiques.js') }}?v={{ time() }}"></script>
@endpush
