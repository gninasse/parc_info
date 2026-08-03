/**
 * show.js — fiche magasin (UX §9) : init des 3 mini-tables d'onglets,
 * édition et toggle depuis l'en-tête.
 */
import '../shared/formatters.js';
import { MagasinForm } from './MagasinForm.js';

$(function () {
    // Les tables des onglets s'initialisent à l'affichage (largeur correcte)
    const initialisees = new Set();

    const initTable = ($table) => {
        if (!$table.length || initialisees.has($table.attr('id'))) return;
        initialisees.add($table.attr('id'));
        $table.bootstrapTable({ locale: 'fr-FR' });
    };

    initTable($('#table-stocks'));

    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', (e) => {
        const cible = $(e.target).data('bs-target');
        if (cible === '#onglet-equipements') initTable($('#table-equipements'));
        if (cible === '#onglet-mouvements') initTable($('#table-mouvements'));
    });

    const form = new MagasinForm('#magasinModal', '#magasin-form', {
        onSaved: () => window.location.reload(),
    });

    $('#btn-edit').on('click', () => {
        $.getJSON(route('stock.magasins.show', window.MAGASIN_ID), (res) => {
            if (res.success) form.openForEdit(res.data);
        });
    });

    $('#btn-toggle').on('click', () => {
        $.ajax({
            url: route('stock.magasins.toggle-status', window.MAGASIN_ID),
            method: 'PATCH',
            dataType: 'json',
            success: (res) => {
                if (res.success) window.location.reload();
            },
            error: (xhr) => {
                const reponse = xhr.responseJSON ?? {};
                Swal.fire({
                    icon: 'error',
                    title: 'Opération refusée',
                    text: reponse.message ?? 'Changement de statut impossible.',
                    showCancelButton: Boolean(reponse.action),
                    cancelButtonText: 'Fermer',
                    confirmButtonText: reponse.action?.label ?? 'Fermer',
                }).then((result) => {
                    if (result.isConfirmed && reponse.action?.url) window.location.href = reponse.action.url;
                });
            },
        });
    });
});
