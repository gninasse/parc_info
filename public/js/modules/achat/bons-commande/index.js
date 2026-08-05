/**
 * index.js — A-02, liste des bons de commande (SPEC_UX A-02).
 *
 * Le serveur décide de tout ce qui engage : quelles actions sont permises,
 * quels totaux afficher, quel format de recherche a été reconnu. Ce fichier
 * ne fait que peindre la réponse et relayer les filtres.
 */
import '../../catalogue/formatters.js';

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

/**
 * Boutons d'action. La grille vient du serveur (ActionsBonCommande) : une
 * action absente de la charge utile est absente de l'écran, et une action
 * inactive porte toujours son diagnostic en infobulle (SPEC_UX §0.3).
 */
window.bcActionsFormatter = function (actions, row) {
    if (!Array.isArray(actions) || actions.length === 0) return '—';

    const boutons = actions.map((action) => {
        const titre = echapper(action.titre);
        const icone = `<i class="bi ${echapper(action.icone)}"></i>`;

        // Inactive, ou active mais dont l'écran cible n'existe pas encore :
        // bouton désactivé plutôt qu'une ancre morte.
        if (!action.actif || !action.url) {
            return `<button type="button" class="btn btn-sm ${echapper(action.classe)}" disabled
                        aria-label="${titre}" title="${titre}" data-bs-toggle="tooltip">${icone}</button>`;
        }

        // Une transition de statut est un POST ou un DELETE : la rendre en
        // lien produirait un GET, donc un 405. Ces actions passent par un
        // bouton de commande, confirmé puis envoyé en AJAX.
        if ((action.methode || 'GET') !== 'GET') {
            return `<button type="button" class="btn btn-sm ${echapper(action.classe)} bc-commande"
                        data-url="${echapper(action.url)}" data-methode="${echapper(action.methode)}"
                        data-cle="${echapper(action.cle)}" data-id="${row.id}"
                        data-numero="${echapper(row.numero_affiche)}"
                        aria-label="${titre}" title="${titre}" data-bs-toggle="tooltip">${icone}</button>`;
        }

        return `<a href="${echapper(action.url)}" class="btn btn-sm ${echapper(action.classe)}"
                   aria-label="${titre}" title="${titre}" data-bs-toggle="tooltip">${icone}</a>`;
    });

    return `<div class="btn-group btn-group-sm" role="group">${boutons.join('')}</div>`;
};

$(function () {
    const $table = $('#bons-commande-table');
    const $etatVide = $('#etat-vide');

    const filtres = () => ({
        // Pilules multiples : tableau de statuts, vide = tous.
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

        // Les infobulles sont recréées à chaque rendu de page.
        $('[data-bs-toggle="tooltip"]').each((_, element) => new bootstrap.Tooltip(element));
    });

    const rafraichir = () => $table.bootstrapTable('refresh');

    $('#filter-statut input, #filter-fournisseur, #filter-du, #filter-au, #filter-regularisations, #filter-mes-brouillons')
        .on('change', rafraichir);

    // Recherche différée : on interroge le serveur quand la frappe s'arrête,
    // pas à chaque caractère.
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

    /*
     * Commandes de transition depuis la liste (renvoi, reprise, suppression).
     * Chacune demande une confirmation adaptée : le renvoi exige un MOTIF
     * (M-06) sans lequel l'auteur devrait deviner quoi corriger, la
     * suppression rappelle ce qu'elle emporte (SW-04), la reprise est bénigne
     * puisqu'elle ne fait que rouvrir son propre brouillon.
     */
    const confirmations = {
        renvoyer: (bouton) => Swal.fire({
            title: 'Renvoyer le bon en brouillon ?',
            input: 'textarea',
            inputLabel: 'Motif du renvoi',
            inputPlaceholder: 'Ce que l\'auteur doit corriger…',
            inputAttributes: { 'aria-label': 'Motif du renvoi' },
            text: 'Le bon repassera en brouillon chez son auteur, qui pourra le corriger et le soumettre à nouveau.',
            showCancelButton: true,
            confirmButtonText: 'Renvoyer en brouillon',
            cancelButtonText: 'Annuler',
            inputValidator: (valeur) =>
                (!valeur || valeur.trim().length < 5)
                    ? 'Indiquez le motif du renvoi : l\'auteur doit savoir quoi corriger.'
                    : undefined,
        }).then((r) => (r.isConfirmed ? { motif: r.value } : null)),

        reprendre: (bouton) => Swal.fire({
            title: 'Reprendre ce bon ?',
            text: `${bouton.data('numero')} repassera en brouillon et redeviendra modifiable.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Reprendre',
            cancelButtonText: 'Annuler',
        }).then((r) => (r.isConfirmed ? {} : null)),

        supprimer: (bouton) => Swal.fire({
            title: `Supprimer le ${bouton.data('numero')} ?`,
            text: 'Ses lignes seront supprimées. Cette action ne laisse pas de trace : un brouillon n\'engage rien.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Supprimer',
            cancelButtonText: 'Annuler',
        }).then((r) => (r.isConfirmed ? {} : null)),
    };

    $table.on('click', '.bc-commande', function () {
        const $bouton = $(this);
        const cle = $bouton.data('cle');
        const demander = confirmations[cle];

        if (!demander) return;

        demander($bouton).then((donnees) => {
            if (donnees === null) return;

            // Anti-double-soumission : une transition ne part qu'une fois.
            $bouton.prop('disabled', true);

            $.ajax({
                url: $bouton.data('url'),
                method: $bouton.data('methode'),
                data: JSON.stringify(donnees),
                contentType: 'application/json',
                dataType: 'json',
            })
                .done((reponse) => {
                    $table.bootstrapTable('refresh');
                    Swal.fire({
                        icon: 'success',
                        title: reponse.message,
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end',
                    });
                })
                .fail((xhr) => {
                    $bouton.prop('disabled', false);
                    Swal.fire({
                        icon: 'error',
                        title: xhr.status === 409 ? 'Le bon a changé d\'état' : 'Action impossible',
                        text: xhr.responseJSON?.message ?? 'Action impossible.',
                    });
                });
        });
    });
});
