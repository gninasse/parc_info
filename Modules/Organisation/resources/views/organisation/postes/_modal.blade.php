<div class="modal fade" id="posteModal" tabindex="-1" aria-labelledby="posteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="posteModalLabel">Poste de travail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="posteForm">
                @csrf
                <input type="hidden" id="poste_id" name="id">
                <div class="modal-body">
                    <!-- Structure Administrative -->
                    <h6 class="border-bottom pb-2 mb-3 text-primary"><i class="fas fa-sitemap mr-1"></i> Structure Administrative</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="niveau_rattachement" class="form-label">Niveau de rattachement <span class="text-danger">*</span></label>
                            <select class="form-select" id="niveau_rattachement" name="niveau_rattachement" required>
                                <option value="direction">Direction</option>
                                <option value="service">Service</option>
                                <option value="unite">Unité</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="direction_id" class="form-label">Direction <span class="text-danger">*</span></label>
                            <select class="form-select" id="direction_id" name="direction_id" required>
                                <option value="">Sélectionner...</option>
                                @foreach($directions as $direction)
                                    <option value="{{ $direction->id }}">{{ $direction->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3 d-none" id="col-service">
                            <label for="service_id" class="form-label">Service <span class="text-danger">*</span></label>
                            <select class="form-select" id="service_id" name="service_id">
                                <option value="">Sélectionner...</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3 d-none" id="col-unite">
                            <label for="unite_id" class="form-label">Unité <span class="text-danger">*</span></label>
                            <select class="form-select" id="unite_id" name="unite_id">
                                <option value="">Sélectionner...</option>
                            </select>
                        </div>
                    </div>

                    <!-- Emplacement Physique -->
                    <h6 class="border-bottom pb-2 mb-3 mt-3 text-primary"><i class="fas fa-map-marker-alt mr-1"></i> Emplacement Physique</h6>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="site_id" class="form-label">Site</label>
                            <select class="form-select" id="site_id" name="site_id">
                                <option value="">Sélectionner...</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}">{{ $site->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="batiment_id" class="form-label">Bâtiment</label>
                            <select class="form-select" id="batiment_id" name="batiment_id">
                                <option value="">Sélectionner...</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="etage_id" class="form-label">Étage</label>
                            <select class="form-select" id="etage_id" name="etage_id">
                                <option value="">Sélectionner...</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="local_id" class="form-label">Local</label>
                            <select class="form-select" id="local_id" name="local_id">
                                <option value="">Sélectionner...</option>
                            </select>
                        </div>
                    </div>

                    <!-- Informations du Poste -->
                    <h6 class="border-bottom pb-2 mb-3 mt-3 text-primary"><i class="fas fa-info-circle mr-1"></i> Informations du Poste</h6>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="code" class="form-label">Code</label>
                            <input type="text" class="form-control" id="code" name="code" placeholder="Auto-généré" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="libelle" class="form-label">Nom du poste <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="libelle" name="libelle" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
                            <select class="form-select" id="statut" name="statut" required>
                                <option value="actif">Actif</option>
                                <option value="inactif">Inactif</option>
                                <option value="en_renovation">En rénovation</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="dossier_employe_id" class="form-label">Agent Occupant</label>
                            <select class="form-select select2" id="dossier_employe_id" name="dossier_employe_id" style="width: 100%">
                                <option value="">Poste vacant</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description / Notes</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-poste"><i class="fas fa-save mr-1"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
