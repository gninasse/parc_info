/**
 * Tableau de bord du module Stock — indicateurs et derniers mouvements.
 */
(function ($) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        $.getJSON(route('stock.dashboard.data'))
            .done(function (reponse) {
                const data = reponse.data || {};

                $('#indicateur-magasins').text(data.magasins_actifs ?? 0);
                $('#indicateur-articles').text(data.articles_en_stock ?? 0);
                $('#indicateur-valeur').text(window.prixFormatter(data.valeur_totale ?? 0));
                $('#indicateur-mouvements').text(data.mouvements_du_jour ?? 0);

                const lignes = (data.derniers_mouvements || []).map(function (mouvement) {
                    const classeTexte = mouvement.type_color === 'warning' ? ' text-dark' : '';
                    return `<tr>
                        <td class="fw-semibold font-monospace">${mouvement.numero}</td>
                        <td><span class="badge bg-${mouvement.type_color}${classeTexte}">${mouvement.type_label}</span></td>
                        <td>${mouvement.magasin}</td>
                        <td class="text-end">${mouvement.quantite}</td>
                        <td class="text-end text-muted">${mouvement.date}</td>
                    </tr>`;
                });

                $('#derniers-mouvements').html(
                    lignes.length
                        ? lignes.join('')
                        : '<tr><td colspan="5" class="text-center text-muted py-4">Aucun mouvement enregistré.</td></tr>'
                );
            })
            .fail(function () {
                $('#derniers-mouvements').html(
                    '<tr><td colspan="5" class="text-center text-danger py-4">Impossible de charger les données.</td></tr>'
                );
            });
    });
})(jQuery);
