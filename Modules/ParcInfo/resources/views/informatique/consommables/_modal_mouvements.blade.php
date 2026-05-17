<div class="modal fade" id="modal-consommer-consommable" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger bg-opacity-10 border-0">
                <h5 class="modal-title fw-bold text-danger">
                    <i class="fas fa-minus-circle me-2"></i>Enregistrer une sortie de stock
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-consommer-consommable">
                @csrf
                <div class="modal-body py-4">
                    <div class="row g-3">
                        <div class="col-12 text-center mb-2">
                            <div class="fw-bold fs-5 text-primary">{{ $consommable->nom }}</div>
                            <div class="small text-muted">Stock disponible : <span class="fw-bold text-dark">{{ $consommable->quantite_stock_actuel }} {{ $consommable->typeConsommable->unite_stock }}s</span></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Quantité à sortir <span class="text-danger">*</span></label>
                            <input type="number" name="quantite" class="form-control" value="1" min="1" max="{{ $consommable->quantite_stock_actuel }}" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Raison</label>
                            <select name="raison" class="form-select">
                                <option value="Remplacement standard">Remplacement standard</option>
                                <option value="Maintenance préventive">Maintenance préventive</option>
                                <option value="Panne / Dysfonctionnement">Panne / Dysfonctionnement</option>
                                <option value="Prêt / Affectation directe">Prêt / Affectation directe</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Équipement cible <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="hidden" name="equipement_id" id="consommation-equipement-id" required>
                                <input type="text" id="consommation-equipement-label" class="form-control" placeholder="Aucun équipement sélectionné" readonly required>
                                <button type="button" class="btn btn-outline-primary" id="btn-select-equipement">
                                    <i class="fas fa-search me-1"></i>Sélectionner
                                </button>
                            </div>
                            <div class="form-text small">Cliquez sur sélectionner pour chercher une imprimante ou un poste de travail.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Notes complémentaires</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Précisions éventuelles..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger px-4" id="btn-save-consommation">
                        <i class="fas fa-check-circle me-2"></i>Valider la sortie
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
