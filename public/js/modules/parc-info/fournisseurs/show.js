/**
 * Fiche Fournisseur - Module Parc Info
 * Pattern: Show & Edit mode "In-place" + Management of associated contacts & contracts
 */

document.addEventListener('DOMContentLoaded', function() {
    const $form = $('#form-edit-fournisseur');
    const $viewActions = $('#view-actions');
    const $formActions = $('#form-actions');
    const $btnEnableEdit = $('#btn-enable-edit');
    const $btnSaveEdit = $('#btn-save-edit');

    const $modalContact = new bootstrap.Modal('#modal-quickadd-contact');
    const $formContact = $('#form-quickadd-contact');

    const $modalContrat = new bootstrap.Modal('#modal-contrat');
    const $formContrat = $('#form-contrat');
    const $btnSaveContrat = $('#btn-save-contrat');

    const fournisseurId = window.location.pathname.split('/').filter(Boolean).pop();

    // ── GESTION DU MODE ÉDITION DU FOURNISSEUR ──
    $btnEnableEdit.on('click', function() {
        $form.find('input, select, textarea').prop('disabled', false);
        $viewActions.addClass('d-none');
        $formActions.removeClass('d-none');
    });

    $btnSaveEdit.on('click', function() {
        console.log('Tentative d\'enregistrement pour le fournisseur:', fournisseurId);
        $btnSaveEdit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');
        
        $.ajax({
            url: route('parc-info.fournisseurs.update', fournisseurId),
            method: 'POST',
            data: $form.serialize() + '&_method=PUT',
            success: function(res) {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'Mis à jour !', text: res.message, timer: 1500 }).then(() => window.location.reload());
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let msg = '';
                Object.values(errors).forEach(e => msg += e[0] + '<br>');
                Swal.fire('Erreur', msg || 'Erreur', 'error');
                $btnSaveEdit.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer');
            }
        });
    });

    // ── GESTION DES CONTACTS ──
    $('#btn-add-contact').on('click', function() {
        $formContact[0].reset();
        $modalContact.show();
    });

    $formContact.on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btn-save-quick-contact');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>...');

        $.ajax({
            url: route('parc-info.fournisseurs.store-contact', fournisseurId),
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    $modalContact.hide();
                    Swal.fire('Succès', res.message, 'success').then(() => window.location.reload());
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let msg = '';
                Object.values(errors).forEach(e => msg += e[0] + '<br>');
                Swal.fire('Erreur', msg || 'Erreur', 'error');
            },
            complete: () => $btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer')
        });
    });

    // ── GESTION DES CONTRATS ──
    $('#btn-add-contrat').on('click', function() {
        $formContrat[0].reset();
        $('#contrat-id').val('');
        $('#contrat-fournisseur-id').val(fournisseurId).trigger('change');
        $('#modalContratLabel span').text('Nouveau Contrat Maintenance');
        $modalContrat.show();
    });

    $(document).on('click', '.btn-edit-contrat', function() {
        const id = $(this).data('id');
        $.ajax({
            url: route('parc-info.contrats.show', id),
            method: 'GET',
            success: function(ct) {
                $('#contrat-id').val(ct.id);
                $formContrat.find('[name="reference"]').val(ct.reference);
                $formContrat.find('[name="nom"]').val(ct.nom);
                $formContrat.find('[name="fournisseur_id"]').val(ct.fournisseur_id).trigger('change');
                $formContrat.find('[name="date_debut"]').val(ct.date_debut ? ct.date_debut.split('T')[0] : '');
                $formContrat.find('[name="date_fin"]').val(ct.date_fin ? ct.date_fin.split('T')[0] : '');
                $formContrat.find('[name="cout"]').val(ct.cout);
                $formContrat.find('[name="notes"]').val(ct.notes);
                
                $('#modalContratLabel span').text('Modifier le Contrat');
                $modalContrat.show();
            }
        });
    });

    $formContrat.on('submit', function(e) {
        e.preventDefault();
        const id = $('#contrat-id').val();
        const url = id ? route('parc-info.contrats.update', id) : route('parc-info.contrats.store');
        const method = id ? 'PUT' : 'POST';
        
        $btnSaveContrat.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>...');

        $.ajax({
            url: url,
            method: id ? 'POST' : 'POST',
            data: $formContrat.serialize() + (id ? '&_method=PUT' : ''),
            success: function(res) {
                if (res.success) {
                    $modalContrat.hide();
                    Swal.fire('Succès', res.message, 'success').then(() => window.location.reload());
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let msg = '';
                Object.values(errors).forEach(e => msg += e[0] + '<br>');
                Swal.fire('Erreur', msg || 'Erreur', 'error');
            },
            complete: () => $btnSaveContrat.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer')
        });
    });

    $(document).on('click', '.btn-delete-contrat', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Supprimer ce contrat ?',
            text: "Cette action est irréversible et ne peut être faite que si aucune licence n'est rattachée.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, supprimer'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('parc-info.contrats.destroy', id),
                    method: 'DELETE',
                    success: function(res) {
                        Swal.fire('Supprimé !', res.message, 'success').then(() => window.location.reload());
                    },
                    error: function(xhr) {
                        Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur', 'error');
                    }
                });
            }
        });
    });

    $(document).on('click', '.btn-delete-contact', function() {
        const id = $(this).data('id');
        Swal.fire('Information', 'La suppression de contact sera implémentée prochainement.', 'info');
    });

    $('.select2-modal').select2({ theme: 'bootstrap-5' });
});
