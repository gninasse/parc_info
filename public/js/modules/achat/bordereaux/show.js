/**
 * Fiche d'un bordereau : consultation, édition sur place, retour en brouillon.
 */
document.addEventListener('DOMContentLoaded', function () {
    const contexte = window.achatFicheBl;
    if (!contexte) return;

    const $formulaire = $('#bl-form');
    const $lignes = $('#lignes-container');
    const $btnModifier = $('#btn-modifier');
    const $btnEnregistrer = $('#btn-enregistrer');
    const $btnAnnulerEdition = $('#btn-annuler-edition');
    const $boutonsLecture = $('#btn-imprimer, #btn-revenir-brouillon')
        .add($btnModifier)
        .add('a.btn-success, a.btn-warning');

    let enEdition = false;
    let sauvegarde = JSON.parse(JSON.stringify(contexte.lignes || []));

    const echapper = (texte) => $('<div>').text(texte ?? '').html();

    // ── Rendu des lignes ───────────────────────────────────────────────────
    function afficherLignes() {
        $lignes.empty();
        $('.colonne-action').toggleClass('d-none', !enEdition);

        const lignes = contexte.lignes || [];

        if (!lignes.length) {
            $lignes.html(
                `<tr><td colspan="${enEdition ? 5 : 4}" class="text-center text-muted py-4">` +
                '<i class="fas fa-info-circle me-1"></i> Aucun article réceptionné.</td></tr>'
            );
            return;
        }

        lignes.forEach(function (ligne, index) {
            const cellules = enEdition
                ? `<td class="text-center">
                       <input type="hidden" name="lignes[${index}][article_id]" value="${ligne.article_id}">
                       <input type="number" name="lignes[${index}][quantite_livree]"
                              class="form-control form-control-sm text-center mx-auto champ-recu"
                              style="width:80px" min="0" max="${ligne.max_qty}"
                              value="${ligne.quantite_saisie}" required>
                   </td>
                   <td class="text-center">
                       <input type="number" name="lignes[${index}][quantite_refusee]"
                              class="form-control form-control-sm text-center mx-auto"
                              style="width:80px" min="0" value="0">
                   </td>
                   <td class="text-center">
                       <button type="button" class="btn btn-sm btn-link text-danger btn-retirer p-0"
                               data-index="${index}" title="Retirer cette ligne">
                           <i class="fas fa-trash-alt"></i>
                       </button>
                   </td>`
                : `<td class="text-center fw-semibold">${ligne.quantite_saisie}</td>
                   <td class="text-center text-muted">—</td>`;

            $lignes.append(`
                <tr>
                    <td>
                        <strong>${echapper(ligne.code_article)}</strong> — ${echapper(ligne.designation)}
                        <span class="badge bg-light text-dark border ms-1">${echapper(ligne.type_label)}</span>
                    </td>
                    <td class="text-center">${ligne.quantite_commandee}</td>
                    ${cellules}
                </tr>
            `);
        });
    }

    // Seules les lignes effectivement reçues sur ce bordereau sont affichées
    // en lecture ; l'édition présente l'ensemble des lignes livrables.
    function lignesAffichables() {
        return (contexte.lignes || []).filter((l) => enEdition || l.quantite_saisie > 0);
    }

    function rendre() {
        const toutes = contexte.lignes;
        contexte.lignes = lignesAffichables();
        afficherLignes();
        contexte.lignes = toutes;
    }

    // ── Bascule en édition ─────────────────────────────────────────────────
    $btnModifier.on('click', function () {
        enEdition = true;
        sauvegarde = JSON.parse(JSON.stringify(contexte.lignes || []));

        $formulaire.find('.champ-editable').prop('disabled', false);
        $boutonsLecture.addClass('d-none');
        $btnEnregistrer.removeClass('d-none');
        $btnAnnulerEdition.removeClass('d-none');

        rendre();
    });

    $btnAnnulerEdition.on('click', function () {
        enEdition = false;
        contexte.lignes = JSON.parse(JSON.stringify(sauvegarde));

        $formulaire.find('.champ-editable').prop('disabled', true);
        Achat.effacerErreurs($formulaire);
        $boutonsLecture.removeClass('d-none');
        $btnEnregistrer.addClass('d-none');
        $btnAnnulerEdition.addClass('d-none');

        rendre();
    });

    $lignes.on('click', '.btn-retirer', function () {
        const index = $(this).data('index');
        const lignesVisibles = lignesAffichables();
        const cible = lignesVisibles[index];

        if (cible) {
            const reelle = contexte.lignes.find((l) => l.article_id === cible.article_id);
            if (reelle) reelle.quantite_saisie = 0;
        }

        rendre();
    });

    $lignes.on('input', '.champ-recu', function () {
        const maximum = parseInt($(this).attr('max'), 10);
        if (parseInt(this.value, 10) > maximum) {
            $(this).val(maximum);
            Achat.attention(`La quantité reçue ne peut pas dépasser ${maximum}.`);
        }
    });

    // ── Enregistrement ─────────────────────────────────────────────────────
    $formulaire.on('submit', function (evenement) {
        evenement.preventDefault();
        Achat.effacerErreurs($formulaire);

        let total = 0;
        $lignes.find('.champ-recu').each(function () {
            total += parseInt(this.value, 10) || 0;
        });

        if (total <= 0) {
            Achat.attention('Le bordereau doit comporter au moins un article reçu.');
            return;
        }

        const restaurer = Achat.chargement($btnEnregistrer, 'Enregistrement…');

        $.ajax({
            url: contexte.urls.mettreAJour,
            method: 'POST',
            data: $formulaire.serialize() + '&_method=PUT',
        }).done(function (reponse) {
            if (!reponse.success) return;
            Achat.succes(reponse.message);
            setTimeout(() => window.location.reload(), 600);
        }).fail(function (xhr) {
            restaurer();

            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                Achat.afficherErreurs($formulaire, xhr.responseJSON.errors);
            } else {
                Achat.erreur(Achat.messageErreur(xhr));
            }
        });
    });

    // ── Retour en brouillon (EF-BL-12) ─────────────────────────────────────
    $('#btn-revenir-brouillon').on('click', function () {
        Achat.confirmer({
            titre: 'Revenir en brouillon ?',
            texte: `<strong>${contexte.numero}</strong><br>` +
                   "Les saisies d'inventaire non finalisées seront abandonnées, " +
                   'et le bordereau redeviendra modifiable.',
            confirmer: 'Oui, revenir en brouillon',
            couleur: '#dc3545',
        }).then(function (resultat) {
            if (!resultat.isConfirmed) return;

            $.post(contexte.urls.revenirBrouillon)
                .done(function (reponse) {
                    Achat.succes(reponse.message);
                    setTimeout(() => window.location.reload(), 700);
                })
                .fail(function (xhr) {
                    Achat.erreur(Achat.messageErreur(xhr, "L'opération a échoué."));
                });
        });
    });

    // ── Impression ─────────────────────────────────────────────────────────
    $('#btn-imprimer').on('click', function () {
        $('#modal-pdf-iframe').attr('src', contexte.urls.imprimer);
        new bootstrap.Modal(document.getElementById('modal-pdf')).show();
    });

    rendre();
});
