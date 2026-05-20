<!-- Wizard Modal -->
<div class="modal fade" id="equipementReseauModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="modal-title fw-bold" id="wizard-title">Ajouter un équipement réseau</h5>
                    <p class="text-muted small mb-0" id="wizard-subtitle">Étape 1 sur 3</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <!-- Stepper -->
                <div class="d-flex justify-content-between position-relative mb-5 px-3">
                    <div class="position-absolute top-50 start-0 translate-middle-y w-100 px-4" style="z-index: 0;">
                        <div class="progress" style="height: 4px;">
                            <div class="progress-bar bg-primary" id="wizard-progress" role="progressbar" style="width: 0%;"></div>
                        </div>
                    </div>

                    <div class="position-relative z-1 text-center">
                        <div class="wizard-step-circle active" data-step="1">1</div>
                        <div class="wizard-step-label text-primary fw-bold mt-2 small">Statut</div>
                    </div>
                    <div class="position-relative z-1 text-center">
                        <div class="wizard-step-circle" data-step="2">2</div>
                        <div class="wizard-step-label text-muted mt-2 small">Informations</div>
                    </div>
                    <div class="position-relative z-1 text-center">
                        <div class="wizard-step-circle" data-step="3" id="nav-step-3">3</div>
                        <div class="wizard-step-label text-muted mt-2 small">Affectation</div>
                    </div>
                </div>

                <form id="equipementReseauForm">
                    <input type="hidden" name="id" id="eq_id">
                    <input type="hidden" name="skip_affectation" id="skip_affectation" value="0">

                    <!-- STEP 1: Statut -->
                    <div class="wizard-step" id="step-1">
                        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Quel est le statut initial de cet équipement ?</h6>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="w-100">
                                    <input type="radio" name="statut" value="en_stock" class="d-none stat-radio">
                                    <div class="card h-100 border-2 cursor-pointer stat-card text-center p-3 transition-all">
                                        <div class="text-secondary mb-2"><i class="bi bi-archive fs-1"></i></div>
                                        <h6 class="fw-bold text-dark">En stock</h6>
                                        <p class="small text-muted mb-0">Disponible pour un déploiement futur</p>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="w-100">
                                    <input type="radio" name="statut" value="en_service" class="d-none stat-radio">
                                    <div class="card h-100 border-2 cursor-pointer stat-card text-center p-3 transition-all">
                                        <div class="text-success mb-2"><i class="bi bi-router fs-1"></i></div>
                                        <h6 class="fw-bold text-dark">En service</h6>
                                        <p class="small text-muted mb-0">Actuellement en production</p>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="w-100">
                                    <input type="radio" name="statut" value="en_reparation" class="d-none stat-radio">
                                    <div class="card h-100 border-2 cursor-pointer stat-card text-center p-3 transition-all">
                                        <div class="text-warning mb-2"><i class="bi bi-tools fs-1"></i></div>
                                        <h6 class="fw-bold text-dark">En réparation</h6>
                                        <p class="small text-muted mb-0">Maintenance ou panne</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Informations -->
                    <div class="wizard-step d-none" id="step-2">
                        <!-- Section 01: Identification -->
                        <div class="mb-4">
                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">01 — Identification</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Code Inventaire</label>
                                    <input type="text" class="form-control bg-light" name="code_inventaire" id="code_inventaire" readonly placeholder="Généré automatiquement (NET-YYYY-XXXX)">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Numéro de série <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="numero_serie" id="numero_serie" required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Marque</label>
                                    <div class="input-group">
                                        <select class="form-select" name="marque_id" id="marque_id">
                                            <option value="">Sélectionner...</option>
                                            @foreach($marques as $marque)
                                                <option value="{{ $marque->id }}">{{ $marque->libelle }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-outline-secondary" type="button" id="btn-add-marque" title="Nouvelle marque"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Modèle <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="modele" id="modele" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Date acquisition</label>
                                    <input type="date" class="form-control" name="date_acquisition" id="date_acquisition">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Mise en service</label>
                                    <input type="date" class="form-control" name="date_mise_en_service" id="date_mise_en_service">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Fin de garantie</label>
                                    <input type="date" class="form-control" name="date_fin_garantie" id="date_fin_garantie">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Valeur d'achat (FCFA)</label>
                                    <input type="number" class="form-control" name="valeur_achat" id="valeur_achat">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">État général <span class="text-danger">*</span></label>
                                    <select class="form-select" name="etat" id="etat" required>
                                        <option value="bon" selected>Bon</option>
                                        <option value="passable">Passable</option>
                                        <option value="mauvais">Mauvais</option>
                                        <option value="avarie">Avarié</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Section 02: Caractéristiques Réseau -->
                        <div>
                            <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">02 — Caractéristiques Réseau</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Type d'équipement</label>
                                    <div class="input-group">
                                        <select class="form-select" name="type_reseau_id" id="type_reseau_id">
                                            <option value="">Sélectionner...</option>
                                            @foreach($typesReseau as $type)
                                                <option value="{{ $type->id }}">{{ $type->libelle }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-outline-secondary" type="button" id="btn-add-type-reseau" title="Nouveau type"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Référence exacte modèle</label>
                                    <input type="text" class="form-control" name="modele_reference" id="modele_reference" placeholder="Ex: Cisco Catalyst 2960X">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Nombre de ports</label>
                                    <input type="number" class="form-control" name="nombre_ports" id="nombre_ports" min="1" max="400" placeholder="Ex: 48">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Vitesse des ports</label>
                                    <select class="form-select" name="vitesse_port" id="vitesse_port">
                                        <option value="">—</option>
                                        <option value="100Mbps">100 Mbps</option>
                                        <option value="1Gbps">1 Gbps</option>
                                        <option value="10Gbps">10 Gbps</option>
                                        <option value="25Gbps">25 Gbps</option>
                                        <option value="40Gbps">40 Gbps</option>
                                        <option value="100Gbps">100 Gbps</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Ports uplink</label>
                                    <input type="number" class="form-control" name="nombre_ports_uplink" id="nombre_ports_uplink" min="0" placeholder="Ex: 2">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small text-muted">IP de gestion</label>
                                    <input type="text" class="form-control" name="adresse_ip_management" id="adresse_ip_management" placeholder="192.168.x.x">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Version Firmware</label>
                                    <input type="text" class="form-control" name="firmware_version" id="firmware_version" placeholder="Ex: 16.12.03">
                                </div>

                                <div class="col-md-6">
                                    <div class="bg-light p-3 rounded-3">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" name="support_poe" id="support_poe" value="1">
                                            <label class="form-check-label text-dark small fw-bold" for="support_poe">Support PoE (Power over Ethernet)</label>
                                        </div>
                                        <div class="mt-2" id="div_poe_budget" style="display:none;">
                                            <input type="number" class="form-control form-control-sm" name="poe_budget_watts" id="poe_budget_watts" placeholder="Budget (Watts) ex: 960" min="0">
                                        </div>

                                        <div class="form-check form-switch mt-3 mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" name="support_vlan" id="support_vlan" value="1">
                                            <label class="form-check-label text-dark small fw-bold" for="support_vlan">Support VLAN</label>
                                        </div>

                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" name="support_stp" id="support_stp" value="1">
                                            <label class="form-check-label text-dark small fw-bold" for="support_stp">Support STP (Spanning Tree)</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="bg-light p-3 rounded-3 h-100">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" name="support_lacp" id="support_lacp" value="1">
                                            <label class="form-check-label text-dark small fw-bold" for="support_lacp">Support LACP (Aggregation)</label>
                                        </div>

                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" name="support_redundance" id="support_redundance" value="1">
                                            <label class="form-check-label text-dark small fw-bold" for="support_redundance">Support Redondance (HA)</label>
                                        </div>

                                        <div class="form-check form-switch mt-3 mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" name="support_snmp" id="support_snmp" value="1">
                                            <label class="form-check-label text-dark small fw-bold" for="support_snmp">Support SNMP</label>
                                        </div>
                                        <div class="row g-2 mt-1" id="div_snmp_config" style="display:none;">
                                            <div class="col-8">
                                                <input type="text" class="form-control form-control-sm" name="snmp_community" id="snmp_community" placeholder="Communauté (ex: public)">
                                            </div>
                                            <div class="col-4">
                                                <select class="form-select form-select-sm" name="snmp_version" id="snmp_version">
                                                    <option value="v1">v1</option>
                                                    <option value="v2c" selected>v2c</option>
                                                    <option value="v3">v3</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small text-muted">VLAN configurés</label>
                                    <input type="text" class="form-control" name="vlans_configures" id="vlans_configures" placeholder="Ex: VLAN1, VLAN2, GUEST, DMZ">
                                </div>

                                <div class="col-12">
                                    <label class="form-label small text-muted">Localisation physique détaillée</label>
                                    <input type="text" class="form-control" name="location_detail" id="location_detail" placeholder="Ex: Armoire 3, Rack U12">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: Affectation -->
                    <div class="wizard-step d-none" id="step-3">
                        <h6 class="fw-bold mb-3"><i class="bi bi-person-workspace text-primary me-2"></i>À qui ou à quel emplacement affecter cet équipement ?</h6>

                        <div class="alert alert-info border-0 bg-info bg-opacity-10 d-none" id="aff-skip-hint">
                            <i class="bi bi-info-circle me-2"></i> Vous pouvez ignorer cette étape et affecter l'équipement plus tard.
                        </div>

                        <input type="hidden" name="type_cible" id="type_cible">
                        <input type="hidden" name="dossier_employe_id" id="dossier_employe_id">
                        <input type="hidden" name="poste_travail_id" id="poste_travail_id">
                        <input type="hidden" name="local_id" id="local_id">

                        <div class="row g-3 mb-4" id="aff-types-container-wizard">
                            <!-- Employé -->
                            <div class="col-md-4">
                                <div class="card h-100 border-0 shadow-sm cursor-pointer aff-type-card transition-all" data-type="EMPLOYE">
                                    <div class="card-body text-center p-3">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex p-3 mb-2">
                                            <i class="bi bi-person fs-4"></i>
                                        </div>
                                        <h6 class="fw-bold">Employé</h6>
                                    </div>
                                </div>
                            </div>
                            <!-- Poste -->
                            <div class="col-md-4">
                                <div class="card h-100 border-0 shadow-sm cursor-pointer aff-type-card transition-all" data-type="POSTE">
                                    <div class="card-body text-center p-3">
                                        <div class="rounded-circle bg-info bg-opacity-10 text-info d-inline-flex p-3 mb-2">
                                            <i class="bi bi-pc-display fs-4"></i>
                                        </div>
                                        <h6 class="fw-bold">Poste</h6>
                                    </div>
                                </div>
                            </div>
                            <!-- Local -->
                            <div class="col-md-4">
                                <div class="card h-100 border-0 shadow-sm cursor-pointer aff-type-card transition-all" data-type="LOCAL">
                                    <div class="card-body text-center p-3">
                                        <div class="rounded-circle bg-secondary bg-opacity-10 text-secondary d-inline-flex p-3 mb-2">
                                            <i class="bi bi-door-open fs-4"></i>
                                        </div>
                                        <h6 class="fw-bold">Local</h6>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Résumés -->
                        <div id="summary-employe" class="card border border-primary bg-primary bg-opacity-10 d-none mb-3 rounded-3">
                            <div class="card-body d-flex justify-content-between align-items-center p-3">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-person-check fs-2 text-primary"></i>
                                    <div>
                                        <h6 class="mb-0 fw-bold text-primary">Employé sélectionné</h6>
                                        <div class="text-dark fw-bold" id="w-employe-name"></div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill aff-reselect">Changer</button>
                            </div>
                        </div>
                        <div id="summary-poste" class="card border border-info bg-info bg-opacity-10 d-none mb-3 rounded-3">
                            <div class="card-body d-flex justify-content-between align-items-center p-3">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-pc-display fs-2 text-info"></i>
                                    <div>
                                        <h6 class="mb-0 fw-bold text-info">Poste sélectionné</h6>
                                        <div class="text-dark fw-bold" id="w-poste-name"></div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-info rounded-pill aff-reselect">Changer</button>
                            </div>
                        </div>
                        <div id="summary-local" class="card border border-secondary bg-secondary bg-opacity-10 d-none mb-3 rounded-3">
                            <div class="card-body d-flex justify-content-between align-items-center p-3">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-door-open fs-2 text-secondary"></i>
                                    <div>
                                        <h6 class="mb-0 fw-bold text-secondary">Local sélectionné</h6>
                                        <div class="text-dark fw-bold" id="w-local-name"></div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill aff-reselect">Changer</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-0 px-4 py-3 bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="btn-prev" style="display:none!important;">Précédent</button>
                    <button type="button" class="btn btn-warning rounded-pill px-4 d-none" id="btn-save-reparation">Enregistrer en réparation</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4" id="btn-next">Suivant</button>
                    <button type="button" class="btn btn-success rounded-pill px-4 d-none" id="btn-submit">
                        <span id="btn-submit-label">Enregistrer l'équipement</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
