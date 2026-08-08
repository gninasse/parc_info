{{-- Modale duale création/édition fournisseur --}}
<div class="modal fade" id="fournisseurModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="fournisseurModalLabel">
                    <i class="fas fa-truck me-2"></i><span id="modal-title-text">Nouveau fournisseur</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <form id="fournisseur-form" novalidate>
                @csrf
                <input type="hidden" id="fournisseur-id" name="id">

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="f-raison-sociale" class="form-label">Raison sociale <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="f-raison-sociale" name="raison_sociale" required>
                        </div>
                        <div class="col-md-4">
                            <label for="f-code" class="form-label">Code</label>
                            <input type="text" class="form-control" id="f-code" name="code" placeholder="Généré (FOUR-…)">
                        </div>
                        <div class="col-md-6">
                            <label for="f-contact" class="form-label">Personne à contacter</label>
                            <input type="text" class="form-control" id="f-contact" name="contact">
                        </div>
                        <div class="col-md-6">
                            <label for="f-telephone" class="form-label">Téléphone</label>
                            <input type="text" class="form-control" id="f-telephone" name="telephone" placeholder="+226 ...">
                        </div>
                        <div class="col-md-6">
                            <label for="f-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="f-email" name="email">
                        </div>
                        <div class="col-md-6">
                            <label for="f-adresse" class="form-label">Adresse</label>
                            <input type="text" class="form-control" id="f-adresse" name="adresse">
                        </div>
                        <div class="col-12">
                            <label for="f-notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="f-notes" name="notes" rows="2"></textarea>
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
