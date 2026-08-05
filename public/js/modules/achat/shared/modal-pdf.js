/**
 * modal-pdf.js — aperçu et impression d'un bon de commande dans une modale
 * (iframe), sans quitter la page. Même pattern que le module Stock
 * (js/modules/stock/shared/modal-pdf.js), sans la bascule de modèles : le bon
 * de commande n'a qu'un seul gabarit.
 *
 * ModalPdf.ouvrir({
 *     url: route('achat.bons-commande.pdf', id),
 *     titre: 'BC-2026-0041',
 * });
 */
export const ModalPdf = {
    _url: null,
    _cable: false,

    ouvrir({ url, titre = 'Impression' }) {
        this._url = url;

        $('#pdf-modal-titre').text(titre);

        this._cabler();
        this._charger();

        bootstrap.Modal.getOrCreateInstance(document.getElementById('pdfModal')).show();
    },

    _cabler() {
        if (this._cable) return;
        this._cable = true;

        // Impression directe du contenu de l'iframe (même origine)
        $('#pdf-imprimer').on('click', () => {
            const iframe = document.getElementById('pdf-iframe');
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch {
                // Visionneuse qui refuse l'accès : repli sur un onglet
                window.open(this._url, '_blank', 'noopener');
            }
        });

        // L'iframe est libérée à la fermeture (sinon le PDF reste en mémoire)
        $('#pdfModal').on('hidden.bs.modal', () => {
            $('#pdf-iframe').attr('src', 'about:blank');
        });

        $('#pdf-iframe').on('load', () => $('#pdf-chargement').addClass('d-none'));
    },

    _charger() {
        $('#pdf-chargement').removeClass('d-none');

        $('#pdf-iframe').attr('src', this._url);
        $('#pdf-telecharger').attr('href', this._url);
        $('#pdf-onglet').attr('href', this._url);
    },
};
