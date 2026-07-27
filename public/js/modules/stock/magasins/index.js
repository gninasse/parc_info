/**
 * Écran Magasins (F1) — liste, création, édition, responsables, statut.
 *
 * PATTERNS §9 : boutons de toolbar désactivés par défaut, activés selon la
 * sélection ; édition pré-remplie via show() ; suppression confirmée par Swal.
 */
(function ($) {
    'use strict';

    let magasinSelectionne = null;
    let employesCharges = false;

    document.addEventListener('DOMContentLoaded', function () {
        const $table = $('#magasins-table');
        const modalMagasin = new bootstrap.Modal('#magasinModal');
        const modalResponsables = new bootstrap.Modal('#responsablesModal');

        // ── Filtres ────────────────────────────────────────────────────────

        $table.bootstrapTable('refreshOptions', {
            queryParams: function (params) {
                params.statut = $('#filter-statut').val();
                return params;
            },
        });

        $('#filter-statut').on('change', () => $table.bootstrapTable('refresh'));

        // ── Sélection ──────────────────────────────────────────────────────

        $table.on('check.bs.table uncheck.bs.table load-success.bs.table', function () {
            const selection = $table.bootstrapTable('getSelections');
            magasinSelectionne = selection.length ? selection[0] : null;

            $('#btn-edit, #btn-responsables, #btn-toggle, #btn-delete').prop('disabled', !magasinSelectionne);
        });

        // ── Création ───────────────────────────────────────────────────────

        $('#btn-add').on('click', function () {
            Stock.effacerErreurs($('#magasin-form'));
            $('#magasin-form')[0].reset();
            $('#magasin-id').val('');
            $('#magasin-code').prop('disabled', false);
            $('#magasin-modal-titre').text('Créer un magasin');
            modalMagasin.show();
        });

        // ── Édition (GET show avant ouverture — PATTERNS §9.4) ─────────────

        $('#btn-edit').on('click', function () {
            if (!magasinSelectionne) return;

            $.getJSON(route('stock.magasins.show', magasinSelectionne.id), function (reponse) {
                const magasin = reponse.data;

                Stock.effacerErreurs($('#magasin-form'));
                $('#magasin-id').val(magasin.id);
                $('#magasin-code').val(magasin.code).prop('disabled', true);
                $('#magasin-libelle').val(magasin.libelle);
                $('#magasin-description').val(magasin.description);
                $('#magasin-modal-titre').text(`Modifier ${magasin.code}`);
                modalMagasin.show();
            });
        });

        // ── Enregistrement ─────────────────────────────────────────────────

        $('#magasin-form').on('submit', function (event) {
            event.preventDefault();

            const id = $('#magasin-id').val();
            const donnees = {
                code: $('#magasin-code').val(),
                libelle: $('#magasin-libelle').val(),
                description: $('#magasin-description').val(),
            };

            const requete = id
                ? $.ajax({ url: route('stock.magasins.update', id), method: 'PUT', data: donnees })
                : $.post(route('stock.magasins.store'), donnees);

            requete
                .done(function (reponse) {
                    modalMagasin.hide();
                    Stock.succes(reponse.message);
                    $table.bootstrapTable('refresh');
                })
                .fail(Stock.gererEchec($('#magasin-form')));
        });

        // ── Activation / désactivation ─────────────────────────────────────

        $('#btn-toggle').on('click', function () {
            if (!magasinSelectionne) return;

            const activer = magasinSelectionne.statut !== 'actif';
            const action = activer ? 'activer' : 'désactiver';

            Stock.confirmer({
                titre: `${activer ? 'Activer' : 'Désactiver'} le magasin ?`,
                texte: activer
                    ? `Le magasin <b>${magasinSelectionne.code}</b> acceptera de nouveau les mouvements.`
                    : `Le magasin <b>${magasinSelectionne.code}</b> n'acceptera plus aucun mouvement (RG-F1-03).`,
                confirmer: `Oui, ${action}`,
                couleur: activer ? '#198754' : '#ffc107',
            }).then(function (resultat) {
                if (!resultat.isConfirmed) return;

                $.post(route(`stock.magasins.${activer ? 'activer' : 'desactiver'}`, magasinSelectionne.id))
                    .done(function (reponse) {
                        Stock.succes(reponse.message);
                        $table.bootstrapTable('refresh');
                    })
                    .fail((xhr) => Stock.erreur(xhr.responseJSON?.message));
            });
        });

        // ── Suppression ────────────────────────────────────────────────────

        $('#btn-delete').on('click', function () {
            if (!magasinSelectionne) return;

            Stock.confirmer({
                titre: 'Supprimer le magasin ?',
                texte: `Le magasin <b>${magasinSelectionne.code}</b> sera supprimé. Cette action est réservée aux magasins sans stock ni mouvement.`,
            }).then(function (resultat) {
                if (!resultat.isConfirmed) return;

                $.ajax({ url: route('stock.magasins.destroy', magasinSelectionne.id), method: 'DELETE' })
                    .done(function (reponse) {
                        Stock.succes(reponse.message);
                        $table.bootstrapTable('refresh');
                    })
                    .fail((xhr) => Stock.erreur(xhr.responseJSON?.message));
            });
        });

        // ── Responsables ───────────────────────────────────────────────────

        function chargerResponsables(magasinId) {
            $.getJSON(route('stock.magasins.show', magasinId), function (reponse) {
                const responsables = reponse.data.responsables || [];

                const lignes = responsables.map(function (responsable) {
                    const badge = responsable.role === 'principal'
                        ? '<span class="badge bg-primary">Principal</span>'
                        : '<span class="badge bg-light text-dark border">Adjoint</span>';

                    return `<tr>
                        <td>${responsable.employe ?? '-'}</td>
                        <td>${badge}</td>
                        <td>${responsable.date_debut ?? '-'}</td>
                        <td>${responsable.date_fin ?? '-'}</td>
                        <td class="text-end">
                            <button class="btn btn-xs btn-outline-danger btn-retirer-responsable"
                                    data-id="${responsable.id}" title="Retirer">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                    </tr>`;
                });

                $('#responsables-liste').html(
                    lignes.length
                        ? lignes.join('')
                        : '<tr><td colspan="5" class="text-center text-muted py-3">Aucun responsable.</td></tr>'
                );
            });
        }

        $('#btn-responsables').on('click', function () {
            if (!magasinSelectionne) return;

            $('#responsables-magasin-libelle').text(`${magasinSelectionne.code} — ${magasinSelectionne.libelle}`);
            $('#responsable-form').data('magasin-id', magasinSelectionne.id);
            chargerResponsables(magasinSelectionne.id);

            if (!employesCharges) {
                $.getJSON(route('stock.magasins.employes'), function (reponse) {
                    const options = (reponse.data || []).map(
                        (employe) => `<option value="${employe.id}">${employe.matricule} — ${employe.nom_complet}</option>`
                    );
                    $('#responsable-employe').html('<option value="">Choisir…</option>' + options.join(''));
                    employesCharges = true;
                });
            }

            modalResponsables.show();
        });

        $('#responsable-form').on('submit', function (event) {
            event.preventDefault();

            const magasinId = $(this).data('magasin-id');

            $.post(route('stock.magasins.responsables.store', magasinId), {
                employe_id: $('#responsable-employe').val(),
                role: $('#responsable-role').val(),
                date_debut: $('#responsable-date-debut').val(),
            })
                .done(function (reponse) {
                    Stock.succes(reponse.message);
                    chargerResponsables(magasinId);
                    $table.bootstrapTable('refresh');
                })
                .fail(Stock.gererEchec($('#responsable-form')));
        });

        $('#responsables-liste').on('click', '.btn-retirer-responsable', function () {
            const responsableId = $(this).data('id');
            const magasinId = $('#responsable-form').data('magasin-id');

            Stock.confirmer({
                titre: 'Retirer ce responsable ?',
                texte: 'Le responsable sera retiré du magasin.',
            }).then(function (resultat) {
                if (!resultat.isConfirmed) return;

                $.ajax({
                    url: route('stock.magasins.responsables.destroy', [magasinId, responsableId]),
                    method: 'DELETE',
                })
                    .done(function (reponse) {
                        Stock.succes(reponse.message);
                        chargerResponsables(magasinId);
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
    window.responsableFormatter = function (valeur) {
        return valeur ? valeur : '<span class="text-muted">—</span>';
    };
})(jQuery);
