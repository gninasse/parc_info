/**
 * index.js — point d'entrée Sorties
 */
import { SortieForm } from './SortieForm.js';

// Les formatters DOIVENT être en window.* et AVANT DOMContentLoaded
window.dateFormatter = function (value) {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('fr-FR', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit'
    });
};

$(function () {
    const $table = $('#sorties-table');

    const tableInstance = {
        refresh: () => $table.bootstrapTable('refresh')
    };

    const form = new SortieForm('#sortieModal', '#sortie-form', tableInstance);

    $('#btn-add-sortie').on('click', () => form.openForAdd());

    // Filtres externes
    $('#filter-magasin').on('change', () => $table.bootstrapTable('refresh'));
    
    $table.bootstrapTable('refreshOptions', {
        queryParams: function (params) {
            params.magasin_id = $('#filter-magasin').val();
            return params;
        }
    });
});
