/**
 * index.js — liste des bons de sortie (UX §4.1).
 */
import '../shared/formatters.js';
import { ModalPdf } from '../shared/modal-pdf.js';

const ICONES_BENEFICIAIRE = {
    direction: 'bi-diagram-3', service: 'bi-people', unite: 'bi-person-workspace',
    poste: 'bi-pc-display', local: 'bi-door-closed', employe: 'bi-person',
};

window.beneficiaireFormatter = function (value, row) {
    const echapper = (t) => $('<span>').text(t ?? '—').html();
    const icone = ICONES_BENEFICIAIRE[row.beneficiaire_type] ?? 'bi-question';
    return `<i class="bi ${icone} me-1 text-muted"></i>${echapper(value ?? row.beneficiaire_type)}`;
};

$(function () {
    const $table = $('#sorties-table');

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
        if (row) window.location.href = route('stock.sorties.show', row.id);
    });

    $('#btn-edit').on('click', () => {
        const row = selection();
        if (!row) return;
        if (['VALIDE', 'ANNULE'].includes(row.statut)) {
            Swal.fire({ icon: 'info', title: 'Bon validé — non modifiable', text: 'Corrigez par contre-mouvement depuis l\'historique.' });
            return;
        }
        window.location.href = route('stock.sorties.edit', row.id);
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
                url: route('stock.sorties.destroy', row.id),
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


    // Impression depuis la liste : aperçu en modale (bons validés uniquement)
    $('#btn-imprimer').on('click', () => {
        const row = selection();
        if (!row) return;

        if (row.statut !== 'VALIDE') {
            Swal.fire({
                icon: 'info',
                title: 'Bon non validé',
                text: "Le bon PDF n'existe qu'après validation.",
            });
            return;
        }

        ModalPdf.ouvrir({
            urlBase: route('stock.sorties.pdf', row.id),
            titre: `Bon de sortie ${row.numero_affiche}`,
            modeles: { articles: 'Bon de sortie', equipements: 'Fiche des équipements' },
            avecEquipements: true,
        });
    });

    $table.on('check.bs.table uncheck.bs.table', function () {
        const sel = $table.bootstrapTable('getSelections');
        const one = sel.length === 1;
        $('#btn-show, #btn-edit, #btn-delete, #btn-imprimer').prop('disabled', !one);
        if (one) {
            const verrouille = ['VALIDE', 'ANNULE'].includes(sel[0].statut);
            $('#btn-imprimer').prop('disabled', sel[0].statut !== 'VALIDE');
            $('#btn-edit, #btn-delete')
                .prop('disabled', verrouille)
                .attr('title', verrouille ? 'Bon validé — utilisez un contre-mouvement pour corriger' : '');
        }
    });

    $('#filter-statut, #filter-magasin, #filter-du, #filter-au').on('change', () => $table.bootstrapTable('refresh'));
    $table.bootstrapTable('refreshOptions', {
        queryParams: (params) => Object.assign(params, {
            statut: $('#filter-statut').val(),
            magasin_id: $('#filter-magasin').val(),
            du: $('#filter-du').val(),
            au: $('#filter-au').val(),
        }),
    });
});
