/**
 * Saisie d'une réception.
 *
 * RG-BL-03 : la quantité reçue est plafonnée au reste à livrer, contrôle
 * doublé côté serveur.
 */
document.addEventListener('DOMContentLoaded', function () {
    const contexte = window.achatBordereau;
    if (!contexte) return;

    const $formulaire = $('#bl-form');
    const $lignes = $('#lignes-container');
    const $btnEnregistrer = $('#btn-save');
    const modaleBc = new bootstrap.Modal('#modal-bc');

    const echapper = (texte) => $('<div>').text(texte ?? '').html();

    // ── Sélection du bon de commande ───────────────────────────────────────
    $('#btn-choisir-bc, #bc-libelle').on('click', function () {
        $('#recherche-bc').val('').trigger('input');
        modaleBc.show();
    });

    $('#recherche-bc').on('input', function () {
        const requete = $(this).val().toLowerCase().trim();
        $('.ligne-bc').each(function () {
            $(this).toggle(($(this).data('recherche') || '').includes(requete));
        });
    });

    $(document).on('click', '.ligne-bc', function () {
        const id = $(this).data('id');
        $('#bon_de_commande_id').val(id);
        $('#bc-libelle').val(`${$(this).data('numero')} — ${$(this).data('fournisseur')}`);
        modaleBc.hide();
        chargerLignes(id);
    });

    // ── Chargement des lignes livrables ────────────────────────────────────
    function messageTableau(contenu, classe) {
        $lignes.html(
            `<tr><td colspan="6" class="text-center ${classe} py-4">${contenu}</td></tr>`
        );
    }

    function chargerLignes(bonCommandeId) {
        if (!bonCommandeId) {
            messageTableau(
                '<i class="fas fa-arrow-up me-1"></i> Sélectionnez un bon de commande.',
                'text-muted'
            );
            $btnEnregistrer.prop('disabled', true);
            return;
        }

        messageTableau(
            '<span class="spinner-border spinner-border-sm me-2 text-primary"></span>Chargement des articles…',
            'text-muted'
        );

        $.get(contexte.urlLignes.replace('__ID__', bonCommandeId))
            .done(function (reponse) {
                const lignes = (reponse.data || []).filter((l) => l.reste_a_livrer > 0);

                if (!lignes.length) {
                    messageTableau(
                        '<i class="fas fa-check-circle me-1"></i> Ce bon de commande est entièrement livré.',
                        'text-success'
                    );
                    $btnEnregistrer.prop('disabled', true);
                    return;
                }

                $lignes.empty();

                lignes.forEach(function (ligne, index) {
                    $lignes.append(`
                        <tr class="ligne-reception">
                            <td>
                                <input type="hidden" name="lignes[${index}][article_id]" value="${ligne.article_id}">
                                <strong>${echapper(ligne.code_article)}</strong> — ${echapper(ligne.designation)}
                                <span class="badge bg-light text-dark border ms-1">${echapper(ligne.type_label)}</span>
                            </td>
                            <td class="text-center">${ligne.quantite_commandee}</td>
                            <td class="text-center text-muted">${ligne.quantite_livree}</td>
                            <td class="text-center fw-semibold text-primary">${ligne.reste_a_livrer}</td>
                            <td class="text-center">
                                <input type="number" name="lignes[${index}][quantite_livree]"
                                       class="form-control form-control-sm text-center mx-auto champ-recu"
                                       style="width:80px" min="0" max="${ligne.reste_a_livrer}"
                                       value="${ligne.reste_a_livrer}" required>
                            </td>
                            <td class="text-center">
                                <input type="number" name="lignes[${index}][quantite_refusee]"
                                       class="form-control form-control-sm text-center mx-auto champ-refuse"
                                       style="width:80px" min="0" value="0">
                            </td>
                        </tr>
                    `);
                });

                $btnEnregistrer.prop('disabled', false);
            })
            .fail(function () {
                messageTableau(
                    '<i class="fas fa-exclamation-triangle me-1"></i> Les articles n\'ont pas pu être chargés.',
                    'text-danger'
                );
                $btnEnregistrer.prop('disabled', true);
            });
    }

    // Plafonnement à la saisie
    $lignes.on('input', '.champ-recu', function () {
        const maximum = parseInt($(this).attr('max'), 10);
        if (parseInt(this.value, 10) > maximum) {
            $(this).val(maximum);
            Achat.attention(`La quantité reçue ne peut pas dépasser le reste à livrer (${maximum}).`);
        }
    });

    if ($('#bon_de_commande_id').val()) {
        chargerLignes($('#bon_de_commande_id').val());
    }

    // ── Enregistrement ─────────────────────────────────────────────────────
    $formulaire.on('submit', function (evenement) {
        evenement.preventDefault();
        Achat.effacerErreurs($formulaire);

        let total = 0;
        $lignes.find('.champ-recu').each(function () {
            total += parseInt(this.value, 10) || 0;
        });

        if (total <= 0) {
            Achat.attention('Saisissez une quantité reçue supérieure à 0 pour au moins un article.');
            return;
        }

        const restaurer = Achat.chargement($btnEnregistrer, 'Enregistrement…');

        $.post(contexte.urlEnregistrement, $formulaire.serialize())
            .done(function (reponse) {
                if (!reponse.success) return;
                Achat.succes(reponse.message);
                setTimeout(() => { window.location.href = reponse.redirect; }, 600);
            })
            .fail(function (xhr) {
                restaurer();

                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Achat.afficherErreurs($formulaire, xhr.responseJSON.errors);
                } else {
                    Achat.erreur(Achat.messageErreur(xhr));
                }
            });
    });
});
