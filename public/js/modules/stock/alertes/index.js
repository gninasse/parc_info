/**
 * Écran Alertes (F8) — notifications in-app du module Stock.
 */
(function ($) {
    'use strict';

    const BADGES = {
        RUPTURE: 'bg-danger',
        ALERTE: 'bg-warning text-dark',
        INFO: 'bg-info text-dark',
    };

    document.addEventListener('DOMContentLoaded', function () {
        function charger() {
            $.getJSON(route('stock.alertes.data'), function (reponse) {
                const lignes = (reponse.rows || []).map(function (notification) {
                    const badge = BADGES[notification.niveau] || BADGES.INFO;
                    const nonLue = notification.lue ? '' : ' bg-primary bg-opacity-10';

                    return `<li class="list-group-item d-flex justify-content-between align-items-start gap-3${nonLue}">
                        <div>
                            <span class="badge ${badge} me-2">${notification.niveau}</span>
                            <span class="fw-semibold">${notification.titre}</span>
                            <div class="text-muted small mt-1">${notification.message}</div>
                            <div class="text-muted small">${notification.date}</div>
                        </div>
                        <div class="text-nowrap">
                            ${notification.url ? `<a href="${notification.url}" class="btn btn-xs btn-outline-primary" title="Ouvrir"><i class="fas fa-arrow-right"></i></a>` : ''}
                            ${notification.lue ? '' : `<button class="btn btn-xs btn-outline-secondary btn-lire" data-id="${notification.id}" title="Marquer comme lue"><i class="fas fa-check"></i></button>`}
                        </div>
                    </li>`;
                });

                $('#alertes-liste').html(
                    lignes.length
                        ? lignes.join('')
                        : '<li class="list-group-item text-center text-muted py-4">Aucune notification.</li>'
                );
            });
        }

        $(document).on('click', '.btn-lire', function () {
            $.post(route('stock.notifications.lire', $(this).data('id')))
                .done(charger)
                .fail((xhr) => Stock.erreur(xhr.responseJSON?.message));
        });

        $('#btn-lire-tout').on('click', function () {
            $.post(route('stock.notifications.lire-tout'))
                .done(function (reponse) {
                    Stock.succes(reponse.message);
                    charger();
                })
                .fail((xhr) => Stock.erreur(xhr.responseJSON?.message));
        });

        charger();
    });
})(jQuery);
