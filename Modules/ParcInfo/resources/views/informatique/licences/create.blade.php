@extends('parcinfo::layouts.master')

@section('header', 'Nouvelle Licence')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.licences.index') }}">Licences</a></li>
    <li class="breadcrumb-item active">Nouvelle</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm col-md-8 mx-auto">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold">Informations de la Licence</h6>
    </div>
    <div class="card-body">
        <form id="form-create-licence">
            @csrf
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Logiciel</label>
                    <select name="logiciel_id" class="form-select" required>
                        <option value="">Sélectionnez un logiciel...</option>
                        @foreach($logiciels as $logiciel)
                            <option value="{{ $logiciel->id }}">{{ $logiciel->nom }} ({{ $logiciel->editeur->nom }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Clé de licence</label>
                    <input type="text" name="cle_licence" class="form-control" placeholder="XXXX-XXXX-XXXX-XXXX">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Numéro de contrat</label>
                    <input type="text" name="numero_contrat" class="form-control" placeholder="CONTRAT-2026-001">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Type d'activation</label>
                    <select name="type_activation" class="form-select" required>
                        <option value="volume">Volume (CAL)</option>
                        <option value="concurrent">Concurrent</option>
                        <option value="subscription">Abonnement (SaaS)</option>
                        <option value="free">Gratuite / Open Source</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Modèle de licencing</label>
                    <select name="modele_licencing" class="form-select" required>
                        <option value="device">Par équipement (Device)</option>
                        <option value="user">Par utilisateur (User)</option>
                        <option value="concurrent">Accès simultanés (Concurrent)</option>
                        <option value="named">Utilisateur nommé (Named)</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Postes accordés</label>
                    <input type="number" name="nombre_postes_accordes" class="form-control" value="1" min="0" required>
                    <div class="form-text small">Mettre 0 pour illimité.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Coût Unitaire</label>
                    <div class="input-group">
                        <input type="number" name="cout_unitaire" class="form-control" step="0.01" placeholder="0.00">
                        <span class="input-group-text">€</span>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Coût Total</label>
                    <div class="input-group">
                        <input type="number" name="cout_total" class="form-control" step="0.01" placeholder="0.00">
                        <span class="input-group-text">€</span>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Date d'acquisition</label>
                    <input type="date" name="date_acquisition" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Date d'activation</label>
                    <input type="date" name="date_activation" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Date d'expiration</label>
                    <input type="date" name="date_expiration" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Fournisseur</label>
                    <div class="input-group">
                        <select name="fournisseur_id" id="select-fournisseur" class="form-select" required>
                            <option value="">Sélectionnez un fournisseur...</option>
                            @foreach($fournisseurs as $fournisseur)
                                <option value="{{ $fournisseur->id }}">{{ $fournisseur->nom }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-primary" id="btn-quickadd-fournisseur" title="Ajout rapide">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Statut Initial</label>
                    <select name="statut" class="form-select" required>
                        <option value="actif">Actif</option>
                        <option value="expire">Expiré</option>
                        <option value="en_renouvellement">En renouvellement</option>
                        <option value="suspendu">Suspendu</option>
                    </select>
                </div>

                <input type="hidden" name="devise" value="EUR">
                <input type="hidden" name="actif" value="1">

                <div class="col-md-12">
                    <label class="form-label fw-semibold">Notes / Conditions particulières</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="..."></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('parc-info.licences.index') }}" class="btn btn-light">Annuler</a>
                <button type="submit" class="btn btn-primary px-4">Enregistrer la licence</button>
            </div>
        </form>
    </div>
@include('parcinfo::shared._modal_fournisseur')
@endsection

@push('js')
<script type="module">
    const modalFournisseur = new bootstrap.Modal('#modal-quickadd-fournisseur');
    const $formFournisseur = $('#form-quickadd-fournisseur');

    // QuickAdd Fournisseur
    $('#btn-quickadd-fournisseur').on('click', () => {
        $formFournisseur[0].reset();
        modalFournisseur.show();
    });

    $formFournisseur.on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btn-save-quick-fournisseur');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>...');

        $.ajax({
            url: route('parc-info.licences.store-fournisseur'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    modalFournisseur.hide();
                    const newF = res.data;
                    const newOption = new Option(newF.nom, newF.id, true, true);
                    $('#select-fournisseur').append(newOption).trigger('change');
                    Swal.fire('Ajouté !', res.message, 'success');
                }
            },
            error: function(xhr) {
                Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur', 'error');
            },
            complete: () => $btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer')
        });
    });

    document.getElementById('form-create-licence').addEventListener('submit', function(e) {
...
        e.preventDefault();
        const $form = $(this);
        const data = $form.serialize();
        
        $.ajax({
            url: route('parc-info.licences.store'),
            method: 'POST',
            data: data,
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
            }
        });
    });
</script>
@endpush
