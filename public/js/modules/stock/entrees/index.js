/**
 * index.js — liste des bons d'entrée (UX §3.1) : filtres, navigation selon le
 * statut, suppression avec SW-DEL-BROUILLON chiffrée.
 */
import '../shared/formatters.js';

$(function () {
    const $table = $('#entrees-table');

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
        if (row) window.location.href = route('stock.entrees.show', row.id);
    });

    $('#btn-edit').on('click', () => {
        const row = selection();
        if (!row) return;
        if (!row.can_edit && row.statut !== 'REFERENCEMENT') {
            // SW-VALIDE-LOCK
            Swal.fire({
                icon: 'info',
                title: 'Bon validé — non modifiable',
                text: 'Corrigez par contre-mouvement depuis l\'historique.',
                showCancelButton: true,
                confirmButtonText: 'Voir l\'historique',
                cancelButtonText: 'Fermer',
            }).then((r) => {
                if (r.isConfirmed && typeof route === 'function') window.location.href = route('stock.dashboard');
            });
            return;
        }
        window.location.href = route('stock.entrees.edit', row.id);
    });

    $('#btn-delete').on('click', () => {
        const row = selection();
        if (!row) return;

        if (!row.can_delete) {
            Swal.fire({
                icon: 'info',
                title: 'Bon validé — non modifiable',
                text: 'Corrigez par contre-mouvement depuis l\'historique.',
            });
            return;
        }

        // SW-DEL-BROUILLON chiffrée (+ références saisies si tampon)
        const references = row.nb_tampons_saisis > 0 ? ` et ses ${row.nb_tampons_saisis} références saisies` : '';
        Swal.fire({
            title: 'Supprimer ce bon ?',
            text: `Le ${row.numero_affiche} et ses ${row.nb_lignes} lignes seront supprimés${references}.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Supprimer',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: route('stock.entrees.destroy', row.id),
                method: 'DELETE',
                dataType: 'json',
                success: (res) => {
                    if (res.success) {
                        $table.bootstrapTable('refresh');
                        Swal.fire({ icon: 'success', title: 'Supprimé', text: res.message, timer: 2000, showConfirmButton: false });
                    }
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
            // Modifier/Supprimer grisés si VALIDÉ/ANNULÉ (UX §0.3)
            const verrouille = ['VALIDE', 'ANNULE'].includes(sel[0].statut);
            $('#btn-edit, #btn-delete')
                .prop('disabled', verrouille)
                .attr('title', verrouille ? 'Bon validé — utilisez un contre-mouvement pour corriger' : '');
        }
    });

    $('#filter-statut, #filter-magasin, #filter-fournisseur, #filter-du, #filter-au')
        .on('change', () => $table.bootstrapTable('refresh'));

    $table.bootstrapTable('refreshOptions', {
        queryParams: (params) => Object.assign(params, {
            statut: $('#filter-statut').val(),
            magasin_id: $('#filter-magasin').val(),
            fournisseur_id: $('#filter-fournisseur').val(),
            du: $('#filter-du').val(),
            au: $('#filter-au').val(),
        }),
    });
});
