<div class="modal fade" id="createDirectionModal" tabindex="-1" aria-labelledby="createDirectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createDirectionModalLabel">Nouvelle Direction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="directionForm">
                @csrf
                <input type="hidden" id="direction_id" name="id">
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
                        <label for="responsable_id" class="form-label">Responsable</label>
                        <select class="form-select" id="responsable_id" name="responsable_id">
                            <option value="">Sélectionner un responsable</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-direction"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
