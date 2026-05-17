<div class="modal fade" id="modal-contrat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info bg-opacity-10 border-0">
                <h5 class="modal-title fw-bold text-info" id="modalContratLabel">
                    <i class="fas fa-file-signature me-2"></i><span>Nouveau Contrat Maintenance</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-contrat">
                @csrf
                <input type="hidden" name="id" id="contrat-id">
                <div class="modal-body py-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Référence du contrat <span class="text-danger">*</span></label>
                            <input type="text" name="reference" class="form-control" placeholder="ex: MAINT-2026-XYZ" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Nom / Désignation <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" placeholder="ex: Support Premium CHU-YO" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Fournisseur <span class="text-danger">*</span></label>
                            <select name="fournisseur_id" id="contrat-fournisseur-id" class="form-select" required>
                                <option value="">Sélectionnez...</option>
                                @foreach($fournisseurs as $f)
                                    <option value="{{ $f->id }}">{{ $f->nom }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date de début</label>
                            <input type="date" name="date_debut" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date de fin</label>
                            <input type="date" name="date_fin" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Coût annuel (HT)</label>
                            <div class="input-group">
                                <input type="number" name="cout" class="form-control" step="0.01" placeholder="0.00">
                                <span class="input-group-text">EUR</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-info text-white px-4" id="btn-save-contrat">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
