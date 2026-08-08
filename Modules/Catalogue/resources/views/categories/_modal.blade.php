{{-- Modale duale création/édition catégorie --}}
<div class="modal fade" id="categorieModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="categorieModalLabel">
                    <i class="fas fa-sitemap me-2"></i><span id="modal-title-text">Nouvelle catégorie</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <form id="categorie-form" novalidate>
                @csrf
                <input type="hidden" id="categorie-id" name="id">

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="f-libelle" class="form-label">Libellé <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="f-libelle" name="libelle" required>
                        </div>
                        <div class="col-md-4">
                            <label for="f-code" class="form-label">Code</label>
                            <input type="text" class="form-control" id="f-code" readonly placeholder="Généré automatiquement">
                        </div>
                        <div class="col-12">
                            <label for="f-parent" class="form-label">Catégorie parente</label>
                            <select class="form-select" id="f-parent" name="parent_id">
                                <option value="">Aucune (premier niveau)</option>
                            </select>
                            <div class="form-text">Laisser vide pour un premier niveau — profondeur max 2.</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-save">
                        <i class="fas fa-save me-1"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
