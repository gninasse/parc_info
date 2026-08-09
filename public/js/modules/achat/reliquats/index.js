/**
 * index.js — A-06, écran des reliquats (SPEC_UX A-06, maquette P-06).
 *
 * Lecture seule, sauf la clôture — qui porte sur le BON, pas sur la ligne :
 * on renonce à tout le reste d'une commande, motif à l'appui (M-03). Le
 * Swal est le MÊME que celui de la fiche et de la liste (ActionsBc) : une
 * seule description du geste dans toute l'application.
 */
import { ActionsBc } from '../shared/actions-bc.js';

const echapper = (texte) => $('<span>').text(texte ?? '').html();

/** Numéro du BC, en monospace, lien vers la fiche. */
window.reliquatNumeroFormatter = function (value, row) {
    const numero = `<span class="font-monospace">${echapper(value)}</span>`;
    const statut = row.statut === 'PARTIEL'
        ? ' <span class="badge bg-warning text-dark">Partiel</span>'
        : ' <span class="badge bg-primary">Validé</span>';

    return row.url_bon ? `<a href="${row.url_bon}">${numero}</a>${statut}` : numero + statut;
};

/** Reste : le chiffre qui compte, avec son montant restant. */
window.reliquatResteFormatter = function (value, row) {
    const reste = Number(value).toLocaleString('fr-FR', { maximumFractionDigits: 2 });
    const montant = Number(row.montant_reste_ht).toLocaleString('fr-FR', { maximumFractionDigits: 0 });

    return `<strong>${reste}</strong><div class="small text-muted">${montant} FCFA HT</div>`;
};

/**
 * Badge d'âge : la couleur vient du SERVEUR (seuils paramétrés A-08), le
 * navigateur ne décide pas de ce qui est en retard.
 */
window.reliquatAgeFormatter = function (value, row) {
    if (value === null || value === undefined) return '<span class="text-muted">—</span>';

    return `<span class="badge bg-${row.age_couleur}">${value} j</span>`;
};

window.reliquatDateFormatter = function (value) {
    return value ?? '<span class="text-muted">—</span>';
};

$(function () {
    const $table = $('#reliquats-table');
    const $etatVide = $('#etat-vide');

    // ── Sélection : la ligne choisie pilote la toolbar ─────────────────────

    const selection = () => {
        const sel = $table.bootstrapTable('getSelections');
        if (!sel.length) {
            Swal.fire({ icon: 'warning', title: 'Attention', text: 'Veuillez sélectionner une ligne.' });
            return null;
        }
        return sel[0];
    };

    const rafraichirToolbar = () => {
        const sel = $table.bootstrapTable('getSelections');
        const ligne = sel.length === 1 ? sel[0] : null;

        $('#btn-voir-bc').prop('disabled', !ligne);
        $('#btn-cloturer')
            .prop('disabled', !ligne || !ligne.peut_cloturer)
            .attr('title', ligne && !ligne.peut_cloturer
                ? 'La clôture ne concerne que les bons partiellement livrés'
                : 'Clôturer le reliquat');
    };

    $table.on('check.bs.table uncheck.bs.table load-success.bs.table', rafraichirToolbar);

    $('#btn-voir-bc').on('click', () => {
        const row = selection();
        if (row?.url_bon) window.location.href = row.url_bon;
    });

    $('#btn-cloturer').on('click', () => {
        const row = selection();
        if (!row) return;

        ActionsBc.cloturer(
            { id: row.bon_commande_id, numero_affiche: row.numero },
            route('achat.bons-commande.cloturer', row.bon_commande_id),
            () => $table.bootstrapTable('refresh')
        );
    });

    // ── Filtres ───────────────────────────────────────────────────────────

    const filtres = () => ({
        fournisseur_id: $('#filter-fournisseur').val(),
        age_min: $('#filter-age input:checked').val(),
        search: $('#filter-recherche').val(),
    });

    $table.bootstrapTable('refreshOptions', {
        queryParams: (params) => Object.assign(params, filtres()),
    });

    const rafraichir = () => $table.bootstrapTable('refresh');

    $('#filter-fournisseur').on('change', rafraichir);
    $('#filter-age input').on('change', rafraichir);

    let minuterie = null;
    $('#filter-recherche').on('input', () => {
        clearTimeout(minuterie);
        minuterie = setTimeout(rafraichir, 350);
    });

    // ── Pied de tableau et EV-04 ──────────────────────────────────────────

    $table.on('load-success.bs.table', function (_, reponse) {
        const total = reponse.total ?? 0;
        const engage = Number(reponse.engage_non_livre_ttc ?? 0);

        $('#pied-compteur').text(`${total.toLocaleString('fr-FR')} ligne${total > 1 ? 's' : ''} en attente de livraison`);
        $('#pied-engage').text(
            `Engagé non livré : ${engage.toLocaleString('fr-FR', { maximumFractionDigits: 0 })} FCFA TTC`
        );

        // L'état vide est une bonne nouvelle : on masque le tableau ET le pied.
        $etatVide.toggleClass('d-none', total > 0);
        $table.closest('.card').toggleClass('d-none', total === 0);

        $('[data-bs-toggle="tooltip"]').each((_, element) => new bootstrap.Tooltip(element));
    });

    // L'export suit les filtres : le fichier décrit ce que l'écran montre.
    $('#btn-export').on('click', function (e) {
        e.preventDefault();
        const params = new URLSearchParams(
            Object.entries(filtres()).filter(([, valeur]) => valeur !== '' && valeur !== undefined)
        );
        window.location.href = `${$(this).attr('href')}?${params.toString()}`;
    });
});
