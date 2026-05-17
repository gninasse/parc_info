<div class="modal fade" id="modal-consommable" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary bg-opacity-10 border-0">
                <h5 class="modal-title fw-bold text-primary" id="modalConsommableLabel">
                    <i class="fas fa-box-open me-2"></i><span>Nouveau Consommable</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-consommable">
                @csrf
                <input type="hidden" name="id" id="consommable-id">
                <div class="modal-body py-4">
                    <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">
                        <i class="fas fa-info-circle me-2"></i>Identification & Stock
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Code Article <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" placeholder="ex: TONER-HP-01" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Désignation <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" placeholder="ex: Toner HP LaserJet Pro CF279A" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Type de consommable <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="type_consommable_id" id="select-type-consommable" class="form-select select2-modal" required>
                                    <option value="">Sélectionnez...</option>
                                    @foreach($types as $t)
                                        <option value="{{ $t->id }}">{{ $t->nom }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary" id="btn-quickadd-type-cons">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Marque</label>
                            <select name="marque_id" class="form-select select2-modal">
                                <option value="">Générique</option>
                                @foreach($marques as $m)
                                    <option value="{{ $m->id }}">{{ $m->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">
                        <i class="fas fa-warehouse me-2"></i>Gestion des Seuils & Coût
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Stock Minimum <span class="text-danger">*</span></label>
                            <input type="number" name="quantite_stock_min" class="form-control" value="5" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Stock Maximum <span class="text-danger">*</span></label>
                            <input type="number" name="quantite_stock_max" class="form-control" value="20" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Coût Unitaire (HT) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="cout_unitaire" class="form-control" step="0.01" placeholder="0.00" required>
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                    </div>

                    <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">
                        <i class="fas fa-truck me-2"></i>Approvisionnement
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Fournisseur Principal <span class="text-danger">*</span></label>
                            <select name="fournisseur_principal_id" id="select-fournisseur" class="form-select select2-modal" required>
                                <option value="">Sélectionnez un fournisseur...</option>
                                @foreach($fournisseurs as $f)
                                    <option value="{{ $f->id }}">{{ $f->nom }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Notes & Compatibilité</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Informations techniques, modèles compatibles..."></textarea>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="est_actif" value="1">
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4" id="btn-save-consommable">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
