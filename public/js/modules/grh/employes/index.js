/**
 * GRH - Dossiers Employés - Index JS
 */

$(function() {
    const $table = $('#employes-table');
    const $modal = $('#employeModal');
    const $form = $('#employeForm');
    const $btnAdd = $('#btn-add-employe');
    const $btnEdit = $('#btn-edit-employe');
    const $btnToggle = $('#btn-toggle-employe');
    const $filterForm = $('#filter-form');
    const $btnResetFilters = $('#btn-reset-filters');

    // Selecteurs structure administrative
    const $niveauRattachement = $('#niveau_rattachement');
    const $dirSelect = $('#direction_id');
    const $srvSelect = $('#service_id');
    const $untSelect = $('#unite_id');
    const $dirContainer = $('#dir-select-container');
    const $srvContainer = $('#srv-select-container');
    const $untContainer = $('#unt-select-container');
    const $selectionContainer = $('#selection-structure-container');
    const $selectionLabel = $('#selection-structure-label');

    // Visualisation Preview
    const $previewDir = $('#preview-direction');
    const $previewSrv = $('#preview-service');
    const $previewUnt = $('#preview-unite');

    // Contacts
    const $contactsContainer = $('#contacts-container');
    const $addContactBtn = $('#add-contact-btn');
    const contactRowTemplate = $('#contact-row-template').html();
    let contactIndex = 0;

    // Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Formatting status in table
    window.statusFormatter = function(value, row) {
        const color = value ? 'success' : 'danger';
        const label = value ? 'Actif' : 'Inactif';
        return `<span class="badge bg-${color}">${label}</span>`;
    };

    // Query params for filters
    window.queryParams = function(params) {
        const formData = $filterForm.serializeArray();
        formData.forEach(item => {
            if (item.value) {
                params[item.name] = item.value;
            }
        });
        return params;
    };

    // Filter events
    $filterForm.find('select').on('change', function() {
        $table.bootstrapTable('refresh');
    });

    $btnResetFilters.on('click', function() {
        $filterForm[0].reset();
        $table.bootstrapTable('refresh');
    });

    // Gestion de la hiérarchie administrative
    $niveauRattachement.on('change', function() {
        const niveau = $(this).val();
        resetStructureSelections();

        if (!niveau) {
            $selectionContainer.addClass('d-none');
            return;
        }

        $selectionContainer.removeClass('d-none');
        $dirContainer.removeClass('d-none');
        $dirSelect.prop('required', true);

        if (niveau === 'direction') {
            $selectionLabel.text('SÉLECTIONNER LA DIRECTION');
            $srvContainer.addClass('d-none');
            $untContainer.addClass('d-none');
        } else if (niveau === 'service') {
            $selectionLabel.text('SÉLECTIONNER LE SERVICE');
            $srvContainer.removeClass('d-none');
            $srvSelect.prop('required', true);
            $untContainer.addClass('d-none');
        } else if (niveau === 'unite') {
            $selectionLabel.text('SÉLECTIONNER L\'UNITÉ');
            $srvContainer.removeClass('d-none');
            $srvSelect.prop('required', true);
            $untContainer.removeClass('d-none');
            $untSelect.prop('required', true);
        }
    });

    $dirSelect.on('change', function() {
        const dirId = $(this).val();
        const dirText = $(this).find('option:selected').text();

        $previewDir.text(dirId ? dirText : 'Direction (Non définie)');

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

    function loadServices(dirId) {
        $.get(`/grh/employes/services-by-direction/${dirId}`, function(services) {
            $srvSelect.empty().append('<option value="">Sélectionner le Service...</option>');
            services.forEach(srv => {
                $srvSelect.append(`<option value="${srv.id}">${srv.libelle}</option>`);
            });
        });
    }

    function loadUnites(srvId) {
        $.get(`/grh/employes/unites-by-service/${srvId}`, function(unites) {
            $untSelect.empty().append('<option value="">Sélectionner l\'Unité...</option>');
            unites.forEach(unt => {
                $untSelect.append(`<option value="${unt.id}">${unt.libelle}</option>`);
            });
        });
    }

    function resetStructureSelections() {
        $dirSelect.val('').prop('required', false);
        $srvSelect.empty().append('<option value="">Sélectionner le Service...</option>').prop('required', false);
        $untSelect.empty().append('<option value="">Sélectionner l\'Unité...</option>').prop('required', false);

        $previewDir.text('Direction Générale');
        $previewSrv.addClass('d-none');
        $previewUnt.addClass('d-none');

        $dirContainer.addClass('d-none');
        $srvContainer.addClass('d-none');
        $untContainer.addClass('d-none');
    }

    // Gestion des contacts
    $addContactBtn.on('click', function() {
        addContactRow();
    });

    function addContactRow(data = null) {
        let rowHtml = contactRowTemplate.replace(/INDEX/g, contactIndex);
        let $row = $(rowHtml);

        if (data) {
            $row.find('select[name*="type_contact"]').val(data.type_contact);
            $row.find('input[name*="valeur"]').val(data.valeur);
        }

        $row.find('.remove-contact-btn').on('click', function() {
            $row.remove();
        });

        $contactsContainer.append($row);
        contactIndex++;
    }

    // Selection management in table
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selections = $table.bootstrapTable('getSelections');
        const disabled = selections.length !== 1;
        $btnEdit.prop('disabled', disabled);
        $btnToggle.prop('disabled', disabled);
    });

    // Show modal for adding
    $btnAdd.on('click', function() {
        $form[0].reset();
        resetStructureSelections();
        $selectionContainer.addClass('d-none');
        $contactsContainer.empty();
        contactIndex = 0;
        addContactRow(); // Ajouter une ligne vide par défaut
        $('#modalTitle').text('Nouveau Collaborateur');
        $modal.modal('show');
    });

    // Save employee
    $form.on('submit', function(e) {
        e.preventDefault();

        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

        const formData = new FormData(this);

        $.ajax({
            url: window.grhRoutes.store,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#btn-save').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');
            },
            success: function(response) {
                Swal.fire({
                    title: 'Succès',
                    text: response.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                $modal.modal('hide');
                $table.bootstrapTable('refresh');
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Une erreur est survenue';
                const errors = xhr.responseJSON?.errors;
                let errorHtml = '';

                if (errors) {
                    errorHtml = '<ul class="text-start mt-2">';
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
                $('#btn-save').prop('disabled', false).html('<i class="fas fa-check-circle me-2"></i> CRÉER L\'EMPLOYÉ');
            }
        });
    });

    // Edit employee (Redirect to show page)
    $btnEdit.on('click', function() {
        const row = $table.bootstrapTable('getSelections')[0];
        window.location.href = window.grhRoutes.show(row.id);
    });

    // Toggle status
    $btnToggle.on('click', function() {
        const row = $table.bootstrapTable('getSelections')[0];
        const action = row.est_actif ? 'désactiver' : 'activer';

        Swal.fire({
            title: `Confirmer la ${action}?`,
            text: `Voulez-vous vraiment ${action} le dossier de ${row.full_name} ?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Oui, confirmer',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: window.grhRoutes.toggle(row.id),
                    method: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function(response) {
                        Swal.fire('Succès', response.message, 'success');
                        $table.bootstrapTable('refresh');
                    },
                    error: function() {
                        Swal.fire('Erreur', 'Impossible de modifier le statut', 'error');
                    }
                });
            }
        });
    });
});
