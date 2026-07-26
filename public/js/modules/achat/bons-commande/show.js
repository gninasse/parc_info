/**
 * Fiche d'un bon de commande : validation, annulation, clôture, impression.
 */
document.addEventListener('DOMContentLoaded', function () {
    const contexte = window.achatFicheBc;
    if (!contexte) return;

    // ── Validation (RG-BC-04) ──────────────────────────────────────────────
    $('#btn-valider').on('click', function () {
        const $bouton = $(this);

        Achat.confirmer({
            titre: 'Valider ce bon de commande ?',
            texte: `<strong>${contexte.numero}</strong><br>` +
                   'La validation verrouille le bon : il ne sera plus modifiable, ' +
                   'et pourra recevoir des livraisons.',
            icone: 'question',
            confirmer: 'Oui, valider',
            couleur: '#198754',
        }).then(function (resultat) {
            if (!resultat.isConfirmed) return;

            const restaurer = Achat.chargement($bouton, 'Validation…');

            $.post(contexte.urls.valider)
                .done(function (reponse) {
                    Achat.succes(reponse.message);
                    setTimeout(() => window.location.reload(), 700);
                })
                .fail(function (xhr) {
                    restaurer();
                    Achat.erreur(Achat.messageErreur(xhr, 'La validation a échoué.'));
                });
        });
    });

    // ── Annulation (RG-BC-05, motif obligatoire) ───────────────────────────
    $('#btn-annuler').on('click', function () {
        Achat.demanderMotif({
            titre: 'Annuler ce bon de commande ?',
            texte: `<strong>${contexte.numero}</strong><br>` +
                   'Le bon ne pourra plus être livré. Cette action est définitive.',
            libelle: "Motif de l'annulation",
            confirmer: 'Oui, annuler',
        }).then(function (motif) {
            if (!motif) return;

            $.post(contexte.urls.annuler, { motif })
                .done(function (reponse) {
                    Achat.succes(reponse.message);
                    setTimeout(() => window.location.reload(), 700);
                })
                .fail(function (xhr) {
                    Achat.erreur(Achat.messageErreur(xhr, "L'annulation a échoué."));
                });
        });
    });

    // ── Clôture du reliquat (EF-BC-18) ─────────────────────────────────────
    $('#btn-cloturer').on('click', function () {
        Achat.demanderMotif({
            titre: 'Clôturer le reliquat ?',
            texte: `<strong>${contexte.numero}</strong><br>` +
                   `${contexte.resteALivrer} unité(s) restent à livrer. La clôture solde ` +
                   'le bon sans remettre en cause les livraisons déjà intégrées.',
            libelle: 'Motif de la clôture',
            placeholder: 'Ex : commande soldée avec le fournisseur, article discontinué…',
            confirmer: 'Oui, clôturer',
            couleur: '#212529',
        }).then(function (motif) {
            if (!motif) return;

            $.post(contexte.urls.cloturer, { motif })
                .done(function (reponse) {
                    Achat.succes(reponse.message);
                    setTimeout(() => window.location.reload(), 700);
                })
                .fail(function (xhr) {
                    Achat.erreur(Achat.messageErreur(xhr, 'La clôture a échoué.'));
                });
        });
    });

    // ── Impression ─────────────────────────────────────────────────────────
    $('#btn-imprimer').on('click', function () {
        $('#modal-pdf-iframe').attr('src', contexte.urls.imprimer);
        new bootstrap.Modal(document.getElementById('modal-pdf')).show();
    });
});
