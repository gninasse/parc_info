<div class="modal fade" id="reseauModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:12px;overflow:hidden">
            <div class="modal-header bg-light border-0 py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-3"><i class="bi bi-printer text-primary fs-5"></i></div>
                    <h5 class="modal-title fw-bold" id="wizard-title">Ajouter une Imprimante</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="bg-white border-bottom p-3">
                <div class="d-flex justify-content-center align-items-center position-relative w-75 mx-auto">
                    <div class="position-absolute top-50 start-0 end-0 translate-middle-y bg-light" style="height:4px;z-index:0"></div>

                    <div class="d-flex justify-content-between w-100 position-relative" style="z-index:1">
                        <div class="text-center">
                            <div class="wizard-step-circle active" data-step="1">1</div>
                            <div class="wizard-step-label text-primary fw-bold" data-step="1">Identification</div>
                        </div>
                        <div class="wizard-step-line" data-after="1"></div>
                        <div class="text-center">
                            <div class="wizard-step-circle" data-step="2">2</div>
                            <div class="wizard-step-label text-muted" data-step="2">Spécifications</div>
                        </div>
                        <div class="wizard-step-line" data-after="2"></div>
                        <div class="text-center">
                            <div class="wizard-step-circle" data-step="3">3</div>
                            <div class="wizard-step-label text-muted" data-step="3">Emplacement</div>
                        </div>
                    </div>
                </div>
            </div>

            <form id="reseauForm">
                @csrf
                <input type="hidden" name="id" id="res_id">

                <div class="modal-body p-4 bg-light bg-opacity-50" style="min-height:50vh">
                    {{-- ── ETAPE 1 ── --}}
                    <div class="wizard-step" id="step-1">
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Statut initial <span class="text-danger">*</span></label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="statut-card border rounded-3 p-3 cursor-pointer bg-white" data-value="en_service">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input" type="radio" name="statut" value="en_service" id="st_service">
                                                <label class="form-check-label fw-bold text-success w-100" for="st_service">
                                                    <i class="bi bi-check-circle me-1"></i> En service
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="statut-card border rounded-3 p-3 cursor-pointer bg-white" data-value="en_stock">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input" type="radio" name="statut" value="en_stock" id="st_stock">
                                                <label class="form-check-label fw-bold text-secondary w-100" for="st_stock">
                                                    <i class="bi bi-box me-1"></i> En stock
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Code Inventaire</label>
                                <input type="text" class="form-control bg-light" name="code_inventaire" placeholder="Auto-généré si vide">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Numéro de série <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="numero_serie" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Marque</label>
                                <select class="form-select" name="marque_id">
                                    <option value="">Sélectionner...</option>
                                    @foreach($marques as $m)
                                        <option value="{{ $m->id }}">{{ $m->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Modèle <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="modele" required>
                            </div>
                        </div>
                    </div>

                    {{-- ── ETAPE 2 ── --}}
                    <div class="wizard-step d-none" id="step-2">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Technologie d'impression</label>
                                <div class="input-group">
                                    <select class="form-select" id="type_imprimante_id" name="type_imprimante_id">
                                        <option value="">Sélectionner...</option>
                                        @foreach($typesImprimantes as $t)
                                            <option value="{{ $t->id }}">{{ $t->libelle }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-outline-secondary" id="btn-add-type-reseau" type="button"><i class="bi bi-plus-lg"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Adresse IP</label>
                                <input type="text" class="form-control" name="adresse_ip" placeholder="Ex: 10.0.0.50">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" name="est_couleur" value="1">
                                    <label class="form-check-label fw-semibold">Supporte la couleur</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" name="est_multifonction" value="1">
                                    <label class="form-check-label fw-semibold">Multifonction (Scanner/Copieur)</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── ETAPE 3 ── --}}
                    <div class="wizard-step d-none" id="step-3">
                        <div id="aff-skip-hint" class="alert alert-info border-0 bg-info bg-opacity-10 mb-4 d-none">
                            <div class="d-flex gap-2">
                                <i class="bi bi-info-circle-fill text-info mt-1"></i>
                                <div>Vous avez sélectionné <b>En stock</b>. Cliquez sur <b>Enregistrer</b>.</div>
                            </div>
                        </div>

                        <div id="aff-selector">
                            <h6 class="fw-bold mb-3 text-primary">Emplacement de l'imprimante</h6>
                            <div class="aff-summary d-none bg-white p-3 rounded-3 border border-primary shadow-sm" id="aff-local-summary">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="fw-bold text-primary mb-0"><i class="bi bi-door-closed me-2"></i>Local sélectionné</h6>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="$(document).trigger('show:local:modal')">Changer</button>
                                </div>
                                <input type="hidden" name="local_id" id="local_id">
                                <div id="local-summary-data" class="small">
                                    <span class="fw-semibold" id="local-summary-libelle"></span>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary w-100 py-3 border-dashed" onclick="$(document).trigger('show:local:modal')" id="btn-select-local-init">
                                <i class="bi bi-geo-alt me-2"></i> Sélectionner un local
                            </button>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-light" id="btn-prev" style="display:none">Retour</button>
                    <div class="ms-auto">
                        <button type="button" class="btn btn-primary" id="btn-next">Suivant</button>
                        <button type="submit" class="btn btn-success d-none" id="btn-submit">
                            <i class="bi bi-check-lg me-1"></i> <span id="btn-submit-label">Enregistrer</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.wizard-step-circle { width: 35px; height: 35px; border-radius: 50%; background: #e9ecef; color: #6c757d; display: flex; align-items: center; justify-content: center; font-weight: bold; margin: 0 auto 8px; border: 2px solid #fff; box-shadow: 0 0 0 2px #e9ecef; transition: all .3s; }
.wizard-step-circle.active { background: var(--bs-primary); color: #fff; box-shadow: 0 0 0 2px var(--bs-primary); }
.wizard-step-circle.done { background: var(--bs-primary); color: #fff; box-shadow: 0 0 0 2px var(--bs-primary); }
.wizard-step-label { font-size: 0.8rem; }
.wizard-step-line { flex-grow: 1; height: 4px; background: #e9ecef; margin: 15px 15px 0; border-radius: 2px; transition: all .3s; }
.wizard-step-line.done { background: var(--bs-primary); }
.statut-card.selected { border-color: var(--bs-primary) !important; background-color: rgba(var(--bs-primary-rgb), .05) !important; }
.border-dashed { border-style: dashed !important; }
</style>
