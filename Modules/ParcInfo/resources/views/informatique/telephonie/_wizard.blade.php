<div class="modal fade" id="reseauModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light py-3">
                <h5 class="modal-title fw-bold" id="wizard-title">Ajouter un Téléphone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="bg-white border-bottom p-3">
                <div class="d-flex justify-content-center align-items-center position-relative w-75 mx-auto">
                    <div class="wizard-step-circle active" data-step="1">1</div>
                    <div class="wizard-step-line" data-after="1"></div>
                    <div class="wizard-step-circle" data-step="2">2</div>
                    <div class="wizard-step-line" data-after="2"></div>
                    <div class="wizard-step-circle" data-step="3">3</div>
                </div>
            </div>
            <form id="reseauForm">
                @csrf
                <div class="modal-body p-4 bg-light bg-opacity-50">
                    <div class="wizard-step" id="step-1">
                        <div class="row g-4">
                            <div class="col-12"><label class="form-label fw-semibold">Statut initial</label>
                                <div class="row g-2">
                                    <div class="col-6"><div class="statut-card border p-3 bg-white" data-value="en_service"><input type="radio" name="statut" value="en_service"> En service</div></div>
                                    <div class="col-6"><div class="statut-card border p-3 bg-white" data-value="en_stock"><input type="radio" name="statut" value="en_stock"> En stock</div></div>
                                </div>
                            </div>
                            <div class="col-md-6"><label class="form-label">N° Série</label><input type="text" class="form-control" name="numero_serie" required></div>
                            <div class="col-md-6"><label class="form-label">Modèle</label><input type="text" class="form-control" name="modele" required></div>
                            <div class="col-md-6"><label class="form-label">Marque</label>
                                <select class="form-select" name="marque_id">
                                    <option value="">—</option>
                                    @foreach($marques as $m)<option value="{{ $m->id }}">{{ $m->libelle }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="wizard-step d-none" id="step-2">
                        <div class="row g-4">
                            <div class="col-md-6"><label class="form-label">Extension (N° court)</label><input type="text" class="form-control" name="extension"></div>
                            <div class="col-md-6"><label class="form-label">Adresse IP (si VoIP)</label><input type="text" class="form-control" name="adresse_ip"></div>
                            <div class="col-12">
                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="est_ip" value="1" checked><label class="form-check-label">Téléphone IP (VoIP)</label></div>
                            </div>
                        </div>
                    </div>
                    <div class="wizard-step d-none" id="step-3">
                        <input type="hidden" name="local_id" id="local_id">
                        <button type="button" class="btn btn-outline-primary w-100 py-4" onclick="$(document).trigger('show:local:modal')">Sélectionner le local d'installation</button>
                        <div id="local-summary-libelle" class="mt-2 fw-bold text-center"></div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" id="btn-prev" style="display:none">Retour</button>
                    <button type="button" class="btn btn-primary" id="btn-next">Suivant</button>
                    <button type="submit" class="btn btn-success d-none" id="btn-submit">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
