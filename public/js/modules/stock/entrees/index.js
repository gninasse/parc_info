/**
 * index.js — point d'entrée Entrées
 */
import { EntreeForm } from './EntreeForm.js';

// Les formatters DOIVENT être en window.* et AVANT DOMContentLoaded
window.dateFormatter = function (value) {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('fr-FR', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit'
    });
};

$(function () {
    const $table = $('#entrees-table');

    const tableInstance = {
        refresh: () => $table.bootstrapTable('refresh')
    };

    const form = new EntreeForm('#entreeModal', '#entree-form', tableInstance);

    $('#btn-add-entree').on('click', () => form.openForAdd());

    // Selection changes for delete button
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const sel = $table.bootstrapTable('getSelections');
        const one = sel.length === 1;
        if (one) {
            const row = sel[0];
            // Only allow deleting manual entries
            $('#btn-delete-entree').prop('disabled', row.type_origine !== 'MANUEL');
        } else {
            $('#btn-delete-entree').prop('disabled', true);
        }
    });

    // Delete handler
    $('#btn-delete-entree').on('click', function () {
        const sel = $table.bootstrapTable('getSelections');
        if (!sel.length) return;
        const row = sel[0];

        Swal.fire({
            title: 'Supprimer ce bon d\'entrée ?',
            text: 'Les stocks seront décrémentés en conséquence. Cette action est irréversible et n\'est possible que pour les entrées de moins de 24h.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: route('stock.entrees.destroy', row.id),
                method: 'DELETE',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: (res) => {
                    if (res.success) {
                        $table.bootstrapTable('refresh');
                        Swal.fire({ icon: 'success', title: 'Supprimé', text: res.message, timer: 3000 });
                    }
                },
                error: (xhr) => {
                    Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Impossible de supprimer l\'entrée.' });
                }
            });
        });
    });

    // Filtres externes
    $('#filter-magasin').on('change', () => $table.bootstrapTable('refresh'));
    
    $table.bootstrapTable('refreshOptions', {
        queryParams: function (params) {
            params.magasin_id = $('#filter-magasin').val();
            return params;
        }
    });
});
