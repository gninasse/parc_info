/**
 * Écran Sorties de stock (F4) — journal, sortie avec affectation, régularisation.
 */
(function ($) {
    'use strict';

    let sortieSelectionnee = null;
    let articlesCharges = false;

    document.addEventListener('DOMContentLoaded', function () {
        const $table = $('#sorties-table');
        const modalSortie = new bootstrap.Modal('#sortieModal');
        const modalRegul = new bootstrap.Modal('#regulModal');
        const modalDetail = new bootstrap.Modal('#sortieDetailModal');

        // ── Filtres ────────────────────────────────────────────────────────

        $table.bootstrapTable('refreshOptions', {
            queryParams: function (params) {
                params.magasin_id = $('#filter-magasin').val();
                params.type_mouvement = $('#filter-type').val();
                params.date_debut = $('#filter-date-debut').val();
                params.date_fin = $('#filter-date-fin').val();
                return params;
            },
        });

        $('#filter-magasin, #filter-type, #filter-date-debut, #filter-date-fin')
            .on('change', () => $table.bootstrapTable('refresh'));

        // ── Sélection ──────────────────────────────────────────────────────

        $table.on('check.bs.table uncheck.bs.table load-success.bs.table', function () {
            const selection = $table.bootstrapTable('getSelections');
            sortieSelectionnee = selection.length ? selection[0] : null;
            $('#btn-show').prop('disabled', !sortieSelectionnee);
        });

        // ── Chargement des selects ─────────────────────────────────────────

        function chargerArticles() {
            if (articlesCharges) return;

            $.getJSON(route('stock.sorties.articles'), function (reponse) {
                const options = (reponse.data || []).map(
                    (article) => `<option value="${article.id}">${article.code_article} — ${article.designation}</option>`
                );
                $('#sortie-article, #regul-article').html('<option value="">Choisir…</option>' + options.join(''));
                articlesCharges = true;
            });
        }

        function chargerCibles(typeCible) {
            $.getJSON(route('stock.sorties.cibles'), { type_cible: typeCible }, function (reponse) {
                const options = (reponse.data || []).map(
                    (cible) => `<option value="${cible.id}">${cible.libelle}</option>`
                );
                $('#sortie-cible').html('<option value="">Choisir…</option>' + options.join(''));
            });
        }

        $('#sortie-type-cible').on('change', function () {
            chargerCibles($(this).val());
        });

        // ── Sortie ─────────────────────────────────────────────────────────

        $('#btn-add').on('click', function () {
            Stock.effacerErreurs($('#sortie-form'));
            $('#sortie-form')[0].reset();
            chargerArticles();
            chargerCibles($('#sortie-type-cible').val());
            modalSortie.show();
        });

        $('#sortie-form').on('submit', function (event) {
            event.preventDefault();

            $.post(route('stock.sorties.store'), {
                magasin_id: $('#sortie-magasin').val(),
                article_id: $('#sortie-article').val(),
                quantite: $('#sortie-quantite').val(),
                type_cible: $('#sortie-type-cible').val(),
                cible_id: $('#sortie-cible').val(),
                reference_document: $('#sortie-reference').val(),
                motif: $('#sortie-motif').val(),
            })
                .done(function (reponse) {
                    modalSortie.hide();
                    Stock.succes(reponse.message);
                    $table.bootstrapTable('refresh');
                })
                .fail(Stock.gererEchec($('#sortie-form')));
        });

        // ── Régularisation négative ────────────────────────────────────────

        $('#btn-regul').on('click', function () {
            Stock.effacerErreurs($('#regul-form'));
            $('#regul-form')[0].reset();
            chargerArticles();
            modalRegul.show();
        });

        $('#regul-form').on('submit', function (event) {
            event.preventDefault();

            $.post(route('stock.sorties.regularisation'), {
                magasin_id: $('#regul-magasin').val(),
                article_id: $('#regul-article').val(),
                quantite: $('#regul-quantite').val(),
                motif: $('#regul-motif').val(),
            })
                .done(function (reponse) {
                    modalRegul.hide();
                    Stock.succes(reponse.message);
                    $table.bootstrapTable('refresh');
                })
                .fail(Stock.gererEchec($('#regul-form')));
        });

        // ── Détail ─────────────────────────────────────────────────────────

        $('#btn-show').on('click', function () {
            if (!sortieSelectionnee) return;

            $.getJSON(route('stock.sorties.show', sortieSelectionnee.id), function (reponse) {
                const sortie = reponse.data;

                $('#detail-numero').text(sortie.numero_mouvement);
                $('#detail-contenu').html(`
                    <dt class="col-sm-4">Type</dt><dd class="col-sm-8">${sortie.type_label}</dd>
                    <dt class="col-sm-4">Article</dt><dd class="col-sm-8">${sortie.code_article} — ${sortie.article}</dd>
                    <dt class="col-sm-4">Magasin</dt><dd class="col-sm-8">${sortie.magasin}</dd>
                    <dt class="col-sm-4">Quantité</dt><dd class="col-sm-8">${sortie.quantite}</dd>
                    <dt class="col-sm-4">Coût unitaire FIFO</dt><dd class="col-sm-8">${window.prixFormatter(sortie.cout_unitaire)}</dd>
                    <dt class="col-sm-4">Valeur</dt><dd class="col-sm-8 fw-semibold">${window.prixFormatter(sortie.valeur)}</dd>
                    <dt class="col-sm-4">Affectation</dt><dd class="col-sm-8">${sortie.cible ?? '-'}</dd>
                    <dt class="col-sm-4">Motif</dt><dd class="col-sm-8">${sortie.motif ?? '-'}</dd>
                    <dt class="col-sm-4">Référence</dt><dd class="col-sm-8">${sortie.reference_document ?? '-'}</dd>
                    <dt class="col-sm-4">Enregistrée le</dt><dd class="col-sm-8">${sortie.created_at}</dd>
                    <dt class="col-sm-4">Par</dt><dd class="col-sm-8">${sortie.createur ?? '-'}</dd>
                `);
                modalDetail.show();
            });
        });

        // ── Tooltips ───────────────────────────────────────────────────────

        $('[data-bs-toggle="tooltip"]').each(function () {
            new bootstrap.Tooltip(this);
        });
    });
})(jQuery);
