<div class="modal fade" id="serveurModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px">

            {{-- Header --}}
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="wizard-title">Ajouter un serveur</h5>
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

            <form id="serveurForm" novalidate>
                @csrf
                <input type="hidden" id="srv_id" name="id">

                <div class="modal-body px-4 py-3" style="min-height:340px">

                    {{-- ── ÉTAPE 1 : STATUT ── --}}
                    <div id="step-1" class="wizard-step">
                        <h6 class="fw-bold text-center mb-1">Statut du serveur</h6>
                        <p class="text-muted text-center small mb-4">Définissez l'état actuel de cet actif dans l'infrastructure.</p>
                        <div class="d-flex flex-column gap-2" id="statut-options">
                            @foreach([
                                ['en_stock',      'bi-archive',    'En stock',      'Prêt pour déploiement ou VM non démarrée'],
                                ['en_service',    'bi-server',     'En service',    'Serveur actif et opérationnel'],
                                ['en_reparation', 'bi-tools',      'En réparation', 'Maintenance matérielle ou logicielle'],
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
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label field-label">Type de serveur <span class="text-danger">*</span></label>
                                <select class="form-select field-input" name="type_serveur" id="type_serveur" required>
                                    <option value="Physique">Serveur Physique</option>
                                    <option value="Virtuel">Machine Virtuelle (VM)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Rôle / Fonction</label>
                                <input type="text" class="form-control field-input" name="role_serveur" id="role_serveur" placeholder="Ex: AD, SQL, Web, App">
                            </div>

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
                                    <button type="button" class="btn btn-outline-secondary btn-add-nomenclature" data-type="marque"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label field-label">Modèle <span class="text-danger">*</span></label>
                                <input type="text" class="form-control field-input" name="modele" id="modele" placeholder="Ex: PowerEdge R740 / ProLiant DL380" required>
                            </div>

                            <div id="section-vm" class="col-12 d-none">
                                <div class="card bg-light border-0 shadow-none p-3">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label field-label">Serveur Hôte (Physique)</label>
                                            <select class="form-select field-input" name="serveur_hote_id" id="serveur_hote_id">
                                                <option value="">Sélectionner l'hôte...</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label field-label">Hyperviseur</label>
                                            <input type="text" class="form-control field-input" name="hyperviseur" id="hyperviseur" placeholder="Ex: ESXi 7.0, Hyper-V">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label field-label">OS</label>
                                <div class="input-group">
                                    <select class="form-select field-input" name="os_type_id" id="os_type_id">
                                        <option value="">Sélectionner...</option>
                                        @foreach($typesOs as $o)
                                        <option value="{{ $o->id }}">{{ $o->libelle }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary btn-add-nomenclature" data-type="os"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Nom d'hôte / FQDN</label>
                                <input type="text" class="form-control field-input" name="nom_hote" id="nom_hote" placeholder="Ex: srv-db-01.chu.local">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label field-label">Adresse IP</label>
                                <input type="text" class="form-control field-input" name="adresse_ip" id="adresse_ip" placeholder="Ex: 192.168.1.10">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label field-label">Adresse MAC</label>
                                <input type="text" class="form-control field-input" name="adresse_mac" id="adresse_mac" placeholder="Ex: 00:1A:2B:3C:4D:5E">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label field-label">Processeur (CPU)</label>
                                <select class="form-select field-input" name="cpu_type_id" id="cpu_type_id">
                                    <option value="">Type...</option>
                                    @foreach($typesCpu as $c)
                                    <option value="{{ $c->id }}">{{ $c->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label field-label">Nb Proc / Cœurs</label>
                                <div class="input-group">
                                    <input type="number" class="form-control field-input" name="nb_processeurs" id="nb_processeurs" placeholder="CPU" title="Nombre de processeurs physiques">
                                    <input type="number" class="form-control field-input" name="nb_coeurs_total" id="nb_coeurs_total" placeholder="Cœurs" title="Nombre total de cœurs">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label field-label">RAM (Go)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control field-input" name="ram_capacite_go" id="ram_capacite_go" placeholder="Capacité">
                                    <select class="form-select field-input" name="ram_type_id" id="ram_type_id">
                                        <option value="">Type</option>
                                        @foreach($typesRam as $r)
                                        <option value="{{ $r->id }}">{{ $r->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-12">
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
                        <h6 class="fw-bold mb-3 text-center">Affectation du serveur</h6>
                        <div class="row g-3 mb-4 justify-content-center" id="affectation-type-cards">
                            @foreach([
                                ['LOCAL',  'bi-door-open',   'Salle Serveurs / Local'],
                                ['POSTE',  'bi-diagram-3',   'Rack / Baie'],
                            ] as [$val,$icon,$label])
                            <div class="col-5">
                                <label class="aff-type-card d-flex flex-column align-items-center justify-content-center gap-2 p-3 rounded-3 border cursor-pointer text-center" data-value="{{ $val }}">
                                    <input type="radio" name="type_cible" value="{{ $val }}" class="d-none">
                                    <div class="aff-type-icon rounded-3 p-3 bg-light"><i class="bi {{ $icon }} fs-3 text-secondary"></i></div>
                                    <small class="fw-semibold" style="font-size:.78rem">{{ $label }}</small>
                                    <i class="bi bi-check-circle-fill text-primary position-absolute top-0 end-0 m-2 d-none check-icon" style="font-size:.9rem"></i>
                                </label>
                            </div>
                            @endforeach
                        </div>

                        {{-- Carte récapitulative Local --}}
                        <div id="aff-local-summary" class="aff-summary d-none">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <h6 class="mb-3 fw-bold"><i class="bi bi-door-open text-primary me-2"></i>Local sélectionné</h6>
                                    <div class="row g-2">
                                        <div class="col-md-3"><small class="text-muted d-block">Code</small><strong id="local-summary-code">—</strong></div>
                                        <div class="col-md-6"><small class="text-muted d-block">Libellé</small><strong id="local-summary-libelle">—</strong></div>
                                        <div class="col-md-3"><small class="text-muted d-block">Étage</small><span id="local-summary-etage">—</span></div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="local_id" id="local_id">
                        </div>

                        {{-- Carte récapitulative Poste (utilisé ici pour Rack) --}}
                        <div id="aff-poste-summary" class="aff-summary d-none">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <h6 class="mb-3 fw-bold"><i class="bi bi-diagram-3 text-primary me-2"></i>Rack / Baie sélectionné</h6>
                                    <div class="row g-2">
                                        <div class="col-md-4"><small class="text-muted d-block">Code</small><strong id="poste-summary-code">—</strong></div>
                                        <div class="col-md-8"><small class="text-muted d-block">Emplacement</small><span id="poste-summary-emplacement">—</span></div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="poste_travail_id" id="poste_travail_id">
                        </div>

                        <div class="text-center mt-3" id="aff-skip-hint">
                            <small class="text-muted">Aucune affectation sélectionnée — le serveur sera enregistré en stock.</small>
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
                            <i class="bi bi-floppy me-1"></i> <span id="btn-submit-label">Enregistrer le serveur</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
