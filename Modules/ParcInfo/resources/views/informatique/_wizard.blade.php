<div class="modal fade" id="equipementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px">

            {{-- Header --}}
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="wizard-title">Ajouter un équipement</h5>
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

            <form id="equipementForm" novalidate>
                @csrf
                <input type="hidden" id="equipement_id" name="id">

                <div class="modal-body px-4 py-3" style="min-height:340px">

                    {{-- ── ÉTAPE 1 : STATUT ── --}}
                    <div id="step-1" class="wizard-step">
                        <h6 class="fw-bold text-center mb-1">Statut de l'équipement</h6>
                        <p class="text-muted text-center small mb-4">Définissez l'état actuel de cet actif dans l'inventaire hospitalier.</p>
                        <div class="d-flex flex-column gap-2" id="statut-options">
                            @foreach([
                                ['en_stock',      'bi-archive',    'En stock',      'Disponible pour déploiement immédiat'],
                                ['en_service',    'bi-check-circle', 'En service',    'Actuellement utilisé par un membre du personnel'],
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
                        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Informations de l'équipement</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label field-label">Code Inventaire</label>
                                <input type="text" class="form-control field-input bg-light" name="code_inventaire" id="code_inventaire" placeholder="Généré automatiquement" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Numéro de Série <span class="text-danger">*</span></label>
                                <input type="text" class="form-control field-input" name="numero_serie" id="numero_serie" placeholder="Ex: S/N 987654321" required>
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
                                    <button type="button" class="btn btn-outline-secondary btn-add-marque-global" title="Nouvelle marque">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label field-label">Modèle <span class="text-danger">*</span></label>
                                <input type="text" class="form-control field-input" name="modele" id="modele" placeholder="Ex: Latitude 5420" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Date d'acquisition</label>
                                <input type="date" class="form-control field-input" name="date_acquisition" id="date_acquisition">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Fin de garantie</label>
                                <input type="date" class="form-control field-input" name="date_fin_garantie" id="date_fin_garantie">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Valeur d'achat (XOF)</label>
                                <input type="number" step="0.01" class="form-control field-input" name="valeur_achat" id="valeur_achat" placeholder="Ex: 500000">
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

                            {{-- Dynamic Fields configuration --}}
                            @php
                                $currentPanel = null;
                            @endphp
                            @foreach($champsConfig as $champ)
                                @if($champ->nom_panel !== $currentPanel)
                                    @php
                                        $currentPanel = $champ->nom_panel;
                                    @endphp
                                    <div class="col-12 mt-4 mb-1">
                                        <h6 class="fw-bold text-primary border-bottom pb-1" style="font-size: .85rem;">{{ $currentPanel }}</h6>
                                    </div>
                                @endif
                                
                                <div class="col-md-6">
                                    <label class="form-label field-label">
                                        {{ $champ->libelle }}
                                        @if($champ->regles_validation && str_contains($champ->regles_validation, 'required'))
                                            <span class="text-danger">*</span>
                                        @endif
                                    </label>
                                    
                                    @if($champ->type_champ === 'select')
                                        <div class="input-group">
                                            <select class="form-select field-input" name="champs_valeurs[{{ $champ->code }}]" id="champ_{{ $champ->code }}" @if($champ->regles_validation && str_contains($champ->regles_validation, 'required')) required @endif>
                                                <option value="">Sélectionner...</option>
                                                @foreach($champ->options_resolved as $valId => $valLibelle)
                                                    <option value="{{ $valId }}">{{ $valLibelle }}</option>
                                                @endforeach
                                            </select>
                                            @if($champ->source_options && str_starts_with($champ->source_options, 'DICT:'))
                                                <button type="button" class="btn btn-outline-secondary btn-quick-add-dict" 
                                                        data-dict-code="{{ substr($champ->source_options, 5) }}" 
                                                        data-field-id="champ_{{ $champ->code }}"
                                                        data-field-libelle="{{ $champ->libelle }}"
                                                        title="Ajouter une option">
                                                    <i class="bi bi-plus"></i>
                                                </button>
                                            @endif
                                        </div>
                                    @elseif($champ->type_champ === 'boolean')
                                        <select class="form-select field-input" name="champs_valeurs[{{ $champ->code }}]" id="champ_{{ $champ->code }}" @if($champ->regles_validation && str_contains($champ->regles_validation, 'required')) required @endif>
                                            <option value="">Sélectionner...</option>
                                            <option value="1">Oui</option>
                                            <option value="0">Non</option>
                                        </select>
                                    @elseif($champ->type_champ === 'number')
                                        <input type="number" class="form-control field-input" name="champs_valeurs[{{ $champ->code }}]" id="champ_{{ $champ->code }}" @if($champ->regles_validation && str_contains($champ->regles_validation, 'required')) required @endif>
                                    @elseif($champ->type_champ === 'date')
                                        <input type="date" class="form-control field-input" name="champs_valeurs[{{ $champ->code }}]" id="champ_{{ $champ->code }}" @if($champ->regles_validation && str_contains($champ->regles_validation, 'required')) required @endif>
                                    @else
                                        <input type="text" class="form-control field-input" name="champs_valeurs[{{ $champ->code }}]" id="champ_{{ $champ->code }}" @if($champ->regles_validation && str_contains($champ->regles_validation, 'required')) required @endif>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- ── ÉTAPE 3 : AFFECTATION ── --}}
                    <div id="step-3" class="wizard-step d-none">
                        <h6 class="fw-bold mb-3">Étape 1 : Responsabilité (Qui ?)</h6>
                        <div class="row g-2 mb-4" id="affectation-type-cards">
                            @foreach([
                                ['EMPLOYE',   'bi-person-badge',   'Employé'],
                                ['POSTE',     'bi-pc-display',     'Poste de Travail'],
                                ['DIRECTION', 'bi-building',       'Direction'],
                                ['SERVICE',   'bi-diagram-3',      'Service'],
                                ['UNITE',     'bi-grid-3x3-gap',   'Unité'],
                            ] as [$val,$icon,$label])
                            <div class="col">
                                <label class="aff-type-card d-flex flex-column align-items-center justify-content-center gap-2 p-2 rounded-3 border cursor-pointer text-center h-100 position-relative" data-value="{{ $val }}">
                                    <input type="radio" name="type_cible" value="{{ $val }}" class="d-none">
                                    <div class="aff-type-icon rounded-3 p-2 bg-light"><i class="bi {{ $icon }} fs-4 text-secondary"></i></div>
                                    <span class="fw-semibold text-wrap" style="font-size:.72rem">{{ $label }}</span>
                                    <i class="bi bi-check-circle-fill text-primary position-absolute top-0 end-0 m-1 d-none check-icon" style="font-size:.8rem"></i>
                                </label>
                            </div>
                            @endforeach
                        </div>

                        {{-- Carte récapitulative Employé --}}
                        <div id="aff-employe-summary" class="aff-summary d-none">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0"><i class="bi bi-person-badge text-primary me-2"></i>Employé sélectionné</h6>
                                    </div>
                                    <div class="row g-2 mt-2">
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Nom</small>
                                            <strong id="emp-summary-nom">—</strong>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">Matricule</small>
                                            <strong id="emp-summary-matricule">—</strong>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">Poste</small>
                                            <span id="emp-summary-poste">—</span>
                                        </div>
                                        <div class="col-md-12">
                                            <small class="text-muted d-block">Rattachement</small>
                                            <span id="emp-summary-rattachement">—</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="dossier_employe_id" id="dossier_employe_id">
                        </div>

                        {{-- Carte récapitulative Poste --}}
                        <div id="aff-poste-summary" class="aff-summary d-none">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0"><i class="bi bi-pc-display text-primary me-2"></i>Poste sélectionné</h6>
                                    </div>
                                    <div class="row g-2 mt-2">
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">Code</small>
                                            <strong id="poste-summary-code">—</strong>
                                        </div>
                                        <div class="col-md-5">
                                            <small class="text-muted d-block">Libellé</small>
                                            <strong id="poste-summary-libelle">—</strong>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Emplacement</small>
                                            <span id="poste-summary-emplacement">—</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="poste_travail_id" id="poste_travail_id">
                        </div>

                        {{-- Carte récapitulative Direction --}}
                        <div id="aff-direction-summary" class="aff-summary d-none">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0"><i class="bi bi-building text-primary me-2"></i>Direction sélectionnée</h6>
                                    </div>
                                    <div class="row g-2 mt-2">
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Code</small>
                                            <strong id="direction-summary-code">—</strong>
                                        </div>
                                        <div class="col-md-8">
                                            <small class="text-muted d-block">Libellé</small>
                                            <strong id="direction-summary-libelle">—</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="direction_id" id="direction_id">
                        </div>

                        {{-- Carte récapitulative Service --}}
                        <div id="aff-service-summary" class="aff-summary d-none">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0"><i class="bi bi-diagram-3 text-primary me-2"></i>Service sélectionné</h6>
                                    </div>
                                    <div class="row g-2 mt-2">
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">Code</small>
                                            <strong id="service-summary-code">—</strong>
                                        </div>
                                        <div class="col-md-5">
                                            <small class="text-muted d-block">Libellé</small>
                                            <strong id="service-summary-libelle">—</strong>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Direction</small>
                                            <span id="service-summary-direction">—</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="service_id" id="service_id">
                        </div>

                        {{-- Carte récapitulative Unité --}}
                        <div id="aff-unite-summary" class="aff-summary d-none">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0"><i class="bi bi-grid-3x3-gap text-primary me-2"></i>Unité sélectionnée</h6>
                                    </div>
                                    <div class="row g-2 mt-2">
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">Code</small>
                                            <strong id="unite-summary-code">—</strong>
                                        </div>
                                        <div class="col-md-5">
                                            <small class="text-muted d-block">Libellé</small>
                                            <strong id="unite-summary-libelle">—</strong>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Service</small>
                                            <span id="unite-summary-service">—</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="unite_id" id="unite_id">
                        </div>

                        {{-- Section Emplacement Physique (Où ?) --}}
                        <div id="emplacement-section" class="mt-4 pt-3 border-top d-none">
                            <h6 class="fw-bold mb-3"><i class="bi bi-geo-alt text-primary me-2"></i>Étape 2 : Emplacement Physique (Où ?)</h6>
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="form-label field-label">Site <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control field-input emp-site-display bg-light text-muted" readonly disabled placeholder="Automatique">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label field-label">Bâtiment <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control field-input emp-batiment-display bg-light text-muted" readonly disabled placeholder="Automatique">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label field-label">Étage <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control field-input emp-etage-display bg-light text-muted" readonly disabled placeholder="Automatique">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label field-label">Bureau / Local <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control field-input emp-local-display border-primary cursor-pointer fw-semibold text-primary" placeholder="Sélectionner un local..." readonly style="background-color: #fff; cursor: pointer;">
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
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
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
