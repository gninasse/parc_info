/**
 * modale-fournisseur.js — sélection du fournisseur du bon de commande.
 *
 * Pattern du projet : une sélection se fait dans une MODALE (gabarit de la
 * modale article, en mode radio), jamais dans un simple select. Alimentée par
 * l'API du Catalogue, recherche bornée côté serveur.
 */
const echapper = (texte) => $('<span>').text(texte ?? '').html();

export class ModaleFournisseur {
    /**
     * @param {object} options
     * @param {string} options.urlFournisseurs  API Catalogue
     * @param {function} options.onChoisir      Reçoit {id, raison_sociale, …}
     */
    constructor(options) {
        this.options = options;
        this.selection = null;
        this.minuterie = null;
    }

    initialiser() {
        const modale = document.getElementById('modal-fournisseur');
        if (modale === null) return;

        modale.addEventListener('shown.bs.modal', () => {
            this.charger();
            document.getElementById('mf-recherche')?.focus();
        });

        $('#mf-recherche').on('input', () => {
            clearTimeout(this.minuterie);
            this.minuterie = setTimeout(() => this.charger(), 300);
        });

        // Sélection radio : cliquer la ligne coche son bouton.
        $('#mf-corps').on('click', 'tr', (evenement) => {
            const $ligne = $(evenement.currentTarget);
            $ligne.find('input[type="radio"]').prop('checked', true);
            this.selection = JSON.parse($ligne.attr('data-fournisseur'));
            $('#mf-choisir').prop('disabled', false);
        });

        // Double-clic : choisir directement, comme partout dans le projet.
        $('#mf-corps').on('dblclick', 'tr', () => $('#mf-choisir').trigger('click'));

        $('#mf-choisir').on('click', () => {
            if (!this.selection) return;
            this.options.onChoisir(this.selection);
            bootstrap.Modal.getInstance(modale)?.hide();
        });
    }

    charger() {
        $('#mf-chargement').removeClass('d-none');
        $('#mf-vide').addClass('d-none');

        $.getJSON(this.options.urlFournisseurs, {
            q: $('#mf-recherche').val() || '',
            limit: 50,
        })
            .done((reponse) => this.rendre(reponse.data || []))
            .fail(() => {
                $('#mf-corps').empty();
                $('#mf-vide').removeClass('d-none')
                    .find('p').text('Le Catalogue n\'a pas répondu. Réessayez dans un instant.');
            })
            .always(() => $('#mf-chargement').addClass('d-none'));
    }

    rendre(fournisseurs) {
        const lignes = fournisseurs.map((fournisseur) => {
            const coche = this.selection?.id === fournisseur.id ? 'checked' : '';

            return `<tr data-fournisseur='${echapper(JSON.stringify(fournisseur))}' role="button">
                <td class="text-center">
                    <input type="radio" class="form-check-input" name="mf-choix" ${coche}
                           aria-label="Choisir ${echapper(fournisseur.raison_sociale)}">
                </td>
                <td><span class="font-monospace small">${echapper(fournisseur.code)}</span></td>
                <td>${echapper(fournisseur.raison_sociale)}</td>
                <td class="small text-muted">${echapper(fournisseur.telephone ?? '—')}</td>
                <td class="small text-muted">${echapper(fournisseur.email ?? '—')}</td>
            </tr>`;
        });

        $('#mf-corps').html(lignes.join(''));
        $('#mf-vide').toggleClass('d-none', fournisseurs.length > 0);
    }
}
