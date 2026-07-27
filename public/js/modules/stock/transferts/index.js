/**
 * Écran Transferts inter-magasins (F5) — création, validation, rejet, annulation.
 */
(function ($) {
    'use strict';

    let transfertSelectionne = null;
    let articlesCharges = false;

    document.addEventListener('DOMContentLoaded', function () {
        const $table = $('#transferts-table');
        const modalTransfert = new bootstrap.Modal('#transfertModal');
        const modalDetail = new bootstrap.Modal('#transfertDetailModal');

        // ── Filtres ────────────────────────────────────────────────────────

        $table.bootstrapTable('refreshOptions', {
            queryParams: function (params) {
                params.magasin_source_id = $('#filter-source').val();
                params.magasin_destination_id = $('#filter-destination').val();
                params.statut = $('#filter-statut').val();
                return params;
            },
        });

        $('#filter-source, #filter-destination, #filter-statut')
            .on('change', () => $table.bootstrapTable('refresh'));

        // ── Sélection (seul un transfert EN_ATTENTE est actionnable) ───────

        $table.on('check.bs.table uncheck.bs.table load-success.bs.table', function () {
            const selection = $table.bootstrapTable('getSelections');
            transfertSelectionne = selection.length ? selection[0] : null;

            const enAttente = transfertSelectionne?.en_attente === true;

            $('#btn-show').prop('disabled', !transfertSelectionne);
            $('#btn-valider, #btn-rejeter, #btn-annuler').prop('disabled', !enAttente);
        });

        // ── Création ───────────────────────────────────────────────────────

        $('#btn-add').on('click', function () {
            Stock.effacerErreurs($('#transfert-form'));
            $('#transfert-form')[0].reset();

            if (!articlesCharges) {
                $.getJSON(route('stock.transferts.articles'), function (reponse) {
                    const options = (reponse.data || []).map(
                        (article) => `<option value="${article.id}">${article.code_article} — ${article.designation}</option>`
                    );
                    $('#transfert-article').html('<option value="">Choisir…</option>' + options.join(''));
                    articlesCharges = true;
                });
            }

            modalTransfert.show();
        });

        $('#transfert-form').on('submit', function (event) {
            event.preventDefault();

            $.post(route('stock.transferts.store'), {
                magasin_source_id: $('#transfert-source').val(),
                magasin_destination_id: $('#transfert-destination').val(),
                article_id: $('#transfert-article').val(),
                quantite: $('#transfert-quantite').val(),
                motif_creation: $('#transfert-motif').val(),
            })
                .done(function (reponse) {
                    modalTransfert.hide();
                    Stock.succes(reponse.message);
                    $table.bootstrapTable('refresh');
                })
                .fail(Stock.gererEchec($('#transfert-form')));
        });

        // ── Validation (double mouvement atomique) ─────────────────────────

        $('#btn-valider').on('click', function () {
            if (!transfertSelectionne) return;

            Stock.confirmer({
                titre: 'Valider le transfert ?',
                texte: `Le transfert <b>${transfertSelectionne.numero_transfert}</b> déplacera `
                    + `<b>${transfertSelectionne.quantite}</b> unité(s) de ${transfertSelectionne.source} `
                    + `vers ${transfertSelectionne.destination}. Le coût FIFO source est conservé.`,
                icone: 'question',
                confirmer: 'Oui, valider',
                couleur: '#198754',
            }).then(function (resultat) {
                if (!resultat.isConfirmed) return;

                $.post(route('stock.transferts.valider', transfertSelectionne.id))
                    .done(function (reponse) {
                        Stock.succes(reponse.message);
                        $table.bootstrapTable('refresh');
                    })
                    .fail((xhr) => Stock.erreur(xhr.responseJSON?.message));
            });
        });

        // ── Rejet (motif obligatoire) ──────────────────────────────────────

        $('#btn-rejeter').on('click', function () {
            if (!transfertSelectionne) return;

            Stock.demanderMotif({
                titre: 'Rejeter le transfert ?',
                texte: `Le transfert <b>${transfertSelectionne.numero_transfert}</b> sera définitivement rejeté.`,
                confirmer: 'Rejeter',
                couleur: '#dc3545',
            }).then(function (motif) {
                if (!motif) return;

                $.post(route('stock.transferts.rejeter', transfertSelectionne.id), { motif })
                    .done(function (reponse) {
                        Stock.succes(reponse.message);
                        $table.bootstrapTable('refresh');
                    })
                    .fail((xhr) => Stock.erreur(xhr.responseJSON?.message));
            });
        });

        // ── Annulation ─────────────────────────────────────────────────────

        $('#btn-annuler').on('click', function () {
            if (!transfertSelectionne) return;

            Stock.confirmer({
                titre: 'Annuler le transfert ?',
                texte: `Le transfert <b>${transfertSelectionne.numero_transfert}</b> sera annulé sans mouvement de stock.`,
            }).then(function (resultat) {
                if (!resultat.isConfirmed) return;

                $.post(route('stock.transferts.annuler', transfertSelectionne.id))
                    .done(function (reponse) {
                        Stock.succes(reponse.message);
                        $table.bootstrapTable('refresh');
                    })
                    .fail((xhr) => Stock.erreur(xhr.responseJSON?.message));
            });
        });

        // ── Détail ─────────────────────────────────────────────────────────

        $('#btn-show').on('click', function () {
            if (!transfertSelectionne) return;

            $.getJSON(route('stock.transferts.show', transfertSelectionne.id), function (reponse) {
                const transfert = reponse.data;

                $('#detail-numero').text(transfert.numero_transfert);
                $('#detail-contenu').html(`
                    <dt class="col-sm-4">Article</dt><dd class="col-sm-8">${transfert.code_article} — ${transfert.article}</dd>
                    <dt class="col-sm-4">Source</dt><dd class="col-sm-8">${transfert.source}</dd>
                    <dt class="col-sm-4">Destination</dt><dd class="col-sm-8">${transfert.destination}</dd>
                    <dt class="col-sm-4">Quantité</dt><dd class="col-sm-8">${transfert.quantite}</dd>
                    <dt class="col-sm-4">Statut</dt><dd class="col-sm-8">${transfert.statut_label}</dd>
                    <dt class="col-sm-4">Motif</dt><dd class="col-sm-8">${transfert.motif_creation ?? '-'}</dd>
                    <dt class="col-sm-4">Motif de rejet</dt><dd class="col-sm-8">${transfert.motif_rejet ?? '-'}</dd>
                    <dt class="col-sm-4">Validé par</dt><dd class="col-sm-8">${transfert.validateur ?? '-'} ${transfert.date_validation ? '(' + transfert.date_validation + ')' : ''}</dd>
                    <dt class="col-sm-4">Mouvement sortant</dt><dd class="col-sm-8 font-monospace">${transfert.mouvement_sortant ?? '-'}</dd>
                    <dt class="col-sm-4">Mouvement entrant</dt><dd class="col-sm-8 font-monospace">${transfert.mouvement_entrant ?? '-'}</dd>
                    <dt class="col-sm-4">Créé le</dt><dd class="col-sm-8">${transfert.created_at} par ${transfert.createur ?? '-'}</dd>
                `);
                modalDetail.show();
            });
        });

        // ── Tooltips ───────────────────────────────────────────────────────

        $('[data-bs-toggle="tooltip"]').each(function () {
            new bootstrap.Tooltip(this);
        });
    });

    // Formatter spécifique à l'écran (window.*, hors DOMContentLoaded).
    window.statutTransfertFormatter = function (valeur, ligne) {
        const classeTexte = ligne.statut_color === 'warning' ? ' text-dark' : '';
        return `<span class="badge bg-${ligne.statut_color}${classeTexte}">${ligne.statut_label}</span>`;
    };
})(jQuery);
