/**
 * Catalogue des articles : liste, création, modification, duplication.
 */
document.addEventListener('DOMContentLoaded', function () {
    const $table = $('#items-table');
    const $formulaire = $('#item-form');
    const modale = new bootstrap.Modal('#item-modal');

    const $btnAjouter = $('#btn-add');
    const $btnModifier = $('#btn-edit');
    const $btnDupliquer = $('#btn-duplicate');
    const $btnBasculer = $('#btn-toggle');
    const $btnSupprimer = $('#btn-delete');

    // ── Filtres ────────────────────────────────────────────────────────────
    $table.bootstrapTable('refreshOptions', {
        queryParams: function (params) {
            params.type_article = $('#filter-type').val();
            params.marque_id = $('#filter-marque').val();
            params.categorie_equipement_id = $('#filter-categorie').val();
            params.actif = $('#filter-actif').val();
            return params;
        },
    });

    $('#filter-type, #filter-marque, #filter-categorie, #filter-actif')
        .on('change', () => $table.bootstrapTable('refresh'));

    // ── Sélection ──────────────────────────────────────────────────────────
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const uneSeule = $table.bootstrapTable('getSelections').length === 1;
        $btnModifier.prop('disabled', !uneSeule);
        $btnDupliquer.prop('disabled', !uneSeule);
        $btnBasculer.prop('disabled', !uneSeule);
        $btnSupprimer.prop('disabled', !uneSeule);
    });

    const ligneSelectionnee = () => $table.bootstrapTable('getSelections')[0];

    // ── Champs conditionnels selon la nature de l'article ──────────────────
    function ajusterChamps(type) {
        $('#group-categorie').toggleClass('d-none', type !== 'equipement');
        $('#group-duree-validite').toggleClass('d-none', type !== 'licence');
    }

    $formulaire.find('[name="type_article"]').on('change', function () {
        ajusterChamps($(this).val());
    });

    function ouvrirOnglet(cible) {
        const bouton = document.querySelector(`#item-modal-tabs [data-bs-target="${cible}"]`);
        if (bouton) bootstrap.Tab.getOrCreateInstance(bouton).show();
    }

    // ── Création ───────────────────────────────────────────────────────────
    $btnAjouter.on('click', function () {
        $formulaire[0].reset();
        Achat.effacerErreurs($formulaire);
        $('#item-id').val('');
        $('#item-modal-action').text('Nouvel');
        $formulaire.find('[name="type_article"]').val('equipement').trigger('change');
        ouvrirOnglet('#tab-general');
        modale.show();
    });

    // ── Modification ───────────────────────────────────────────────────────
    function ouvrirModification(id) {
        $.get(route('achat.articles.show', id))
            .done(function (reponse) {
                if (!reponse.success) return;

                const article = reponse.data;
                Achat.effacerErreurs($formulaire);
                $('#item-id').val(article.id);

                [
                    'code_article', 'designation', 'description', 'reference_constructeur',
                    'marque_id', 'categorie_equipement_id', 'fournisseur_prefere_id',
                    'prix_indicatif', 'unite_mesure', 'taux_tva', 'compte_comptable',
                    'duree_validite_mois', 'url_fiche_technique',
                ].forEach(function (champ) {
                    $formulaire.find(`[name="${champ}"]`).val(article[champ] ?? '');
                });

                $formulaire.find('[name="type_article"]').val(article.type_article).trigger('change');

                $('#item-modal-action').text('Modifier l’');
                ouvrirOnglet('#tab-general');
                modale.show();
            })
            .fail(function (xhr) {
                Achat.erreur(Achat.messageErreur(xhr, "L'article n'a pas pu être chargé."));
            });
    }

    $btnModifier.on('click', function () {
        const ligne = ligneSelectionnee();
        if (ligne) ouvrirModification(ligne.id);
    });

    $table.on('dbl-click-row.bs.table', (e, ligne) => ouvrirModification(ligne.id));

    // ── Enregistrement ─────────────────────────────────────────────────────
    $formulaire.on('submit', function (evenement) {
        evenement.preventDefault();

        const id = $('#item-id').val();
        const donnees = new FormData(this);
        const restaurer = Achat.chargement($('#btn-save'), 'Enregistrement…');

        Achat.effacerErreurs($formulaire);

        if (id) {
            donnees.append('_method', 'PUT');
        }

        $.ajax({
            url: id ? route('achat.articles.update', id) : route('achat.articles.store'),
            method: 'POST', // FormData impose POST, la méthode réelle passe par _method
            data: donnees,
            processData: false,
            contentType: false,
        }).done(function (reponse) {
            if (!reponse.success) return;
            modale.hide();
            Achat.succes(reponse.message);
            $table.bootstrapTable('refresh');
        }).fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                Achat.afficherErreurs($formulaire, xhr.responseJSON.errors);
                ouvrirOnglet($formulaire.find('.is-invalid').closest('.tab-pane').length
                    ? `#${$formulaire.find('.is-invalid').closest('.tab-pane').attr('id')}`
                    : '#tab-general');
            } else {
                Achat.erreur(Achat.messageErreur(xhr));
            }
        }).always(restaurer);
    });

    // ── Duplication ────────────────────────────────────────────────────────
    $btnDupliquer.on('click', function () {
        const ligne = ligneSelectionnee();
        if (!ligne) return;

        $.post(route('achat.articles.dupliquer', ligne.id))
            .done(function (reponse) {
                Achat.succes(reponse.message);
                $table.bootstrapTable('refresh');
            })
            .fail(function (xhr) {
                Achat.erreur(Achat.messageErreur(xhr, 'La duplication a échoué.'));
            });
    });

    // ── Activation / désactivation ─────────────────────────────────────────
    $btnBasculer.on('click', function () {
        const ligne = ligneSelectionnee();
        if (!ligne) return;

        $.post(route('achat.articles.toggle-actif', ligne.id))
            .done(function (reponse) {
                Achat.succes(reponse.message);
                $table.bootstrapTable('refresh');
            })
            .fail(function (xhr) {
                Achat.erreur(Achat.messageErreur(xhr, 'Le changement de statut a échoué.'));
            });
    });

    // ── Suppression ────────────────────────────────────────────────────────
    $btnSupprimer.on('click', function () {
        const ligne = ligneSelectionnee();
        if (!ligne) return;

        Achat.confirmer({
            titre: 'Supprimer cet article ?',
            texte: `<strong>${$('<div>').text(ligne.designation).html()}</strong><br>` +
                   "S'il est référencé dans un bon de commande, il sera désactivé plutôt que supprimé.",
            confirmer: 'Oui, supprimer',
        }).then(function (resultat) {
            if (!resultat.isConfirmed) return;

            $.ajax({ url: route('achat.articles.destroy', ligne.id), method: 'DELETE' })
                .done(function (reponse) {
                    // ENF-FIA-06 : une désactivation n'est pas annoncée comme une suppression.
                    if (reponse.supprime) {
                        Achat.succes(reponse.message);
                    } else {
                        Achat.attention(reponse.message);
                    }
                    $table.bootstrapTable('refresh');
                })
                .fail(function (xhr) {
                    Achat.erreur(Achat.messageErreur(xhr, 'La suppression a échoué.'));
                });
        });
    });
});
