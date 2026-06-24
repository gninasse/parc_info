@extends('achat::layouts.master')

@section('title', 'Nouveau Bordereau de Livraison - Achat')
@section('header', 'Nouveau Bordereau de Livraison')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.bordereaux.index') }}">Bordereaux</a></li>
    <li class="breadcrumb-item active">Nouveau</li>
@endsection

@section('content')
@php
    $selectedBc = null;
    if ($selectedBcId) {
        $selectedBc = $bonsCommande->firstWhere('id', $selectedBcId);
    }
@endphp
<div class="row g-3">
    <div class="col-lg-12">
        <form id="form-create-bl" autocomplete="off">
            @csrf
            
            {{-- ── CARD HEADER BL ── --}}
            <div class="card border-1 rounded-1 mb-3">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-shipping-fast text-primary me-2"></i>Informations de Livraison</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Bon de Commande BC <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <input type="hidden" name="bon_de_commande_id" id="input-bc-id" value="{{ $selectedBcId }}" required>
                                <input type="text" id="input-bc-display" class="form-control bg-light cursor-pointer" 
                                       placeholder="Sélectionner un BC..." readonly 
                                       data-bs-toggle="modal" data-bs-target="#modal-select-bc"
                                       value="{{ $selectedBc ? ($selectedBc->numero_commande . ' - ' . $selectedBc->fournisseur->nom) : '' }}">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-select-bc">
                                    <i class="fas fa-search"></i>
                                </button>
                                <button type="button" id="btn-clear-bc" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Date de livraison <span class="text-danger">*</span></label>
                            <input type="date" name="date_livraison" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Réf. Bordereau Physique <span class="text-danger">*</span></label>
                            <input type="text" name="ref_bordereau_physique" class="form-control form-control-sm text-uppercase" placeholder="Ex: BL-PHY-99882" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Observations / Remarques</label>
                            <textarea name="commentaire" class="form-control form-control-sm" rows="2" placeholder="Saisir d'éventuelles observations sur la livraison (produits abîmés, manquants...)"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── CARD LIGNES DE BL ── --}}
            <div class="card border-1 rounded-1 mb-3">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-boxes text-primary me-2"></i>Articles Reçus</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="table-lignes-bl">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40%;">Article</th>
                                    <th style="width: 13%;" class="text-center">Qté Commandée</th>
                                    <th style="width: 13%;" class="text-center">Déjà Livrée</th>
                                    <th style="width: 13%;" class="text-center">Reste à Livrer</th>
                                    <th style="width: 13%;" class="text-center">Qté Reçue <span class="text-danger">*</span></th>
                                    <th style="width: 8%;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="lines-container">
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-arrow-up me-1"></i> Veuillez sélectionner un bon de commande ci-dessus pour charger ses lignes.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ── BOUTONS ACTIONS ── --}}
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('achat.bordereaux.index') }}" class="btn btn-sm btn-outline-secondary rounded-1 px-4">Annuler</a>
                <button type="submit" class="btn btn-sm btn-primary rounded-1 px-4" id="btn-save" disabled>
                    <i class="fas fa-save me-2"></i>Enregistrer le Bordereau
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sélection Bon de Commande -->
<div class="modal fade shadow" id="modal-select-bc" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-1">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h5 class="modal-title fs-6 fw-bold"><i class="fas fa-file-invoice me-2 text-success"></i>Sélectionner un Bon de Commande</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="search-bc" class="form-control bg-light border-start-0" placeholder="Rechercher par numéro, fournisseur...">
                </div>
                
                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-hover table-sm align-middle mb-0 border-top" id="table-modal-bc">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 25%;">N° Commande</th>
                                <th style="width: 45%;">Fournisseur</th>
                                <th style="width: 15%;">Date</th>
                                <th style="width: 15%; text-align: right;">Montant Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bonsCommande as $bc)
                                <tr class="bc-row cursor-pointer" 
                                    data-id="{{ $bc->id }}" 
                                    data-numero="{{ $bc->numero_commande }}" 
                                    data-fournisseur="{{ $bc->fournisseur->nom }}"
                                    data-search="{{ strtolower($bc->numero_commande . ' ' . $bc->fournisseur->nom) }}">
                                    <td><span class="badge bg-secondary font-monospace">{{ $bc->numero_commande }}</span></td>
                                    <td class="fw-bold text-dark">{{ $bc->fournisseur->nom }}</td>
                                    <td class="text-muted small">{{ $bc->date_commande->format('d/m/Y') }}</td>
                                    <td class="text-end font-monospace text-muted">{{ number_format($bc->montant_total, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="fas fa-info-circle me-1"></i> Aucun bon de commande en cours trouvé.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-xs btn-outline-secondary rounded-1" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('js/modules/achat/bordereaux/create.js') }}?v={{ time() }}"></script>
@endpush
