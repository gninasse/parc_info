/**
 * Gestion de la liste des Bons de Commande - Module Achat
 * Pattern: AJAX + Bootstrap Table
 */

document.addEventListener('DOMContentLoaded', function() {
    const $table = $('#bons-commande-table');
    const $btnShow = $('#btn-show');
    const $btnAnnuler = $('#btn-annuler');
    const $btnDelete = $('#btn-delete');

    // ── FILTRES RECHERCHE ──
    $('#filter-fournisseur, #filter-statut').on('change', function() {
        $table.bootstrapTable('refresh');
    });

    // Passer les filtres à l'AJAX
    $table.bootstrapTable('refreshOptions', {
        queryParams: function(params) {
            params.fournisseur_id = $('#filter-fournisseur').val();
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
            $btnAnnuler.prop('disabled', !(row.statut === 'brouillon' || row.statut === 'valide'));
            $btnDelete.prop('disabled', !(row.statut === 'brouillon'));
        } else {
            $btnAnnuler.prop('disabled', true);
            $btnDelete.prop('disabled', true);
        }
    });

    // ── ACTION SHOW/EDIT ──
    function showItem(id) {
        window.location.href = route('achat.bons-commande.show', id);
    }

    $btnShow.on('click', function() {
        const row = $table.bootstrapTable('getSelections')[0];
        if (row) showItem(row.id);
    });

    $table.on('dbl-click-row.bs.table', function(e, row) {
        showItem(row.id);
    });

    // ── ACTION ANNULER ──
    $btnAnnuler.on('click', function() {
        const row = $table.bootstrapTable('getSelections')[0];
        if (!row) return;

        Swal.fire({
            title: 'Annuler cette commande ?',
            text: `Êtes-vous sûr de vouloir annuler le bon de commande ${row.numero_commande} ?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Oui, annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('achat.bons-commande.annuler', row.id),
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Annulé !', res.message, 'success');
                            $table.bootstrapTable('refresh');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur lors de l\'annulation', 'error');
                    }
                });
            }
        });
    });

    // ── ACTION SUPPRIMER ──
    $btnDelete.on('click', function() {
        const row = $table.bootstrapTable('getSelections')[0];
        if (!row) return;

        Swal.fire({
            title: 'Supprimer ce bon de commande ?',
            text: 'Cette action est irréversible !',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, supprimer'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('achat.bons-commande.destroy', row.id),
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
