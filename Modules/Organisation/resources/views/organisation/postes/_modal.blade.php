<div class="modal fade" id="posteModal" tabindex="-1" aria-labelledby="posteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="posteModalLabel">Poste de Travail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="posteForm">
                @csrf
                <input type="hidden" id="poste_id" name="id">
                <div class="modal-body">
                    
                    <div class="row">
                        <div class="col-md-6 border-end">
                            <h6 class="fw-bold mb-3 text-primary">Structure Administrative</h6>
                            
                            <div class="mb-3">
                                <label for="niveau_rattachement" class="form-label">Niveau de rattachement <span class="text-danger">*</span></label>
                                <select class="form-select" id="niveau_rattachement" name="niveau_rattachement" required>
                                    <option value="">Choisir un niveau...</option>
                                    <option value="direction">Direction</option>
                                    <option value="service">Service</option>
                                    <option value="unite">Unité</option>
                                </select>
                            </div>

                            <div class="mb-3 cascade-field d-none" id="field-direction">
                                <label for="direction_id" class="form-label">Direction <span class="text-danger">*</span></label>
                                <select class="form-select" id="direction_id" name="direction_id">
                                    <option value="">Sélectionner une direction...</option>
                                    @foreach($directions as $direction)
                                        <option value="{{ $direction->id }}">{{ $direction->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3 cascade-field d-none" id="field-service">
                                <label for="service_id" class="form-label">Service <span class="text-danger">*</span></label>
                                <select class="form-select" id="service_id" name="service_id" disabled>
                                    <option value="">— Sélectionner d'abord une direction —</option>
                                </select>
                            </div>

                            <div class="mb-3 cascade-field d-none" id="field-unite">
                                <label for="unite_id" class="form-label">Unité <span class="text-danger">*</span></label>
                                <select class="form-select" id="unite_id" name="unite_id" disabled>
                                    <option value="">— Sélectionner d'abord un service —</option>
                                </select>
                            </div>

                            <h6 class="fw-bold mb-3 mt-4 text-primary">Informations du Poste</h6>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label for="code" class="form-label">Code</label>
                                    <input type="text" class="form-control bg-light" id="code" name="code" placeholder="Auto-généré" readonly>
                                </div>
                                <div class="col-6">
                                    <label for="libelle" class="form-label">Libellé <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="libelle" name="libelle" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3 text-primary">Emplacement Physique</h6>
                            <div class="mb-3">
                                <label for="site_id" class="form-label">Site</label>
                                <select class="form-select" id="site_id" name="site_id">
                                    <option value="">Sélectionner un site...</option>
                                    @foreach($sites as $site)
                                        <option value="{{ $site->id }}">{{ $site->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="batiment_id" class="form-label">Bâtiment</label>
                                <select class="form-select" id="batiment_id" name="batiment_id" disabled>
                                    <option value="">— Sélectionner d'abord un site —</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="etage_id" class="form-label">Étage</label>
                                <select class="form-select" id="etage_id" name="etage_id" disabled>
                                    <option value="">— Sélectionner d'abord un bâtiment —</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="local_id" class="form-label">Local</label>
                                <select class="form-select" id="local_id" name="local_id" disabled>
                                    <option value="">— Sélectionner d'abord un étage —</option>
                                </select>
                            </div>

                            <h6 class="fw-bold mb-3 mt-4 text-primary">Affectation</h6>
                            <div class="mb-3">
                                <label for="dossier_employe_id" class="form-label">Employé affecté</label>
                                <select class="form-select select2-employe" id="dossier_employe_id" name="dossier_employe_id">
                                    <option value="">Rechercher un employé...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-poste">
                        <i class="fas fa-save me-1"></i> <span id="btn-save-label">Enregistrer le Poste</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- Toute la logique JS est gérée par PosteTravailForm.js --}}
