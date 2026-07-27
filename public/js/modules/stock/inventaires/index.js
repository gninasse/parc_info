/**
 * Écran Inventaires (F6) — ouverture, saisie des comptages, validation, annulation.
 */
(function ($) {
    'use strict';

    let inventaireSelectionne = null;

    document.addEventListener('DOMContentLoaded', function () {
        const $table = $('#inventaires-table');
        const modalInventaire = new bootstrap.Modal('#inventaireModal');
        const modalSaisie = new bootstrap.Modal('#saisieModal');

        // ── Filtres ────────────────────────────────────────────────────────

        $table.bootstrapTable('refreshOptions', {
            queryParams: function (params) {
                params.magasin_id = $('#filter-magasin').val();
                params.statut = $('#filter-statut').val();
                return params;
            },
        });

        $('#filter-magasin, #filter-statut').on('change', () => $table.bootstrapTable('refresh'));

        // ── Sélection ──────────────────────────────────────────────────────

        $table.on('check.bs.table uncheck.bs.table load-success.bs.table', function () {
            const selection = $table.bootstrapTable('getSelections');
            inventaireSelectionne = selection.length ? selection[0] : null;

            const enCours = inventaireSelectionne?.en_cours === true;
            $('#btn-saisir, #btn-valider, #btn-annuler').prop('disabled', !enCours);
        });

        // ── Ouverture ──────────────────────────────────────────────────────

        $('#btn-add').on('click', function () {
            Stock.effacerErreurs($('#inventaire-form'));
            $('#inventaire-form')[0].reset();
            modalInventaire.show();
        });

        $('#inventaire-form').on('submit', function (event) {
            event.preventDefault();

            $.post(route('stock.inventaires.store'), {
                magasin_id: $('#inventaire-magasin').val(),
                date_inventaire: $('#inventaire-date').val(),
            })
                .done(function (reponse) {
                    modalInventaire.hide();
                    Stock.succes(reponse.message);
                    $table.bootstrapTable('refresh');
                })
                .fail(Stock.gererEchec($('#inventaire-form')));
        });

        // ── Saisie des comptages ───────────────────────────────────────────

        $('#btn-saisir').on('click', function () {
            if (!inventaireSelectionne) return;

            $.getJSON(route('stock.inventaires.show', inventaireSelectionne.id), function (reponse) {
                const inventaire = reponse.data;

                $('#saisie-numero').text(inventaire.numero_inventaire);
                $('#saisie-form').data('inventaire-id', inventaire.id);

                $('#saisie-lignes').html((inventaire.lignes || []).map((ligne) => `<tr>
                    <td class="font-monospace">${ligne.code_article}</td>
                    <td>${ligne.article}</td>
                    <td class="text-end">${ligne.quantite_theorique}</td>
                    <td class="text-end">
                        <input type="number" min="0" class="form-control form-control-sm text-end comptage"
                               data-ligne-id="${ligne.id}" data-theorique="${ligne.quantite_theorique}"
                               value="${ligne.quantite_reelle ?? ''}">
                    </td>
                    <td class="text-end fw-semibold ecart" data-ligne-id="${ligne.id}">
                        ${ligne.ecart ?? '-'}
                    </td>
                </tr>`).join(''));

                modalSaisie.show();
            });
        });

        // Écart affiché en direct pendant la saisie.
        $('#saisie-lignes').on('input', '.comptage', function () {
            const theorique = parseInt($(this).data('theorique'), 10);
            const compte = $(this).val();
            const $ecart = $(`.ecart[data-ligne-id="${$(this).data('ligne-id')}"]`);

            if (compte === '') {
                $ecart.text('-').removeClass('text-danger text-success');
                return;
            }

            const ecart = parseInt(compte, 10) - theorique;
            $ecart.text(ecart > 0 ? `+${ecart}` : ecart)
                .toggleClass('text-danger', ecart < 0)
                .toggleClass('text-success', ecart > 0);
        });

        $('#saisie-form').on('submit', function (event) {
            event.preventDefault();

            const comptages = {};
            $('#saisie-lignes .comptage').each(function () {
                if ($(this).val() !== '') {
                    comptages[$(this).data('ligne-id')] = $(this).val();
                }
            });

            $.post(route('stock.inventaires.lignes', $(this).data('inventaire-id')), { comptages })
                .done(function (reponse) {
                    modalSaisie.hide();
                    Stock.succes(reponse.message);
                    $table.bootstrapTable('refresh');
                })
                .fail(Stock.gererEchec($('#saisie-form')));
        });

        // ── Validation (RG-F6-04→08) ───────────────────────────────────────

        $('#btn-valider').on('click', function () {
            if (!inventaireSelectionne) return;

            Stock.confirmer({
                titre: 'Clôturer l\'inventaire ?',
                texte: `L'inventaire <b>${inventaireSelectionne.numero_inventaire}</b> sera clôturé : chaque écart `
                    + 'produira un mouvement de régularisation (lots FIFO). Cette action est irréversible.',
                icone: 'question',
                confirmer: 'Oui, clôturer',
                couleur: '#198754',
            }).then(function (resultat) {
                if (!resultat.isConfirmed) return;

                $.post(route('stock.inventaires.valider', inventaireSelectionne.id))
                    .done(function (reponse) {
                        Stock.succes(reponse.message);
                        $table.bootstrapTable('refresh');
                    })
                    .fail((xhr) => Stock.erreur(xhr.responseJSON?.message));
            });
        });

        // ── Annulation (RG-F6-09) ──────────────────────────────────────────

        $('#btn-annuler').on('click', function () {
            if (!inventaireSelectionne) return;

            Stock.confirmer({
                titre: 'Annuler l\'inventaire ?',
                texte: `L'inventaire <b>${inventaireSelectionne.numero_inventaire}</b> sera annulé sans aucun mouvement.`,
            }).then(function (resultat) {
                if (!resultat.isConfirmed) return;

                $.post(route('stock.inventaires.annuler', inventaireSelectionne.id))
                    .done(function (reponse) {
                        Stock.succes(reponse.message);
                        $table.bootstrapTable('refresh');
                    })
                    .fail((xhr) => Stock.erreur(xhr.responseJSON?.message));
            });
        });

        // ── Tooltips ───────────────────────────────────────────────────────

        $('[data-bs-toggle="tooltip"]').each(function () {
            new bootstrap.Tooltip(this);
        });
    });

    // Formatter spécifique à l'écran (window.*, hors DOMContentLoaded).
    window.statutInventaireFormatter = function (valeur, ligne) {
        const classeTexte = ligne.statut_color === 'warning' ? ' text-dark' : '';
        return `<span class="badge bg-${ligne.statut_color}${classeTexte}">${ligne.statut_label}</span>`;
    };
})(jQuery);
