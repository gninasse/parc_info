/**
 * Gestion des Consommables - Module Parc Info
 * Pattern: AJAX + Bootstrap Table + Modal Creation + Quick Adds
 */

window.consommablesQueryParams = function(params) {
    return {
        limit: params.limit,
        offset: params.offset,
        search: params.search,
        sort: params.sort,
        order: params.order,
        type_consommable_id: $('#filter-type').val(),
        statut: $('#filter-statut').val()
    };
};

window.codeFormatter = function(value, row) {
    return `<a href="${route('parc-info.consommables.show', row.id)}" class="fw-bold text-primary text-decoration-none">${value}</a>`;
};

window.stockFormatter = function(value, row) {
    const isLow = row.stock_actuel <= parseInt(row.seuil.split('/')[0].trim());
    return `<span class="fw-bold ${isLow ? 'text-danger' : 'text-dark'}">${value}</span> <small class="text-muted">${row.unite}</small>`;
};

window.statusFormatter = function(value, row) {
    return row.status_label;
};

window.actionsFormatter = function(value, row) {
    return `
        <div class="btn-group btn-group-sm">
            <a href="${route('parc-info.consommables.show', row.id)}" class="btn btn-light border" title="Voir détails / Modifier">
                <i class="fas fa-eye text-primary"></i>
            </a>
            <a href="${route('parc-info.consommables.show', row.id)}" class="btn btn-light border" title="Modifier">
                <i class="fas fa-edit text-info"></i>
            </a>
            <button class="btn btn-light border btn-action-toggle" data-id="${row.id}" title="Changer statut">
                <i class="fas fa-power-off"></i>
            </button>
        </div>
    `;
};

document.addEventListener('DOMContentLoaded', function() {
    const $table = $('#consommables-table');
    const $modal = new bootstrap.Modal('#modal-consommable');
    const $form = $('#form-consommable');
    const $btnSave = $('#btn-save-consommable');
    
    const $modalType = new bootstrap.Modal('#modal-quickadd-type-cons');
    const $formType = $('#form-quickadd-type-cons');

    const $btnEditToolbar = $('#btn-edit');
    const $btnToggleToolbar = $('#btn-toggle-status');
    const $btnDeleteToolbar = $('#btn-delete');

    // Initialize Select2 inside Modal
    $('.select2-modal').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#modal-consommable')
    });

    // ── GESTION DE LA SELECTION ──
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selections = $table.bootstrapTable('getSelections');
        const hasOne = selections.length === 1;
        $btnEditToolbar.prop('disabled', !hasOne);
        $btnToggleToolbar.prop('disabled', !hasOne);
        $btnDeleteToolbar.prop('disabled', !hasOne);
    });

    // Mise à jour des KPIs
    $table.on('load-success.bs.table', function (e, data) {
        if (data.stats) {
            $('#kpi-total').text(data.stats.total);
            $('#kpi-rupture').text(data.stats.en_rupture);
            $('#kpi-valeur').text(new Intl.NumberFormat('fr-FR').format(data.stats.valeur_totale) + ' €');
            $('#kpi-mouvements').text(data.stats.mouvements_mois);
        }
    });

    // ── FILTRES ──
    $('#btn-apply-filters').on('click', () => $table.bootstrapTable('refresh'));
    $('#btn-reset-filters').on('click', () => {
        $('#filter-type, #filter-statut').val('').trigger('change');
        $table.bootstrapTable('refresh');
    });

    // ── AJOUT (MODALE) ──
    $('#btn-add').on('click', function() {
        $form[0].reset();
        $('#consommable-id').val('');
        $('#modalConsommableLabel span').text('Nouveau Consommable');
        $form.find('.select2-modal').val('').trigger('change');
        $modal.show();
    });

    // ── MODIFICATION DEPUIS LA TOOLBAR ──
    $btnEditToolbar.on('click', function() {
        const selections = $table.bootstrapTable('getSelections');
        if (selections.length === 1) {
            window.location.href = route('parc-info.consommables.show', selections[0].id);
        }
    });

    // ── ENREGISTREMENT (AJOUT) ──
    $form.on('submit', function(e) {
        e.preventDefault();
        $btnSave.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');

        $.ajax({
            url: route('parc-info.consommables.store'),
            method: 'POST',
            data: $form.serialize(),
            success: function(res) {
                if (res.success) {
                    $modal.hide();
                    Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500 });
                    $table.bootstrapTable('refresh');
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let msg = '';
                Object.values(errors).forEach(e => msg += e[0] + '<br>');
                Swal.fire('Erreur de validation', msg || 'Une erreur est survenue', 'error');
            },
            complete: () => $btnSave.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer')
        });
    });

    // ── QUICK ADD TYPE DE CONSOMMABLE ──
    $('#btn-quickadd-type-cons').on('click', () => {
        const modalEl = document.getElementById('modal-consommable');
        const originalFocus = modalEl.getAttribute('tabindex');
        modalEl.removeAttribute('tabindex');
        
        $formType[0].reset();
        $modalType.show();

        $formType.off('submit').on('submit', function(e) {
            e.preventDefault();
            const $btnSaveType = $('#btn-save-quick-type-cons');
            $btnSaveType.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>...');

            $.ajax({
                url: route('parc-info.consommables.store-type'),
                method: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    if (res.success) {
                        $modalType.hide();
                        $('#select-type-consommable').append(new Option(res.data.nom, res.data.id, true, true)).trigger('change');
                        Swal.fire({ icon: 'success', title: 'Type Ajouté !', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                    }
                },
                error: function(xhr) {
                    Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur', 'error');
                },
                complete: () => {
                    $btnSaveType.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer');
                    if (originalFocus) modalEl.setAttribute('tabindex', originalFocus);
                }
            });
        });
    });

    // ── QUICK ADD MARQUE (SWEETALERT / ORDINATEURS PATTERN) ──
    $('#btn-quickadd-marque').on('click', function() {
        const modalEl = document.getElementById('modal-consommable');
        const originalFocus = modalEl.getAttribute('tabindex');
        modalEl.removeAttribute('tabindex');

        Swal.fire({
            title: 'Nouvelle Marque',
            input: 'text',
            inputPlaceholder: 'Nom de la marque...',
            showCancelButton: true,
            confirmButtonText: 'Ajouter',
            cancelButtonText: 'Annuler',
            showLoaderOnConfirm: true,
            preConfirm: (val) => {
                if (!val) return Swal.showValidationMessage('Veuillez saisir une valeur');
                return $.post("/parc-info/marques", { libelle: val, _token: csrfToken })
                    .then(res => res.data)
                    .catch(err => {
                        Swal.showValidationMessage(err.responseJSON?.message || 'Erreur');
                    });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                const data = result.value;
                $('#select-marque').append(new Option(data.libelle, data.id, true, true)).trigger('change');
                Swal.fire({ icon: 'success', title: 'Marque Ajoutée !', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
            }
            if (originalFocus) modalEl.setAttribute('tabindex', originalFocus);
        });
    });

    // ── TOGGLE STATUT ──
    function toggleStatus(id) {
        $.ajax({
            url: `/parc-info/informatique/consommables/${id}/toggle`,
            method: 'PATCH',
            data: {
                _token: csrfToken
            },
            success: function(res) {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500 });
                    $table.bootstrapTable('refresh');
                }
            },
            error: function(xhr) {
                Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur', 'error');
            }
        });
    }

    $btnToggleToolbar.on('click', () => toggleStatus($table.bootstrapTable('getSelections')[0].id));
    $(document).on('click', '.btn-action-toggle', function() { toggleStatus($(this).data('id')); });

    // ── SUPPRESSION ──
    $btnDeleteToolbar.on('click', () => {
        const id = $table.bootstrapTable('getSelections')[0].id;
        Swal.fire({
            title: 'Supprimer ce consommable ?',
            text: "Cette action est irréversible et impossible si des mouvements de stock y sont associés.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/parc-info/informatique/consommables/${id}`,
                    method: 'DELETE',
                    data: {
                        _token: csrfToken
                    },
                    success: function(res) {
                        Swal.fire('Supprimé !', res.message, 'success');
                        $table.bootstrapTable('refresh');
                    },
                    error: function(xhr) {
                        Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur', 'error');
                    }
                });
            }
        });
    });

    // Fix scroll modales
    $(document).on('hidden.bs.modal', '.modal', function () {
        if ($('.modal:visible').length) $('body').addClass('modal-open');
    });
});
