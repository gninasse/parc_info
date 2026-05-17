<div class="modal fade" id="modal-quickadd-editeur" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary bg-opacity-10 border-0">
                <h5 class="modal-title fw-bold text-primary">
                    <i class="fas fa-building me-2"></i>Ajout rapide Éditeur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-quickadd-editeur">
                @csrf
                <div class="modal-body py-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Nom de l'éditeur <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" placeholder="ex: Microsoft, Adobe..." required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Site Web</label>
                            <input type="url" name="site_web" class="form-control" placeholder="https://www.editeur.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email Support</label>
                            <input type="email" name="email_support" class="form-control" placeholder="support@editeur.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Téléphone Support</label>
                            <input type="text" name="telephone_support" class="form-control" placeholder="+33...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4" id="btn-save-quick-editeur">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
