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
    const $rattachementContainer = $('#rattachement-container');
    const $rattachementLabel = $('#rattachement-label');
    const $niveauRattachement = $('#niveau_rattachement');

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

    // Handle rattachement selection in modal
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
        $rattachementContainer.addClass('d-none');
        $('#modalTitle').text('Nouveau Dossier Employé');
        $modal.modal('show');
    });

    // Save employee
    $form.on('submit', function(e) {
        e.preventDefault();
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
                Swal.fire('Succès', response.message, 'success');
                $modal.modal('hide');
                $table.bootstrapTable('refresh');
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Une erreur est survenue';
                Swal.fire('Erreur', message, 'error');
            },
            complete: function() {
                $('#btn-save').prop('disabled', false).html('<i class="fas fa-save me-1"></i> ENREGISTRER');
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
            cancelButtonText: 'Annuler'
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
