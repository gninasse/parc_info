/**
 * Liste des bons de commande.
 */
document.addEventListener('DOMContentLoaded', function () {
    const $table = $('#items-table');
    const $btnVoir = $('#btn-show');
    const $btnImprimer = $('#btn-print');
    const $btnAnnuler = $('#btn-annuler');
    const $btnSupprimer = $('#btn-delete');

    // ── Filtres ────────────────────────────────────────────────────────────
    $table.bootstrapTable('refreshOptions', {
        queryParams: function (params) {
            params.fournisseur_id = $('#filter-fournisseur').val();
            params.statut = $('#filter-statut').val();
            params.date_debut = $('#filter-date-debut').val();
            params.date_fin = $('#filter-date-fin').val();
            return params;
        },
    });

    $('#filter-fournisseur, #filter-statut, #filter-date-debut, #filter-date-fin')
        .on('change', () => $table.bootstrapTable('refresh'));

    // ── Sélection : les actions suivent le statut de la ligne ──────────────
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selection = $table.bootstrapTable('getSelections');
        const ligne = selection.length === 1 ? selection[0] : null;

        $btnVoir.prop('disabled', !ligne);
        $btnImprimer.prop('disabled', !ligne);

        // RG-BC-05 : annulation possible avant toute livraison intégrée.
        $btnAnnuler.prop('disabled', !ligne || !['brouillon', 'valide'].includes(ligne.statut));
        // RG-BC-03 : suppression réservée aux brouillons.
        $btnSupprimer.prop('disabled', !ligne || ligne.statut !== 'brouillon');
    });

    const ligneSelectionnee = () => $table.bootstrapTable('getSelections')[0];

    // ── Consultation ───────────────────────────────────────────────────────
    function ouvrir(id) {
        window.location.href = route('achat.bons-commande.show', id);
    }

    $btnVoir.on('click', function () {
        const ligne = ligneSelectionnee();
        if (ligne) ouvrir(ligne.id);
    });

    $table.on('dbl-click-row.bs.table', (e, ligne) => ouvrir(ligne.id));

    // ── Impression ─────────────────────────────────────────────────────────
    $btnImprimer.on('click', function () {
        const ligne = ligneSelectionnee();
        if (!ligne) return;

        $('#modal-pdf-iframe').attr('src', `${route('achat.bons-commande.imprimer', ligne.id)}?pdf=1`);
        new bootstrap.Modal(document.getElementById('modal-pdf')).show();
    });

    // ── Annulation (motif obligatoire) ─────────────────────────────────────
    $btnAnnuler.on('click', function () {
        const ligne = ligneSelectionnee();
        if (!ligne) return;

        Achat.demanderMotif({
            titre: 'Annuler ce bon de commande ?',
            texte: `<strong>${ligne.numero_commande}</strong><br>` +
                   "L'annulation est définitive : le bon ne pourra plus être livré.",
            libelle: "Motif de l'annulation",
            confirmer: 'Oui, annuler',
        }).then(function (motif) {
            if (!motif) return;

            $.post(route('achat.bons-commande.annuler', ligne.id), { motif })
                .done(function (reponse) {
                    Achat.succes(reponse.message);
                    $table.bootstrapTable('refresh');
                })
                .fail(function (xhr) {
                    Achat.erreur(Achat.messageErreur(xhr, "L'annulation a échoué."));
                });
        });
    });

    // ── Suppression ────────────────────────────────────────────────────────
    $btnSupprimer.on('click', function () {
        const ligne = ligneSelectionnee();
        if (!ligne) return;

        Achat.confirmer({
            titre: 'Supprimer ce bon de commande ?',
            texte: `<strong>${ligne.numero_commande}</strong><br>Cette action est irréversible.`,
            confirmer: 'Oui, supprimer',
        }).then(function (resultat) {
            if (!resultat.isConfirmed) return;

            $.ajax({ url: route('achat.bons-commande.destroy', ligne.id), method: 'DELETE' })
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
