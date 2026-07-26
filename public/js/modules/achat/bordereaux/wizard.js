/**
 * Assistant d'intégration au parc.
 *
 * RG-WZ-05 : la finalisation reste verrouillée tant qu'une étape n'est pas
 * complétée. Le contrôle est également refait côté serveur.
 */
document.addEventListener('DOMContentLoaded', function () {
    const contexte = window.achatWizard;
    if (!contexte) return;

    const etapesCompletes = new Set();

    // État initial : les étapes déjà enregistrées sont marquées complètes.
    $('#wizard-nav .nav-link.etape-complete').each(function () {
        etapesCompletes.add(String($(this).data('article-id')));
    });

    // ── Navigation ─────────────────────────────────────────────────────────
    function etapeActive() {
        return document.querySelector('#wizard-nav .nav-link.active');
    }

    $('.btn-etape-precedente').on('click', function () {
        const precedente = etapeActive()?.previousElementSibling;
        if (precedente) bootstrap.Tab.getOrCreateInstance(precedente).show();
    });

    function allerEtapeSuivante() {
        const suivante = etapeActive()?.nextElementSibling;
        if (suivante) bootstrap.Tab.getOrCreateInstance(suivante).show();
    }

    // ── Application des caractéristiques communes (EF-INT-18) ──────────────
    $('.btn-appliquer-communs').on('click', function () {
        const $panneau = $(this).closest('.tab-pane');
        let appliquees = 0;

        $panneau.find('.champ-commun').each(function () {
            const code = $(this).data('code-champ');
            const valeur = $(this).val();

            if (valeur === '' || valeur === null) return;

            $panneau.find(`.champ-unite[data-code-champ="${code}"]`).val(valeur);
            appliquees++;
        });

        if (appliquees) {
            Achat.succes(`${appliquees} caractéristique(s) reportée(s) sur toutes les unités.`);
        } else {
            Achat.attention('Renseignez au moins une caractéristique commune avant de l\'appliquer.');
        }
    });

    // ── Enregistrement d'une étape ─────────────────────────────────────────
    $('.form-etape').on('submit', function (evenement) {
        evenement.preventDefault();

        const $formulaire = $(this);
        const articleId = String($formulaire.data('article-id'));
        const $bouton = $formulaire.find('.btn-enregistrer-etape');

        // Contrôle natif du navigateur avant envoi
        if (!this.checkValidity()) {
            this.reportValidity();
            return;
        }

        const restaurer = Achat.chargement($bouton, 'Enregistrement…');

        $.post($formulaire.data('url'), $formulaire.serialize() + '&completed=1')
            .done(function (reponse) {
                if (!reponse.success) return;

                etapesCompletes.add(articleId);

                const $navigation = $(`#etape-${articleId}`);
                $navigation.addClass('etape-complete');
                $navigation.find('.icone-etat').html('<i class="fas fa-check-circle text-success"></i>');

                $(`#recapitulatif [data-article-id="${articleId}"] .badge-etat`)
                    .removeClass('bg-danger').addClass('bg-success').text('Complété');

                actualiserFinalisation();
                Achat.succes('Étape enregistrée.');
                setTimeout(allerEtapeSuivante, 350);
            })
            .fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Achat.afficherErreurs($formulaire, xhr.responseJSON.errors);
                } else {
                    Achat.erreur(Achat.messageErreur(xhr, "L'étape n'a pas pu être enregistrée."));
                }
            })
            .always(restaurer);
    });

    // ── Éligibilité à la finalisation ──────────────────────────────────────
    function actualiserFinalisation() {
        const complet = etapesCompletes.size >= contexte.nombreEtapes;
        $('#btn-finaliser').prop('disabled', !complet);
        $('#avertissement-finalisation').toggleClass('d-none', complet);
    }

    actualiserFinalisation();

    // ── Finalisation (RGC-05) ──────────────────────────────────────────────
    $('#btn-finaliser').on('click', function () {
        const $bouton = $(this);

        Achat.confirmer({
            titre: 'Finaliser l’intégration ?',
            texte: 'Les équipements et licences seront créés dans le parc informatique, ' +
                   'et les stocks mis à jour.<br><strong>Cette opération est définitive.</strong>',
            icone: 'question',
            confirmer: 'Oui, intégrer au parc',
            couleur: '#198754',
        }).then(function (resultat) {
            if (!resultat.isConfirmed) return;

            const restaurer = Achat.chargement($bouton, 'Intégration en cours…');

            $.post(contexte.urlValidation)
                .done(function (reponse) {
                    if (!reponse.success) return;

                    Swal.fire({
                        icon: 'success',
                        title: 'Intégration réussie',
                        text: reponse.message,
                        confirmButtonText: 'Consulter le bordereau',
                    }).then(() => { window.location.href = reponse.redirect; });
                })
                .fail(function (xhr) {
                    restaurer();
                    // L'intégration est transactionnelle : en cas d'échec, rien n'a été créé.
                    Achat.erreur(Achat.messageErreur(
                        xhr,
                        "L'intégration a échoué. Aucun élément n'a été créé dans le parc."
                    ));
                });
        });
    });
});
