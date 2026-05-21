<div class="modal fade" id="cameraModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px">

            {{-- Header --}}
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="wizard-title">Ajouter une Caméra IP</h5>
                    <small class="text-muted" id="wizard-subtitle">Sécurité Physique - CHU Yalgado</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            {{-- Stepper --}}
            <div class="px-4 pt-4 pb-2">
                <div class="d-flex align-items-center justify-content-center gap-0" id="wizard-stepper">
                    @foreach([['1','STATUT'],['2','SPECIFICATIONS'],['3','AFFECTATION']] as [$n,$label])
                    <div class="d-flex align-items-center {{ !$loop->last ? 'flex-grow-1' : '' }}">
                        <div class="d-flex flex-column align-items-center">
                            <div class="wizard-step-circle {{ $loop->first ? 'active' : '' }}" data-step="{{ $n }}">{{ $n }}</div>
                            <small class="wizard-step-label mt-1 {{ $loop->first ? 'text-primary fw-bold' : 'text-muted' }}" data-step="{{ $n }}">{{ $label }}</small>
                        </div>
                        @if(!$loop->last)
                        <div class="wizard-step-line flex-grow-1 mx-2" data-after="{{ $n }}"></div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <form id="cameraForm" novalidate>
                @csrf
                <input type="hidden" id="wf_id" name="id">

                <div class="modal-body px-4 py-3" style="min-height:340px">

                    {{-- ── ÉTAPE 1 : STATUT ── --}}
                    <div id="step-1" class="wizard-step">
                        <h6 class="fw-bold text-center mb-1">Statut de l'équipement</h6>
                        <p class="text-muted text-center small mb-4">Définissez l'état actuel de cet actif dans l'inventaire.</p>
                        <div class="d-flex flex-column gap-2" id="statut-options">
                            @foreach([
                                ['en_stock',      'bi-archive',         'En stock',      'Disponible pour déploiement immédiat'],
                                ['en_service',    'bi-camera-video',    'En service',    'Actuellement connectée et en production'],
                                ['en_reparation', 'bi-tools',            'En réparation', 'En maintenance ou défectueuse'],
                            ] as [$val,$icon,$label,$desc])
                            <label class="statut-card d-flex align-items-center gap-3 p-3 rounded-3 border cursor-pointer" data-value="{{ $val }}">
                                <input type="radio" name="statut" value="{{ $val }}" class="d-none">
                                <div class="statut-card-icon rounded-2 p-2 bg-light"><i class="bi {{ $icon }} fs-5 text-secondary"></i></div>
                                <div>
                                    <div class="fw-semibold small">{{ $label }}</div>
                                    <div class="text-muted" style="font-size:.78rem">{{ $desc }}</div>
                                </div>
                                <i class="bi bi-check-circle-fill text-primary ms-auto d-none check-icon"></i>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- ── ÉTAPE 2 : SPECIFICATIONS ── --}}
                    <div id="step-2" class="wizard-step d-none">
                        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Informations techniques de la Caméra</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label field-label">Code Inventaire</label>
                                <input type="text" class="form-control field-input bg-light" name="code_inventaire" id="code_inventaire" placeholder="Généré automatiquement (CAM-YYYY-NNNN)" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Numéro de Série <span class="text-danger">*</span></label>
                                <input type="text" class="form-control field-input" name="numero_serie" id="numero_serie" placeholder="Ex: S/N Hikvision-5432" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label field-label">Marque</label>
                                <div class="input-group">
                                    <select class="form-select field-input" name="marque_id" id="marque_id">
                                        <option value="">Sélectionner...</option>
                                        @foreach($marques as $m)
                                            <option value="{{ $m->id }}">{{ $m->libelle }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" id="btn-add-marque" title="Nouvelle marque">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label field-label">Modèle <span class="text-danger">*</span></label>
                                <input type="text" class="form-control field-input" name="modele" id="modele" placeholder="Ex: DS-2CD2143G0-I" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Adresse IP</label>
                                <input type="text" class="form-control field-input" name="adresse_ip" id="adresse_ip" placeholder="Ex: 192.168.10.50">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Adresse MAC</label>
                                <input type="text" class="form-control field-input" name="adresse_mac" id="adresse_mac" placeholder="Ex: AA:BB:CC:DD:EE:FF">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Résolution</label>
                                <input type="text" class="form-control field-input" name="resolution" id="resolution" placeholder="Ex: 1080p, 4K (8MP)">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Type de Caméra</label>
                                <select class="form-select field-input" name="type_camera" id="type_camera">
                                    <option value="">Sélectionner...</option>
                                    <option value="Dôme">Dôme</option>
                                    <option value="Tube / Bullet">Tube / Bullet</option>
                                    <option value="PTZ / Motorisée">PTZ / Motorisée</option>
                                    <option value="Boîtier / Box">Boîtier / Box</option>
                                    <option value="Autre">Autre</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label field-label">Emplacement descriptif</label>
                                <input type="text" class="form-control field-input" name="emplacement" id="emplacement" placeholder="Ex: Entrée principale, face au portail d'accueil">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">État</label>
                                <select class="form-select field-input" name="etat" id="etat">
                                    <option value="bon">Bon</option>
                                    <option value="passable">Passable</option>
                                    <option value="mauvais">Mauvais</option>
                                    <option value="avarie">Avarié</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Date d'acquisition</label>
                                <input type="date" class="form-control field-input" name="date_acquisition" id="date_acquisition">
                            </div>
                        </div>
                    </div>

                    {{-- ── ÉTAPE 3 : AFFECTATION ── --}}
                    <div id="step-3" class="wizard-step d-none">
                        <h6 class="fw-bold mb-3"><i class="bi bi-door-open text-primary me-2"></i>Sélectionner le Local d'installation</h6>
                        <p class="text-muted small mb-4">Associez cette caméra à un local ou un bureau spécifique afin de la localiser géographiquement sur site.</p>

                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <button type="button" class="btn btn-outline-primary w-100 py-4 d-flex flex-column align-items-center justify-content-center gap-2 border-dashed" id="btn-select-local" style="border-style: dashed; border-width: 2px;">
                                    <div class="rounded-circle bg-primary bg-opacity-10 p-3"><i class="bi bi-door-open fs-3 text-primary"></i></div>
                                    <span class="fw-semibold">Choisir le Local technique ou Bureau</span>
                                    <small class="text-muted">Cliquez pour parcourir les sites et locaux</small>
                                </button>
                            </div>
                        </div>

                        {{-- Récapitulatif du local --}}
                        <div id="aff-local-summary" class="aff-summary mt-4 d-none">
                            <div class="card border-primary shadow-sm" style="border-radius:12px">
                                <div class="card-body">
                                    <h6 class="mb-2 text-primary fw-semibold"><i class="bi bi-door-open-fill me-2"></i>Local sélectionné</h6>
                                    <div class="row g-2">
                                        <div class="col-md-4"><small class="text-muted d-block">Code</small><strong id="local-summary-code">—</strong></div>
                                        <div class="col-md-8"><small class="text-muted d-block">Libellé</small><strong id="local-summary-libelle">—</strong></div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="local_id" id="local_id">
                            <input type="hidden" name="type_cible" value="LOCAL">
                        </div>

                        <div class="text-center mt-4" id="aff-skip-hint">
                            <small class="text-muted">Aucune affectation sélectionnée — la caméra sera enregistrée en stock.</small>
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-between">
                    <button type="button" class="btn btn-link text-dark text-decoration-none fw-medium shadow-none" data-bs-dismiss="modal">Annuler</button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary px-4" id="btn-prev" style="display:none!important">
                            <i class="bi bi-chevron-left me-1"></i> Précédent
                        </button>
                        <button type="button" class="btn btn-success px-4 d-none" id="btn-save-reparation">
                            <i class="bi bi-tools me-1"></i> Enregistrer en réparation
                        </button>
                        <button type="button" class="btn btn-primary px-4" id="btn-next">
                            Suivant <i class="bi bi-chevron-right ms-1"></i>
                        </button>
                        <button type="submit" class="btn btn-primary px-4 d-none" id="btn-submit">
                            <i class="bi bi-floppy me-1"></i> <span id="btn-submit-label">Enregistrer la caméra</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.wizard-step-circle { width: 36px; height: 36px; border-radius: 50%; background: #e9ecef; color: #6c757d; font-weight: 700; font-size: .85rem; display: flex; align-items: center; justify-content: center; transition: all .2s; }
.wizard-step-circle.active { background: #0d6efd; color: #fff; }
.wizard-step-circle.done { background: #0d6efd; color: #fff; }
.wizard-step-line { height: 2px; background: #dee2e6; transition: background .2s; }
.wizard-step-line.done { background: #0d6efd; }
.wizard-step-label { font-size: .68rem; letter-spacing: .5px; text-transform: uppercase; }

.statut-card { cursor: pointer; transition: all .15s; border: 1px solid #dee2e6; }
.statut-card:hover { border-color: #0d6efd !important; background: #f0f6ff; }
.statut-card.selected { border-color: #0d6efd !important; background: #f0f6ff; }
.statut-card.selected .statut-card-icon { background: #dbeafe !important; }
.statut-card.selected .check-icon { display: inline !important; }

.field-label { font-size: .78rem; font-weight: 600; color: #475467; margin-bottom: 4px; }
.field-input { font-size: .875rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; }
.field-input:focus { background: #fff; border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13,110,253,.1); }
</style>
