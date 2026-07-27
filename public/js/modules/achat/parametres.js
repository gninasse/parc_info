/**
 * Écran Paramètres du module Achat (EF-ADM-01→03).
 */
(function ($) {
    'use strict';

    let parametreSelectionne = null;

    document.addEventListener('DOMContentLoaded', function () {
        const $table = $('#parametres-table');
        const modal = new bootstrap.Modal('#parametreModal');

        $table.on('check.bs.table uncheck.bs.table load-success.bs.table', function () {
            const selection = $table.bootstrapTable('getSelections');
            parametreSelectionne = selection.length ? selection[0] : null;

            // EF-ADM-03 — un paramètre verrouillé n'est pas éditable.
            $('#btn-edit').prop('disabled', !parametreSelectionne || !parametreSelectionne.modifiable);
        });

        $('#btn-edit').on('click', function () {
            if (!parametreSelectionne) return;

            Achat.effacerErreurs($('#parametre-form'));
            $('#parametre-libelle').text(parametreSelectionne.libelle);
            $('#parametre-description').text(parametreSelectionne.description || '');
            $('#parametre-valeur').val(parametreSelectionne.valeur);
            modal.show();
        });

        $('#parametre-form').on('submit', function (event) {
            event.preventDefault();

            $.ajax({
                url: route('achat.parametres.update', parametreSelectionne.id),
                method: 'PUT',
                data: { valeur: $('#parametre-valeur').val() },
            })
                .done(function (reponse) {
                    modal.hide();
                    Achat.succes(reponse.message);
                    $table.bootstrapTable('refresh');
                })
                .fail(function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Achat.afficherErreurs($('#parametre-form'), xhr.responseJSON.errors);
                        return;
                    }
                    Achat.erreur(xhr.responseJSON?.message);
                });
        });

        $('[data-bs-toggle="tooltip"]').each(function () {
            new bootstrap.Tooltip(this);
        });
    });

    // Formatters (window.*, hors DOMContentLoaded).
    window.valeurFormatter = function (valeur) {
        return `<span class="badge bg-light text-dark border font-monospace">${valeur}</span>`;
    };

    window.modifiableFormatter = function (valeur) {
        return valeur
            ? '<span class="badge bg-success">Oui</span>'
            : '<span class="badge bg-secondary"><i class="fas fa-lock me-1"></i>Verrouillé</span>';
    };
})(jQuery);
