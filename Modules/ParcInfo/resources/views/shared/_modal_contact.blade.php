<div class="modal fade" id="modal-quickadd-contact" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary bg-opacity-10 border-0">
                <h5 class="modal-title fw-bold text-primary">
                    <i class="fas fa-user-plus me-2"></i>Nouveau Contact
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-quickadd-contact">
                @csrf
                <div class="modal-body py-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" placeholder="ex: DUPONT" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Prénom</label>
                            <input type="text" name="prenom" class="form-control" placeholder="ex: Jean">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Fonction / Poste</label>
                            <input type="text" name="fonction" class="form-control" placeholder="ex: Commercial, Support Tech...">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="jean.dupont@fournisseur.com">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Téléphone direct</label>
                            <input type="text" name="telephone" class="form-control" placeholder="01 02 03 04 05">
                        </div>
                    </div>
                </div>
                <input type="hidden" name="est_actif" value="1">
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4" id="btn-save-quick-contact">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
