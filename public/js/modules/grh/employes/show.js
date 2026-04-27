/**
 * GRH - Dossiers Employés - Show/Edit JS
 * Pattern aligné sur Core/users/show.js
 */

$(function () {
    const $form = $('#employe-form');
    const $btnEdit = $('#btn-edit-mode');
    const $btnCancel = $('#btn-cancel');
    const $btnSave = $('#btn-save-profile');
    const $btnToggle = $('#btn-toggle-status');
    const $viewActions = $('#view-actions');
    const $formActions = $('#form-actions');

    // Sélecteurs structure administrative
    const $niveauRattachement = $('#niveau_rattachement');
    const $dirSelect = $('#direction_id');
    const $srvSelect = $('#service_id');
    const $untSelect = $('#unite_id');
    const $dirContainer = $('#dir-select-container');
    const $srvContainer = $('#srv-select-container');
    const $untContainer = $('#unt-select-container');

    // Visualisation
    const $previewDir = $('#preview-direction');
    const $previewSrv = $('#preview-service');
    const $previewUnt = $('#preview-unite');

    // Contacts
    const $contactsContainer = $('#contacts-container');
    const $addContactBtn = $('#add-contact-btn');
    const contactRowTemplate = $('#contact-row-template').html();
    let contactIndex = $contactsContainer.find('.contact-row').length;

    // -------------------------------------------------------
    // Edit mode
    // -------------------------------------------------------
    $btnEdit.on('click', function () {
        enableEditMode();
    });

    $btnCancel.on('click', function () {
        disableEditMode();
        window.location.reload();
    });

    function enableEditMode() {
        $form.find('input, select, textarea').prop('disabled', false);
        $viewActions.addClass('d-none');
        $formActions.removeClass('d-none');
        $addContactBtn.removeClass('d-none');
        $('.contact-actions').removeClass('d-none');
        $('#no-contacts-alert').addClass('d-none');
    }

    function disableEditMode() {
        $form.find('input, select, textarea').prop('disabled', true);
        $viewActions.removeClass('d-none');
        $formActions.addClass('d-none');
        $addContactBtn.addClass('d-none');
        $('.contact-actions').addClass('d-none');
    }

    // -------------------------------------------------------
    // Hiérarchie administrative
    // -------------------------------------------------------
    $niveauRattachement.on('change', function () {
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

    $dirSelect.on('change', function () {
        const dirId = $(this).val();
        const dirText = $(this).find('option:selected').text();
        $previewDir.text(dirId ? dirText : 'Direction Générale');

        if (dirId && ($niveauRattachement.val() === 'service' || $niveauRattachement.val() === 'unite')) {
            loadServices(dirId);
        } else {
            $srvSelect.empty().append('<option value="">Sélectionner le service...</option>');
            $untSelect.empty().append('<option value="">Sélectionner l\'unité...</option>');
            $previewSrv.addClass('d-none');
            $previewUnt.addClass('d-none');
        }
    });

    $srvSelect.on('change', function () {
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
            $untSelect.empty().append('<option value="">Sélectionner l\'unité...</option>');
        }
    });

    $untSelect.on('change', function () {
        const untId = $(this).val();
        const untText = $(this).find('option:selected').text();
        if (untId) {
            $previewUnt.text(untText).removeClass('d-none');
        } else {
            $previewUnt.addClass('d-none');
        }
    });

    function loadServices(dirId, selectedSrvId = null) {
        $.get(window.grhRoutes.services(dirId), function (services) {
            $srvSelect.empty().append('<option value="">Sélectionner le service...</option>');
            services.forEach(srv => {
                const selected = (selectedSrvId == srv.id) ? 'selected' : '';
                $srvSelect.append(`<option value="${srv.id}" ${selected}>${srv.libelle}</option>`);
            });
        });
    }

    function loadUnites(srvId, selectedUntId = null) {
        $.get(window.grhRoutes.unites(srvId), function (unites) {
            $untSelect.empty().append('<option value="">Sélectionner l\'unité...</option>');
            unites.forEach(unt => {
                const selected = (selectedUntId == unt.id) ? 'selected' : '';
                $untSelect.append(`<option value="${unt.id}" ${selected}>${unt.libelle}</option>`);
            });
        });
    }

    // -------------------------------------------------------
    // Contacts
    // -------------------------------------------------------
    $addContactBtn.on('click', function () {
        addContactRow();
    });

    // Contacts existants : bouton suppression
    $contactsContainer.on('click', '.remove-contact-btn', function () {
        $(this).closest('.contact-row').remove();
    });

    function addContactRow(data = null) {
        let rowHtml = contactRowTemplate.replace(/INDEX/g, contactIndex);
        let $row = $(rowHtml);

        if (data) {
            $row.find('select[name*="type_contact"]').val(data.type_contact);
            $row.find('input[name*="valeur"]').val(data.valeur);
        }

        $row.find('.remove-contact-btn').on('click', function () {
            $row.remove();
        });

        $contactsContainer.append($row);
        contactIndex++;
    }

    // -------------------------------------------------------
    // Sauvegarde du profil
    // -------------------------------------------------------
    $form.on('submit', function (e) {
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
            beforeSend: function () {
                $btnSave.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enregistrement...');
            },
            success: function (response) {
                Swal.fire({
                    title: 'Succès',
                    text: response.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                }).then(() => {
                    window.location.reload();
                });
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Une erreur est survenue';
                const errors = xhr.responseJSON?.errors;
                let errorHtml = '';

                if (errors) {
                    errorHtml = '<ul class="text-start mt-2 small">';
                    Object.values(errors).forEach(err => {
                        errorHtml += `<li>${err}</li>`;
                    });
                    errorHtml += '</ul>';
                }

                Swal.fire({ title: 'Erreur', html: message + errorHtml, icon: 'error' });
            },
            complete: function () {
                $btnSave.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Enregistrer');
            },
        });
    });

    // -------------------------------------------------------
    // Toggle statut
    // -------------------------------------------------------
    $btnToggle.on('click', function () {
        const action = $(this).data('action');
        const confirmColor = action === 'activer' ? '#28a745' : '#dc3545';

        Swal.fire({
            title: `Confirmer la ${action} ?`,
            text: `Voulez-vous vraiment ${action} ce dossier employé ?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: confirmColor,
            confirmButtonText: `Oui, ${action}`,
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: window.grhRoutes.toggle,
                    method: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function (response) {
                        Swal.fire('Succès', response.message, 'success').then(() => {
                            window.location.reload();
                        });
                    },
                    error: function () {
                        Swal.fire('Erreur', 'Impossible de changer le statut', 'error');
                    },
                });
            }
        });
    });
});
