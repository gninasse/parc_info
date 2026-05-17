@extends('parcinfo::layouts.master')

@section('header', 'Fiche Consommable')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.consommables.index') }}">Consommables</a></li>
    <li class="breadcrumb-item active">{{ $consommable->code }}</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
<style>
    .select2-container--bootstrap-5 .select2-selection { border-radius: 0.375rem; }
</style>
@endpush

@section('content')
<div class="row g-4">
    {{-- ── En-tête / Profil ── --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-body p-0">
                <div class="d-flex flex-column flex-md-row">
                    <div class="bg-primary bg-opacity-10 p-4 d-flex align-items-center justify-content-center" style="min-width: 200px;">
                        <i class="fas fa-box-open fa-5x text-primary"></i>
                    </div>
                    <div class="p-4 flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h4 class="fw-bold mb-1">{{ $consommable->nom }}</h4>
                                <span class="badge bg-light text-primary border border-primary border-opacity-25 px-3 py-2">
                                    <i class="fas fa-barcode me-2"></i>{{ $consommable->code }}
                                </span>
                                <span class="badge bg-{{ $consommable->est_actif ? 'success' : 'danger' }} px-3 py-2 ms-2">
                                    {{ $consommable->est_actif ? 'ACTIF' : 'INACTIF' }}
                                </span>
                            </div>
                            <div id="view-actions">
                                <button class="btn btn-primary px-4" id="btn-enable-edit">
                                    <i class="fas fa-edit me-2"></i>Modifier
                                </button>
                                <button class="btn btn-outline-success ms-2" id="btn-open-appro">
                                    <i class="fas fa-plus-circle me-2"></i>Approvisionner
                                </button>
                                <button class="btn btn-outline-danger ms-2" id="btn-open-consommer">
                                    <i class="fas fa-minus-circle me-2"></i>Sortie Stock
                                </button>
                            </div>
                            <div id="form-actions" class="d-none">
                                <button type="button" class="btn btn-success px-4" id="btn-save-edit">
                                    <i class="fas fa-save me-2"></i>Enregistrer
                                </button>
                                <button type="button" class="btn btn-secondary ms-2" onclick="window.location.reload()">
                                    Annuler
                                </button>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3 text-center border-end">
                                <div class="small text-muted text-uppercase fw-semibold">Stock Actuel</div>
                                <div class="fw-bold fs-4 {{ $consommable->quantite_stock_actuel <= $consommable->quantite_stock_min ? 'text-danger' : 'text-dark' }}">
                                    {{ $consommable->quantite_stock_actuel }} <small>{{ $consommable->typeConsommable->unite_stock }}s</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted text-uppercase fw-semibold">Type</div>
                                <div class="fw-bold">{{ $consommable->typeConsommable->nom }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted text-uppercase fw-semibold">Marque</div>
                                <div class="fw-bold">{{ $consommable->marque?->libelle ?: 'Générique' }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted text-uppercase fw-semibold">Valeur Stock</div>
                                <div class="fw-bold text-success fs-5">{{ number_format($consommable->valeur_stock, 2, ',', ' ') }} €</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Détails & Historique ── --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3 text-center">
                <ul class="nav nav-tabs card-header-tabs" id="consommableTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" id="infos-tab" data-bs-toggle="tab" data-bs-target="#tab-infos" type="button">
                            <i class="fas fa-info-circle me-2"></i>Informations générales
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="mouvements-tab" data-bs-toggle="tab" data-bs-target="#tab-mouvements" type="button">
                            <i class="fas fa-history me-2"></i>Historique Mouvements
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="consommableTabsContent">
                    {{-- Tab Infos --}}
                    <div class="tab-pane fade show active" id="tab-infos" role="tabpanel">
                        <form id="form-edit-consommable">
                            @csrf
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Code Article</label>
                                    <input type="text" name="code" class="form-control" value="{{ $consommable->code }}" disabled required>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold">Désignation</label>
                                    <input type="text" name="nom" class="form-control" value="{{ $consommable->nom }}" disabled required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Type de consommable</label>
                                    <select name="type_consommable_id" class="form-select" disabled required>
                                        @foreach($types as $t)
                                            <option value="{{ $t->id }}" {{ $consommable->type_consommable_id == $t->id ? 'selected' : '' }}>{{ $t->nom }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Marque</label>
                                    <select name="marque_id" class="form-select" disabled>
                                        <option value="">Générique</option>
                                        @foreach($marques as $m)
                                            <option value="{{ $m->id }}" {{ $consommable->marque_id == $m->id ? 'selected' : '' }}>{{ $m->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Stock Minimum</label>
                                    <input type="number" name="quantite_stock_min" class="form-control" value="{{ $consommable->quantite_stock_min }}" disabled required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Stock Maximum</label>
                                    <input type="number" name="quantite_stock_max" class="form-control" value="{{ $consommable->quantite_stock_max }}" disabled required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Coût Unitaire (EUR)</label>
                                    <input type="number" name="cout_unitaire" class="form-control" value="{{ $consommable->cout_unitaire }}" step="0.01" disabled required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold">Fournisseur Principal</label>
                                    <select name="fournisseur_principal_id" class="form-select" disabled required>
                                        @foreach($fournisseurs as $f)
                                            <option value="{{ $f->id }}" {{ $consommable->fournisseur_principal_id == $f->id ? 'selected' : '' }}>{{ $f->nom }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Notes & Compatibilité</label>
                                    <textarea name="notes" class="form-control" rows="3" disabled>{{ $consommable->notes }}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Tab Mouvements --}}
                    <div class="tab-pane fade" id="tab-mouvements" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Qté</th>
                                        <th>Utilisateur</th>
                                        <th>Cible / Raison</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($consommable->mouvementsStock->sortByDesc('date_mouvement') as $mvt)
                                    <tr>
                                        <td class="small">{{ $mvt->date_mouvement->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $mvt->type_mouvement === 'Achat' ? 'success' : 'primary' }}">
                                                {{ $mvt->type_mouvement }}
                                            </span>
                                        </td>
                                        <td class="fw-bold">{{ $mvt->type_mouvement === 'Achat' ? '+' : '-' }}{{ $mvt->quantite }}</td>
                                        <td class="small">{{ $mvt->utilisateur->name }}</td>
                                        <td class="small">
                                            @if($mvt->equipement_id)
                                                <i class="fas fa-desktop me-1"></i> Équipement #{{ $mvt->equipement_id }}
                                            @endif
                                            <div class="text-muted">{{ $mvt->raison ?: $mvt->reference_commande }}</div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="text-center py-4 text-muted">Aucun mouvement.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Sidebar Info ── --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold">Dernier Approvisionnement</h6>
            </div>
            <div class="card-body text-center">
                <div class="fs-4 fw-bold text-primary mb-1">
                    {{ $consommable->date_dernier_approvisionnement?->format('d/m/Y') ?: 'Jamais' }}
                </div>
                <div class="small text-muted">Date constatée en stock</div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold">Fournisseur</h6>
            </div>
            <div class="card-body">
                <div class="fw-bold text-dark fs-5">{{ $consommable->fournisseur->nom }}</div>
                <div class="text-muted small">Code: {{ $consommable->fournisseur->code }}</div>
                <hr class="my-3">
                <div class="mb-2 small"><i class="fas fa-envelope me-2 text-muted"></i>{{ $consommable->fournisseur->email ?: 'N/A' }}</div>
                <div class="small"><i class="fas fa-phone me-2 text-muted"></i>{{ $consommable->fournisseur->telephone ?: 'N/A' }}</div>
            </div>
        </div>
    </div>
</div>
@include('parcinfo::informatique.consommables._modal_mouvements')
@include('parcinfo::shared._modal_selection_equipement')
@endsection

@push('js')
<script type="module" src="{{ asset('js/modules/parc-info/consommables/show.js') }}?v={{ time() }}"></script>
@endpush
