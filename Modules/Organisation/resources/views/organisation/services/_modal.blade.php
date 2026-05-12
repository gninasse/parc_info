<div class="modal fade" id="createServiceModal" tabindex="-1" aria-labelledby="createServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createServiceModalLabel">Nouveau Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="serviceForm">
                @csrf
                <input type="hidden" id="service_id" name="id">
                <div class="modal-body">
                    <!-- Helper Select for Cascading -->
                    <div class="mb-3">
                        <label for="c_site_id" class="form-label">Site</label>
                        <select class="form-select" id="c_site_id">
                            <option value="">Sélectionner un site</option>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->libelle }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Sélectionnez le site pour filtrer les directions</small>
                    </div>

                    <div class="mb-3">
                        <label for="c_direction_id" class="form-label">Direction <span class="text-danger">*</span></label>
                        <select class="form-select" id="c_direction_id" name="direction_id" required disabled>
                            <option value="">Sélectionner d'abord un site</option>
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
                        <label for="type_service" class="form-label">Type de Service <span class="text-danger">*</span></label>
                        <select class="form-select" id="type_service" name="type_service" required>
                            <option value="">Sélectionner un type</option>
                            <option value="administratif">Administratif</option>
                            <option value="clinique">Clinique</option>
                            <option value="medico_technique">Médico-Technique</option>
                        </select>
                        <small class="text-muted">Administratif: DAF, DRH | Clinique: Médecine, Chirurgie | Médico-Technique: Radiologie, Labo</small>
                    </div>
                    <div class="mb-3">
                        <label for="chef_service_id" class="form-label">Chef de Service</label>
                        <select class="form-select select2" id="chef_service_id" name="chef_service_id">
                            <option value="">Rechercher un chef de service...</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-service"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
