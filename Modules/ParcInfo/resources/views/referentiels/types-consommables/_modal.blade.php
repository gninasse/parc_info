<div class="modal fade" id="item-modal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalLabel"><span>Nouveau</span> Type de Consommable</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="item-form" novalidate>
                @csrf
                <input type="hidden" id="item-id" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="code" name="code" required>
                    </div>
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nom" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="categorie" class="form-label">Catégorie <span class="text-danger">*</span></label>
                        <select class="form-select" id="categorie" name="categorie" required>
                            <option value="">Sélectionner une catégorie</option>
                            <option value="Impression">Impression</option>
                            <option value="Fournitures Bureau">Fournitures Bureau</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Reseau">Réseau</option>
                            <option value="Securite">Sécurité</option>
                            <option value="Accessoires">Accessoires</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="sous_categorie" class="form-label">Sous-Catégorie</label>
                        <input type="text" class="form-control" id="sous_categorie" name="sous_categorie">
                    </div>
                    <div class="mb-3">
                        <label for="unite_stock" class="form-label">Unité de stock <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="unite_stock" name="unite_stock" placeholder="ex: Cartouche, Rame" required>
                    </div>
                    <div class="mb-3">
                        <label for="seul_reapprovisionnement" class="form-label">Seuil de réapprovisionnement</label>
                        <input type="number" class="form-control" id="seul_reapprovisionnement" name="seul_reapprovisionnement" min="0" value="5">
                    </div>
                    <div class="mb-3">
                        <label for="duree_conservation_jours" class="form-label">Durée conservation (jours)</label>
                        <input type="number" class="form-control" id="duree_conservation_jours" name="duree_conservation_jours" min="0">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-save"><i class="fas fa-save me-2"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>