<div class="modal fade" id="onduleurModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px">

            {{-- Header --}}
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="wizard-title">Ajouter un onduleur</h5>
                    <small class="text-muted" id="wizard-subtitle">Infrastructure IT - CHU Yalgado</small>
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

            <form id="onduleurForm" novalidate>
                @csrf
                <input type="hidden" id="ond_id" name="id">

                <div class="modal-body px-4 py-3" style="min-height:340px">

                    {{-- ── ÉTAPE 1 : STATUT ── --}}
                    <div id="step-1" class="wizard-step">
                        <h6 class="fw-bold text-center mb-1">Statut de l'équipement</h6>
                        <p class="text-muted text-center small mb-4">Définissez l'état actuel de cet actif dans l'inventaire.</p>
                        <div class="d-flex flex-column gap-2" id="statut-options">
                            @foreach([
                                ['en_stock',      'bi-archive',         'En stock',      'Disponible pour déploiement immédiat'],
                                ['en_service',    'bi-lightning-charge','En service',    'Actuellement utilisé en production'],
                                ['en_reparation', 'bi-tools',            'En réparation', 'Maintenance technique ou panne'],
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
                        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Informations de l'onduleur</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label field-label">Code Inventaire</label>
                                <input type="text" class="form-control field-input bg-light" name="code_inventaire" id="code_inventaire" placeholder="Généré automatiquement (OND-YYYY-NNNN)" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Numéro de Série <span class="text-danger">*</span></label>
                                <input type="text" class="form-control field-input" name="numero_serie" id="numero_serie" placeholder="Ex: S/N 123456789" required>
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
                                <input type="text" class="form-control field-input" name="modele" id="modele" placeholder="Ex: Smart-UPS 1500" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Puissance (VA)</label>
                                <input type="number" class="form-control field-input" name="puissance_va" id="puissance_va" placeholder="Ex: 1500" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Autonomie (minutes)</label>
                                <input type="number" class="form-control field-input" name="autonomie_minutes" id="autonomie_minutes" placeholder="Ex: 15" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Dernier remplacement batterie</label>
                                <input type="date" class="form-control field-input" name="date_dernier_remplacement_batterie" id="date_dernier_remplacement_batterie">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="est_redondant" id="est_redondant" value="1">
                                    <label class="form-check-label field-label" for="est_redondant">
                                        Équipement Redondant
                                    </label>
                                </div>
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
                                ['EMPLOYE','bi-person-badge','Employé'],
                                ['POSTE',  'bi-pc-display',  'Poste'],
                                ['LOCAL',  'bi-door-open',   'Local'],
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

                        {{-- Récapitulatifs (mis à jour par selection_modals.js) --}}
                        <div id="aff-employe-summary" class="aff-summary d-none">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <h6 class="mb-2"><i class="bi bi-person-badge text-primary me-2"></i>Employé sélectionné</h6>
                                    <div class="row g-2">
                                        <div class="col-md-6"><small class="text-muted d-block">Nom</small><strong id="emp-summary-nom">—</strong></div>
                                        <div class="col-md-6"><small class="text-muted d-block">Matricule</small><strong id="emp-summary-matricule">—</strong></div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="dossier_employe_id" id="dossier_employe_id">
                        </div>

                        <div id="aff-poste-summary" class="aff-summary d-none">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <h6 class="mb-2"><i class="bi bi-pc-display text-primary me-2"></i>Poste sélectionné</h6>
                                    <div class="row g-2">
                                        <div class="col-md-4"><small class="text-muted d-block">Code</small><strong id="poste-summary-code">—</strong></div>
                                        <div class="col-md-8"><small class="text-muted d-block">Libellé</small><strong id="poste-summary-libelle">—</strong></div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="poste_travail_id" id="poste_travail_id">
                        </div>

                        <div id="aff-local-summary" class="aff-summary d-none">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <h6 class="mb-2"><i class="bi bi-door-open text-primary me-2"></i>Local sélectionné</h6>
                                    <div class="row g-2">
                                        <div class="col-md-4"><small class="text-muted d-block">Code</small><strong id="local-summary-code">—</strong></div>
                                        <div class="col-md-8"><small class="text-muted d-block">Libellé</small><strong id="local-summary-libelle">—</strong></div>
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
.wizard-step-circle { width: 36px; height: 36px; border-radius: 50%; background: #e9ecef; color: #6c757d; font-weight: 700; font-size: .85rem; display: flex; align-items: center; justify-content: center; transition: all .2s; }
.wizard-step-circle.active { background: #0d6efd; color: #fff; }
.wizard-step-circle.done { background: #0d6efd; color: #fff; }
.wizard-step-line { height: 2px; background: #dee2e6; transition: background .2s; }
.wizard-step-line.done { background: #0d6efd; }
.wizard-step-label { font-size: .68rem; letter-spacing: .5px; text-transform: uppercase; }

.statut-card, .aff-type-card { cursor: pointer; transition: all .15s; border: 1px solid #dee2e6; }
.statut-card:hover, .aff-type-card:hover { border-color: #0d6efd !important; background: #f0f6ff; }
.statut-card.selected, .aff-type-card.selected { border-color: #0d6efd !important; background: #f0f6ff; }
.statut-card.selected .statut-card-icon, .aff-type-card.selected .aff-type-icon { background: #dbeafe !important; }
.statut-card.selected .check-icon, .aff-type-card.selected .check-icon { display: inline !important; }

.field-label { font-size: .78rem; font-weight: 600; color: #475467; margin-bottom: 4px; }
.field-input { font-size: .875rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; }
.field-input:focus { background: #fff; border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13,110,253,.1); }
</style>
