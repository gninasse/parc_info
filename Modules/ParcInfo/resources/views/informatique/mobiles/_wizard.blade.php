<div class="modal fade" id="mobileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px">

            {{-- Header --}}
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="wizard-title">Ajouter un équipement mobile</h5>
                    <small class="text-muted" id="wizard-subtitle">Configuration des terminaux mobiles - CHU Yalgado</small>
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

            <form id="mobileForm" novalidate>
                @csrf
                <input type="hidden" id="mob_id" name="id">

                <div class="modal-body px-4 py-3" style="min-height:400px">

                    {{-- ── ÉTAPE 1 : STATUT ── --}}
                    <div id="step-1" class="wizard-step">
                        <h6 class="fw-bold text-center mb-1">Statut de l'équipement</h6>
                        <p class="text-muted text-center small mb-4">Définissez l'état actuel de cet actif dans l'inventaire hospitalier.</p>
                        <div class="d-flex flex-column gap-2" id="statut-options">
                            @foreach([
                                ['en_stock',      'bi-archive',       'En stock',      'Disponible pour déploiement immédiat'],
                                ['en_service',    'bi-phone-vibrate', 'En service',    'Actuellement utilisé par un membre du personnel'],
                                ['en_reparation', 'bi-tools',         'En réparation', 'Maintenance technique ou panne signalée'],
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
                        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Informations techniques</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label field-label">Code Inventaire</label>
                                <input type="text" class="form-control field-input bg-light" name="code_inventaire" id="code_inventaire" placeholder="Généré automatiquement" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Numéro de Série <span class="text-danger">*</span></label>
                                <input type="text" class="form-control field-input" name="numero_serie" id="numero_serie" placeholder="Ex: S/N 987654321" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Marque</label>
                                <div class="input-group">
                                    <select class="form-select field-input" name="marque_id" id="marque_id">
                                        <option value="">Sélectionner...</option>
                                        @foreach($marques as $m)
                                            <option value="{{ $m->id }}">{{ $m->libelle }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary shadow-none" id="btn-add-marque" title="Nouvelle marque">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Modèle <span class="text-danger">*</span></label>
                                <input type="text" class="form-control field-input" name="modele" id="modele" placeholder="Ex: Galaxy Tab S9, iPhone 15" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Type de Mobile</label>
                                <div class="input-group">
                                    <select class="form-select field-input" name="type_mobile_id" id="type_mobile_id">
                                        <option value="">Sélectionner...</option>
                                        @foreach($typesMobiles as $t)
                                            <option value="{{ $t->id }}">{{ $t->libelle }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary shadow-none" id="btn-add-type-mobile" title="Nouveau type">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Version OS</label>
                                <input type="text" class="form-control field-input" name="version_os" id="version_os" placeholder="Ex: Android 14, iOS 17.2">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">IMEI 1</label>
                                <input type="text" class="form-control field-input" name="imei_1" id="imei_1" placeholder="N° IMEI principal">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">IMEI 2 (Optionnel)</label>
                                <input type="text" class="form-control field-input" name="imei_2" id="imei_2" placeholder="N° IMEI secondaire">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">N° Tél associé</label>
                                <input type="text" class="form-control field-input" name="num_tel_associe" id="num_tel_associe" placeholder="Ex: +226 70 00 00 00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Statut MDM</label>
                                <input type="text" class="form-control field-input" name="statut_mdm" id="statut_mdm" placeholder="Ex: Enrôlé, Non enrôlé">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label field-label">Batterie (mAh)</label>
                                <input type="number" class="form-control field-input" name="capacite_batterie_mah" id="capacite_batterie_mah" placeholder="Ex: 5000">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label field-label">État Écran</label>
                                <select class="form-select field-input" name="etat_ecran" id="etat_ecran">
                                    <option value="Intact">Intact</option>
                                    <option value="Rayé">Rayé</option>
                                    <option value="Fissuré">Fissuré</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label field-label">État Général</label>
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
                            <div class="col-md-6">
                                <label class="form-label field-label">Fin de garantie</label>
                                <input type="date" class="form-control field-input" name="date_fin_garantie" id="date_fin_garantie">
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="a_coque_protection" id="a_coque_protection" value="1" checked>
                                    <label class="form-check-label small fw-semibold" for="a_coque_protection">Possède une coque de protection / verre trempé</label>
                                </div>
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

                        {{-- Résumés de sélection --}}
                        <div id="aff-employe-summary" class="aff-summary d-none">
                            <div class="card border-primary bg-primary bg-opacity-10">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1 fw-bold text-primary"><i class="bi bi-person-badge me-2"></i>Employé sélectionné</h6>
                                            <div id="emp-summary-nom" class="fw-bold">—</div>
                                            <div class="small text-muted">Matricule: <span id="emp-summary-matricule">—</span></div>
                                            <div class="small text-muted">Poste: <span id="emp-summary-poste">—</span></div>
                                            <div class="small text-muted">Rattachement: <span id="emp-summary-rattachement">—</span></div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary aff-type-card" data-value="EMPLOYE">Changer</button>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="dossier_employe_id" id="dossier_employe_id">
                        </div>

                        <div id="aff-poste-summary" class="aff-summary d-none">
                            <div class="card border-primary bg-primary bg-opacity-10">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1 fw-bold text-primary"><i class="bi bi-pc-display me-2"></i>Poste sélectionné</h6>
                                            <div id="poste-summary-code" class="fw-bold">—</div>
                                            <div id="poste-summary-libelle" class="small text-muted">—</div>
                                            <div class="small text-muted">Emplacement: <span id="poste-summary-emplacement">—</span></div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary aff-type-card" data-value="POSTE">Changer</button>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="poste_travail_id" id="poste_travail_id">
                        </div>

                        <div id="aff-local-summary" class="aff-summary d-none">
                            <div class="card border-primary bg-primary bg-opacity-10">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1 fw-bold text-primary"><i class="bi bi-door-open me-2"></i>Local sélectionné</h6>
                                            <div id="local-summary-code" class="fw-bold">—</div>
                                            <div id="local-summary-libelle" class="small text-muted">—</div>
                                            <div class="small text-muted">Emplacement: <span id="local-summary-complet">—</span></div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary aff-type-card" data-value="LOCAL">Changer</button>
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
                    <button type="button" class="btn btn-link text-dark text-decoration-none fw-medium shadow-none px-0" data-bs-dismiss="modal">Annuler</button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary px-4 shadow-none" id="btn-prev" style="display:none!important">
                            <i class="bi bi-chevron-left me-1"></i> Précédent
                        </button>
                        <button type="button" class="btn btn-success px-4 shadow-none d-none" id="btn-save-reparation">
                            <i class="bi bi-tools me-1"></i> Enregistrer en réparation
                        </button>
                        <button type="button" class="btn btn-primary px-4 shadow-none" id="btn-next">
                            Suivant <i class="bi bi-chevron-right ms-1"></i>
                        </button>
                        <button type="submit" class="btn btn-primary px-4 shadow-none d-none" id="btn-submit">
                            <i class="bi bi-floppy me-1"></i> <span id="btn-submit-label">Enregistrer l'actif</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Stepper UI */
.wizard-step-circle {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    font-weight: 700;
    font-size: .85rem;
    display: flex; align-items: center; justify-content: center;
    transition: all .2s;
    border: 2px solid transparent;
}
.wizard-step-circle.active  { background: #0d6efd; color: #fff; box-shadow: 0 0 0 4px rgba(13,110,253,.15); }
.wizard-step-circle.done    { background: #e0eeff; color: #0d6efd; border-color: #0d6efd; }
.wizard-step-circle.done::before { content: '✓'; }
.wizard-step-line { height: 2px; background: #dee2e6; transition: background .2s; }
.wizard-step-line.done { background: #0d6efd; }
.wizard-step-label { font-size: .68rem; letter-spacing: .5px; text-transform: uppercase; }

/* Statut cards */
.statut-card { cursor: pointer; transition: all .15s ease-in-out; border: 1.5px solid #dee2e6 !important; }
.statut-card:hover { border-color: #0d6efd !important; background: #f0f6ff; }
.statut-card.selected { border-color: #0d6efd !important; background: #f0f6ff; box-shadow: 0 4px 12px rgba(13,110,253,.08); }
.statut-card.selected .statut-card-icon { background: #dbeafe !important; }
.statut-card.selected .statut-card-icon i { color: #0d6efd !important; }
.statut-card.selected .check-icon { display: inline !important; }

/* Affectation type cards */
.aff-type-card { cursor: pointer; transition: all .15s ease-in-out; position: relative; border: 1.5px solid #dee2e6 !important; }
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
