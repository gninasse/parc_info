<div class="modal fade" id="modal-quickadd-fournisseur" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success bg-opacity-10 border-0">
                <h5 class="modal-title fw-bold text-success">
                    <i class="fas fa-truck me-2"></i>Ajout rapide Fournisseur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-quickadd-fournisseur">
                @csrf
                <div class="modal-body py-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Nom de l'entreprise <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" placeholder="ex: SoftSell France" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Type</label>
                            <select name="type" class="form-select">
                                <option value="Revendeur">Revendeur</option>
                                <option value="Editeur">Éditeur</option>
                                <option value="Distributeur">Distributeur</option>
                                <option value="Prestataire">Prestataire</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Téléphone</label>
                            <input type="text" name="telephone" class="form-control" placeholder="01 02 03 04 05">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Email de contact</label>
                            <input type="email" name="email" class="form-control" placeholder="contact@fournisseur.com">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Adresse</label>
                            <textarea name="adresse" class="form-control" rows="2" placeholder="Adresse complète..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success px-4" id="btn-save-quick-fournisseur">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
