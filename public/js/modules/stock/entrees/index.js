/**
 * Écran Entrées de stock (F3) — journal, saisie manuelle, suppression encadrée.
 */
(function ($) {
    'use strict';

    let entreeSelectionnee = null;
    let articlesCharges = false;

    document.addEventListener('DOMContentLoaded', function () {
        const $table = $('#entrees-table');
        const modalEntree = new bootstrap.Modal('#entreeModal');
        const modalDetail = new bootstrap.Modal('#entreeDetailModal');

        // ── Filtres ────────────────────────────────────────────────────────

        $table.bootstrapTable('refreshOptions', {
            queryParams: function (params) {
                params.magasin_id = $('#filter-magasin').val();
                params.type_mouvement = $('#filter-type').val();
                params.type_origine = $('#filter-origine').val();
                params.date_debut = $('#filter-date-debut').val();
                params.date_fin = $('#filter-date-fin').val();
                return params;
            },
        });

        $('#filter-magasin, #filter-type, #filter-origine, #filter-date-debut, #filter-date-fin')
            .on('change', () => $table.bootstrapTable('refresh'));

        // ── Sélection ──────────────────────────────────────────────────────

        $table.on('check.bs.table uncheck.bs.table load-success.bs.table', function () {
            const selection = $table.bootstrapTable('getSelections');
            entreeSelectionnee = selection.length ? selection[0] : null;

            $('#btn-show').prop('disabled', !entreeSelectionnee);
            // RG-F3-05/06 — seule une entrée manuelle récente est supprimable.
            $('#btn-delete').prop('disabled', !entreeSelectionnee || !entreeSelectionnee.supprimable);
        });

        // ── Motif conditionnel (RG-F3-03) ──────────────────────────────────

        $('#entree-type').on('change', function () {
            const regularisation = $(this).val() === 'REGULARISATION_PLUS';
            $('#entree-motif-obligatoire').toggleClass('d-none', !regularisation);
            $('#entree-motif').prop('required', regularisation);
        });

        // ── Saisie ─────────────────────────────────────────────────────────

        $('#btn-add').on('click', function () {
            Stock.effacerErreurs($('#entree-form'));
            $('#entree-form')[0].reset();
            $('#entree-type').trigger('change');

            if (!articlesCharges) {
                $.getJSON(route('stock.entrees.articles'), function (reponse) {
                    const options = (reponse.data || []).map(
                        (article) => `<option value="${article.id}">${article.code_article} — ${article.designation}</option>`
                    );
                    $('#entree-article').html('<option value="">Choisir…</option>' + options.join(''));
                    articlesCharges = true;
                });
            }

            modalEntree.show();
        });

        $('#entree-form').on('submit', function (event) {
            event.preventDefault();

            $.post(route('stock.entrees.store'), {
                type_mouvement: $('#entree-type').val(),
                magasin_id: $('#entree-magasin').val(),
                article_id: $('#entree-article').val(),
                quantite: $('#entree-quantite').val(),
                cout_unitaire: $('#entree-cout').val(),
                reference_document: $('#entree-reference').val(),
                motif: $('#entree-motif').val(),
            })
                .done(function (reponse) {
                    modalEntree.hide();
                    Stock.succes(reponse.message);
                    $table.bootstrapTable('refresh');
                })
                .fail(Stock.gererEchec($('#entree-form')));
        });

        // ── Détail ─────────────────────────────────────────────────────────

        $('#btn-show').on('click', function () {
            if (!entreeSelectionnee) return;

            $.getJSON(route('stock.entrees.show', entreeSelectionnee.id), function (reponse) {
                const entree = reponse.data;

                $('#detail-numero').text(entree.numero_mouvement);
                $('#detail-contenu').html(`
                    <dt class="col-sm-4">Type</dt><dd class="col-sm-8">${entree.type_label}</dd>
                    <dt class="col-sm-4">Origine</dt><dd class="col-sm-8">${entree.type_origine}</dd>
                    <dt class="col-sm-4">Article</dt><dd class="col-sm-8">${entree.code_article} — ${entree.article}</dd>
                    <dt class="col-sm-4">Magasin</dt><dd class="col-sm-8">${entree.magasin}</dd>
                    <dt class="col-sm-4">Quantité</dt><dd class="col-sm-8">${entree.quantite}</dd>
                    <dt class="col-sm-4">Coût unitaire</dt><dd class="col-sm-8">${window.prixFormatter(entree.cout_unitaire)}</dd>
                    <dt class="col-sm-4">Valeur</dt><dd class="col-sm-8 fw-semibold">${window.prixFormatter(entree.valeur)}</dd>
                    <dt class="col-sm-4">Référence</dt><dd class="col-sm-8">${entree.reference_document ?? '-'}</dd>
                    <dt class="col-sm-4">Motif</dt><dd class="col-sm-8">${entree.motif ?? '-'}</dd>
                    <dt class="col-sm-4">Enregistrée le</dt><dd class="col-sm-8">${entree.created_at}</dd>
                    <dt class="col-sm-4">Par</dt><dd class="col-sm-8">${entree.createur ?? '-'}</dd>
                `);
                modalDetail.show();
            });
        });

        // ── Suppression (RG-F3-05/06) ──────────────────────────────────────

        $('#btn-delete').on('click', function () {
            if (!entreeSelectionnee) return;

            Stock.confirmer({
                titre: 'Supprimer cette entrée ?',
                texte: `L'entrée <b>${entreeSelectionnee.numero_mouvement}</b> et son lot FIFO seront supprimés, `
                    + 'et le stock recalculé. Cette action ne concerne que les entrées manuelles de moins de 24 h.',
            }).then(function (resultat) {
                if (!resultat.isConfirmed) return;

                $.ajax({ url: route('stock.entrees.destroy', entreeSelectionnee.id), method: 'DELETE' })
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
})(jQuery);
