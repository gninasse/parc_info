<div class="modal fade" id="item-modal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalLabel"><span>Nouveau</span> Éditeur</h5>
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
                        <label for="site_web" class="form-label">Site Web</label>
                        <input type="url" class="form-control" id="site_web" name="site_web" placeholder="https://example.com">
                    </div>
                    <div class="mb-3">
                        <label for="email_support" class="form-label">Email Support</label>
                        <input type="email" class="form-control" id="email_support" name="email_support">
                    </div>
                    <div class="mb-3">
                        <label for="telephone_support" class="form-label">Téléphone Support</label>
                        <input type="text" class="form-control" id="telephone_support" name="telephone_support">
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="est_actif" name="est_actif" value="1" checked>
                            <label class="form-check-label" for="est_actif">Éditeur Actif</label>
                        </div>
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