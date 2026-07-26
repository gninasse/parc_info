/**
 * Liste des bordereaux de livraison.
 */
document.addEventListener('DOMContentLoaded', function () {
    const $table = $('#items-table');
    const $btnVoir = $('#btn-show');
    const $btnImprimer = $('#btn-print');
    const $btnWizard = $('#btn-wizard');
    const $btnSupprimer = $('#btn-delete');

    $table.bootstrapTable('refreshOptions', {
        queryParams: function (params) {
            params.bon_de_commande_id = $('#filter-bc').val();
            params.statut = $('#filter-statut').val();
            return params;
        },
    });

    $('#filter-bc, #filter-statut').on('change', () => $table.bootstrapTable('refresh'));

    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selection = $table.bootstrapTable('getSelections');
        const ligne = selection.length === 1 ? selection[0] : null;

        $btnVoir.prop('disabled', !ligne);
        $btnImprimer.prop('disabled', !ligne);
        // RG-WZ-01 : l'assistant reste accessible tant que le bordereau n'est pas validé.
        $btnWizard.prop('disabled', !ligne || ligne.statut === 'valide');
        // RG-BL-06 : suppression réservée aux brouillons.
        $btnSupprimer.prop('disabled', !ligne || ligne.statut !== 'brouillon');
    });

    const ligneSelectionnee = () => $table.bootstrapTable('getSelections')[0];

    function ouvrir(id) {
        window.location.href = route('achat.bordereaux.show', id);
    }

    $btnVoir.on('click', function () {
        const ligne = ligneSelectionnee();
        if (ligne) ouvrir(ligne.id);
    });

    $table.on('dbl-click-row.bs.table', (e, ligne) => ouvrir(ligne.id));

    $btnWizard.on('click', function () {
        const ligne = ligneSelectionnee();
        if (ligne) window.location.href = route('achat.bordereaux.wizard', ligne.id);
    });

    $btnImprimer.on('click', function () {
        const ligne = ligneSelectionnee();
        if (!ligne) return;

        $('#modal-pdf-iframe').attr('src', route('achat.bordereaux.imprimer', ligne.id));
        new bootstrap.Modal(document.getElementById('modal-pdf')).show();
    });

    $btnSupprimer.on('click', function () {
        const ligne = ligneSelectionnee();
        if (!ligne) return;

        Achat.confirmer({
            titre: 'Supprimer ce bordereau ?',
            texte: `<strong>${ligne.numero_livraison}</strong><br>Cette action est irréversible.`,
            confirmer: 'Oui, supprimer',
        }).then(function (resultat) {
            if (!resultat.isConfirmed) return;

            $.ajax({ url: route('achat.bordereaux.destroy', ligne.id), method: 'DELETE' })
                .done(function (reponse) {
                    Achat.succes(reponse.message);
                    $table.bootstrapTable('refresh');
                })
                .fail(function (xhr) {
                    Achat.erreur(Achat.messageErreur(xhr, 'La suppression a échoué.'));
                });
        });
    });
});
