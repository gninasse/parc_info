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

    // Selecteurs structure administrative
    const $niveauRattachement = $('#niveau_rattachement');
    const $dirSelect = $('#direction_id');
    const $srvSelect = $('#service_id');
    const $untSelect = $('#unite_id');
    const $dirContainer = $('#dir-select-container');
    const $srvContainer = $('#srv-select-container');
    const $untContainer = $('#unt-select-container');

    // Visualisation Preview
    const $previewDir = $('#preview-direction');
    const $previewSrv = $('#preview-service');
    const $previewUnt = $('#preview-unite');

    // Contacts
    const $contactsContainer = $('#contacts-container');
    const $addContactBtn = $('#add-contact-btn');
    const contactRowTemplate = $('#contact-row-template').html();
    let contactIndex = $contactsContainer.find('.contact-row').length;

    // Switch to edit mode
    $btnEdit.on('click', function() {
        $inputs.prop('disabled', false);
        $formActions.removeClass('d-none');
        $btnEdit.addClass('d-none');
        $btnToggle.addClass('d-none');
        $addContactBtn.removeClass('d-none');
        $('.contact-actions').removeClass('d-none');
        $('#no-contacts-alert').addClass('d-none');

        $inputs.removeClass('bg-light').addClass('bg-white shadow-sm border');
        $('#matricule').prop('disabled', true).addClass('bg-light'); // Matricule reste non modifiable
    });

    // Cancel edit mode
    $btnCancel.on('click', function() {
        window.location.reload();
    });

    // Gestion de la hiérarchie administrative
    $niveauRattachement.on('change', function() {
        const niveau = $(this).val();

        $dirContainer.removeClass('d-none');
        if (niveau === 'direction') {
            $srvContainer.addClass('d-none');
            $untContainer.addClass('d-none');
            $srvSelect.val('');
            $untSelect.val('');
            $previewSrv.addClass('d-none');
            $previewUnt.addClass('d-none');
        } else if (niveau === 'service') {
            $srvContainer.removeClass('d-none');
            $untContainer.addClass('d-none');
            $untSelect.val('');
            $previewUnt.addClass('d-none');
        } else if (niveau === 'unite') {
            $srvContainer.removeClass('d-none');
            $untContainer.removeClass('d-none');
        }
    });

    $dirSelect.on('change', function() {
        const dirId = $(this).val();
        const dirText = $(this).find('option:selected').text();

        $previewDir.text(dirId ? dirText : 'Direction Générale');

        if (dirId && ($niveauRattachement.val() === 'service' || $niveauRattachement.val() === 'unite')) {
            loadServices(dirId);
        } else {
            $srvSelect.empty().append('<option value="">Sélectionner le Service...</option>');
            $untSelect.empty().append('<option value="">Sélectionner l\'Unité...</option>');
            $previewSrv.addClass('d-none');
            $previewUnt.addClass('d-none');
        }
    });

    $srvSelect.on('change', function() {
        const srvId = $(this).val();
        const srvText = $(this).find('option:selected').text();

        if (srvId) {
            $previewSrv.text(srvText).removeClass('d-none');
            if ($niveauRattachement.val() === 'unite') {
                loadUnites(srvId);
            } else {
                $previewUnt.addClass('d-none');
            }
        } else {
            $previewSrv.addClass('d-none');
            $previewUnt.addClass('d-none');
            $untSelect.empty().append('<option value="">Sélectionner l\'Unité...</option>');
        }
    });

    $untSelect.on('change', function() {
        const untId = $(this).val();
        const untText = $(this).find('option:selected').text();

        if (untId) {
            $previewUnt.text(untText).removeClass('d-none');
        } else {
            $previewUnt.addClass('d-none');
        }
    });

    function loadServices(dirId, selectedSrvId = null) {
        $.get(window.grhRoutes.services(dirId), function(services) {
            $srvSelect.empty().append('<option value="">Sélectionner le Service...</option>');
            services.forEach(srv => {
                const selected = (selectedSrvId == srv.id) ? 'selected' : '';
                $srvSelect.append(`<option value="${srv.id}" ${selected}>${srv.libelle}</option>`);
            });
        });
    }

    function loadUnites(srvId, selectedUntId = null) {
        $.get(window.grhRoutes.unites(srvId), function(unites) {
            $untSelect.empty().append('<option value="">Sélectionner l\'Unité...</option>');
            unites.forEach(unt => {
                const selected = (selectedUntId == unt.id) ? 'selected' : '';
                $untSelect.append(`<option value="${unt.id}" ${selected}>${unt.libelle}</option>`);
            });
        });
    }

    // Gestion des contacts
    $addContactBtn.on('click', function() {
        addContactRow();
    });

    $('.remove-contact-btn').on('click', function() {
        $(this).closest('.contact-row').remove();
    });

    function addContactRow() {
        let rowHtml = contactRowTemplate.replace(/INDEX/g, contactIndex);
        let $row = $(rowHtml);

        $row.find('.remove-contact-btn').on('click', function() {
            $row.remove();
        });

        $contactsContainer.append($row);
        contactIndex++;
    }

    // Save profile
    $form.on('submit', function(e) {
        e.preventDefault();

        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

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
                const errors = xhr.responseJSON?.errors;
                let errorHtml = '';

                if (errors) {
                    errorHtml = '<ul class="text-start mt-2 small">';
                    Object.values(errors).forEach(err => {
                        errorHtml += `<li>${err}</li>`;
                    });
                    errorHtml += '</ul>';
                }

                Swal.fire({
                    title: 'Erreur',
                    html: message + errorHtml,
                    icon: 'error'
                });
            },
            complete: function() {
                $btnSave.prop('disabled', false).html('<i class="fas fa-save me-1"></i> ENREGISTRER');
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
