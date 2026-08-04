{{--
    Sélecteur d'article en MODALE (dérogation UX §3.2 arbitrée par Ibrahim :
    remplace le Select2 de ligne). Recherche débouncée sur
    /catalogue/api/articles (S11), badge de nature + prix indicatif,
    double-clic = choisir. Piloté par js/modules/stock/shared/selecteur-article.js.
--}}
<div class="modal fade" id="selecteurArticleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sélectionner un article</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <select class="form-select form-select-sm" id="sa-filtre-nature">
                            <option value="">Toutes les natures</option>
                            <option value="consommable">C — Consommables</option>
                            <option value="piece">P — Pièces</option>
                            <option value="equipement">E — Équipements (modèle × N)</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <input type="text" class="form-control form-control-sm" id="sa-recherche"
                               placeholder="Code ou nom d'article…" autocomplete="off">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px;"></th>
                                <th style="width:70px;" class="text-center">Nature</th>
                                <th>Code</th>
                                <th>Nom</th>
                                <th>Unité</th>
                                <th class="text-end">Prix indicatif</th>
                            </tr>
                        </thead>
                        <tbody id="sa-liste" style="cursor:pointer;"></tbody>
                    </table>
                </div>
                <div class="text-muted small" id="sa-vide" hidden>Aucun article pour cette recherche.</div>

                <div class="alert alert-light border mt-2 mb-0 small">
                    <i class="bi bi-info-circle me-1"></i>
                    L'article n'existe pas ? <a href="{{ route('catalogue.articles.index') }}" target="_blank" rel="noopener">Créez-le dans le module Catalogue →</a>
                    (le brouillon attend sans rien perdre — D7)
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="sa-confirmer" disabled>Choisir</button>
            </div>
        </div>
    </div>
</div>
