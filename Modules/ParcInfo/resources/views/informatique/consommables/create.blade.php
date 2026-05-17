@extends('parcinfo::layouts.master')

@section('header', 'Ajouter au Catalogue')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.consommables.index') }}">Consommables</a></li>
    <li class="breadcrumb-item active">Nouvel article</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm col-md-8 mx-auto">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold">Informations du Consommable</h6>
    </div>
    <div class="card-body">
        <form id="form-create-consommable">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Code Article</label>
                    <input type="text" name="code" class="form-control" placeholder="TONER-HP-001" required>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Désignation</label>
                    <input type="text" name="nom" class="form-control" placeholder="Toner HP LaserJet Pro CF279A" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Type de consommable</label>
                    <select name="type_consommable_id" class="form-select" required>
                        <option value="">Sélectionnez un type...</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}">{{ $type->nom }} ({{ $type->categorie }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Marque</label>
                    <select name="marque_id" class="form-select">
                        <option value="">Sélectionnez une marque...</option>
                        @foreach($marques as $marque)
                            <option value="{{ $marque->id }}">{{ $marque->libelle }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Fournisseur Principal</label>
                    <select name="fournisseur_principal_id" class="form-select" required>
                        <option value="">Sélectionnez un fournisseur...</option>
                        @foreach($fournisseurs as $f)
                            <option value="{{ $f->id }}">{{ $f->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Coût Unitaire Estimé</label>
                    <div class="input-group">
                        <input type="number" name="cout_unitaire" class="form-control" step="0.01" placeholder="0.00" required>
                        <span class="input-group-text">€</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Seuil d'alerte (Min)</label>
                    <input type="number" name="quantite_stock_min" class="form-control" value="5" min="0" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Stock Maximum conseillé</label>
                    <input type="number" name="quantite_stock_max" class="form-control" value="20" min="1" required>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-semibold">Notes / Compatibilité</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="..."></textarea>
                </div>
                
                <input type="hidden" name="est_actif" value="1">
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('parc-info.consommables.index') }}" class="btn btn-light">Annuler</a>
                <button type="submit" class="btn btn-primary px-4">Enregistrer au catalogue</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script type="module">
    document.getElementById('form-create-consommable').addEventListener('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        
        $.ajax({
            url: route('parc-info.consommables.store'),
            method: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    Swal.fire('Succès', response.message, 'success').then(() => {
                        window.location.href = response.redirect;
                    });
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let errorMsg = '';
                Object.values(errors).forEach(err => errorMsg += err[0] + '<br>');
                Swal.fire('Erreur', errorMsg || 'Une erreur est survenue', 'error');
            }
        });
    });
</script>
@endpush
