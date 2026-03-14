<div class="modal fade" id="posteModal" tabindex="-1" aria-labelledby="posteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="posteModalLabel">Nouvelle Poste de travail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="posteForm">
                @csrf
                <input type="hidden" id="poste_id" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="direction_id" class="form-label">Direction <span class="text-danger">*</span></label>
                            <select class="form-select" id="direction_id" name="direction_id" required>
                                <option value="">Sélectionner une direction</option>
                                @foreach($directions as $direction)
                                    <option value="{{ $direction->id }}">{{ $direction->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="service_id" class="form-label">Service <span class="text-danger">*</span></label>
                            <select class="form-select" id="service_id" name="service_id" required>
                                <option value="">Sélectionner un service</option>
                                @foreach($services as $service)
                                    <option value="{{ $service->id }}">{{ $service->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="unite_id" class="form-label">Unité</label>
                            <select class="form-select" id="unite_id" name="unite_id">
                                <option value="">Sélectionner une unité</option>
                                @foreach($unites as $unite)
                                    <option value="{{ $unite->id }}">{{ $unite->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="local_id" class="form-label">Local</label>
                            <select class="form-select" id="local_id" name="local_id">
                                <option value="">Sélectionner un local</option>
                                @foreach($locaux as $local)
                                    <option value="{{ $local->id }}">{{ $local->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="code" class="form-label">Code</label>
                            <input type="text" class="form-control" id="code" name="code" placeholder="Auto-généré" readonly>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="libelle" class="form-label">Libellé <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="libelle" name="libelle" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="agent_id" class="form-label">Agent / Responsable</label>
                            <select class="form-select" id="agent_id" name="agent_id">
                                <option value="">Sélectionner un agent</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
                            <select class="form-select" id="statut" name="statut" required>
                                <option value="actif">Actif</option>
                                <option value="inactif">Inactif</option>
                                <option value="en_renovation">En rénovation</option>
                                <option value="supprime">Supprimé</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-poste"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
