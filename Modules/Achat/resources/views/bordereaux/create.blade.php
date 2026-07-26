@extends('achat::layouts.master')

@section('title', 'Nouvelle réception - Achat')
@section('header', 'Enregistrer une réception')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.bordereaux.index') }}">Bordereaux de livraison</a></li>
    <li class="breadcrumb-item active">Nouvelle réception</li>
@endsection

@section('content')
@php $bcPreselectionne = $bonsCommande->firstWhere('id', (int) $bonCommandePreselectionne); @endphp

<form id="bl-form" autocomplete="off" novalidate>
    @csrf

    {{-- ── EN-TÊTE ─────────────────────────────────────────────────────── --}}
    <div class="card border-1 rounded-1 mb-3">
        <div class="card-header bg-white border-0 py-3">
            <h6 class="mb-0 fw-bold"><i class="fas fa-shipping-fast text-primary me-2"></i>Informations de livraison</h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Bon de commande <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="bon_de_commande_id" id="bon_de_commande_id"
                               value="{{ $bcPreselectionne?->id }}">
                        <input type="text" id="bc-libelle" class="form-control bg-light" readonly
                               placeholder="Sélectionner un bon de commande…"
                               value="{{ $bcPreselectionne ? $bcPreselectionne->numero_commande.' — '.$bcPreselectionne->fournisseur?->nom : '' }}">
                        <button type="button" class="btn btn-outline-primary" id="btn-choisir-bc">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div class="form-text" style="font-size:.72rem">
                        Seuls les bons validés ou partiellement livrés sont proposés.
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold" for="date_livraison">
                        Date de livraison <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="date_livraison" id="date_livraison"
                           class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold" for="ref_bordereau_physique">
                        Réf. du bordereau fournisseur <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="ref_bordereau_physique" id="ref_bordereau_physique"
                           class="form-control form-control-sm text-uppercase" required
                           placeholder="Ex : BL-PHY-99882">
                    <div class="form-text" style="font-size:.72rem">
                        Unique : empêche la double saisie d'une même livraison.
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label small fw-semibold" for="commentaire">Observations de réception</label>
                    <textarea name="commentaire" id="commentaire" class="form-control form-control-sm" rows="2"
                              placeholder="Matériel abîmé, colis manquant, réserve émise…"></textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- ── LIGNES ──────────────────────────────────────────────────────── --}}
    <div class="card border-1 rounded-1 mb-3">
        <div class="card-header bg-white border-0 py-3">
            <h6 class="mb-0 fw-bold"><i class="fas fa-boxes text-primary me-2"></i>Articles reçus</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="table-lignes">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 34%;">Article</th>
                            <th style="width: 12%;" class="text-center">Commandé</th>
                            <th style="width: 12%;" class="text-center">Déjà livré</th>
                            <th style="width: 12%;" class="text-center">Reste à livrer</th>
                            <th style="width: 14%;" class="text-center">Quantité reçue <span class="text-danger">*</span></th>
                            <th style="width: 16%;" class="text-center">Refusé</th>
                        </tr>
                    </thead>
                    <tbody id="lignes-container">
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-arrow-up me-1"></i>
                                Sélectionnez un bon de commande pour charger les articles à réceptionner.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('achat.bordereaux.index') }}" class="btn btn-sm btn-outline-secondary rounded-1 px-4">Annuler</a>
        <button type="submit" class="btn btn-sm btn-primary rounded-1 px-4" id="btn-save" disabled>
            <i class="fas fa-save me-2"></i>Enregistrer la réception
        </button>
    </div>
</form>

@include('achat::bordereaux._modal_bc')

@endsection

@push('js')
<script>
    window.achatBordereau = {
        mode: 'creation',
        urlEnregistrement: @json(route('achat.bordereaux.store')),
        urlLignes: @json(route('achat.bons-commande.lignes-a-livrer', ['bon_commande' => '__ID__'])),
        lignes: [],
    };
</script>
<script src="{{ asset('js/modules/achat/bordereaux/create.js') }}?v={{ time() }}"></script>
@endpush
