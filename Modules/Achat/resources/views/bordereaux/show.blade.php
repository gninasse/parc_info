@extends('achat::layouts.master')

@section('title', "Bordereau {$bl->numero_livraison} - Achat")
@section('header', "Bordereau de Livraison : {$bl->numero_livraison}")

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.bordereaux.index') }}">Bordereaux</a></li>
    <li class="breadcrumb-item active">{{ $bl->numero_livraison }}</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-12">
        <form id="form-edit-bl" autocomplete="off">
            @csrf
            <input type="hidden" id="bl-id" name="id" value="{{ $bl->id }}">
            <input type="hidden" id="input-bc-id" name="bon_de_commande_id" value="{{ $bl->bon_de_commande_id }}">
            
            {{-- ── BARRE D'ACTIONS DE L'ENTÊTE ── --}}
            <div class="card border-1 rounded-1 mb-3">
                <div class="card-body py-2 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small text-muted me-2">Statut actuel :</span>
                        @if($bl->statut === 'brouillon')
                            <span class="badge bg-secondary"><i class="fas fa-edit me-1"></i>Brouillon</span>
                        @elseif($bl->statut === 'wizard')
                            <span class="badge bg-warning text-white"><i class="fas fa-magic me-1"></i>Wizard en cours</span>
                        @elseif($bl->statut === 'valide')
                            <span class="badge bg-success"><i class="fas fa-check-double me-1"></i>Validé & Intégré</span>
                        @endif
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('achat.bordereaux.index') }}" class="btn btn-sm btn-outline-secondary rounded-1">
                            <i class="fas fa-arrow-left me-1"></i>Retour
                        </a>
                        
                        @if($bl->statut === 'brouillon')
                            @can('achat.bordereaux.edit')
                            <button type="button" id="btn-toggle-edit" class="btn btn-sm btn-outline-primary rounded-1">
                                <i class="fas fa-edit me-1"></i>Modifier
                            </button>
                            <a href="{{ route('achat.bordereaux.wizard', $bl->id) }}" class="btn btn-sm btn-success rounded-1 text-white">
                                <i class="fas fa-magic me-1"></i>Lancer l'intégration
                            </a>
                            @endcan
                        @elseif($bl->statut === 'wizard')
                            @can('achat.bordereaux.edit')
                            <a href="{{ route('achat.bordereaux.wizard', $bl->id) }}" class="btn btn-sm btn-warning rounded-1 text-white">
                                <i class="fas fa-magic me-1"></i>Continuer l'intégration
                            </a>
                            @endcan
                        @endif

                        {{-- Boutons cachés par défaut, affichés lors de l'édition --}}
                        <button type="submit" id="btn-save-edit" class="btn btn-sm btn-primary rounded-1 px-3 d-none">
                            <i class="fas fa-save me-1"></i>Enregistrer
                        </button>
                        <button type="button" id="btn-cancel-edit" class="btn btn-sm btn-outline-danger rounded-1 d-none">
                            Annuler
                        </button>
                    </div>
                </div>
            </div>

            {{-- ── CARD HEADER BL ── --}}
            <div class="card border-1 rounded-1 mb-3">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-shipping-fast text-primary me-2"></i>Informations de Livraison</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        {{-- Affichage lecture seule BC --}}
                        <div class="col-md-4" id="bc-display-readonly">
                            <label class="form-label small fw-bold">Bon de Commande Correspondant BC</label>
                            <input type="text" class="form-control form-control-sm bg-light text-dark fw-semibold" value="{{ $bl->bonCommande->numero_commande }} - {{ $bl->bonCommande->fournisseur->nom }}" readonly>
                        </div>
                        {{-- Affichage mode édition BC --}}
                        <div class="col-md-4 d-none" id="bc-display-edit">
                            <label class="form-label small fw-bold">Bon de Commande BC <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <input type="text" id="input-bc-display" class="form-control bg-light cursor-pointer" 
                                       placeholder="Sélectionner un BC..." readonly 
                                       data-bs-toggle="modal" data-bs-target="#modal-select-bc"
                                       value="{{ $bl->bonCommande->numero_commande }} - {{ $bl->bonCommande->fournisseur->nom }}">
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
                            <input type="date" name="date_livraison" class="form-control form-control-sm field-input" value="{{ $bl->date_livraison->toDateString() }}" required disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Réf. Bordereau Physique <span class="text-danger">*</span></label>
                            <input type="text" name="ref_bordereau_physique" class="form-control form-control-sm field-input text-uppercase" value="{{ $bl->ref_bordereau_physique }}" required disabled>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Observations / Remarques</label>
                            <textarea name="commentaire" class="form-control form-control-sm field-input" rows="2" placeholder="Aucune observation..." disabled>{{ $bl->commentaire }}</textarea>
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
                                    <th style="width: 45%;">Article</th>
                                    <th style="width: 20%;" class="text-center">Qté Commandée (BC)</th>
                                    <th style="width: 20%;" class="text-center">Qté Reçue sur ce BL</th>
                                    <th style="width: 15%;" class="text-center th-action d-none">Action</th>
                                </tr>
                            </thead>
                            <tbody id="lines-container">
                                {{-- Les lignes seront générées dynamiquement en JS --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
        
        {{-- ── SECTION INFORMATIONS AUDIT ── --}}
        <div class="card border-1 rounded-1 mt-3">
            <div class="card-body py-3 bg-light">
                <div class="row text-muted small">
                    <div class="col-md-6">
                        <i class="fas fa-user-edit me-1"></i><strong>Créé par :</strong> {{ $bl->createur ? $bl->createur->name : 'Système' }}<br>
                        <i class="fas fa-clock me-1"></i><strong>Le :</strong> {{ $bl->created_at->format('d/m/Y à H:i') }}
                    </div>
                    <div class="col-md-6 border-start">
                        <i class="fas fa-history me-1"></i><strong>Dernière modification :</strong> {{ $bl->updated_at->format('d/m/Y à H:i') }}
                    </div>
                </div>
            </div>
        </div>
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
<script>
    // Liste des lignes de livraison pour gestion JS
    window.existingLines = @json($existingLines);
</script>
<script src="{{ asset('js/modules/achat/bordereaux/show.js') }}?v={{ time() }}"></script>
@endpush
