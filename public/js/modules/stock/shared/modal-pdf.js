/**
 * modal-pdf.js — aperçu et impression d'un bon dans une modale (iframe),
 * sans quitter la page. Partagé par les fiches et les listes des trois
 * documents ; la bascule de modèle réutilise le paramètre ?modele=.
 *
 * ModalPdf.ouvrir({
 *     urlBase: route('stock.entrees.pdf', id),  // sans ?modele=
 *     titre: 'ENT-2026-0007',
 *     modeles: { articles: 'Bon de réception', equipements: 'Fiche des équipements' },
 *     avecEquipements: true,     // false → la bascule est masquée
 *     modele: 'articles',        // modèle ouvert en premier
 * });
 */
export const ModalPdf = {
    _etat: { urlBase: null, modele: 'articles' },

    ouvrir({ urlBase, titre = 'Impression', modeles = {}, avecEquipements = true, modele = 'articles' }) {
        this._etat.urlBase = urlBase;
        this._etat.modele = avecEquipements ? modele : 'articles';

        $('#pdf-modal-titre').text(titre);
        $('#pdf-libelle-articles').text(modeles.articles ?? 'Bon');
        $('#pdf-libelle-equipements').text(modeles.equipements ?? 'Équipements');
        $('#pdf-modeles').toggleClass('d-none', !avecEquipements);
        $(`#pdf-modele-${this._etat.modele}`).prop('checked', true);

        this._cabler();
        this._charger();

        bootstrap.Modal.getOrCreateInstance(document.getElementById('pdfModal')).show();
    },

    _cabler() {
        if (this._cable) return;
        this._cable = true;

        $('#pdf-modeles input').on('change', (e) => {
            this._etat.modele = e.target.value;
            this._charger();
        });

        // Impression directe du contenu de l'iframe (même origine)
        $('#pdf-imprimer').on('click', () => {
            const iframe = document.getElementById('pdf-iframe');
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch {
                // Visionneuse qui refuse l'accès : repli sur un onglet
                window.open(this._url(), '_blank', 'noopener');
            }
        });

        // L'iframe libérée à la fermeture (sinon le PDF reste en mémoire)
        $('#pdfModal').on('hidden.bs.modal', () => {
            $('#pdf-iframe').attr('src', 'about:blank');
        });

        $('#pdf-iframe').on('load', () => $('#pdf-chargement').addClass('d-none'));
    },

    _url() {
        const separateur = this._etat.urlBase.includes('?') ? '&' : '?';

        return `${this._etat.urlBase}${separateur}modele=${this._etat.modele}`;
    },

    _charger() {
        $('#pdf-chargement').removeClass('d-none');

        const url = this._url();
        $('#pdf-iframe').attr('src', url);
        $('#pdf-telecharger').attr('href', url);
        $('#pdf-onglet').attr('href', url);
    },
};

/**
 * Câble les liens « Imprimer le bon » d'une fiche sur la modale : tout
 * élément portant data-pdf-url ouvre l'aperçu au lieu d'un nouvel onglet.
 */
export function cablerLiensPdf(contexte = document) {
    $(contexte).on('click', '[data-pdf-url]', function (e) {
        e.preventDefault();
        const $lien = $(this);

        ModalPdf.ouvrir({
            urlBase: $lien.data('pdf-url'),
            titre: $lien.data('pdf-titre'),
            modeles: {
                articles: $lien.data('pdf-libelle-articles'),
                equipements: $lien.data('pdf-libelle-equipements'),
            },
            avecEquipements: Boolean($lien.data('pdf-avec-equipements')),
            modele: $lien.data('pdf-modele') || 'articles',
        });
    });
}
