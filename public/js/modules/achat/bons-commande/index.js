/**
 * index.js — A-02, liste des bons de commande (SPEC_UX A-02).
 *
 * Pattern du projet (Core rôles, Stock entrées) : on SÉLECTIONNE une ligne
 * (radio, click-to-select), puis la TOOLBAR agit dessus. Les boutons sans
 * droit sont absents du HTML (@can), ceux bloqués par l'état de la ligne
 * sélectionnée restent grisés avec leur diagnostic en infobulle (SPEC_UX §0.3).
 *
 * Le serveur décide de tout ce qui engage : les drapeaux peut_* de chaque
 * ligne viennent de la grille ActionsBonCommande, et chaque POST revérifie
 * permission et état.
 */
import '../../catalogue/formatters.js';
import { ModalPdf } from '../shared/modal-pdf.js';
import { ActionsBc } from '../shared/actions-bc.js';

const echapper = (texte) => $('<span>').text(texte ?? '').html();

/** Numéro : définitif en monospace, brouillon en italique gris (convention Stock). */
window.bcNumeroFormatter = function (value, row) {
    const regularisation = row.est_regularisation
        ? '<span class="pictogramme-regularisation me-1" title="Bon de régularisation (période d\'intérim)" data-bs-toggle="tooltip"></span>'
        : '';

    const numero = String(value ?? '').startsWith('Brouillon')
        ? `<em class="text-muted">${echapper(value)}</em>`
        : `<span class="font-monospace">${echapper(value)}</span>`;

    return `${regularisation}${numero}`;
};

/** Montant TTC : qualifié explicitement, jamais un nombre nu (SPEC_UX §0.4). */
window.bcMontantTtcFormatter = function (value) {
    if (value === null || value === undefined) return '—';
    return `${Number(value).toLocaleString('fr-FR', { maximumFractionDigits: 0 })} <small class="text-muted">FCFA TTC</small>`;
};

/**
 * Mini-barre de progression des livraisons. Absente avant validation : un bon
 * non engagé n'a rien à livrer, une barre à 0 % y serait trompeuse.
 */
window.bcProgressionFormatter = function (value) {
    if (!value) return '<span class="text-muted">—</span>';

    const couleur = value.pourcentage >= 100 ? 'bg-success' : 'bg-warning';
    const libelle = `${value.livre.toLocaleString('fr-FR')}/${value.commande.toLocaleString('fr-FR')} unités`;

    return `<div title="${libelle}" data-bs-toggle="tooltip">
        <div class="progress barre-livraison" role="progressbar"
             aria-label="Progression de livraison" aria-valuenow="${value.pourcentage}"
             aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar ${couleur}" style="width:${Math.min(value.pourcentage, 100)}%"></div>
        </div>
        <small class="text-muted">${libelle}</small>
    </div>`;
};

/** Pilule de statut : couleur + texte (le texte suffit sans la couleur). */
window.bcStatutFormatter = function (value, row) {
    // « orange » n'existe pas dans la palette Bootstrap : PARTIEL emprunte
    // warning, qui porte la même intention de vigilance (DESIGN.md).
    const couleur = row.statut_couleur === 'orange' ? 'warning text-dark' : row.statut_couleur;
    const barre = row.statut === 'ANNULE' ? ' text-decoration-line-through' : '';

    return `<span class="badge bg-${couleur}${barre}">${echapper(row.statut_label)}</span>`;
};

$(function () {
    const $table = $('#bons-commande-table');
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

    /**
     * État des boutons selon la ligne sélectionnée. Un bouton grisé par
     * l'ÉTAT porte son diagnostic en infobulle ; un bouton sans droit
     * n'existe pas dans le HTML (SPEC_UX §0.3).
     */
    const rafraichirToolbar = () => {
        const sel = $table.bootstrapTable('getSelections');
        const ligne = sel.length === 1 ? sel[0] : null;

        const configurer = (id, actif, diagnostic) => {
            const $bouton = $(id);
            if ($bouton.length === 0) return;
            $bouton.prop('disabled', !ligne || !actif)
                .attr('title', (!ligne || actif) ? ($bouton.data('titre-initial') ?? '') : (diagnostic ?? ''));
        };

        // Mémoriser le libellé d'origine des infobulles au premier passage.
        $('#toolbar button').each(function () {
            if ($(this).data('titre-initial') === undefined) {
                $(this).data('titre-initial', $(this).attr('title') ?? '');
            }
        });

        configurer('#btn-show', ligne?.peut_voir);
        configurer('#btn-edit', ligne?.peut_modifier, ligne?.diagnostic_modification);
        configurer('#btn-delete', ligne?.peut_supprimer, ligne?.diagnostic_modification);
        configurer('#btn-soumettre', ligne?.peut_soumettre, ligne?.diagnostic_soumission);
        configurer('#btn-reprendre', ligne?.peut_reprendre);
        configurer('#btn-valider', ligne?.peut_valider);
        configurer('#btn-renvoyer', ligne?.peut_renvoyer);
        configurer('#btn-imprimer', ligne?.peut_imprimer, 'Le PDF n\'existe qu\'après validation');
    };

    $table.on('check.bs.table uncheck.bs.table load-success.bs.table', rafraichirToolbar);

    // ── Navigation ─────────────────────────────────────────────────────────

    $('#btn-show').on('click', () => {
        const row = selection();
        if (!row) return;
        window.location.href = route('achat.bons-commande.show', row.id);
    });

    $('#btn-edit').on('click', () => {
        const row = selection();
        if (!row) return;
        window.location.href = route('achat.bons-commande.edit', row.id);
    });

    // ── Impression : modale iframe (pattern du projet), jamais un onglet ───

    $('#btn-imprimer').on('click', () => {
        const row = selection();
        if (!row) return;

        if (!row.url_pdf) {
            Swal.fire({ icon: 'info', title: 'PDF indisponible', text: 'Le PDF n\'existe qu\'après validation.' });
            return;
        }

        ModalPdf.ouvrir({ url: row.url_pdf, titre: `Bon de commande ${row.numero_affiche}` });
    });

    // ── Commandes (transitions d'état) ─────────────────────────────────────
    //
    // Les confirmations (SW-02, SW-04, M-06) vivent dans le module partagé
    // ActionsBc, commun à la liste et à la fiche A-04 : une seule source de
    // vérité pour chaque Swal. Ici, le rappel rafraîchit le tableau.

    const apres = () => $table.bootstrapTable('refresh');

    $('#btn-delete').on('click', () => {
        const row = selection();
        if (!row) return;
        ActionsBc.supprimer(row, route('achat.bons-commande.destroy', row.id), apres);
    });

    $('#btn-soumettre').on('click', () => {
        const row = selection();
        if (!row) return;
        // SW-01 vit sur le récapitulatif : la confirmation chiffrée exige la
        // lecture des lignes, qu'une ligne de tableau ne porte pas.
        window.location.href = route('achat.bons-commande.recapitulatif', row.id);
    });

    $('#btn-reprendre').on('click', () => {
        const row = selection();
        if (!row) return;
        ActionsBc.reprendre(row, route('achat.bons-commande.reprendre', row.id), apres);
    });

    $('#btn-renvoyer').on('click', () => {
        const row = selection();
        if (!row) return;
        ActionsBc.renvoyer(row, route('achat.bons-commande.renvoyer', row.id), apres);
    });

    $('#btn-valider').on('click', () => {
        const row = selection();
        if (!row) return;
        ActionsBc.valider(
            row,
            route('achat.bons-commande.signaux', row.id),
            route('achat.bons-commande.valider', row.id),
            apres
        );
    });

    // ── Filtres et pied de tableau ─────────────────────────────────────────

    const filtres = () => ({
        statut: $('#filter-statut input:checked').map((_, e) => e.value).get(),
        fournisseur_id: $('#filter-fournisseur').val(),
        du: $('#filter-du').val(),
        au: $('#filter-au').val(),
        regularisations: $('#filter-regularisations').is(':checked') ? 1 : 0,
        mes_brouillons: $('#filter-mes-brouillons').is(':checked') ? 1 : 0,
        search: $('#filter-recherche').val(),
    });

    $table.bootstrapTable('refreshOptions', {
        queryParams: (params) => Object.assign(params, filtres()),
    });

    // Pied de tableau et EV-02 : les deux décrivent le jeu FILTRÉ, dont seul
    // le serveur connaît l'étendue réelle.
    $table.on('load-success.bs.table', function (_, reponse) {
        const total = reponse.total ?? 0;
        const montant = Number(reponse.montant_ttc_affiche ?? 0);

        $('#pied-compteur').text(`${total.toLocaleString('fr-FR')} bon${total > 1 ? 's' : ''}`);
        $('#pied-total').text(
            `Total affiché : ${montant.toLocaleString('fr-FR', { maximumFractionDigits: 0 })} FCFA TTC`
        );

        // Message de la recherche « tous formats » : dire ce qui a été
        // compris évite un tableau vide inexpliqué (UX3-01).
        const message = reponse.recherche?.message;
        $('#recherche-aide')
            .text(message ?? 'Tous les formats de numéro sont acceptés.')
            .toggleClass('text-info', Boolean(message));

        $etatVide.toggleClass('d-none', total > 0);

        $('[data-bs-toggle="tooltip"]').each((_, element) => new bootstrap.Tooltip(element));
    });

    const rafraichir = () => $table.bootstrapTable('refresh');

    $('#filter-statut input, #filter-fournisseur, #filter-du, #filter-au, #filter-regularisations, #filter-mes-brouillons')
        .on('change', rafraichir);

    // Recherche différée : on interroge le serveur quand la frappe s'arrête.
    let minuterie = null;
    $('#filter-recherche').on('input', () => {
        clearTimeout(minuterie);
        minuterie = setTimeout(rafraichir, 350);
    });

    $('#btn-reinitialiser').on('click', () => {
        $('#filter-statut input').prop('checked', false);
        $('#filter-fournisseur, #filter-du, #filter-au, #filter-recherche').val('');
        $('#filter-regularisations, #filter-mes-brouillons').prop('checked', false);
        rafraichir();
    });
});
