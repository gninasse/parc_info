/**
 * GRH - Dossiers Employés - Show/Edit JS
 */

$(function() {
    const $form = $('#employe-form');
    const $btnEdit = $('#btn-edit-mode');
    const $btnCancel = $('#btn-cancel');
    const $btnSave = $('#btn-save-profile');
    const $btnToggle = $('#btn-toggle-status');
    const $formActions = $('#form-actions');
    const $inputs = $form.find('input, select, textarea');
    const $niveauRattachement = $('#niveau_rattachement');
    const $rattachementLabel = $('#rattachement-label');
    const $rattachementContainer = $('#rattachement-container');

    // Switch to edit mode
    $btnEdit.on('click', function() {
        $inputs.prop('disabled', false);
        $formActions.removeClass('d-none');
        $btnEdit.addClass('d-none');
        $btnToggle.addClass('d-none');
        $('#btn-add-contact-row').removeClass('d-none');

        // Ensure some fields are always editable or styled correctly
        $inputs.removeClass('bg-light').addClass('border-primary shadow-sm');
        $('#matricule').prop('disabled', true); // Matricule non modifiable?
    });

    // Cancel edit mode
    $btnCancel.on('click', function() {
        window.location.reload();
    });

    // Handle rattachement selection
    $niveauRattachement.on('change', function() {
        const val = $(this).val();
        $rattachementContainer.removeClass('d-none');
        $('#direction_id, #service_id, #unite_id').addClass('d-none').prop('required', false);

        if (val === 'direction') {
            $rattachementLabel.text('Sélectionner la Direction *');
            $('#direction_id').removeClass('d-none').prop('required', true);
        } else if (val === 'service') {
            $rattachementLabel.text('Sélectionner le Service *');
            $('#service_id').removeClass('d-none').prop('required', true);
        } else if (val === 'unite') {
            $rattachementLabel.text('Sélectionner l\'Unité *');
            $('#unite_id').removeClass('d-none').prop('required', true);
        } else {
            $rattachementContainer.addClass('d-none');
        }
    });

    // Save profile
    $form.on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.ajax({
            url: window.grhRoutes.update,
            method: 'PUT',
            data: formData,
            beforeSend: function() {
                $btnSave.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');
            },
            success: function(response) {
                Swal.fire({
                    title: 'Succès',
                    text: response.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Une erreur est survenue lors de la mise à jour';
                Swal.fire('Erreur', message, 'error');
            },
            complete: function() {
                $btnSave.prop('disabled', false).html('<i class="fas fa-save me-1"></i> ENREGISTRER LES MODIFICATIONS');
            }
        });
    });

    // Toggle status
    $btnToggle.on('click', function() {
        const action = $(this).data('action');
        const confirmColor = action === 'activer' ? '#28a745' : '#dc3545';

        Swal.fire({
            title: `Confirmer la ${action}?`,
            text: `Voulez-vous vraiment ${action} ce dossier employé ?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: confirmColor,
            confirmButtonText: `Oui, ${action}`,
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: window.grhRoutes.toggle,
                    method: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function(response) {
                        Swal.fire('Succès', response.message, 'success').then(() => {
                            window.location.reload();
                        });
                    },
                    error: function() {
                        Swal.fire('Erreur', 'Impossible de changer le statut', 'error');
                    }
                });
            }
        });
    });
});
