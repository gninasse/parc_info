@extends('achat::layouts.master')

@section('title', 'Nouveau Bon de Commande - Achat')
@section('header', 'Nouveau Bon de Commande')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.bons-commande.index') }}">Bons de Commande</a></li>
    <li class="breadcrumb-item active">Nouveau</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-12">
        <form id="form-create-bc" autocomplete="off" novalidate>
            @csrf
            
            {{-- ── CARD HEADER COMMANDE ── --}}
            <div class="card border-1 rounded-1 mb-3 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-file-contract text-primary me-2"></i>Entête du Bon de Commande</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fournisseur <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <input type="hidden" name="fournisseur_id" id="fournisseur_id" required>
                                <input type="text" class="form-control" id="fournisseur_name_display" placeholder="Sélectionner un fournisseur..." readonly required>
                                <button class="btn btn-outline-primary" type="button" id="btn-choose-fournisseur">
                                    <i class="fas fa-search me-1"></i>Choisir
                                </button>
                                <button class="btn btn-outline-danger d-none" type="button" id="btn-clear-fournisseur">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div id="fournisseur_code_display" class="small text-muted mt-1 d-none">
                                Code Fournisseur : <span class="fw-semibold font-monospace" id="fournisseur_code_text"></span>
                            </div>
                            <div class="invalid-feedback d-block mt-1 small" id="err-fournisseur"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date de commande <span class="text-danger">*</span></label>
                            <input type="date" name="date_commande" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                            <div class="invalid-feedback d-block mt-1 small" id="err-date_commande"></div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Observations / Notes</label>
                            <textarea name="commentaire" class="form-control form-control-sm" rows="2" placeholder="Ajouter des notes, spécifications pour la livraison..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── CARD LIGNES DE COMMANDE ── --}}
            <div class="card border-1 rounded-1 mb-3 shadow-sm">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-list-ol text-primary me-2"></i>Lignes de Commande</h6>
                    <button type="button" id="btn-add-line" class="btn btn-sm btn-outline-primary rounded-1">
                        <i class="fas fa-plus me-1"></i>Ajouter une ligne
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="table-lignes-commande">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50%;">Article <span class="text-danger">*</span></th>
                                    <th style="width: 15%;" class="text-center">Quantité <span class="text-danger">*</span></th>
                                    <th style="width: 15%;" class="text-end">Prix Unitaire (FCFA) <span class="text-danger">*</span></th>
                                    <th style="width: 15%;" class="text-end">Montant Ligne</th>
                                    <th style="width: 5%;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="lines-container">
                                {{-- Les lignes seront ajoutées dynamiquement en JS --}}
                            </tbody>
                            <tfoot class="table-light border-top">
                                <tr>
                                    <td colspan="3" class="text-end fw-semibold">Total HT :</td>
                                    <td class="text-end fw-semibold text-dark text-nowrap" id="total-ht">0 FCFA</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end fw-semibold">TVA (18%) :</td>
                                    <td class="text-end fw-semibold text-dark text-nowrap" id="total-tva">0 FCFA</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Montant Total TTC :</td>
                                    <td class="text-end fw-bold text-primary fs-5 text-nowrap" id="total-general">0 FCFA</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ── BOUTONS ACTIONS ── --}}
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('achat.bons-commande.index') }}" class="btn btn-sm btn-outline-secondary rounded-1 px-4">Annuler</a>
                <button type="submit" class="btn btn-sm btn-primary rounded-1 px-4" id="btn-save">
                    <i class="fas fa-save me-2"></i>Enregistrer le Bon de Commande
                </button>
            </div>
        </form>
    </div>
</div>

@include('achat::bons_commande.partials.modals')

@endsection

@push('js')
<script>
    window.articlesCatalogue = @json($articlesCatalogue);
    window.existingLines = [];
</script>
<script src="{{ asset('js/modules/achat/bons-commande/create.js') }}?v={{ time() }}"></script>
@endpush
