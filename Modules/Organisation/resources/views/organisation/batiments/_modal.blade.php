<div class="modal fade" id="createBatimentModal" tabindex="-1" aria-labelledby="createBatimentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createBatimentModalLabel">Nouveau Bâtiment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="batimentForm">
                @csrf
                <input type="hidden" id="batiment_id" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="site_id" class="form-label">Site <span class="text-danger">*</span></label>
                        <select class="form-select" id="site_id" name="site_id" required>
                            <option value="">Sélectionner un site</option>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="code" name="code" required>
                    </div>
                    <div class="mb-3">
                        <label for="libelle" class="form-label">Libellé <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="libelle" name="libelle" required>
                    </div>
                    <div class="mb-3">
                        <label for="nombre_etages" class="form-label">Nombre d'étages</label>
                        <input type="number" class="form-control" id="nombre_etages" name="nombre_etages" min="0">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-batiment"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
