/**
 * index.js — État des stocks (UX §2) : filtres (magasin, nature, catégorie en
 * cascade Catalogue, statut d'alerte en segmented control), lignes sous
 * inventaire grisées + cadenas, exports contextualisés, modale MD-SEUIL.
 */
import '../shared/formatters.js';
import { SeuilModal } from './SeuilModal.js';

/** Ligne sous inventaire : grisée + tooltip (le cadenas est dans la colonne Actions). */
window.niveauRowAttributes = function (row) {
    if (!row.sous_inventaire) return {};
    return {
        class: 'ligne-sous-inventaire',
        title: `Article en cours d'inventaire (${row.inventaire_reference})`,
    };
};

/** Actions de ligne : 🎚 seuil (permission + hors inventaire), cadenas sinon. */
window.niveauActionsFormatter = function (value, row) {
    if (row.sous_inventaire) {
        return `<i class="bi bi-lock-fill text-muted" data-bs-toggle="tooltip" title="Article en cours d'inventaire (${row.inventaire_reference})"></i>`;
    }
    if (!window.PEUT_AJUSTER_SEUIL) return '—';
    return `<button class="btn btn-outline-secondary btn-sm btn-seuil" data-id="${row.id}" data-bs-toggle="tooltip" title="Ajuster le seuil">
        <i class="bi bi-sliders"></i>
    </button>`;
};

$(function () {
    const $table = $('#niveaux-table');

    const filtres = () => ({
        magasin_id: $('#filter-magasin').val(),
        nature: $('#filter-nature').val(),
        categorie_id: $('#filter-categorie').val(),
        statut: $('#filter-statut input:checked').val(),
    });

    $table.bootstrapTable('refreshOptions', {
        queryParams: (params) => Object.assign(params, filtres()),
    });

    $('#filter-magasin, #filter-nature, #filter-categorie').on('change', () => $table.bootstrapTable('refresh'));
    $('#filter-statut input').on('change', () => $table.bootstrapTable('refresh'));

    // Catégories Catalogue (cascade 2 niveaux — S11, via l'API inter-modules)
    $.getJSON('/catalogue/api/categories', (res) => {
        const $categorie = $('#filter-categorie');
        (res.data ?? []).forEach((parent) => {
            $categorie.append(new Option(parent.libelle, parent.id));
            (parent.enfants ?? []).forEach((enfant) => {
                $categorie.append(new Option(`— ${enfant.libelle}`, enfant.id));
            });
        });
    });

    // Modale MD-SEUIL
    const seuilModal = new SeuilModal({
        magasins: $('#filter-magasin option').toArray()
            .filter((option) => option.value !== '')
            .map((option) => ({ id: option.value, libelle: option.text })),
        onSaved: () => $table.bootstrapTable('refresh'),
    });

    $table.on('click', '.btn-seuil', function () {
        const id = Number($(this).data('id'));
        const row = $table.bootstrapTable('getData').find((ligne) => ligne.id === id);
        if (row) seuilModal.openForNiveau(row);
    });

    $('#btn-seuil-article').on('click', () => seuilModal.openForArticle());

    // Exports : mêmes filtres que la table (amendement n°18 côté serveur)
    $('.export-lien').on('click', function (e) {
        e.preventDefault();
        const params = new URLSearchParams(
            Object.entries({ ...filtres(), format: $(this).data('format'), search: $('.search-input').val() ?? '' })
                .filter(([, valeur]) => valeur !== undefined && valeur !== null && valeur !== '')
        );
        window.location.href = `${route('stock.niveaux.export')}?${params.toString()}`;
    });
});
