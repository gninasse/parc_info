{{--
    Modale d'impression : le PDF s'affiche dans une iframe, sans quitter la
    page (pattern commun du projet — cf. stock::shared._modal_pdf). Impression
    directe, téléchargement et ouverture dans un onglet.

    Le bon de commande n'a qu'un seul modèle : la bascule de modèles du Stock
    n'a pas d'équivalent ici et n'est pas reproduite.

    Piloté par js/modules/achat/shared/modal-pdf.js (ModalPdf).
--}}
<div class="modal fade" id="pdfModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="height: 90vh;">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="pdf-modal-titre">Impression</h5>

                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="pdf-imprimer">
                        <i class="bi bi-printer me-1"></i>Imprimer
                    </button>
                    <a class="btn btn-sm btn-outline-secondary" id="pdf-telecharger" download>
                        <i class="bi bi-download me-1"></i>Télécharger
                    </a>
                    <a class="btn btn-sm btn-outline-secondary" id="pdf-onglet" target="_blank" rel="noopener"
                       data-bs-toggle="tooltip" title="Ouvrir dans un onglet">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                    <button type="button" class="btn-close ms-1" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
            </div>
            <div class="modal-body p-0 position-relative">
                <div class="position-absolute top-50 start-50 translate-middle text-muted" id="pdf-chargement">
                    <span class="spinner-border spinner-border-sm me-2"></span>Préparation du document…
                </div>
                <iframe id="pdf-iframe" title="Aperçu du document"
                        style="width:100%; height:100%; border:0;"></iframe>
            </div>
        </div>
    </div>
</div>
