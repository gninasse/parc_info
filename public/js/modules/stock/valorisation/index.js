/**
 * Écran Valorisation (F7) — valorisation actuelle, snapshots immuables, recalcul.
 */
(function ($) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const $table = $('#valorisation-table');
        const $snapshots = $('#snapshots-table');
        const modalSnapshot = new bootstrap.Modal('#snapshotModal');

        // ── Filtre magasin (table + lien PDF) ──────────────────────────────

        $table.bootstrapTable('refreshOptions', {
            queryParams: function (params) {
                params.magasin_id = $('#filter-magasin').val();
                return params;
            },
        });

        $('#filter-magasin').on('change', function () {
            $table.bootstrapTable('refresh');

            const url = new URL($('#btn-pdf').attr('href'), window.location.origin);
            if ($(this).val()) {
                url.searchParams.set('magasin_id', $(this).val());
            } else {
                url.searchParams.delete('magasin_id');
            }
            $('#btn-pdf').attr('href', url.toString());
        });

        // ── Snapshot manuel ────────────────────────────────────────────────

        $('#btn-snapshot').on('click', function () {
            Stock.confirmer({
                titre: 'Créer un snapshot manuel ?',
                texte: 'Une photographie immuable de la valorisation actuelle sera enregistrée (RG-F7-03).',
                icone: 'question',
                confirmer: 'Créer',
                couleur: '#0d6efd',
            }).then(function (resultat) {
                if (!resultat.isConfirmed) return;

                $.post(route('stock.valorisation.snapshots.store'))
                    .done(function (reponse) {
                        Stock.succes(reponse.message);
                        $snapshots.bootstrapTable('refresh');
                    })
                    .fail((xhr) => Stock.erreur(xhr.responseJSON?.message));
            });
        });

        // ── Recalcul FIFO ──────────────────────────────────────────────────

        $('#btn-recalculer').on('click', function () {
            Stock.confirmer({
                titre: 'Recalculer les projections ?',
                texte: 'Les quantités et valeurs seront réalignées sur les lots FIFO, qui font foi.',
                icone: 'question',
                confirmer: 'Recalculer',
                couleur: '#ffc107',
            }).then(function (resultat) {
                if (!resultat.isConfirmed) return;

                $.post(route('stock.valorisation.recalculer'))
                    .done(function (reponse) {
                        Stock.succes(reponse.message);
                        $table.bootstrapTable('refresh');
                    })
                    .fail((xhr) => Stock.erreur(xhr.responseJSON?.message));
            });
        });

        // ── Détail d'un snapshot ───────────────────────────────────────────

        $(document).on('click', '.btn-snapshot-detail', function () {
            const snapshotId = $(this).data('id');

            $.getJSON(route('stock.valorisation.snapshots.show', snapshotId), function (reponse) {
                const snapshot = reponse.data;

                $('#snapshot-reference').text(snapshot.reference);
                $('#snapshot-lignes').html((snapshot.lignes || []).map((ligne) => `<tr>
                    <td>${ligne.magasin}</td>
                    <td class="font-monospace">${ligne.code_article}</td>
                    <td>${ligne.article}</td>
                    <td class="text-end">${ligne.quantite}</td>
                    <td class="text-end">${window.prixFormatter(ligne.cout_unitaire_moyen)}</td>
                    <td class="text-end fw-semibold">${window.prixFormatter(ligne.valeur_fifo)}</td>
                </tr>`).join(''));

                modalSnapshot.show();
            });
        });

        // ── Tooltips ───────────────────────────────────────────────────────

        $('[data-bs-toggle="tooltip"]').each(function () {
            new bootstrap.Tooltip(this);
        });
    });

    // Formatters (window.*, hors DOMContentLoaded).
    window.typeSnapshotFormatter = function (valeur) {
        return valeur === 'MENSUEL'
            ? '<span class="badge bg-primary">Mensuel</span>'
            : '<span class="badge bg-light text-dark border">Manuel</span>';
    };

    window.detailSnapshotFormatter = function (valeur) {
        return `<button class="btn btn-xs btn-outline-info btn-snapshot-detail" data-id="${valeur}" title="Voir le détail">
            <i class="fas fa-eye"></i>
        </button>`;
    };
})(jQuery);
