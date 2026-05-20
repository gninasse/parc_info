@extends('parcinfo::layouts.master')

@section('header', 'Détails Équipement Réseau')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Informatique</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.equipements-reseau.index') }}">Équipements Réseau</a></li>
    <li class="breadcrumb-item active">{{ $equipement->code_inventaire }}</li>
@endsection

@section('content')
<div class="container-fluid mb-4">
    <!-- Header Card -->
    <div class="card border-0 shadow-sm mb-4 overflow-hidden rounded-4">
        <div class="card-body p-4 position-relative">
            <div class="position-absolute top-0 end-0 p-4 opacity-10">
                <i class="bi bi-router" style="font-size: 8rem;"></i>
            </div>

            <div class="d-flex justify-content-between align-items-start position-relative z-1">
                <div class="d-flex gap-4 align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary p-4 rounded-4">
                        <i class="bi bi-router fs-1"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h4 class="mb-0 fw-bold">{{ $equipement->marque->libelle ?? '—' }} {{ $equipement->modele }}</h4>
                            @if($equipement->statut === 'en_service')
                                <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill">En service</span>
                            @elseif($equipement->statut === 'en_stock')
                                <span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary border-opacity-25 px-2 py-1 rounded-pill">En stock</span>
                            @elseif($equipement->statut === 'en_reparation')
                                <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-25 px-2 py-1 rounded-pill">En réparation</span>
                            @else
                                <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25 px-2 py-1 rounded-pill">{{ ucfirst($equipement->statut) }}</span>
                            @endif

                            @if($equipement->etat === 'bon')
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill"><i class="bi bi-star-fill me-1"></i>Bon état</span>
                            @elseif($equipement->etat === 'passable')
                                <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill"><i class="bi bi-star-half me-1"></i>Passable</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill"><i class="bi bi-star me-1"></i>Mauvais/Avarié</span>
                            @endif
                        </div>

                        <div class="text-muted d-flex gap-4 mt-3 small">
                            <div><i class="bi bi-upc-scan me-1 text-primary"></i> <strong class="text-dark">{{ $equipement->code_inventaire }}</strong></div>
                            <div><i class="bi bi-hash me-1 text-primary"></i> N/S: <strong class="text-dark">{{ $equipement->numero_serie }}</strong></div>
                            @if($equipement->equipementReseau->adresse_ip_management)
                                <div><i class="bi bi-hdd-network me-1 text-primary"></i> IP: <strong class="text-dark">{{ $equipement->equipementReseau->adresse_ip_management }}</strong></div>
                            @endif
                            @if($equipement->equipementReseau->nombre_ports)
                                <div><i class="bi bi-diagram-2 me-1 text-primary"></i> Ports: <strong class="text-dark">{{ $equipement->equipementReseau->nombre_ports }}</strong></div>
                            @endif
                            @if($equipement->equipementReseau->vitesse_port)
                                <div><i class="bi bi-speedometer2 me-1 text-primary"></i> Vitesse: <strong class="text-dark">{{ $equipement->equipementReseau->vitesse_port }}</strong></div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-arrow-repeat me-1"></i> Statut
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><a class="dropdown-item d-flex align-items-center gap-2 btn-statut" href="#" data-statut="en_stock"><i class="bi bi-archive text-secondary"></i> Mettre en stock</a></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2 btn-statut" href="#" data-statut="en_service"><i class="bi bi-check-circle text-success"></i> Mettre en service</a></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2 btn-statut" href="#" data-statut="en_reparation"><i class="bi bi-tools text-warning"></i> Envoyer en réparation</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2 btn-statut text-danger" href="#" data-statut="perdu"><i class="bi bi-question-circle"></i> Déclarer perdu</a></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2 btn-statut text-danger" href="#" data-statut="reforme"><i class="bi bi-trash"></i> Réformer</a></li>
                        </ul>
                    </div>

                    @if($equipement->affectationActive)
                        <button id="btn-desaffecter" class="btn btn-warning rounded-pill px-3">
                            <i class="bi bi-x-circle me-1"></i> Désaffecter
                        </button>
                    @else
                        <button id="btn-nouvelle-affectation" class="btn btn-primary rounded-pill px-3" {{ $equipement->statut !== 'en_stock' ? 'disabled title="L\'équipement doit être en stock pour être affecté"' : '' }}>
                            <i class="bi bi-person-plus me-1"></i> Affecter
                        </button>
                    @endif

                    <button id="btn-edit-toggle" class="btn btn-outline-primary rounded-pill px-3">
                        <i class="bi bi-pencil me-1"></i> Éditer
                    </button>
                </div>
            </div>
        </div>

        <div class="card-footer bg-white p-0 border-top">
            <ul class="nav nav-tabs nav-tabs-custom border-0" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active px-4 py-3" data-bs-toggle="tab" href="#pane-fiche"><i class="bi bi-card-text me-2"></i>Fiche Technique</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-4 py-3" data-bs-toggle="tab" href="#pane-affectation">
                        <i class="bi bi-geo-alt me-2"></i>Affectation Actuelle
                        @if($equipement->affectationActive)
                            <span class="position-absolute top-25 start-75 translate-middle p-1 bg-success border border-light rounded-circle"></span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-4 py-3" data-bs-toggle="tab" href="#pane-historique-aff"><i class="bi bi-clock-history me-2"></i>Historique des affectations</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-4 py-3" data-bs-toggle="tab" href="#pane-historique-chg"><i class="bi bi-journal-text me-2"></i>Journal des changements</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- PANE 1: Fiche Technique -->
        <div class="tab-pane fade show active" id="pane-fiche" role="tabpanel">
            <form id="ficheForm" action="{{ route('parc-info.equipements-reseau.update', $equipement->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-4">
                    <!-- Section 01 : Identification -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100 rounded-4">
                            <div class="card-header bg-white border-0 pt-4 pb-0">
                                <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-info-square me-2"></i>01 — Identification & Achat</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Code Inventaire</label>
                                        <input type="text" class="form-control bg-light" id="f_code_inventaire" value="{{ $equipement->code_inventaire }}" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Numéro de série</label>
                                        <input type="text" class="form-control editable-field" name="numero_serie" id="f_numero_serie" value="{{ $equipement->numero_serie }}" disabled required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Marque</label>
                                        <div class="input-group">
                                            <select class="form-select editable-field" name="marque_id" id="f_marque_id" disabled>
                                                <option value="">Sélectionner...</option>
                                                @foreach($marques as $marque)
                                                    <option value="{{ $marque->id }}" {{ $equipement->marque_id == $marque->id ? 'selected' : '' }}>{{ $marque->libelle }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-outline-secondary d-none btn-add-ref" type="button" data-ref="marque"><i class="bi bi-plus-lg"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Modèle</label>
                                        <input type="text" class="form-control editable-field" name="modele" id="f_modele" value="{{ $equipement->modele }}" disabled required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Statut</label>
                                        <select class="form-select editable-field" name="statut" id="f_statut" disabled required>
                                            <option value="en_stock" {{ $equipement->statut === 'en_stock' ? 'selected' : '' }}>En stock</option>
                                            <option value="en_service" {{ $equipement->statut === 'en_service' ? 'selected' : '' }}>En service</option>
                                            <option value="en_reparation" {{ $equipement->statut === 'en_reparation' ? 'selected' : '' }}>En réparation</option>
                                            <option value="perdu" {{ $equipement->statut === 'perdu' ? 'selected' : '' }}>Perdu</option>
                                            <option value="reforme" {{ $equipement->statut === 'reforme' ? 'selected' : '' }}>Réformé</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">État</label>
                                        <select class="form-select editable-field" name="etat" id="f_etat" disabled required>
                                            <option value="bon" {{ $equipement->etat === 'bon' ? 'selected' : '' }}>Bon</option>
                                            <option value="passable" {{ $equipement->etat === 'passable' ? 'selected' : '' }}>Passable</option>
                                            <option value="mauvais" {{ $equipement->etat === 'mauvais' ? 'selected' : '' }}>Mauvais</option>
                                            <option value="avarie" {{ $equipement->etat === 'avarie' ? 'selected' : '' }}>Avarié</option>
                                        </select>
                                    </div>

                                    <div class="col-12"><hr class="my-2 opacity-10"></div>

                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Date d'acquisition</label>
                                        <input type="date" class="form-control editable-field" name="date_acquisition" value="{{ $equipement->date_acquisition?->format('Y-m-d') }}" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Mise en service</label>
                                        <input type="date" class="form-control editable-field" name="date_mise_en_service" value="{{ $equipement->date_mise_en_service?->format('Y-m-d') }}" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Fin de garantie</label>
                                        <input type="date" class="form-control editable-field" name="date_fin_garantie" value="{{ $equipement->date_fin_garantie?->format('Y-m-d') }}" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Valeur d'achat (FCFA)</label>
                                        <input type="number" class="form-control editable-field" name="valeur_achat" value="{{ $equipement->valeur_achat }}" disabled>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label text-muted small">Référence exacte (modèle)</label>
                                        <input type="text" class="form-control editable-field" name="modele_reference" value="{{ $equipement->equipementReseau->modele_reference }}" disabled placeholder="Ex: Cisco Catalyst 2960X-48TS-L">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 02 : Caractéristiques Réseau -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100 rounded-4">
                            <div class="card-header bg-white border-0 pt-4 pb-0">
                                <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-hdd-network me-2"></i>02 — Caractéristiques Réseau</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label text-muted small">Type d'équipement réseau</label>
                                        <div class="input-group">
                                            <select class="form-select editable-field" name="type_reseau_id" id="f_type_reseau_id" disabled>
                                                <option value="">Sélectionner...</option>
                                                @foreach($typesReseau as $type)
                                                    <option value="{{ $type->id }}" {{ $equipement->equipementReseau->type_reseau_id == $type->id ? 'selected' : '' }}>{{ $type->libelle }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-outline-secondary d-none btn-add-ref" type="button" data-ref="type-reseau"><i class="bi bi-plus-lg"></i></button>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label text-muted small">Nombre de ports</label>
                                        <input type="number" class="form-control editable-field" name="nombre_ports" id="f_nombre_ports" value="{{ $equipement->equipementReseau->nombre_ports }}" min="1" max="400" disabled>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted small">Vitesse des ports</label>
                                        <select class="form-select editable-field" name="vitesse_port" id="f_vitesse_port" disabled>
                                            <option value="">—</option>
                                            <option value="100Mbps" {{ $equipement->equipementReseau->vitesse_port === '100Mbps' ? 'selected' : '' }}>100 Mbps</option>
                                            <option value="1Gbps" {{ $equipement->equipementReseau->vitesse_port === '1Gbps' ? 'selected' : '' }}>1 Gbps</option>
                                            <option value="10Gbps" {{ $equipement->equipementReseau->vitesse_port === '10Gbps' ? 'selected' : '' }}>10 Gbps</option>
                                            <option value="25Gbps" {{ $equipement->equipementReseau->vitesse_port === '25Gbps' ? 'selected' : '' }}>25 Gbps</option>
                                            <option value="40Gbps" {{ $equipement->equipementReseau->vitesse_port === '40Gbps' ? 'selected' : '' }}>40 Gbps</option>
                                            <option value="100Gbps" {{ $equipement->equipementReseau->vitesse_port === '100Gbps' ? 'selected' : '' }}>100 Gbps</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted small">Ports uplink</label>
                                        <input type="number" class="form-control editable-field" name="nombre_ports_uplink" id="f_nombre_ports_uplink" value="{{ $equipement->equipementReseau->nombre_ports_uplink }}" min="0" disabled>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">IP de gestion</label>
                                        <input type="text" class="form-control editable-field" name="adresse_ip_management" id="f_adresse_ip_management" value="{{ $equipement->equipementReseau->adresse_ip_management }}" disabled placeholder="192.168.x.x">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small">Version Firmware</label>
                                        <input type="text" class="form-control editable-field" name="firmware_version" id="f_firmware_version" value="{{ $equipement->equipementReseau->firmware_version }}" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 03 : Capacités Techniques -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100 rounded-4">
                            <div class="card-header bg-white border-0 pt-4 pb-0">
                                <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-gear-wide-connected me-2"></i>03 — Capacités Techniques</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input editable-field" type="checkbox" role="switch" name="support_poe" id="f_support_poe" value="1" {{ $equipement->equipementReseau->support_poe ? 'checked' : '' }} disabled>
                                            <label class="form-check-label text-muted small" for="f_support_poe">Support PoE</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input editable-field" type="checkbox" role="switch" name="support_vlan" id="f_support_vlan" value="1" {{ $equipement->equipementReseau->support_vlan ? 'checked' : '' }} disabled>
                                            <label class="form-check-label text-muted small" for="f_support_vlan">Support VLAN</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input editable-field" type="checkbox" role="switch" name="support_stp" id="f_support_stp" value="1" {{ $equipement->equipementReseau->support_stp ? 'checked' : '' }} disabled>
                                            <label class="form-check-label text-muted small" for="f_support_stp">Support STP</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input editable-field" type="checkbox" role="switch" name="support_lacp" id="f_support_lacp" value="1" {{ $equipement->equipementReseau->support_lacp ? 'checked' : '' }} disabled>
                                            <label class="form-check-label text-muted small" for="f_support_lacp">Support LACP</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input editable-field" type="checkbox" role="switch" name="support_snmp" id="f_support_snmp" value="1" {{ $equipement->equipementReseau->support_snmp ? 'checked' : '' }} disabled>
                                            <label class="form-check-label text-muted small" for="f_support_snmp">Support SNMP</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input editable-field" type="checkbox" role="switch" name="support_redundance" id="f_support_redundance" value="1" {{ $equipement->equipementReseau->support_redundance ? 'checked' : '' }} disabled>
                                            <label class="form-check-label text-muted small" for="f_support_redundance">Support Redondance (HA)</label>
                                        </div>
                                    </div>

                                    <div class="col-12 mt-3" id="div_f_poe_budget" style="{{ $equipement->equipementReseau->support_poe ? '' : 'display:none;' }}">
                                        <label class="form-label text-muted small">Budget PoE (Watts)</label>
                                        <input type="number" class="form-control editable-field" name="poe_budget_watts" id="f_poe_budget_watts" value="{{ $equipement->equipementReseau->poe_budget_watts }}" min="0" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 04 : Configuration Réseau -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100 rounded-4">
                            <div class="card-header bg-white border-0 pt-4 pb-0">
                                <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-sliders me-2"></i>04 — Configuration & Localisation</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label text-muted small">VLAN configurés</label>
                                        <input type="text" class="form-control editable-field" name="vlans_configures" value="{{ $equipement->equipementReseau->vlans_configures }}" disabled placeholder="Ex: VLAN1, VLAN2, GUEST, DMZ">
                                    </div>

                                    <div class="col-12" id="div_f_snmp_config" style="{{ $equipement->equipementReseau->support_snmp ? '' : 'display:none;' }}">
                                        <div class="row g-2">
                                            <div class="col-md-8">
                                                <label class="form-label text-muted small">Communauté SNMP</label>
                                                <input type="text" class="form-control editable-field" name="snmp_community" value="{{ $equipement->equipementReseau->snmp_community }}" disabled>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label text-muted small">Version</label>
                                                <select class="form-select editable-field" name="snmp_version" disabled>
                                                    <option value="">—</option>
                                                    <option value="v1" {{ $equipement->equipementReseau->snmp_version === 'v1' ? 'selected' : '' }}>v1</option>
                                                    <option value="v2c" {{ $equipement->equipementReseau->snmp_version === 'v2c' ? 'selected' : '' }}>v2c</option>
                                                    <option value="v3" {{ $equipement->equipementReseau->snmp_version === 'v3' ? 'selected' : '' }}>v3</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 mt-auto">
                                        <label class="form-label text-muted small">Localisation physique détaillée</label>
                                        <input type="text" class="form-control editable-field" name="location_detail" value="{{ $equipement->equipementReseau->location_detail }}" disabled placeholder="Ex: Armoire 3, Rack U12">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer des actions d'édition -->
                <div class="row mt-4 d-none" id="fiche-actions">
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-light rounded-pill px-4 me-2" id="btn-cancel-edit">Annuler</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4" id="btn-save-fiche">
                            <i class="bi bi-check-lg me-1"></i> Enregistrer les modifications
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- PANE 2: Affectation Actuelle -->
        <div class="tab-pane fade" id="pane-affectation" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    @if($equipement->affectationActive)
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle">
                                        <i class="bi bi-geo-alt fs-4"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 fw-bold">Affectation Active</h5>
                                        <div class="text-muted small">Depuis le {{ $equipement->affectationActive->date_debut->format('d/m/Y') }} ({{ $equipement->affectationActive->date_debut->diffForHumans() }})</div>
                                    </div>
                                </div>

                                <div class="bg-light rounded-4 p-4 mt-4">
                                    @if($equipement->affectationActive->employe)
                                        <!-- Affecté à un employé -->
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <span class="text-muted d-block small mb-1">Type d'affectation</span>
                                                <span class="badge bg-primary">Employé</span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <span class="text-muted d-block small mb-1">Employé</span>
                                                <strong>{{ $equipement->affectationActive->employe->nom_complet }}</strong>
                                                <div class="small text-muted">{{ $equipement->affectationActive->employe->matricule }}</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <span class="text-muted d-block small mb-1">Fonction</span>
                                                <span>{{ $equipement->affectationActive->employe->fonction ?? '—' }}</span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <span class="text-muted d-block small mb-1">Service / Direction</span>
                                                <span>{{ $equipement->affectationActive->service->libelle ?? '—' }}</span><br>
                                                <span class="small text-muted">{{ $equipement->affectationActive->direction->libelle ?? '—' }}</span>
                                            </div>
                                        </div>
                                    @elseif($equipement->affectationActive->posteTravail)
                                        <!-- Affecté à un poste -->
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <span class="text-muted d-block small mb-1">Type d'affectation</span>
                                                <span class="badge bg-info text-dark">Poste de travail</span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <span class="text-muted d-block small mb-1">Poste</span>
                                                <strong>{{ $equipement->affectationActive->posteTravail->nom }}</strong>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <span class="text-muted d-block small mb-1">Service / Direction</span>
                                                <span>{{ $equipement->affectationActive->service->libelle ?? '—' }}</span><br>
                                                <span class="small text-muted">{{ $equipement->affectationActive->direction->libelle ?? '—' }}</span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <span class="text-muted d-block small mb-1">Localisation</span>
                                                @if($equipement->affectationActive->posteTravail->local)
                                                    <span>{{ $equipement->affectationActive->posteTravail->local->nom }}</span><br>
                                                    <span class="small text-muted">
                                                        {{ $equipement->affectationActive->posteTravail->local->etage->batiment->site->libelle ?? '' }} >
                                                        {{ $equipement->affectationActive->posteTravail->local->etage->batiment->nom ?? '' }} >
                                                        {{ $equipement->affectationActive->posteTravail->local->etage->nom ?? '' }}
                                                    </span>
                                                @else
                                                    <span>—</span>
                                                @endif
                                            </div>
                                        </div>
                                    @elseif($equipement->affectationActive->local)
                                        <!-- Affecté à un local -->
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <span class="text-muted d-block small mb-1">Type d'affectation</span>
                                                <span class="badge bg-secondary">Local commun</span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <span class="text-muted d-block small mb-1">Local</span>
                                                <strong>{{ $equipement->affectationActive->local->nom }}</strong>
                                            </div>
                                            <div class="col-12 mb-3">
                                                <span class="text-muted d-block small mb-1">Localisation complète</span>
                                                <span>
                                                    {{ $equipement->affectationActive->local->etage->batiment->site->libelle ?? '' }} >
                                                    {{ $equipement->affectationActive->local->etage->batiment->nom ?? '' }} >
                                                    {{ $equipement->affectationActive->local->etage->nom ?? '' }}
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4 text-center border-start">
                                <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                                <h5 class="mt-3">Équipement en place</h5>
                                <p class="text-muted small">Cet équipement est actuellement déployé et fonctionnel dans cette affectation.</p>
                                <button class="btn btn-outline-warning rounded-pill px-4 mt-2 btn-desaffecter-trigger">
                                    <i class="bi bi-x-circle me-1"></i> Désaffecter
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="bg-light rounded-circle d-inline-flex p-4 mb-3">
                                <i class="bi bi-geo-alt text-muted" style="font-size: 3rem;"></i>
                            </div>
                            <h5>Aucune affectation active</h5>
                            <p class="text-muted">Cet équipement est actuellement {{ $equipement->statut === 'en_stock' ? 'en stock' : 'au statut "' . str_replace('_', ' ', $equipement->statut) . '"' }} et n'est affecté à personne ni aucun lieu.</p>

                            @if($equipement->statut === 'en_stock')
                                <button id="btn-nouvelle-affectation-2" class="btn btn-primary rounded-pill px-4 mt-2">
                                    <i class="bi bi-plus-lg me-1"></i> Créer une affectation
                                </button>
                            @else
                                <div class="alert alert-warning d-inline-flex align-items-center mb-0 mt-2">
                                    <i class="bi bi-exclamation-triangle me-2"></i> L'équipement doit être retourné en stock pour être affecté.
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- PANE 3: Historique des affectations -->
        <div class="tab-pane fade" id="pane-historique-aff" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Statut</th>
                                <th>Cible</th>
                                <th>Détail / Localisation</th>
                                <th>Début</th>
                                <th>Fin</th>
                                <th>Durée</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($equipement->affectations()->orderByDesc('date_debut')->get() as $aff)
                                <tr>
                                    <td class="ps-4">
                                        @if($aff->statut)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Terminée</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($aff->employe)
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25"><i class="bi bi-person me-1"></i> Employé</span>
                                        @elseif($aff->posteTravail)
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25"><i class="bi bi-pc-display me-1"></i> Poste</span>
                                        @elseif($aff->local)
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25"><i class="bi bi-door-open me-1"></i> Local</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($aff->employe)
                                            <strong>{{ $aff->employe->nom_complet }}</strong>
                                            <div class="small text-muted">{{ $aff->service->libelle ?? '—' }}</div>
                                        @elseif($aff->posteTravail)
                                            <strong>{{ $aff->posteTravail->nom }}</strong>
                                            <div class="small text-muted">{{ $aff->service->libelle ?? '—' }}</div>
                                        @elseif($aff->local)
                                            <strong>{{ $aff->local->nom }}</strong>
                                        @endif
                                    </td>
                                    <td>{{ $aff->date_debut->format('d/m/Y') }}</td>
                                    <td>{{ $aff->date_fin ? $aff->date_fin->format('d/m/Y') : '—' }}</td>
                                    <td>
                                        @if($aff->date_fin)
                                            {{ $aff->date_debut->diffForHumans($aff->date_fin, true) }}
                                        @else
                                            {{ $aff->date_debut->diffForHumans(null, true) }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Aucun historique d'affectation</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PANE 4: Journal des changements -->
        <div class="tab-pane fade" id="pane-historique-chg" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="timeline">
                        @forelse($equipement->historique as $hist)
                            <div class="timeline-item pb-4 border-start border-2 ms-3 ps-4 position-relative
                                {{ $hist->type_changement === 'STATUT' ? 'border-primary' : '' }}
                                {{ $hist->type_changement === 'ETAT' ? 'border-warning' : '' }}
                                {{ $hist->type_changement === 'AFFECTATION' ? 'border-success' : '' }}
                                {{ $hist->type_changement === 'TECHNIQUE' ? 'border-secondary' : '' }}
                            ">
                                <!-- Bullet -->
                                <div class="position-absolute start-0 top-0 translate-middle-x rounded-circle
                                    {{ $hist->type_changement === 'STATUT' ? 'bg-primary' : '' }}
                                    {{ $hist->type_changement === 'ETAT' ? 'bg-warning' : '' }}
                                    {{ $hist->type_changement === 'AFFECTATION' ? 'bg-success' : '' }}
                                    {{ $hist->type_changement === 'TECHNIQUE' ? 'bg-secondary' : '' }}
                                " style="width: 1rem; height: 1rem; margin-top: 0.25rem;"></div>

                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0 fw-bold">
                                        @if($hist->type_changement === 'STATUT') Changement de statut
                                        @elseif($hist->type_changement === 'ETAT') Changement d'état
                                        @elseif($hist->type_changement === 'AFFECTATION') Mouvement / Affectation
                                        @elseif($hist->type_changement === 'TECHNIQUE') Modification technique
                                        @else {{ $hist->type_changement }} @endif
                                    </h6>
                                    <span class="text-muted small"><i class="bi bi-clock me-1"></i> {{ $hist->date_changement->format('d/m/Y H:i') }}</span>
                                </div>

                                <p class="text-muted mb-2 small">{{ $hist->motif }}</p>

                                @if($hist->type_changement === 'STATUT')
                                    <div class="d-inline-flex align-items-center bg-light rounded-pill px-3 py-1 small">
                                        <span class="text-secondary">{{ $hist->ancien_statut ?? '—' }}</span>
                                        <i class="bi bi-arrow-right mx-2 text-muted"></i>
                                        <strong class="text-primary">{{ $hist->nouveau_statut }}</strong>
                                    </div>
                                @elseif($hist->type_changement === 'ETAT')
                                    <div class="d-inline-flex align-items-center bg-light rounded-pill px-3 py-1 small">
                                        <span class="text-secondary">{{ $hist->ancien_etat ?? '—' }}</span>
                                        <i class="bi bi-arrow-right mx-2 text-muted"></i>
                                        <strong class="text-warning">{{ $hist->nouvel_etat }}</strong>
                                    </div>
                                @endif

                                <div class="text-muted small mt-2">
                                    <i class="bi bi-person me-1"></i> Par: {{ $hist->utilisateur->name ?? 'Système' }}
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted">Aucun événement enregistré</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nouvelle Affectation -->
<div class="modal fade" id="affectationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-light border-0 px-4 py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2 text-primary"></i>Nouvelle Affectation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light bg-opacity-50">
                <form id="affectationForm">
                    <input type="hidden" name="equipement_id" value="{{ $equipement->id }}">
                    <input type="hidden" name="type_cible" id="aff_type_cible">
                    <input type="hidden" name="dossier_employe_id" id="aff_employe_id">
                    <input type="hidden" name="poste_travail_id" id="aff_poste_id">
                    <input type="hidden" name="local_id" id="aff_local_id">

                    <p class="text-muted mb-4">À qui ou à quel emplacement souhaitez-vous affecter cet équipement ?</p>

                    <div class="row g-3 mb-4" id="aff-types-container">
                        <!-- Employé -->
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm cursor-pointer aff-type-card transition-all" data-type="EMPLOYE">
                                <div class="card-body text-center p-4">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex p-3 mb-3">
                                        <i class="bi bi-person fs-3"></i>
                                    </div>
                                    <h6 class="fw-bold">Un employé</h6>
                                    <p class="text-muted small mb-0">Affectation nominative à un agent</p>
                                </div>
                            </div>
                        </div>

                        <!-- Poste -->
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm cursor-pointer aff-type-card transition-all" data-type="POSTE">
                                <div class="card-body text-center p-4">
                                    <div class="rounded-circle bg-info bg-opacity-10 text-info d-inline-flex p-3 mb-3">
                                        <i class="bi bi-pc-display fs-3"></i>
                                    </div>
                                    <h6 class="fw-bold">Un poste de travail</h6>
                                    <p class="text-muted small mb-0">Rattachement à un bureau spécifique</p>
                                </div>
                            </div>
                        </div>

                        <!-- Local -->
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm cursor-pointer aff-type-card transition-all" data-type="LOCAL">
                                <div class="card-body text-center p-4">
                                    <div class="rounded-circle bg-secondary bg-opacity-10 text-secondary d-inline-flex p-3 mb-3">
                                        <i class="bi bi-door-open fs-3"></i>
                                    </div>
                                    <h6 class="fw-bold">Un local commun</h6>
                                    <p class="text-muted small mb-0">Salle de réunion, couloir, etc.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Résumés des sélections -->
                    <div id="aff-summary-employe" class="card border border-primary bg-primary bg-opacity-10 d-none mb-3 rounded-3">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-person-check fs-2 text-primary"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold text-primary">Employé sélectionné</h6>
                                    <div class="text-dark fw-bold" id="aff-employe-name"></div>
                                    <div class="small text-muted" id="aff-employe-sub"></div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill aff-reselect">Changer</button>
                        </div>
                    </div>

                    <div id="aff-summary-poste" class="card border border-info bg-info bg-opacity-10 d-none mb-3 rounded-3">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-pc-display fs-2 text-info"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold text-info">Poste sélectionné</h6>
                                    <div class="text-dark fw-bold" id="aff-poste-name"></div>
                                    <div class="small text-muted" id="aff-poste-sub"></div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-info rounded-pill aff-reselect">Changer</button>
                        </div>
                    </div>

                    <div id="aff-summary-local" class="card border border-secondary bg-secondary bg-opacity-10 d-none mb-3 rounded-3">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-door-open fs-2 text-secondary"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold text-secondary">Local sélectionné</h6>
                                    <div class="text-dark fw-bold" id="aff-local-name"></div>
                                    <div class="small text-muted" id="aff-local-sub"></div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill aff-reselect">Changer</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 px-4 py-3 bg-light">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary rounded-pill px-4" id="btn-submit-affectation" disabled>
                    <i class="bi bi-check-lg me-1"></i> Enregistrer l'affectation
                </button>
            </div>
        </div>
    </div>
</div>

@include('parcinfo::informatique.ordinateurs._selection_modals')

@endsection

@push('scripts')
<script>
    // Variables globales pour le JS externe
    window.equipementId = {{ $equipement->id }};
    window.routes = {
        updateStatut: "{{ route('parc-info.equipements-reseau.update-statut', $equipement->id) }}",
        desaffecter: "{{ route('parc-info.equipements-reseau.desaffecter', $equipement->id) }}",
        storeAffectation: "{{ route('parc-info.equipements-reseau.store-affectation') }}"
    };
</script>
<script type="module" src="{{ asset('js/modules/parc-info/equipements-reseau/show.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modules/parc-info/ordinateurs/selection_modals.js') }}?v={{ time() }}"></script>
@endpush
