<div class="modal fade" id="ordinateurModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px">

            {{-- Header --}}
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="wizard-title">Ajouter un ordinateur</h5>
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

            <form id="ordinateurForm" novalidate>
                @csrf
                <input type="hidden" id="ord_id" name="id">

                <div class="modal-body px-4 py-3" style="min-height:340px">

                    {{-- ── ÉTAPE 1 : STATUT ── --}}
                    <div id="step-1" class="wizard-step">
                        <h6 class="fw-bold text-center mb-1">Statut de l'équipement</h6>
                        <p class="text-muted text-center small mb-4">Définissez l'état actuel de cet actif dans l'inventaire hospitalier.</p>
                        <div class="d-flex flex-column gap-2" id="statut-options">
                            @foreach([
                                ['en_stock',      'bi-archive',    'En stock',      'Disponible pour déploiement immédiat'],
                                ['en_service',    'bi-pc-display', 'En service',    'Actuellement utilisé par un membre du personnel'],
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
                        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Informations de l'ordinateur</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label field-label">Code Inventaire <span class="text-danger">*</span></label>
                                <input type="text" class="form-control field-input" name="code_inventaire" id="code_inventaire" placeholder="Ex: CHU-PC-2024-001" required>
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
                                    <button type="button" class="btn btn-outline-secondary" id="btn-add-marque" title="Nouvelle marque">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label field-label">Modèle <span class="text-danger">*</span></label>
                                <input type="text" class="form-control field-input" name="modele" id="modele" placeholder="Ex: OptiPlex 7000" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label field-label">Type PC</label>
                                <div class="btn-group w-100" role="group">
                                    @foreach(['Portable','Fixe','Workstation'] as $t)
                                    <input type="radio" class="btn-check" name="type_pc" id="type_pc_{{ $t }}" value="{{ $t }}" {{ $t === 'Fixe' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-primary btn-sm" for="type_pc_{{ $t }}">{{ $t }}</label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">CPU</label>
                                <input type="text" class="form-control field-input" name="processeur_model" id="processeur_model" placeholder="Ex: Intel Core i7-1185G7">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">RAM</label>
                                <div class="row g-2">
                                    <div class="col-7">
                                        <select class="form-select field-input" name="ram_capacite_go" id="ram_capacite_go">
                                            <option value="">— Go —</option>
                                            @foreach([4,8,16,32,64,128] as $r)
                                            <option value="{{ $r }}">{{ $r }} Go</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-5">
                                        <div class="input-group">
                                            <select class="form-select field-input" name="ram_type_id" id="ram_type_id">
                                                <option value="">Type</option>
                                                @foreach($typesRam as $r)
                                                <option value="{{ $r->id }}">{{ $r->libelle }}</option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="btn btn-outline-secondary" id="btn-add-ram" title="Nouveau type RAM"><i class="bi bi-plus"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Système d'exploitation</label>
                                <div class="input-group">
                                    <select class="form-select field-input" name="os_type_id" id="os_type_id">
                                        <option value="">Sélectionner...</option>
                                        @foreach($typesOs as $o)
                                        <option value="{{ $o->id }}">{{ $o->libelle }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" id="btn-add-os" title="Nouveau système d'exploitation"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Stockage</label>
                                <div class="row g-2">
                                    <div class="col-7">
                                        <input type="number" class="form-control field-input" name="stockage_capacite_go" id="stockage_capacite_go" placeholder="Go">
                                    </div>
                                    <div class="col-5">
                                        <div class="input-group">
                                            <select class="form-select field-input" name="disque_type_id" id="disque_type_id">
                                                <option value="">Type</option>
                                                @foreach($typesDisque as $d)
                                                <option value="{{ $d->id }}">{{ $d->libelle }}</option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="btn btn-outline-secondary" id="btn-add-disque" title="Nouveau type de disque"><i class="bi bi-plus"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Date d'acquisition <span class="text-danger">*</span></label>
                                <input type="date" class="form-control field-input" name="date_acquisition" id="date_acquisition" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Fin de garantie</label>
                                <input type="date" class="form-control field-input" name="date_fin_garantie" id="date_fin_garantie">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Nom d'hôte</label>
                                <input type="text" class="form-control field-input" name="nom_hote" id="nom_hote" placeholder="Ex: PC-DRH-042">
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

                        {{-- Détails Employé --}}
                        <div id="aff-employe" class="aff-detail d-none">
                            <div class="card border-0 bg-light rounded-3 p-3">
                                <div class="fw-semibold small mb-3"><i class="bi bi-person-badge text-primary me-2"></i>Détails de l'employé</div>
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label field-label">Matricule</label>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control field-input" id="employe-search" placeholder="Rechercher par matricule...">
                                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                        </div>
                                        <input type="hidden" name="dossier_employe_id" id="dossier_employe_id">
                                    </div>
                                    <div class="col-md-7">
                                        <label class="form-label field-label">Nom & Prénoms</label>
                                        <input type="text" class="form-control field-input" id="employe-nom" readonly placeholder="—">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label field-label">Date d'affectation <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control field-input" name="date_debut" id="aff-date-debut-emp">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label field-label">Durée prévue (Optionnel)</label>
                                        <select class="form-select field-input" name="type_affectation" id="aff-type-emp">
                                            <option value="PERMANENTE">Permanente</option>
                                            <option value="TEMPORAIRE">Temporaire</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <div class="alert alert-info py-2 small mb-0">
                                            <i class="bi bi-info-circle me-1"></i>
                                            L'affectation à un employé génère automatiquement une fiche de prise en charge matérielle.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Détails Poste --}}
                        <div id="aff-poste" class="aff-detail d-none">
                            <div class="card border-0 bg-light rounded-3 p-3">
                                <div class="fw-semibold small mb-3"><i class="bi bi-pc-display text-primary me-2"></i>Détails du poste de travail</div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label field-label">Recherche du poste de travail</label>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control field-input" id="poste-search" placeholder="Code ou libellé du poste...">
                                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                        </div>
                                        <input type="hidden" name="poste_travail_id" id="poste_travail_id">
                                    </div>
                                    <div id="poste-detail" class="col-12 d-none">
                                        <div class="row g-2 p-2 bg-white rounded-3 border">
                                            <div class="col-md-3"><div class="text-muted" style="font-size:.7rem;text-transform:uppercase">Code Poste</div><div class="fw-bold small text-primary" id="poste-code">—</div></div>
                                            <div class="col-md-3"><div class="text-muted" style="font-size:.7rem;text-transform:uppercase">Libellé</div><div class="fw-semibold small" id="poste-libelle">—</div></div>
                                            <div class="col-md-3"><div class="text-muted" style="font-size:.7rem;text-transform:uppercase">Service</div><div class="small" id="poste-service">—</div></div>
                                            <div class="col-md-3"><div class="text-muted" style="font-size:.7rem;text-transform:uppercase">Local</div><div class="small" id="poste-local">—</div></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label field-label">Date d'affectation</label>
                                        <input type="date" class="form-control field-input" name="date_debut" id="aff-date-debut-poste">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label field-label">Durée prévue</label>
                                        <select class="form-select field-input" name="type_affectation" id="aff-type-poste">
                                            <option value="PERMANENTE">Permanente</option>
                                            <option value="TEMPORAIRE">Temporaire</option>
                                            <option value="">Indéterminée</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Détails Local --}}
                        <div id="aff-local" class="aff-detail d-none">
                            <div class="card border-0 bg-light rounded-3 p-3">
                                <div class="fw-semibold small mb-3"><i class="bi bi-geo-alt text-primary me-2"></i>Détails de la localisation</div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label field-label">Sélectionner un local</label>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control field-input" id="local-search" placeholder="Rechercher un local...">
                                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                        </div>
                                        <input type="hidden" name="local_id" id="local_id">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label field-label">Rattachement administratif <span class="text-danger">*</span></label>
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <select class="form-select field-input" name="niveau_rattachement" id="niveau_rattachement">
                                                    <option value="">Niveau...</option>
                                                    <option value="DIRECTION">Direction</option>
                                                    <option value="SERVICE">Service</option>
                                                    <option value="UNITE">Unité</option>
                                                </select>
                                            </div>
                                            <div class="col-md-8">
                                                <select class="form-select field-input" name="direction_id_aff" id="direction_id_aff">
                                                    <option value="">Structure administrative...</option>
                                                    @foreach($directions as $d)
                                                    <option value="{{ $d->id }}">{{ $d->libelle }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
