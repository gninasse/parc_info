{{-- Modale MD-SEUIL (UX §2, 420 px) + variante « seuil sur un article » (crée la ligne à 0). --}}
<div class="modal fade" id="seuilModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content">
            <form id="seuil-form" novalidate>
                <div class="modal-header">
                    <h6 class="modal-title" id="seuil-modal-title">Ajuster le seuil local</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="seuil-niveau-id">

                    {{-- Variante « seuil sur un article » : sélecteurs affichés uniquement en création --}}
                    <div id="seuil-bloc-article" class="d-none">
                        <div class="mb-3">
                            <label for="seuil-magasin" class="form-label">Magasin <span class="text-danger">*</span></label>
                            <select class="form-select" id="seuil-magasin" name="magasin_id"></select>
                        </div>
                        <div class="mb-3">
                            <label for="seuil-article" class="form-label">Article <span class="text-danger">*</span></label>
                            <select class="form-select" id="seuil-article" name="article_id"
                                    data-placeholder="Rechercher au catalogue…"></select>
                        </div>
                    </div>

                    <div class="mb-1">
                        <label for="seuil-valeur" class="form-label">Seuil local</label>
                        <input type="number" class="form-control" id="seuil-valeur" name="seuil" min="0" step="1">
                        {{-- Texte d'aide exact de l'UX §2 --}}
                        <div class="form-text" id="seuil-aide">
                            Laisser vide pour hériter du seuil de l'article (<span id="seuil-article-defaut">—</span>).
                            La pose d'un seuil sur un article jamais reçu crée sa ligne à 0.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btn-seuil-save">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
