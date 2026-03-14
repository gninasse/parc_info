<div class="modal fade" id="createUniteModal" tabindex="-1" aria-labelledby="createUniteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createUniteModalLabel">Nouvelle Unité</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uniteForm">
                @csrf
                <input type="hidden" id="unite_id" name="id">
                <div class="modal-body">
                    <!-- Cascading Selects -->
                    <div class="mb-3">
                        <label for="c_site_id" class="form-label">Site <span class="text-danger">*</span></label>
                        <select class="form-select" id="c_site_id" name="site_id" required>
                            <option value="">Sélectionner un site</option>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="c_direction_id" class="form-label">Direction <span class="text-danger">*</span></label>
                        <select class="form-select" id="c_direction_id" name="direction_id" disabled required>
                            <option value="">Sélectionner d'abord un site</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="c_service_id" class="form-label">Service <span class="text-danger">*</span></label>
                        <select class="form-select" id="c_service_id" name="service_id" required disabled>
                            <option value="">Sélectionner d'abord une direction</option>
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
                        <label for="major_id" class="form-label">Major (Infirmier Chef)</label>
                        <select class="form-select" id="major_id" name="major_id">
                            <option value="">Chargement...</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-unite"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
