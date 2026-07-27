/**
 * Écran Stock par article (F2) — niveaux, alertes, initialisation, détail FIFO.
 */
(function ($) {
    'use strict';

    let articleSelectionne = null;

    document.addEventListener('DOMContentLoaded', function () {
        const $table = $('#articles-table');
        const modalInit = new bootstrap.Modal('#initModal');
        const modalDetail = new bootstrap.Modal('#detailModal');

        // ── Filtres ────────────────────────────────────────────────────────

        $table.bootstrapTable('refreshOptions', {
            queryParams: function (params) {
                params.magasin_id = $('#filter-magasin').val();
                params.statut_alerte = $('#filter-alerte').val();
                return params;
            },
        });

        $('#filter-magasin, #filter-alerte').on('change', () => $table.bootstrapTable('refresh'));

        // ── Sélection ──────────────────────────────────────────────────────

        $table.on('check.bs.table uncheck.bs.table load-success.bs.table', function () {
            const selection = $table.bootstrapTable('getSelections');
            articleSelectionne = selection.length ? selection[0] : null;
            $('#btn-detail').prop('disabled', !articleSelectionne);
        });

        // ── Initialisation (RG-F2-01→03) ───────────────────────────────────

        $('#init-magasin').on('change', function () {
            const magasinId = $(this).val();
            const $article = $('#init-article');

            if (!magasinId) {
                $article.prop('disabled', true).html('<option value="">Choisir d\'abord un magasin…</option>');
                return;
            }

            $.getJSON(route('stock.articles.disponibles'), { magasin_id: magasinId }, function (reponse) {
                const options = (reponse.data || []).map(
                    (article) => `<option value="${article.id}">${article.code_article} — ${article.designation}</option>`
                );
                $article.prop('disabled', false)
                    .html('<option value="">Choisir…</option>' + options.join(''));
            });
        });

        $('#btn-init').on('click', function () {
            Stock.effacerErreurs($('#init-form'));
            $('#init-form')[0].reset();
            $('#init-article').prop('disabled', true).html('<option value="">Choisir d\'abord un magasin…</option>');
            modalInit.show();
        });

        $('#init-form').on('submit', function (event) {
            event.preventDefault();

            $.post(route('stock.articles.initialiser'), {
                magasin_id: $('#init-magasin').val(),
                article_id: $('#init-article').val(),
                quantite_initiale: $('#init-quantite').val(),
                cout_unitaire: $('#init-cout').val(),
            })
                .done(function (reponse) {
                    modalInit.hide();
                    Stock.succes(reponse.message);
                    $table.bootstrapTable('refresh');
                })
                .fail(Stock.gererEchec($('#init-form')));
        });

        // ── Détail ─────────────────────────────────────────────────────────

        $('#btn-detail').on('click', function () {
            if (!articleSelectionne) return;

            $('#detail-article-designation').text(
                `${articleSelectionne.code_article} — ${articleSelectionne.designation}`
            );

            $.getJSON(route('stock.articles.show', articleSelectionne.article_id), function (reponse) {
                const magasins = reponse.data.magasins || [];

                $('#detail-magasins').html(
                    magasins.length
                        ? magasins.map((m) => `<tr>
                            <td>${m.magasin_code} — ${m.magasin_libelle}</td>
                            <td class="text-end fw-semibold">${m.quantite}</td>
                            <td class="text-end">${window.prixFormatter(m.valeur_fifo)}</td>
                            <td class="text-end text-muted">${window.dateHeureFormatter(m.derniere_entree_at)}</td>
                            <td class="text-end text-muted">${window.dateHeureFormatter(m.derniere_sortie_at)}</td>
                        </tr>`).join('')
                        : '<tr><td colspan="5" class="text-center text-muted py-3">Aucun stock.</td></tr>'
                );
            });

            $.getJSON(route('stock.articles.lots', articleSelectionne.article_id), function (reponse) {
                const lots = reponse.data || [];

                $('#detail-lots').html(
                    lots.length
                        ? lots.map((lot) => `<tr>
                            <td>${lot.magasin}</td>
                            <td class="text-end">${lot.date_entree}</td>
                            <td class="text-end">${lot.quantite_initiale}</td>
                            <td class="text-end fw-semibold">${lot.quantite_restante}</td>
                            <td class="text-end">${window.prixFormatter(lot.cout_unitaire)}</td>
                            <td class="text-end">${window.prixFormatter(lot.valeur_restante)}</td>
                        </tr>`).join('')
                        : '<tr><td colspan="6" class="text-center text-muted py-3">Aucun lot disponible.</td></tr>'
                );
            });

            modalDetail.show();
        });

        // ── Tooltips ───────────────────────────────────────────────────────

        $('[data-bs-toggle="tooltip"]').each(function () {
            new bootstrap.Tooltip(this);
        });
    });
})(jQuery);
