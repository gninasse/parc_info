<div class="modal fade" id="modal-fournisseur" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary bg-opacity-10 border-0">
                <h5 class="modal-title fw-bold text-primary">
                    <i class="fas fa-truck me-2"></i><span>Nouveau Fournisseur</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-fournisseur">
                @csrf
                <input type="hidden" name="id" id="fournisseur-id">
                <div class="modal-body py-4">
                    <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">
                        <i class="fas fa-info-circle me-2"></i>Informations Générales
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Code Fournisseur <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" placeholder="ex: FOUR-MS" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Nom de l'entreprise <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" placeholder="ex: Microsoft France" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Type d'entité</label>
                            <select name="type" class="form-select">
                                <option value="Revendeur">Revendeur</option>
                                <option value="Editeur">Éditeur</option>
                                <option value="Distributeur">Distributeur</option>
                                <option value="Prestataire">Prestataire</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Score de fiabilité (%)</label>
                            <input type="number" name="fiabilite_score" class="form-control" value="100" min="0" max="100">
                        </div>
                    </div>

                    <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">
                        <i class="fas fa-address-book me-2"></i>Coordonnées
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email principal</label>
                            <input type="email" name="email" class="form-control" placeholder="contact@fournisseur.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Téléphone</label>
                            <input type="text" name="telephone" class="form-control" placeholder="01 02 03 04 05">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Adresse complète</label>
                            <textarea name="adresse" class="form-control" rows="2" placeholder="N°, rue, CP, Ville..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4" id="btn-save-fournisseur">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
