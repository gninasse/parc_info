/**
 * index.js — page Articles (KPI, filtres cascadés, table)
 */
import '../formatters.js'; // badges nature/statut, FCFA, seuil (partagés module)
import { ArticleForm } from './ArticleForm.js';
import { ArticleActions } from './ArticleActions.js';

$(function () {
    const $table = $('#articles-table');

    const tableInstance = {
        refresh: () => $table.bootstrapTable('refresh'),
        getSelectedId: () => {
            const sel = $table.bootstrapTable('getSelections');
            if (!sel.length) {
                Swal.fire({ icon: 'warning', title: 'Attention', text: 'Veuillez sélectionner une ligne.' });
                return null;
            }
            return sel[0].id;
        },
    };

    const form = new ArticleForm('#articleModal', '#article-form', tableInstance);
    new ArticleActions(tableInstance, form);

    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const sel = $table.bootstrapTable('getSelections');
        $('#btn-edit, #btn-duplicate, #btn-toggle, #btn-delete').prop('disabled', sel.length !== 1);
    });

    // KPI rafraîchis à chaque chargement de données (le serveur les joint)
    $table.on('load-success.bs.table', function (event, data) {
        if (!data.kpis) return;
        Object.entries(data.kpis).forEach(([nature, nb]) => $(`#kpi-${nature}`).text(nb));
    });

    // Cascade catégorie → sous-catégorie
    let cascade = [];
    $.getJSON(route('catalogue.articles.categories-cascade'), (res) => {
        cascade = res.data ?? [];
        const $categorie = $('#filter-categorie');
        cascade.forEach((racine) => $categorie.append(new Option(racine.libelle, racine.id)));
    });

    $('#filter-categorie').on('change', function () {
        const racine = cascade.find((c) => String(c.id) === String($(this).val()));
        const $sous = $('#filter-sous-categorie');
        $sous.find('option:not(:first)').remove();
        $sous.prop('disabled', !racine || !racine.enfants.length);
        (racine?.enfants ?? []).forEach((enfant) => $sous.append(new Option(enfant.libelle, enfant.id)));
        $sous.val('');
        $table.bootstrapTable('refresh');
    });

    $('#filter-nature, #filter-sous-categorie, #filter-fournisseur, #filter-statut')
        .on('change', () => $table.bootstrapTable('refresh'));

    // Pré-filtrage par l'URL : « Voir tous → » de la fiche fournisseur
    // (?fournisseur_id=X) et liens ParcInfo/redirections (?nature=consommable)
    const parametres = new URLSearchParams(window.location.search);
    if (parametres.get('nature')) $('#filter-nature').val(parametres.get('nature'));
    if (parametres.get('fournisseur_id')) $('#filter-fournisseur').val(parametres.get('fournisseur_id'));

    $table.bootstrapTable('refreshOptions', {
        queryParams: function (params) {
            params.nature = $('#filter-nature').val();
            params.categorie_id = $('#filter-sous-categorie').val() || $('#filter-categorie').val();
            params.fournisseur_id = $('#filter-fournisseur').val();
            params.est_actif = $('#filter-statut').val();
            return params;
        },
    });
});
