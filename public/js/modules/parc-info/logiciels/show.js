/**
 * Fiche Logiciel - Module Parc Info
 * Pattern: Show & Edit mode "In-place" + Management of attached licenses
 */

document.addEventListener('DOMContentLoaded', function() {
    const $form = $('#form-edit-logiciel');
    const $viewActions = $('#view-actions');
    const $formActions = $('#form-actions');
    const $btnEnableEdit = $('#btn-enable-edit');
    const $btnSaveEdit = $('#btn-save-edit');
    const $btnToggleStatus = $('#btn-toggle-status');

    // Modales & Forms
    const $modalLicence = new bootstrap.Modal('#modal-licence');
    const $formLicence = $('#form-licence');
    const $modalEditeur = new bootstrap.Modal('#modal-quickadd-editeur');
    const $formEditeur = $('#form-quickadd-editeur');
    const $modalFournisseur = new bootstrap.Modal('#modal-quickadd-fournisseur');
    const $formFournisseur = $('#form-quickadd-fournisseur');
    const $modalContrat = new bootstrap.Modal('#modal-quickadd-contrat');
    const $formContrat = $('#form-quickadd-contrat');

    const logicielId = window.location.pathname.split('/').pop();

    // ── GESTION DU MODE ÉDITION DU LOGICIEL ──
    $btnEnableEdit.on('click', function() {
        $form.find('input, select, textarea').prop('disabled', false);
        $('#btn-quickadd-editeur').removeClass('d-none');
        $viewActions.addClass('d-none');
        $formActions.removeClass('d-none');
    });

    $btnSaveEdit.on('click', function() {
        $btnSaveEdit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');
        $.ajax({
            url: route('parc-info.logiciels.update', logicielId),
            method: 'PUT',
            data: $form.serialize(),
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

    $btnToggleStatus.on('click', function() {
        Swal.fire({ title: 'Changer le statut ?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Oui' }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({ url: route('parc-info.logiciels.toggle', logicielId), method: 'PATCH' }).then(res => {
                    Swal.fire('Succès', res.message, 'success').then(() => window.location.reload());
                });
            }
        });
    });

    // ── GESTION DES LICENCES ATTACHÉES ──
    $('#btn-add-licence').on('click', function() {
        $formLicence[0].reset();
        $('#licence-id').val('');
        // Pré-remplir le logiciel actuel et verrouiller le champ
        $formLicence.find('[name="logiciel_id"]').val(logicielId).trigger('change').prop('disabled', true);
        $('#modalLicenceLabel span').text('Ajouter une licence à ce logiciel');
        $modalLicence.show();
    });

    $formLicence.on('submit', function(e) {
        e.preventDefault();
        // Réactiver temporairement pour serialize si disabled
        $formLicence.find('[name="logiciel_id"]').prop('disabled', false);
        const data = $formLicence.serialize();
        $formLicence.find('[name="logiciel_id"]').prop('disabled', true);

        const $btn = $('#btn-save-licence');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>...');

        $.ajax({
            url: route('parc-info.licences.store'),
            method: 'POST',
            data: data,
            success: function(res) {
                if (res.success) {
                    $modalLicence.hide();
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

    $(document).on('click', '.btn-delete-licence', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Supprimer cette licence ?',
            text: "Attention, cette action est irréversible.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, supprimer'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('parc-info.licences.destroy', id),
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

    // ── QUICK ADDS (MODALES) ──
    
    // QuickAdd Éditeur
    $('#btn-quickadd-editeur').on('click', () => { $formEditeur[0].reset(); $modalEditeur.show(); });
    $formEditeur.on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btn-save-quick-editeur');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: route('parc-info.logiciels.store-editeur'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    $modalEditeur.hide();
                    $('#select-editeur').append(new Option(res.data.nom, res.data.id, true, true)).trigger('change');
                    Swal.fire('Succès', res.message, 'success');
                }
            },
            complete: () => $btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer')
        });
    });

    // QuickAdd Fournisseur
    $('#btn-quickadd-fournisseur').on('click', () => { $formFournisseur[0].reset(); $modalFournisseur.show(); });
    $formFournisseur.on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btn-save-quick-fournisseur');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: route('parc-info.licences.store-fournisseur'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    $modalFournisseur.hide();
                    $('#select-fournisseur').append(new Option(res.data.nom, res.data.id, true, true)).trigger('change');
                    Swal.fire('Succès', res.message, 'success');
                }
            },
            complete: () => $btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer')
        });
    });

    // QuickAdd Contrat
    $('#btn-quickadd-contrat').on('click', () => { $formContrat[0].reset(); $modalContrat.show(); });
    $formContrat.on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btn-save-quick-contrat');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: route('parc-info.licences.store-contrat'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    $modalContrat.hide();
                    $('#select-contrat').append(new Option(res.data.reference + ' - ' + res.data.nom, res.data.id, true, true)).trigger('change');
                    Swal.fire('Succès', res.message, 'success');
                }
            },
            complete: () => $btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer')
        });
    });

    $('.select2-modal').select2({ theme: 'bootstrap-5' });
});
