<div class="modal fade" id="createEtageModal" tabindex="-1" aria-labelledby="createEtageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEtageModalLabel">Nouvel Étage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="etageForm">
                @csrf
                <input type="hidden" id="etage_id" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="c_site_id" class="form-label">Site</label>
                        <select class="form-select" id="c_site_id">
                            <option value="">Sélectionner un site</option>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->libelle }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Sélectionnez le site pour filtrer les bâtiments</small>
                    </div>

                    <div class="mb-3">
                        <label for="c_batiment_id" class="form-label">Bâtiment <span class="text-danger">*</span></label>
                        <select class="form-select" id="c_batiment_id" name="batiment_id" required disabled>
                            <option value="">Sélectionner d'abord un site</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="numero" class="form-label">Numéro <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="numero" name="numero" required>
                        <small class="text-muted">Ex: 0 pour Rez-de-chaussée, 1 pour 1er étage, etc.</small>
                    </div>
                    <div class="mb-3">
                        <label for="libelle" class="form-label">Libellé <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="libelle" name="libelle" required>
                        <small class="text-muted">Ex: Rez-de-chaussée, 1er Étage, etc.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-etage"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
