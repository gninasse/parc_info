/**
 * Gestion des Fournisseurs - Module Parc Info
 * Pattern: AJAX + Bootstrap Table
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
            <a href="${route('parc-info.fournisseurs.show', row.id)}" class="btn btn-light border" title="Modifier">
                <i class="fas fa-edit text-info"></i>
            </a>
            <button class="btn btn-light border btn-action-toggle" data-id="${row.id}" title="Changer statut">
                <i class="fas fa-power-off"></i>
            </button>
        </div>
    `;
};

document.addEventListener('DOMContentLoaded', function() {
    const $table = $('#fournisseurs-table');
    
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
        window.location.href = route('parc-info.fournisseurs.create');
    });

    // ── MODIFICATION DEPUIS LA TOOLBAR ──
    $btnEditToolbar.on('click', function() {
        const selections = $table.bootstrapTable('getSelections');
        if (selections.length === 1) {
            window.location.href = route('parc-info.fournisseurs.show', selections[0].id);
        }
    });

    // ── TOGGLE STATUT ──
    function toggleStatus(id) {
        $.ajax({
            url: route('parc-info.fournisseurs.toggle', id),
            method: 'PATCH',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
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
            title: 'Supprimer ce fournisseur ?',
            text: "Cette action est irréversible et impossible si des licences y sont liées.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('parc-info.fournisseurs.destroy', id),
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
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
});
