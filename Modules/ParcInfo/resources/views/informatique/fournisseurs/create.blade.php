@extends('parcinfo::layouts.master')

@section('header', 'Nouveau Fournisseur')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.fournisseurs.index') }}">Fournisseurs</a></li>
    <li class="breadcrumb-item active">Nouveau</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm col-md-8 mx-auto" style="border-radius:12px">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-truck text-primary me-2"></i>Informations du Fournisseur</h6>
    </div>
    <div class="card-body p-4">
        <form id="form-create-fournisseur">
            @csrf
            <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">
                <i class="fas fa-info-circle me-2"></i>Identification
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Code Fournisseur <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control field-input" placeholder="ex: FOUR-MS" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label small fw-bold">Nom de l'entreprise <span class="text-danger">*</span></label>
                    <input type="text" name="nom" class="form-control field-input" placeholder="ex: Microsoft France" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Type d'entité</label>
                    <select name="type" class="form-select field-input">
                        <option value="Revendeur">Revendeur</option>
                        <option value="Editeur">Éditeur</option>
                        <option value="Distributeur">Distributeur</option>
                        <option value="Prestataire">Prestataire</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Score de fiabilité (%)</label>
                    <input type="number" name="fiabilite_score" class="form-control field-input" value="100" min="0" max="100">
                </div>
            </div>

            <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">
                <i class="fas fa-address-book me-2"></i>Coordonnées
            </h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Email principal</label>
                    <input type="email" name="email" class="form-control field-input" placeholder="contact@fournisseur.com">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Téléphone</label>
                    <input type="text" name="telephone" class="form-control field-input" placeholder="01 02 03 04 05">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Adresse</label>
                    <textarea name="adresse" class="form-control field-input" rows="2" placeholder="Rue, avenue..."></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Code Postal</label>
                    <input type="text" name="code_postal" class="form-control field-input" placeholder="75001">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Ville</label>
                    <input type="text" name="ville" class="form-control field-input" placeholder="Paris">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Pays</label>
                    <input type="text" name="pays" class="form-control field-input" value="France">
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('parc-info.fournisseurs.index') }}" class="btn btn-light px-4">Annuler</a>
                <button type="submit" class="btn btn-primary px-4" id="btn-save">
                    <i class="fas fa-save me-2"></i>Enregistrer le fournisseur
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('css')
<style>
.field-input   { font-size:.875rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; }
.field-input:not(:disabled):focus { background:#fff; border-color:#0d6efd; box-shadow:0 0 0 3px rgba(13,110,253,.1); }
</style>
@endpush

@push('js')
<script type="module">
    document.getElementById('form-create-fournisseur').addEventListener('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btn-save');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');

        $.ajax({
            url: "{{ route('parc-info.fournisseurs.store') }}",
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message,
                        timer: 1500
                    }).then(() => {
                        window.location.href = response.redirect;
                    });
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let errorMsg = '';
                Object.values(errors).forEach(err => errorMsg += err[0] + '<br>');
                Swal.fire('Erreur de validation', errorMsg || 'Une erreur est survenue', 'error');
                $btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer le fournisseur');
            }
        });
    });
</script>
@endpush
