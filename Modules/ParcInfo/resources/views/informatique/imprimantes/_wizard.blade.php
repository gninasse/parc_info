<div class="modal fade" id="imprimanteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px">

            {{-- Header --}}
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="wizard-title">Ajouter une imprimante</h5>
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

            <form id="imprimanteForm" novalidate>
                @csrf
                <input type="hidden" id="imp_id" name="id">

                <div class="modal-body px-4 py-3" style="min-height:340px">

                    {{-- ── ÉTAPE 1 : STATUT ── --}}
                    <div id="step-1" class="wizard-step">
                        <h6 class="fw-bold text-center mb-1 small text-uppercase" style="letter-spacing:.5px">Statut de l'équipement</h6>
                        <p class="text-muted text-center small mb-4">Définissez l'état actuel de cet actif dans l'inventaire.</p>
                        <div class="d-flex flex-column gap-2" id="statut-options">
                            @foreach([
                                ['en_stock',      'bi-archive',    'En stock',      'Disponible pour déploiement immédiat'],
                                ['en_service',    'bi-printer',    'En service',    'Actuellement utilisé en production'],
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
                        <h6 class="fw-bold mb-3 small text-uppercase" style="letter-spacing:.5px"><i class="bi bi-info-circle text-primary me-2"></i>Informations techniques</h6>
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
                                <div class="input-group shadow-sm rounded-3">
                                    <select class="form-select field-input border-end-0" name="marque_id" id="marque_id">
                                        <option value="">Sélectionner...</option>
                                        @foreach($marques as $m)
                                            <option value="{{ $m->id }}">{{ $m->libelle }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary border-start-0 bg-light" id="btn-add-marque" title="Nouvelle marque">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label field-label">Modèle <span class="text-danger">*</span></label>
                                <input type="text" class="form-control field-input" name="modele" id="modele" placeholder="Ex: LaserJet Pro M404dn" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Technologie d'impression</label>
                                <div class="input-group shadow-sm rounded-3">
                                    <select class="form-select field-input border-end-0" name="type_imprimante_id" id="type_imprimante_id">
                                        <option value="">Sélectionner...</option>
                                        @foreach($typesImprimantes as $t)
                                        <option value="{{ $t->id }}">{{ $t->libelle }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary border-start-0 bg-light" id="btn-add-type-imprimante" title="Nouveau type"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Adresse IP</label>
                                <input type="text" class="form-control field-input" name="adresse_ip" id="adresse_ip" placeholder="Ex: 192.168.1.50">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label d-block mb-2">Options matérielles</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="est_couleur" id="est_couleur">
                                        <label class="form-check-label small" for="est_couleur">Couleur</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="est_multifonction" id="est_multifonction">
                                        <label class="form-check-label small" for="est_multifonction">Multifonction</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Fonctions</label>
                                <input type="text" class="form-control field-input" name="fonctions" id="fonctions" placeholder="Print, Scan, Copy...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label field-label">Communauté SNMP</label>
                                <input type="text" class="form-control field-input font-monospace" name="snmp_community" id="snmp_community" placeholder="public">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label field-label">Date d'acquisition</label>
                                <input type="date" class="form-control field-input" name="date_acquisition" id="date_acquisition">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label field-label">Fin de garantie</label>
                                <input type="date" class="form-control field-input" name="date_fin_garantie" id="date_fin_garantie">
                            </div>
                            <div class="col-12">
                                <label class="form-label field-label">État général</label>
                                <div class="btn-group w-100" role="group">
                                    @foreach(['bon'=>'Bon','passable'=>'Passable','mauvais'=>'Mauvais','avarie'=>'Avarié'] as $v=>$l)
                                    <input type="radio" class="btn-check" name="etat" id="etat_{{ $v }}" value="{{ $v }}" {{ $v === 'bon' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-secondary btn-sm" for="etat_{{ $v }}">{{ $l }}</label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── ÉTAPE 3 : AFFECTATION ── --}}
                    <div id="step-3" class="wizard-step d-none">
                        <h6 class="fw-bold mb-3 small text-uppercase" style="letter-spacing:.5px">Cible de l'affectation</h6>
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

                        {{-- Carte récapitulative Employé --}}
                        <div id="aff-employe-summary" class="aff-summary d-none">
                            <div class="card border-primary bg-light bg-opacity-10">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0 small fw-bold text-primary text-uppercase"><i class="bi bi-person-badge me-2"></i>Employé sélectionné</h6>
                                    </div>
                                    <div class="row g-2 mt-2">
                                        <div class="col-md-8">
                                            <small class="text-muted d-block" style="font-size:.65rem">Nom complet</small>
                                            <strong id="emp-summary-nom" class="small">—</strong>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block" style="font-size:.65rem">Matricule</small>
                                            <strong id="emp-summary-matricule" class="small">—</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="dossier_employe_id" id="dossier_employe_id">
                        </div>

                        {{-- Carte récapitulative Poste --}}
                        <div id="aff-poste-summary" class="aff-summary d-none">
                            <div class="card border-primary bg-light bg-opacity-10">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0 small fw-bold text-primary text-uppercase"><i class="bi bi-pc-display me-2"></i>Poste sélectionné</h6>
                                    </div>
                                    <div class="row g-2 mt-2">
                                        <div class="col-md-4">
                                            <small class="text-muted d-block" style="font-size:.65rem">Code</small>
                                            <strong id="poste-summary-code" class="small">—</strong>
                                        </div>
                                        <div class="col-md-8">
                                            <small class="text-muted d-block" style="font-size:.65rem">Libellé</small>
                                            <strong id="poste-summary-libelle" class="small">—</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="poste_travail_id" id="poste_travail_id">
                        </div>

                        {{-- Carte récapitulative Local --}}
                        <div id="aff-local-summary" class="aff-summary d-none">
                            <div class="card border-primary bg-light bg-opacity-10">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0 small fw-bold text-primary text-uppercase"><i class="bi bi-door-open me-2"></i>Local sélectionné</h6>
                                    </div>
                                    <div class="row g-2 mt-2">
                                        <div class="col-md-12">
                                            <small class="text-muted d-block" style="font-size:.65rem">Libellé</small>
                                            <strong id="local-summary-libelle" class="small">—</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="local_id" id="local_id">
                        </div>

                        <div class="text-center mt-3" id="aff-skip-hint">
                            <small class="text-muted italic">Aucune affectation — l'équipement restera en stock.</small>
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-between">
                    <button type="button" class="btn btn-link text-dark text-decoration-none fw-medium shadow-none small" data-bs-dismiss="modal">Annuler</button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-4" id="btn-prev" style="display:none!important">
                            <i class="bi bi-chevron-left me-1"></i> Précédent
                        </button>
                        <button type="button" class="btn btn-success btn-sm px-4 d-none" id="btn-save-reparation">
                            <i class="bi bi-tools me-1"></i> Enregistrer
                        </button>
                        <button type="button" class="btn btn-primary btn-sm px-4" id="btn-next">
                            Suivant <i class="bi bi-chevron-right ms-1"></i>
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 d-none" id="btn-submit">
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
    width: 32px; height: 32px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    font-weight: 700;
    font-size: .8rem;
    display: flex; align-items: center; justify-content: center;
    transition: all .2s;
}
.wizard-step-circle.active  { background: #0d6efd; color: #fff; box-shadow: 0 0 0 4px rgba(13,110,253,0.15); }
.wizard-step-circle.done    { background: #198754; color: #fff; }
.wizard-step-circle.done::before { content: '✓'; }
.wizard-step-line {
    height: 2px; background: #dee2e6; transition: background .2s;
}
.wizard-step-line.done { background: #198754; }
.wizard-step-label { font-size: .62rem; letter-spacing: .5px; text-transform: uppercase; font-weight: 600; }

/* Statut cards */
.statut-card { cursor: pointer; transition: border-color .15s, background .15s; border-width: 2px !important; }
.statut-card:hover { border-color: #0d6efd !important; background: #f8fbff; }
.statut-card.selected { border-color: #0d6efd !important; background: #f0f6ff; }
.statut-card.selected .statut-card-icon { background: #dbeafe !important; }
.statut-card.selected .statut-card-icon i { color: #0d6efd !important; }
.statut-card.selected .check-icon { display: inline !important; }

/* Affectation type cards */
.aff-type-card { cursor: pointer; transition: border-color .15s; position: relative; border-width: 2px !important; }
.aff-type-card:hover { border-color: #0d6efd !important; }
.aff-type-card.selected { border-color: #0d6efd !important; background: #f0f6ff; }
.aff-type-card.selected .aff-type-icon { background: #dbeafe !important; }
.aff-type-card.selected .aff-type-icon i { color: #0d6efd !important; }
.aff-type-card.selected .check-icon { display: inline !important; }

/* Fields */
.field-label { font-size: .75rem; font-weight: 600; color: #475467; margin-bottom: 4px; }
.field-input  { font-size: .875rem; background: #fff; border: 1px solid #d0d5dd; border-radius: 8px; padding: .5rem .75rem; }
.field-input:focus { border-color: #0d6efd; box-shadow: 0 0 0 4px rgba(13,110,253,.1); outline: none; }
</style>
