/**
 * Gestion des pièces jointes, partagée par les fiches BC et BL.
 *
 * Correction AN-16 : ce comportement était dupliqué à l'identique dans
 * bons_commande/show.blade.php et bordereaux/show.blade.php.
 *
 * Attend window.achatFicheBc ou window.achatFicheBl pour l'URL de dépôt.
 */
document.addEventListener('DOMContentLoaded', function () {
    const contexte = window.achatFicheBc || window.achatFicheBl;
    if (!contexte) return;

    const $formulaire = $('#form-document');
    const $tableau = $('#table-documents tbody');
    const $compteur = $('#compteur-documents');

    function majCompteur(delta) {
        const valeur = Math.max(0, (parseInt($compteur.text(), 10) || 0) + delta);
        $compteur.text(valeur);
    }

    // ── Dépôt ──────────────────────────────────────────────────────────────
    $formulaire.on('submit', function (evenement) {
        evenement.preventDefault();

        const $bouton = $('#btn-upload-document');
        const restaurer = Achat.chargement($bouton, 'Téléversement…');
        Achat.effacerErreurs($formulaire);

        $.ajax({
            url: contexte.urls.documents,
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
        }).done(function (reponse) {
            if (!reponse.success) return;

            const doc = reponse.data;
            $('.ligne-vide-documents').remove();

            $tableau.append(`
                <tr id="document-${doc.id}">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <i class="${doc.icone} fs-5"></i>
                            <div>
                                <div class="fw-bold text-dark"></div>
                                <div class="text-muted" style="font-size:.72rem">Ajouté le ${doc.date}</div>
                            </div>
                        </div>
                    </td>
                    <td class="text-muted notes"></td>
                    <td class="text-center text-muted text-nowrap">${doc.taille_lisible}</td>
                    <td class="auteur"></td>
                    <td class="text-end text-nowrap">
                        <a href="${doc.url_telechargement}" class="btn btn-xs btn-outline-primary" title="Télécharger">
                            <i class="fas fa-download"></i>
                        </a>
                        <button type="button" class="btn btn-xs btn-outline-danger btn-supprimer-document"
                                data-id="${doc.id}" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);

            // Injection par .text() : le nom du fichier vient de l'utilisateur.
            const $ligne = $(`#document-${doc.id}`);
            $ligne.find('.fw-bold').text(doc.nom);
            $ligne.find('.notes').text(doc.notes || '-');
            $ligne.find('.auteur').text(doc.auteur || 'Système');

            majCompteur(1);
            $formulaire[0].reset();
            Achat.succes(reponse.message);
        }).fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                Achat.afficherErreurs($formulaire, xhr.responseJSON.errors);
            } else {
                Achat.erreur(Achat.messageErreur(xhr, "Le document n'a pas pu être ajouté."));
            }
        }).always(restaurer);
    });

    // ── Suppression ────────────────────────────────────────────────────────
    $(document).on('click', '.btn-supprimer-document', function () {
        const id = $(this).data('id');

        Achat.confirmer({
            titre: 'Supprimer ce document ?',
            texte: 'Le document ne sera plus accessible depuis cette fiche.',
            confirmer: 'Oui, supprimer',
        }).then(function (resultat) {
            if (!resultat.isConfirmed) return;

            $.ajax({
                url: route('achat.documents.destroy', id),
                method: 'DELETE',
            }).done(function (reponse) {
                if (!reponse.success) return;

                $(`#document-${id}`).remove();
                majCompteur(-1);

                if ($tableau.find('tr').length === 0) {
                    $tableau.append(
                        '<tr class="ligne-vide-documents"><td colspan="5" class="text-center text-muted py-4">' +
                        '<i class="fas fa-info-circle me-1"></i> Aucun document joint.</td></tr>'
                    );
                }

                Achat.succes(reponse.message);
            }).fail(function (xhr) {
                Achat.erreur(Achat.messageErreur(xhr, 'La suppression a échoué.'));
            });
        });
    });
});
