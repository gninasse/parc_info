<div class="modal fade" id="createLocalModal" tabindex="-1" aria-labelledby="createLocalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createLocalModalLabel">Nouveau Local</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="localForm">
                @csrf
                <input type="hidden" id="local_id" name="id">
                <div class="modal-body">
                    <!-- Cascading Selects -->
                    <div class="mb-3">
                        <label for="c_site_id" class="form-label">Site <span class="text-danger">*</span></label>
                        <select class="form-select" id="c_site_id">
                            <option value="">Sélectionner un site</option>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->libelle }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="c_batiment_id" class="form-label">Bâtiment <span class="text-danger">*</span></label>
                        <select class="form-select" id="c_batiment_id" disabled>
                            <option value="">Sélectionner d'abord un site</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="c_etage_id" class="form-label">Étage <span class="text-danger">*</span></label>
                        <select class="form-select" id="c_etage_id" name="etage_id" required disabled>
                            <option value="">Sélectionner d'abord un bâtiment</option>
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
                        <label for="type_local" class="form-label">Type de local <span class="text-danger">*</span></label>
                        <select class="form-select" id="type_local" name="type_local" required>
                            <option value="">Sélectionner un type</option>
                            <option value="bureau">Bureau</option>
                            <option value="salle_soins">Salle de soins</option>
                            <option value="salle_attente">Salle d'attente</option>
                            <option value="magasin">Magasin</option>
                            <option value="couloir">Couloir</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="superficie_m2" class="form-label">Superficie (m²)</label>
                        <input type="number" class="form-control" id="superficie_m2" name="superficie_m2" step="0.01" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-local"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
