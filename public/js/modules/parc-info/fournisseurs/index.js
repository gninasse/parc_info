/**
 * Gestion des Fournisseurs - Module Parc Info
 * Pattern: AJAX + Bootstrap Table + Modales
 */

window.codeFormatter = function(value, row) {
    return `<a href="${route('parc-info.fournisseurs.show', row.id)}" class="fw-bold text-primary text-decoration-none">${value}</a>`;
};

window.actionsFormatter = function(value, row) {
    return `
        <div class="btn-group btn-group-sm">
            <a href="${route('parc-info.fournisseurs.show', row.id)}" class="btn btn-light border" title="Voir détails">
                <i class="fas fa-eye text-primary"></i>
            </a>
            <button class="btn btn-light border btn-action-edit" data-id="${row.id}" title="Modifier">
                <i class="fas fa-edit text-info"></i>
            </button>
            <button class="btn btn-light border btn-action-toggle" data-id="${row.id}" title="Changer statut">
                <i class="fas fa-power-off"></i>
            </button>
        </div>
    `;
};

document.addEventListener('DOMContentLoaded', function() {
    const $table = $('#fournisseurs-table');
    const $modal = new bootstrap.Modal('#modal-fournisseur');
    const $form = $('#form-fournisseur');
    const $btnSave = $('#btn-save-fournisseur');
    
    const $btnEditToolbar = $('#btn-edit');
    const $btnToggleToolbar = $('#btn-toggle-status');
    const $btnDeleteToolbar = $('#btn-delete');

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
        $form[0].reset();
        $('#fournisseur-id').val('');
        $modal.show();
    });

    // ── MODIFICATION ──
    function editFournisseur(id) {
        $.ajax({
            url: route('parc-info.fournisseurs.show', id) + '?json=1',
            method: 'GET',
            success: function(f) {
                $('#fournisseur-id').val(f.id);
                $form.find('[name="code"]').val(f.code);
                $form.find('[name="nom"]').val(f.nom);
                $form.find('[name="type"]').val(f.type);
                $form.find('[name="fiabilite_score"]').val(f.fiabilite_score);
                $form.find('[name="email"]').val(f.email);
                $form.find('[name="telephone"]').val(f.telephone);
                $form.find('[name="adresse"]').val(f.adresse);
                $modal.show();
            }
        });
    }

    $btnEditToolbar.on('click', () => editFournisseur($table.bootstrapTable('getSelections')[0].id));
    $(document).on('click', '.btn-action-edit', function() { editFournisseur($(this).data('id')); });

    // ── ENREGISTREMENT ──
    $form.on('submit', function(e) {
        e.preventDefault();
        const id = $('#fournisseur-id').val();
        const url = id ? route('parc-info.fournisseurs.update', id) : route('parc-info.fournisseurs.store');
        const method = id ? 'PUT' : 'POST';
        
        $btnSave.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>...');

        $.ajax({
            url: url,
            method: method,
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
                Swal.fire('Erreur', msg || 'Erreur', 'error');
            },
            complete: () => $btnSave.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer')
        });
    });

    // ── SUPPRESSION ──
    $btnDeleteToolbar.on('click', () => {
        const id = $table.bootstrapTable('getSelections')[0].id;
        Swal.fire({
            title: 'Supprimer ce fournisseur ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, supprimer'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('parc-info.fournisseurs.destroy', id),
                    method: 'DELETE',
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
});
