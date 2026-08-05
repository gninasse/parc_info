/**
 * modale-articles.js — M-01, sélection d'articles en mode MULTI (UX2-04).
 *
 * Le point qui fait toute la valeur de cette modale : la sélection est
 * PERSISTANTE entre les recherches. On cherche « toner », on coche, on cherche
 * « Dell », on coche, et les deux partent ensemble dans le bon. Une modale qui
 * oublie la sélection à chaque frappe oblige à faire un aller-retour par
 * article, ce qui est précisément le geste qu'on veut supprimer.
 *
 * Les articles proviennent de l'API du Catalogue (contrat §2.1), avec le
 * fournisseur de la commande en tête de tri.
 */
import { NATURES } from '../../catalogue/formatters.js';

const echapper = (texte) => $('<span>').text(texte ?? '').html();

const fcfa = (valeur) =>
    valeur === null || valeur === undefined || valeur === ''
        ? '—'
        : `${Number(valeur).toLocaleString('fr-FR', { maximumFractionDigits: 0 })}`;

export class ModaleArticles {
    /**
     * @param {object} options
     * @param {string} options.urlArticles           API Catalogue
     * @param {function} options.fournisseurPrefereId Fournisseur de la commande
     * @param {function} options.onAjouter            Reçoit les articles retenus
     */
    constructor(options) {
        this.options = options;
        // Map id → article : la sélection survit aux recherches successives.
        this.selection = new Map();
        this.minuterie = null;
    }

    initialiser() {
        const modale = document.getElementById('modal-articles');
        if (modale === null) return;

        // Rechargement à l'ouverture : le catalogue a pu bouger, et le
        // fournisseur de la commande a pu changer depuis la dernière fois.
        modale.addEventListener('shown.bs.modal', () => this.charger());

        $('#ma-recherche').on('input', () => {
            clearTimeout(this.minuterie);
            this.minuterie = setTimeout(() => this.charger(), 300);
        });

        $('#ma-nature').on('change', () => this.charger());

        $('#ma-corps').on('change', 'input[type="checkbox"]', (evenement) => {
            const $case = $(evenement.currentTarget);
            const article = JSON.parse($case.attr('data-article'));

            if ($case.is(':checked')) {
                this.selection.set(article.id, article);
            } else {
                this.selection.delete(article.id);
            }

            this.rafraichirCompteur();
        });

        $('#ma-ajouter').on('click', () => {
            this.options.onAjouter(Array.from(this.selection.values()));
            this.selection.clear();
            this.rafraichirCompteur();
            bootstrap.Modal.getInstance(modale)?.hide();
        });
    }

    charger() {
        const parametres = {
            q: $('#ma-recherche').val() || '',
            nature: $('#ma-nature').val() || '',
            limit: 50,
        };

        // Tri de pertinence : les articles du fournisseur de la commande
        // d'abord (contrat API §2.1).
        const fournisseur = this.options.fournisseurPrefereId();
        if (fournisseur) parametres.fournisseur_prefere_id = fournisseur;

        $('#ma-chargement').removeClass('d-none');
        $('#ma-vide').addClass('d-none');

        $.getJSON(this.options.urlArticles, parametres)
            .done((reponse) => this.rendre(reponse.data || [], fournisseur))
            .fail(() => {
                $('#ma-corps').empty();
                $('#ma-vide').removeClass('d-none')
                    .find('p').text('Le Catalogue n\'a pas répondu. Réessayez dans un instant.');
            })
            .always(() => $('#ma-chargement').addClass('d-none'));
    }

    rendre(articles, fournisseurPrefere) {
        const lignes = articles.map((article) => {
            const nature = NATURES[article.nature] || {};
            // La case reflète la sélection en cours : un article coché puis
            // retrouvé par une autre recherche reste coché.
            const coche = this.selection.has(article.id) ? 'checked' : '';
            const etoile = fournisseurPrefere && String(article.fournisseur_principal_id) === String(fournisseurPrefere)
                ? '<i class="bi bi-star-fill text-warning ms-1" title="Fournisseur de la commande"></i>'
                : '';

            return `<tr>
                <td>
                    <input type="checkbox" class="form-check-input" ${coche}
                           data-article='${echapper(JSON.stringify(article))}'
                           aria-label="Sélectionner ${echapper(article.nom)}">
                </td>
                <td><span class="badge ${nature.classes ?? ''}" style="${nature.style ?? ''}"
                          title="${echapper(nature.libelle ?? article.nature)}">${nature.icone ?? ''}${nature.court ?? ''}</span></td>
                <td><span class="font-monospace small">${echapper(article.code)}</span></td>
                <td>${echapper(article.nom)}${etoile}</td>
                <td class="small text-muted">${echapper(article.unite_stock)}</td>
                <td class="text-end">${fcfa(article.prix_indicatif)}</td>
                <td class="text-center"><span class="badge bg-secondary-subtle text-secondary-emphasis">${Number(article.taux_tva ?? 18)} %</span></td>
            </tr>`;
        });

        $('#ma-corps').html(lignes.join(''));
        $('#ma-vide').toggleClass('d-none', articles.length > 0);
        this.rafraichirCompteur();
    }

    rafraichirCompteur() {
        const nombre = this.selection.size;
        $('#ma-compteur').text(`${nombre} article(s) sélectionné(s)`);
        $('#ma-ajouter').prop('disabled', nombre === 0);
    }
}
