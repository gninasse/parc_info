/**
 * Gestion des Logiciels - Module Parc Info
 * Pattern: AJAX + Bootstrap Table + Modales
 */

window.logicielsQueryParams = function(params) {
    return {
        limit: params.limit,
        offset: params.offset,
        search: params.search,
        sort: params.sort,
        order: params.order,
        editeur_id: $('#filter-editeur').val(),
        type_licence_id: $('#filter-type').val()
    };
};

window.codeFormatter = function(value, row) {
    return `<a href="${route('parc-info.logiciels.show', row.id)}" class="fw-bold text-primary text-decoration-none">${value}</a>`;
};

window.statusFormatter = function(value, row) {
    return row.status_label;
};

window.actionsFormatter = function(value, row) {
    return `
        <div class="btn-group btn-group-sm">
            <a href="${route('parc-info.logiciels.show', row.id)}" class="btn btn-light border" title="Voir détails">
                <i class="fas fa-eye text-primary"></i>
            </a>
            <button class="btn btn-light border btn-action-edit" data-id="${row.id}" title="Modifier">
                <i class="fas fa-edit text-info"></i>
            </button>
            <button class="btn btn-light border btn-action-toggle" data-id="${row.id}" title="Changer statut">
                <i class="fas fa-power-off ${row.est_actif ? 'text-danger' : 'text-success'}"></i>
            </button>
        </div>
    `;
};

document.addEventListener('DOMContentLoaded', function() {
    const $table = $('#logiciels-table');
    const $modalLogiciel = new bootstrap.Modal('#modal-logiciel');
    const $modalEditeur = new bootstrap.Modal('#modal-quickadd-editeur');
    const $formLogiciel = $('#form-logiciel');
    const $formEditeur = $('#form-quickadd-editeur');
    const $btnSaveLogiciel = $('#btn-save-logiciel');
    
    const $btnEditToolbar = $('#btn-edit');
    const $btnToggleToolbar = $('#btn-toggle-status');
    const $btnDeleteToolbar = $('#btn-delete');

    // ── GESTION DES FILTRES ──
    $('#btn-apply-filters').on('click', () => $table.bootstrapTable('refresh'));
    $('#btn-reset-filters').on('click', () => {
        $('#filter-editeur, #filter-type').val('').trigger('change');
        $table.bootstrapTable('refresh');
    });

    // ── GESTION DE LA SELECTION ──
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selections = $table.bootstrapTable('getSelections');
        const hasOne = selections.length === 1;
        $btnEditToolbar.prop('disabled', !hasOne);
        $btnToggleToolbar.prop('disabled', !hasOne);
        $btnDeleteToolbar.prop('disabled', !hasOne);
    });

    // ── AJOUT ──
    $('#btn-add').on('click', function() {
        $formLogiciel[0].reset();
        $('#logiciel-id').val('');
        $('#modalLogicielLabel span').text('Nouveau Logiciel');
        $('#select-editeur').val('').trigger('change');
        $modalLogiciel.show();
    });

    // ── MODIFICATION ──
    function editLogiciel(id) {
        $.ajax({
            url: route('parc-info.logiciels.show', id) + '?json=1',
            method: 'GET',
            success: function(l) {
                $('#logiciel-id').val(l.id);
                $formLogiciel.find('[name="code"]').val(l.code);
                $formLogiciel.find('[name="nom"]').val(l.nom);
                $formLogiciel.find('[name="editeur_id"]').val(l.editeur_id).trigger('change');
                $formLogiciel.find('[name="type_licence_id"]').val(l.type_licence_id);
                $formLogiciel.find('[name="categorie"]').val(l.categorie);
                $formLogiciel.find('[name="description"]').val(l.description);
                $formLogiciel.find('[name="notes"]').val(l.notes);
                
                $('#modalLogicielLabel span').text('Modifier le Logiciel');
                $modalLogiciel.show();
            }
        });
    }

    $btnEditToolbar.on('click', () => editLogiciel($table.bootstrapTable('getSelections')[0].id));
    $(document).on('click', '.btn-action-edit', function() { editLogiciel($(this).data('id')); });

    // ── ENREGISTREMENT ──
    $formLogiciel.on('submit', function(e) {
        e.preventDefault();
        const id = $('#logiciel-id').val();
        const url = id ? route('parc-info.logiciels.update', id) : route('parc-info.logiciels.store');
        const method = id ? 'PUT' : 'POST';
        
        $btnSaveLogiciel.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');

        $.ajax({
            url: url,
            method: method,
            data: $formLogiciel.serialize(),
            success: function(res) {
                if (res.success) {
                    $modalLogiciel.hide();
                    Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500 });
                    $table.bootstrapTable('refresh');
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let msg = '';
                Object.values(errors).forEach(e => msg += e[0] + '<br>');
                Swal.fire('Erreur', msg || 'Une erreur est survenue', 'error');
            },
            complete: function() {
                $btnSaveLogiciel.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer');
            }
        });
    });

    // ── TOGGLE STATUT ──
    function toggleStatus(id) {
        Swal.fire({
            title: 'Changer le statut ?',
            text: "Le logiciel sera activé ou désactivé dans le catalogue.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Oui, changer'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('parc-info.logiciels.toggle', id),
                    method: 'PATCH',
                    success: function(res) {
                        Swal.fire('Mis à jour !', res.message, 'success');
                        $table.bootstrapTable('refresh');
                    }
                });
            }
        });
    }

    $btnToggleToolbar.on('click', () => toggleStatus($table.bootstrapTable('getSelections')[0].id));
    $(document).on('click', '.btn-action-toggle', function() { toggleStatus($(this).data('id')); });

    // ── SUPPRESSION ──
    $btnDeleteToolbar.on('click', () => {
        const id = $table.bootstrapTable('getSelections')[0].id;
        Swal.fire({
            title: 'Supprimer ce logiciel ?',
            text: "Cette action est irréversible et ne peut être faite que si aucune licence n'est rattachée.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, supprimer'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('parc-info.logiciels.destroy', id),
                    method: 'DELETE',
                    success: function(res) {
                        Swal.fire('Supprimé !', res.message, 'success');
                        $table.bootstrapTable('refresh');
                    },
                    error: function(xhr) {
                        Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur lors de la suppression', 'error');
                    }
                });
            }
        });
    });

    // ── QUICK ADD EDITEUR (MODALE) ──
    $('#btn-quickadd-editeur').on('click', function() {
        $formEditeur[0].reset();
        $modalEditeur.show();
    });

    $formEditeur.on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btn-save-quick-editeur');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');

        $.ajax({
            url: route('parc-info.logiciels.store-editeur'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    $modalEditeur.hide();
                    const newE = res.data;
                    const newOption = new Option(newE.nom, newE.id, true, true);
                    $('#select-editeur').append(newOption).trigger('change');
                    Swal.fire('Ajouté !', res.message, 'success');
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let msg = '';
                Object.values(errors).forEach(e => msg += e[0] + '<br>');
                Swal.fire('Erreur', msg || 'Une erreur est survenue', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer');
            }
        });
    });

    $('.select2-modal').select2({ theme: 'bootstrap-5' });
});
