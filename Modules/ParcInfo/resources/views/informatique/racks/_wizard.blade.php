<div class="modal fade" id="rackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px">
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="wizard-title">Ajouter une baie/rack</h5>
                    <small class="text-muted" id="wizard-subtitle">Configuration de l'infrastructure physique - CHU Yalgado</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

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

            <form id="rackForm" novalidate>
                @csrf
                <input type="hidden" id="rack_id" name="id">
                <input type="hidden" id="type_infra_id" name="type_infra_id">

                <div class="modal-body px-4 py-3" style="min-height:340px">
                    <div id="step-1" class="wizard-step">
                        <h6 class="fw-bold text-center mb-1">Statut de l'équipement</h6>
                        <p class="text-muted text-center small mb-4">Définissez l'état actuel de cette baie ou de ce rack.</p>
                        <div class="d-flex flex-column gap-2" id="statut-options">
                            @foreach([
                                ['en_stock', 'bi-archive', 'En stock', 'Non installé'],
                                ['en_service', 'bi-hdd-stack', 'En service', 'Installé et utilisé'],
                                ['en_reparation', 'bi-tools', 'En réparation', 'Maintenance'],
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

                    <div id="step-2" class="wizard-step d-none">
                        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Informations de la baie / du rack</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label field-label">Code Inventaire</label>
                                <input type="text" class="form-control field-input bg-light" name="code_inventaire" id="code_inventaire" placeholder="Généré automatiquement (RACK-YYYY-NNNN)" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Numéro de Série <span class="text-danger">*</span></label>
                                <input type="text" class="form-control field-input" name="numero_serie" id="numero_serie" required>
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
                                <input type="text" class="form-control field-input" name="modele" id="modele" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label field-label">Capacité en U</label>
                                <input type="number" class="form-control field-input" name="u_capacite_totale" id="u_capacite_totale" min="1" placeholder="Ex: 42">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label field-label">Nombre de prises PDU</label>
                                <input type="number" class="form-control field-input" name="nb_prises_pdu" id="nb_prises_pdu" min="0" placeholder="Ex: 12">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="est_redondant" id="est_redondant" value="1">
                                    <label class="form-check-label field-label mb-0" for="est_redondant">Alimentation redondante</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label field-label">Date d'acquisition</label>
                                <input type="date" class="form-control field-input" name="date_acquisition" id="date_acquisition">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label field-label">Mise en service</label>
                                <input type="date" class="form-control field-input" name="date_mise_en_service" id="date_mise_en_service">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label field-label">Fin de garantie</label>
                                <input type="date" class="form-control field-input" name="date_fin_garantie" id="date_fin_garantie">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Valeur d'achat (FCFA)</label>
                                <input type="number" class="form-control field-input" name="valeur_achat" id="valeur_achat" min="0">
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

                    <div id="step-3" class="wizard-step d-none">
                        <h6 class="fw-bold mb-3">Local d'installation</h6>
                        <div class="row g-3 mb-4" id="affectation-type-cards">
                            <div class="col-12">
                                <label class="aff-type-card d-flex flex-column align-items-center justify-content-center gap-2 p-3 rounded-3 border cursor-pointer text-center position-relative" data-value="LOCAL">
                                    <input type="radio" name="type_cible" value="LOCAL" class="d-none">
                                    <div class="aff-type-icon rounded-3 p-3 bg-light"><i class="bi bi-door-open fs-3 text-secondary"></i></div>
                                    <small class="fw-semibold" style="font-size:.78rem">Affecter à un local</small>
                                    <i class="bi bi-check-circle-fill text-primary position-absolute top-0 end-0 m-2 d-none check-icon" style="font-size:.9rem"></i>
                                </label>
                            </div>
                        </div>

                        <div id="aff-local-summary" class="aff-summary d-none">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0"><i class="bi bi-door-open text-primary me-2"></i>Local sélectionné</h6>
                                    </div>
                                    <div class="row g-2 mt-2">
                                        <div class="col-md-2">
                                            <small class="text-muted d-block">Code</small>
                                            <strong id="local-summary-code">—</strong>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted d-block">Libellé</small>
                                            <strong id="local-summary-libelle">—</strong>
                                        </div>
                                        <div class="col-md-2">
                                            <small class="text-muted d-block">Type</small>
                                            <span id="local-summary-type">—</span>
                                        </div>
                                        <div class="col-md-2">
                                            <small class="text-muted d-block">Étage</small>
                                            <span id="local-summary-etage">—</span>
                                        </div>
                                        <div class="col-md-2">
                                            <small class="text-muted d-block">Bâtiment</small>
                                            <span id="local-summary-batiment">—</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="local_id" id="local_id">
                        </div>

                        <div class="text-center mt-3" id="aff-skip-hint">
                            <small class="text-muted">Aucune affectation sélectionnée. L'équipement pourra rester sans installation active.</small>
                        </div>
                    </div>
                </div>

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
.wizard-step-circle {
    width: 36px; height: 36px; border-radius: 50%; background: #e9ecef; color: #6c757d; font-weight: 700;
    font-size: .85rem; display: flex; align-items: center; justify-content: center; transition: all .2s;
}
.wizard-step-circle.active { background: #0d6efd; color: #fff; }
.wizard-step-circle.done { background: #0d6efd; color: #fff; }
.wizard-step-line { height: 2px; background: #dee2e6; transition: background .2s; }
.wizard-step-line.done { background: #0d6efd; }
.wizard-step-label { font-size: .68rem; letter-spacing: .5px; text-transform: uppercase; }
.statut-card { cursor: pointer; transition: border-color .15s, background .15s; }
.statut-card:hover { border-color: #0d6efd !important; background: #f0f6ff; }
.statut-card.selected { border-color: #0d6efd !important; background: #f0f6ff; }
.statut-card.selected .statut-card-icon { background: #dbeafe !important; }
.statut-card.selected .statut-card-icon i { color: #0d6efd !important; }
.statut-card.selected .check-icon { display: inline !important; }
.aff-type-card { cursor: pointer; transition: border-color .15s; position: relative; }
.aff-type-card:hover { border-color: #0d6efd !important; }
.aff-type-card.selected { border-color: #0d6efd !important; background: #f0f6ff; }
.aff-type-card.selected .aff-type-icon { background: #dbeafe !important; }
.aff-type-card.selected .aff-type-icon i { color: #0d6efd !important; }
.aff-type-card.selected .check-icon { display: inline !important; }
.field-label { font-size: .78rem; font-weight: 600; color: #475467; margin-bottom: 4px; }
.field-input { font-size: .875rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; }
.field-input:focus { background: #fff; border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13,110,253,.1); }
</style>
