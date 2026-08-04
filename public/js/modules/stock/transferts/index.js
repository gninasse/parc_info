/**
 * index.js — liste des transferts (UX §5.1) : filtre ?cible=mon-magasin
 * (toggle visible si l'utilisateur est responsable d'un magasin).
 */
import '../shared/formatters.js';

$(function () {
    const $table = $('#transferts-table');

    const selection = () => {
        const sel = $table.bootstrapTable('getSelections');
        if (!sel.length) {
            Swal.fire({ icon: 'warning', title: 'Attention', text: 'Veuillez sélectionner une ligne.' });
            return null;
        }
        return sel[0];
    };

    $('#btn-show').on('click', () => {
        const row = selection();
        if (row) window.location.href = route('stock.transferts.show', row.id);
    });

    $('#btn-edit').on('click', () => {
        const row = selection();
        if (!row) return;
        if (['VALIDE', 'ANNULE'].includes(row.statut)) {
            Swal.fire({ icon: 'info', title: 'Bon validé — non modifiable', text: 'Corrigez par contre-mouvement depuis l\'historique.' });
            return;
        }
        window.location.href = route('stock.transferts.edit', row.id);
    });

    $('#btn-delete').on('click', () => {
        const row = selection();
        if (!row) return;
        if (!row.can_delete) {
            Swal.fire({ icon: 'info', title: 'Bon validé — non modifiable', text: 'Corrigez par contre-mouvement depuis l\'historique.' });
            return;
        }

        const pointees = row.nb_pointees > 0 ? ` et ses ${row.nb_pointees} unités pointées` : '';
        Swal.fire({
            title: 'Supprimer ce bon ?',
            text: `Le ${row.numero_affiche} et ses ${row.nb_lignes} lignes seront supprimés${pointees}.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Supprimer',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: route('stock.transferts.destroy', row.id),
                method: 'DELETE',
                dataType: 'json',
                success: (res) => {
                    $table.bootstrapTable('refresh');
                    Swal.fire({ icon: 'success', title: 'Supprimé', text: res.message, timer: 2000, showConfirmButton: false });
                },
                error: (xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Suppression impossible.' }),
            });
        });
    });

    $table.on('check.bs.table uncheck.bs.table', function () {
        const sel = $table.bootstrapTable('getSelections');
        const one = sel.length === 1;
        $('#btn-show, #btn-edit, #btn-delete').prop('disabled', !one);
        if (one) {
            const verrouille = ['VALIDE', 'ANNULE'].includes(sel[0].statut);
            $('#btn-edit, #btn-delete')
                .prop('disabled', verrouille)
                .attr('title', verrouille ? 'Bon validé — utilisez un contre-mouvement pour corriger' : '');
        }
    });

    $('#filter-statut, #filter-magasin, #filter-du, #filter-au, #filter-mon-magasin')
        .on('change', () => $table.bootstrapTable('refresh'));

    $table.bootstrapTable('refreshOptions', {
        queryParams: (params) => Object.assign(params, {
            statut: $('#filter-statut').val(),
            magasin_id: $('#filter-magasin').val(),
            du: $('#filter-du').val(),
            au: $('#filter-au').val(),
            cible: $('#filter-mon-magasin').is(':checked') ? 'mon-magasin' : '',
        }),
    });
});
