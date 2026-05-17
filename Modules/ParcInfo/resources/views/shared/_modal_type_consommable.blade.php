<div class="modal fade" id="modal-quickadd-type-cons" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary bg-opacity-10 border-0">
                <h5 class="modal-title fw-bold text-primary">
                    <i class="fas fa-tags me-2"></i>Nouveau Type Consommable
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-quickadd-type-cons">
                @csrf
                <div class="modal-body py-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Nom du type <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" placeholder="ex: Toner Noir" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Catégorie <span class="text-danger">*</span></label>
                            <select name="categorie" class="form-select" required>
                                <option value="Impression">Impression</option>
                                <option value="Fournitures Bureau">Fournitures Bureau</option>
                                <option value="Reseau">Réseau</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Securite">Sécurité</option>
                                <option value="Accessoires">Accessoires</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Unité de stock <span class="text-danger">*</span></label>
                            <input type="text" name="unite_stock" class="form-control" placeholder="ex: Cartouche, Rame, Unité..." required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4" id="btn-save-quick-type-cons">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
