/**
 * Gestion de la liste des Bordereaux de Livraison - Module Achat
 * Pattern: AJAX + Bootstrap Table
 */

document.addEventListener('DOMContentLoaded', function() {
    const $table = $('#bordereaux-table');
    const $btnShow = $('#btn-show');
    const $btnWizard = $('#btn-wizard');
    const $btnDelete = $('#btn-delete');

    // ── FILTRES RECHERCHE ──
    $('#filter-bc, #filter-statut').on('change', function() {
        $table.bootstrapTable('refresh');
    });

    // Passer les filtres à l'AJAX
    $table.bootstrapTable('refreshOptions', {
        queryParams: function(params) {
            params.bon_de_commande_id = $('#filter-bc').val();
            params.statut = $('#filter-statut').val();
            return params;
        }
    });

    // ── SELECTION EVENT ──
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selections = $table.bootstrapTable('getSelections');
        const hasOne = selections.length === 1;
        
        $btnShow.prop('disabled', !hasOne);

        if (hasOne) {
            const row = selections[0];
            $btnWizard.prop('disabled', !(row.statut === 'brouillon' || row.statut === 'wizard'));
            $btnDelete.prop('disabled', !(row.statut === 'brouillon'));
        } else {
            $btnWizard.prop('disabled', true);
            $btnDelete.prop('disabled', true);
        }
    });

    // ── ACTION SHOW ──
    function showItem(id) {
        window.location.href = route('achat.bordereaux.show', id);
    }

    $btnShow.on('click', function() {
        const row = $table.bootstrapTable('getSelections')[0];
        if (row) showItem(row.id);
    });

    $table.on('dbl-click-row.bs.table', function(e, row) {
        showItem(row.id);
    });

    // ── ACTION WIZARD ──
    $btnWizard.on('click', function() {
        const row = $table.bootstrapTable('getSelections')[0];
        if (row) {
            window.location.href = route('achat.bordereaux.wizard', row.id);
        }
    });

    // ── ACTION SUPPRIMER ──
    $btnDelete.on('click', function() {
        const row = $table.bootstrapTable('getSelections')[0];
        if (!row) return;

        Swal.fire({
            title: 'Supprimer ce bordereau ?',
            text: 'Cette action est irréversible !',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, supprimer'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('achat.bordereaux.destroy', row.id),
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Supprimé !', res.message, 'success');
                            $table.bootstrapTable('refresh');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur lors de la suppression', 'error');
                    }
                });
            }
        });
    });
});
