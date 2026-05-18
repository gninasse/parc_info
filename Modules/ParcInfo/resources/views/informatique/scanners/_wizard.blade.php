<div class="modal fade" id="scannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px">

            {{-- Header --}}
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="wizard-title">Ajouter un scanner</h5>
                    <small class="text-muted" id="wizard-subtitle">Configuration de l'infrastructure IT - CHU Yalgado</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            {{-- Stepper --}}
            <div class="px-4 pt-4 pb-2">
                <div class="d-flex align-items-center justify-content-center gap-0" id="wizard-stepper">
                    @foreach([['1','STATUT'],['2','INFORMATIONS'],['3','AFFECTATION']] as [$n,$label])
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

            <form id="scannerForm" novalidate>
                @csrf
                <input type="hidden" id="scn_id" name="id">

                <div class="modal-body px-4 py-3" style="min-height:340px">

                    {{-- ── ÉTAPE 1 : STATUT ── --}}
                    <div id="step-1" class="wizard-step">
                        <h6 class="fw-bold text-center mb-1">Statut de l'équipement</h6>
                        <p class="text-muted text-center small mb-4">Définissez l'état actuel de cet actif dans l'inventaire hospitalier.</p>
                        <div class="d-flex flex-column gap-2" id="statut-options">
                            @foreach([
                                ['en_stock',      'bi-archive',    'En stock',      'Disponible pour déploiement immédiat'],
                                ['en_service',    'bi-upc-scan',   'En service',    'Actuellement utilisé par un membre du personnel'],
                                ['en_reparation', 'bi-tools',      'En réparation', 'Maintenance technique ou panne signalée'],
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

                    {{-- ── ÉTAPE 2 : INFORMATIONS ── --}}
                    <div id="step-2" class="wizard-step d-none">
                        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Informations du scanner</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label field-label">Code Inventaire</label>
                                <input type="text" class="form-control field-input bg-light" name="code_inventaire" id="code_inventaire" placeholder="Généré automatiquement" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Numéro de Série <span class="text-danger">*</span></label>
                                <input type="text" class="form-control field-input" name="numero_serie" id="numero_serie" placeholder="Ex: S/N 12345678" required>
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
                                <input type="text" class="form-control field-input" name="modele" id="modele" placeholder="Ex: ScanJet Pro 4500" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label field-label">Résolution DPI max</label>
                                <input type="number" class="form-control field-input" name="resolution_dpi_max" id="resolution_dpi_max" placeholder="Ex: 600" min="72" max="9600">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Format maximum</label>
                                <select class="form-select field-input" name="format_max" id="format_max">
                                    <option value="">Sélectionner...</option>
                                    @foreach(['A4','A3','A2','Lettre','Légal'] as $f)
                                    <option value="{{ $f }}">{{ $f }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label field-label">Type de capteur</label>
                                <select class="form-select field-input" name="type_capteur" id="type_capteur">
                                    <option value="">Sélectionner...</option>
                                    <option value="CCD">CCD</option>
                                    <option value="CIS">CIS</option>
                                    <option value="CMOS">CMOS</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Interface de connexion</label>
                                <select class="form-select field-input" name="interface_connexion" id="interface_connexion">
                                    <option value="">Sélectionner...</option>
                                    <option value="USB">USB</option>
                                    <option value="Ethernet">Ethernet</option>
                                    <option value="WiFi">WiFi</option>
                                    <option value="USB + WiFi">USB + WiFi</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label field-label">Adresse IP (si réseau)</label>
                                <input type="text" class="form-control field-input" name="adresse_ip" id="adresse_ip" placeholder="192.168.x.x">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">État général</label>
                                <select class="form-select field-input" name="etat" id="etat">
                                    <option value="bon">Bon</option>
                                    <option value="passable">Passable</option>
                                    <option value="mauvais">Mauvais</option>
                                    <option value="avarie">Avarié</option>
                                </select>
                            </div>

                            <div class="col-12 d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="est_recto_verso" id="est_recto_verso" value="1">
                                    <label class="form-check-label small" for="est_recto_verso">Recto-Verso automatique</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="a_chargeur_auto" id="a_chargeur_auto" value="1">
                                    <label class="form-check-label small" for="a_chargeur_auto">Chargeur automatique (ADF)</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label field-label">Date d'acquisition</label>
                                <input type="date" class="form-control field-input" name="date_acquisition" id="date_acquisition">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Fin de garantie</label>
                                <input type="date" class="form-control field-input" name="date_fin_garantie" id="date_fin_garantie">
                            </div>
                        </div>
                    </div>

                    {{-- ── ÉTAPE 3 : AFFECTATION ── --}}
                    <div id="step-3" class="wizard-step d-none">
                        <h6 class="fw-bold mb-3">Type d'affectation</h6>
                        <div class="row g-3 mb-4" id="affectation-type-cards">
                            @foreach([
                                ['EMPLOYE','bi-person-badge','Affecter à un employé'],
                                ['POSTE',  'bi-pc-display',  'Affecter à un poste'],
                                ['LOCAL',  'bi-door-open',   'Affecter à un local'],
                            ] as [$val,$icon,$label])
                            <div class="col-4">
                                <label class="aff-type-card d-flex flex-column align-items-center justify-content-center gap-2 p-3 rounded-3 border cursor-pointer text-center" data-value="{{ $val }}">
                                    <input type="radio" name="type_cible" value="{{ $val }}" class="d-none">
                                    <div class="aff-type-icon rounded-3 p-3 bg-light"><i class="bi {{ $icon }} fs-3 text-secondary"></i></div>
                                    <small class="fw-semibold" style="font-size:.78rem">{{ $label }}</small>
                                    <i class="bi bi-check-circle-fill text-primary position-absolute top-0 end-0 m-2 d-none check-icon" style="font-size:.9rem"></i>
                                </label>
                            </div>
                            @endforeach
                        </div>

                        {{-- Summary cards --}}
                        <div id="aff-employe-summary" class="aff-summary d-none">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <h6 class="mb-3 small fw-bold text-primary"><i class="bi bi-person-badge me-2"></i>Employé sélectionné</h6>
                                    <div class="row g-2 mt-2">
                                        <div class="col-md-6"><small class="text-muted d-block">Nom</small><strong id="emp-summary-nom">—</strong></div>
                                        <div class="col-md-3"><small class="text-muted d-block">Matricule</small><strong id="emp-summary-matricule">—</strong></div>
                                        <div class="col-md-3"><small class="text-muted d-block">Poste</small><span id="emp-summary-poste">—</span></div>
                                        <div class="col-md-12"><small class="text-muted d-block">Rattachement</small><span id="emp-summary-rattachement">—</span></div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="dossier_employe_id" id="dossier_employe_id">
                        </div>

                        <div id="aff-poste-summary" class="aff-summary d-none">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <h6 class="mb-3 small fw-bold text-primary"><i class="bi bi-pc-display me-2"></i>Poste sélectionné</h6>
                                    <div class="row g-2 mt-2">
                                        <div class="col-md-3"><small class="text-muted d-block">Code</small><strong id="poste-summary-code">—</strong></div>
                                        <div class="col-md-5"><small class="text-muted d-block">Libellé</small><strong id="poste-summary-libelle">—</strong></div>
                                        <div class="col-md-4"><small class="text-muted d-block">Emplacement</small><span id="poste-summary-emplacement">—</span></div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="poste_travail_id" id="poste_travail_id">
                        </div>

                        <div id="aff-local-summary" class="aff-summary d-none">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <h6 class="mb-3 small fw-bold text-primary"><i class="bi bi-door-open me-2"></i>Local sélectionné</h6>
                                    <div class="row g-2 mt-2">
                                        <div class="col-md-2"><small class="text-muted d-block">Code</small><strong id="local-summary-code">—</strong></div>
                                        <div class="col-md-4"><small class="text-muted d-block">Libellé</small><strong id="local-summary-libelle">—</strong></div>
                                        <div class="col-md-6"><small class="text-muted d-block">Nom complet</small><span id="local-summary-complet">—</span></div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="local_id" id="local_id">
                        </div>

                        <div class="text-center mt-3" id="aff-skip-hint">
                            <small class="text-muted">Aucune affectation sélectionnée — l'équipement sera enregistré en stock.</small>
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
                            <i class="bi bi-floppy me-1"></i> <span id="btn-submit-label">Enregistrer l'actif</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Stepper */
.wizard-step-circle {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    font-weight: 700;
    font-size: .85rem;
    display: flex; align-items: center; justify-content: center;
    transition: all .2s;
}
.wizard-step-circle.active  { background: #0d6efd; color: #fff; }
.wizard-step-circle.done    { background: #0d6efd; color: #fff; }
.wizard-step-circle.done::before { content: '✓'; }
.wizard-step-line {
    height: 2px; background: #dee2e6; transition: background .2s;
}
.wizard-step-line.done { background: #0d6efd; }
.wizard-step-label { font-size: .68rem; letter-spacing: .5px; text-transform: uppercase; }

/* Statut cards */
.statut-card { cursor: pointer; transition: border-color .15s, background .15s; }
.statut-card:hover { border-color: #0d6efd !important; background: #f0f6ff; }
.statut-card.selected { border-color: #0d6efd !important; background: #f0f6ff; }
.statut-card.selected .statut-card-icon { background: #dbeafe !important; }
.statut-card.selected .statut-card-icon i { color: #0d6efd !important; }
.statut-card.selected .check-icon { display: inline !important; }

/* Affectation type cards */
.aff-type-card { cursor: pointer; transition: border-color .15s; position: relative; }
.aff-type-card:hover { border-color: #0d6efd !important; }
.aff-type-card.selected { border-color: #0d6efd !important; background: #f0f6ff; }
.aff-type-card.selected .aff-type-icon { background: #dbeafe !important; }
.aff-type-card.selected .aff-type-icon i { color: #0d6efd !important; }
.aff-type-card.selected .check-icon { display: inline !important; }

/* Fields */
.field-label { font-size: .78rem; font-weight: 600; color: #475467; margin-bottom: 4px; }
.field-input  { font-size: .875rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; }
.field-input:focus { background: #fff; border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13,110,253,.1); }
</style>
