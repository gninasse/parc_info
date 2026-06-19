@extends('parcinfo::layouts.master')

@section('header', $equipement->code_inventaire)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.serveurs.index') }}">Serveurs Physiques</a></li>
    <li class="breadcrumb-item active">{{ $equipement->code_inventaire }}</li>
@endsection

@php
    $s   = $equipement->serveur;
    $aff = $equipement->affectationActive;
    $statutColors = [
        'en_service'   => 'success',
        'en_stock'     => 'secondary',
        'en_reparation'=> 'warning',
        'perdu'        => 'danger',
        'reforme'      => 'dark',
    ];
    $etatColors = ['bon'=>'success','passable'=>'warning','mauvais'=>'danger','avarie'=>'danger'];
    $sc = $statutColors[$equipement->statut] ?? 'info';
    $ec = $etatColors[$equipement->etat]     ?? 'secondary';
@endphp

@section('content')

{{-- ── HEADER CARD ── --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px">
    <div class="card-body p-4">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <div class="rounded-3 d-flex align-items-center justify-content-center bg-primary bg-opacity-10"
                     style="width:72px;height:72px">
                    <i class="bi bi-cpu fs-2 text-primary"></i>
                </div>
            </div>
            <div class="col">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h4 class="fw-bold mb-0">{{ $equipement->marque?->libelle }} {{ $equipement->modele }}</h4>
                    <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }} border border-{{ $sc }}-subtle px-2 py-1">
                        {!! $equipement->statut_label !!}
                    </span>
                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">
                        SERVEUR PHYSIQUE
                    </span>
                </div>
                <div class="d-flex gap-4 flex-wrap text-muted small">
                    <span><i class="bi bi-upc me-1"></i>{{ $equipement->code_inventaire }}</span>
                    <span><i class="bi bi-hdd-network me-1"></i>{{ $s->nom_hote ?: 'Sans nom' }}</span>
                    <span><i class="bi bi-broadcast me-1"></i>{{ $s->adresse_ip ?: 'Pas d\'IP' }}</span>
                    @if($s->typeOs)
                    <span><i class="bi bi-window me-1"></i>{{ $s->typeOs->libelle }}</span>
                    @endif
                </div>
            </div>
            <div class="col-auto d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-arrow-repeat me-1"></i> Statut
                    </button>
                    <ul class="dropdown-menu shadow border-0">
                        <li><a class="dropdown-item" href="#" data-statut="en_stock">En stock</a></li>
                        <li><a class="dropdown-item" href="#" data-statut="en_service">En service</a></li>
                        <li><a class="dropdown-item" href="#" data-statut="en_reparation">En réparation</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#" data-statut="perdu">Perdu / Volé</a></li>
                        <li><a class="dropdown-item text-danger" href="#" data-statut="reforme">Réformé</a></li>
                    </ul>
                </div>
                @if($aff)
                <button class="btn btn-outline-danger btn-sm bg-white" id="btn-desaffecter">
                    <i class="bi bi-x-circle me-1"></i> Désaffecter
                </button>
                <button class="btn btn-outline-secondary btn-sm" id="btn-nouvelle-affectation">
                    <i class="bi bi-geo-alt me-1"></i> Changer d'emplacement
                </button>
                @else
                <button class="btn btn-outline-secondary btn-sm" id="btn-nouvelle-affectation">
                    <i class="bi bi-geo-alt me-1"></i> Affecter
                </button>
                @endif
                <button class="btn btn-primary btn-sm" id="btn-edit-toggle">
                    <i class="bi bi-pencil me-1"></i> Modifier
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── TABS ── --}}
<ul class="nav nav-tabs border-0 mb-3" id="showTabs" role="tablist">
    @foreach([
        ['fiche',      'bi-cpu',           'Fiche Technique'],
        ['virtua',     'bi-layers',        'Virtualisation'],
        ['affectation','bi-geo-alt',       'Emplacement'],
        ['licences',   'bi-file-lock',     'Licences'],
        ['historique-chg','bi-journal-text', 'Journal'],
    ] as [$id,$icon,$label])
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $loop->first ? 'active' : '' }} fw-semibold small px-3"
                id="tab-{{ $id }}" data-bs-toggle="tab" data-bs-target="#pane-{{ $id }}"
                type="button" role="tab">
            <i class="bi {{ $icon }} me-1"></i>{{ $label }}
        </button>
    </li>
    @endforeach
</ul>

<div class="tab-content" id="showTabsContent">
{{-- ══ TAB 1 : FICHE TECHNIQUE ══ --}}
<div class="tab-pane fade show active" id="pane-fiche" role="tabpanel">
    <form id="ficheForm">
        @csrf
        @method('PUT')

        <div class="row g-3">
            {{-- Identification & Système --}}
            <div class="col-md-8">
                <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
                    <div class="card-body p-4">
                        <h6 class="section-title mb-4"><span class="section-num">01</span> Identification & Système</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="field-label">Code Inventaire</label>
                                <input type="text" class="form-control field-input" id="f_code_inventaire" value="{{ $equipement->code_inventaire }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="field-label">Numéro de Série <span class="text-danger">*</span></label>
                                <input type="text" class="form-control field-input" name="numero_serie" value="{{ $equipement->numero_serie }}" id="f_numero_serie" disabled required>
                            </div>
                            <div class="col-md-6">
                                <label class="field-label">Marque</label>
                                <div class="input-group">
                                    <select class="form-select field-input" name="marque_id" id="f_marque_id" disabled>
                                        <option value="">—</option>
                                        @foreach($marques as $m)
                                        <option value="{{ $m->id }}" {{ $equipement->marque_id == $m->id ? 'selected':'' }}>{{ $m->libelle }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary d-none btn-add-ref" id="btn-add-f-marque" title="Nouvelle marque"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="field-label">Modèle <span class="text-danger">*</span></label>
                                <input type="text" class="form-control field-input" name="modele" value="{{ $equipement->modele }}" id="f_modele" disabled required>
                            </div>
                            <div class="col-md-6">
                                <label class="field-label">Nom d'hôte / FQDN</label>
                                <input type="text" class="form-control field-input" name="nom_hote" value="{{ $s->nom_hote }}" id="f_nom_hote" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="field-label">Système d'exploitation</label>
                                <div class="input-group">
                                    <select class="form-select field-input" name="os_type_id" id="f_os_type_id" disabled>
                                        <option value="">—</option>
                                        @foreach($typesOs as $o)
                                        <option value="{{ $o->id }}" {{ $s->os_type_id == $o->id ? 'selected':'' }}>{{ $o->libelle }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary d-none btn-add-ref" id="btn-add-f-os" title="Nouveau système d'exploitation"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="field-label">Rôle / Fonction</label>
                                <input type="text" class="form-control field-input" name="role_serveur" value="{{ $s->role_serveur }}" id="f_role_serveur" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="field-label">Hyperviseur (Ex: ESXi, Proxmox)</label>
                                <input type="text" class="form-control field-input" name="hyperviseur" value="{{ $s->hyperviseur }}" id="f_hyperviseur" disabled>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Configuration Matérielle --}}
                <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
                    <div class="card-body p-4">
                        <h6 class="section-title mb-4"><span class="section-num">02</span> Configuration Matérielle</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="field-label">Type CPU</label>
                                <div class="input-group">
                                    <select class="form-select field-input" name="cpu_type_id" id="f_cpu_type_id" disabled>
                                        <option value="">—</option>
                                        @foreach($typesCpu as $c)
                                        <option value="{{ $c->id }}" {{ $s->cpu_type_id == $c->id ? 'selected':'' }}>{{ $c->libelle }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary d-none btn-add-ref" id="btn-add-f-cpu" title="Nouveau type CPU"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="field-label">Nb Proc.</label>
                                <input type="number" class="form-control field-input" name="nb_processeurs" value="{{ $s->nb_processeurs }}" id="f_nb_processeurs" disabled>
                            </div>
                            <div class="col-md-4">
                                <label class="field-label">Total Cœurs</label>
                                <input type="number" class="form-control field-input" name="nb_coeurs_total" value="{{ $s->nb_coeurs_total }}" id="f_nb_coeurs_total" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="field-label">RAM (Go)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control field-input" name="ram_capacite_go" value="{{ $s->ram_capacite_go }}" id="f_ram_capacite_go" disabled>
                                    <select class="form-select field-input" name="ram_type_id" id="f_ram_type_id" disabled>
                                        <option value="">Type...</option>
                                        @foreach($typesRam as $r)
                                        <option value="{{ $r->id }}" {{ $s->ram_type_id == $r->id ? 'selected':'' }}>{{ $r->libelle }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary d-none btn-add-ref" id="btn-add-f-ram" title="Nouveau type RAM"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="field-label">Stockage (Go)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control field-input" name="stockage_capacite_go" value="{{ $s->stockage_capacite_go }}" id="f_stockage_capacite_go" disabled>
                                    <select class="form-select field-input" name="disque_type_id" id="f_disque_type_id" disabled>
                                        <option value="">Type...</option>
                                        @foreach($typesDisque as $d)
                                        <option value="{{ $d->id }}" {{ $s->disque_type_id == $d->id ? 'selected':'' }}>{{ $d->libelle }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary d-none btn-add-ref" id="btn-add-f-disque" title="Nouveau type Disque"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Réseau & Rack + Acquisition & Cycle de vie --}}
            <div class="col-md-4">
                {{-- Réseau & Rack --}}
                <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
                    <div class="card-body p-4">
                        <h6 class="section-title mb-4"><span class="section-num">03</span> Réseau & Rack</h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="field-label">Adresse IP</label>
                                <input type="text" class="form-control field-input" name="adresse_ip" value="{{ $s->adresse_ip }}" id="f_adresse_ip" disabled>
                            </div>
                            <div class="col-12">
                                <label class="field-label">Adresse MAC</label>
                                <input type="text" class="form-control field-input" name="adresse_mac" value="{{ $s->adresse_mac }}" id="f_adresse_mac" disabled>
                            </div>
                            <div class="col-12">
                                <label class="field-label">Domaine</label>
                                <input type="text" class="form-control field-input" name="domaine" value="{{ $s->domaine }}" id="f_domaine" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="field-label">U de départ (Rack)</label>
                                <input type="number" class="form-control field-input" name="u_position_depart" value="{{ $s->u_position_depart }}" id="f_u_position_depart" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="field-label">U de fin (Rack)</label>
                                <input type="number" class="form-control field-input" name="u_position_fin" value="{{ $s->u_position_fin }}" id="f_u_position_fin" disabled>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Acquisition & Cycle de vie --}}
                <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
                    <div class="card-body p-4">
                        <h6 class="section-title mb-4"><span class="section-num">04</span> Acquisition & Cycle de vie</h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="field-label">Date d'acquisition</label>
                                <input type="date" class="form-control field-input" name="date_acquisition" value="{{ $equipement->date_acquisition?->format('Y-m-d') }}" id="f_date_acquisition" disabled>
                            </div>
                    <div class="col-12">
                        <label class="field-label">Bordereau de livraison</label>
                        <input type="text" class="form-control field-input" name="ref_bordereau" id="f_ref_bordereau" value="{{ $equipement->ref_bordereau }}" disabled placeholder="—">
                    </div>
                            <div class="col-12">
                                <label class="field-label">Date de mise en service</label>
                                <input type="date" class="form-control field-input" name="date_mise_en_service" value="{{ $equipement->date_mise_en_service?->format('Y-m-d') }}" id="f_date_mise_en_service" disabled>
                            </div>
                            <div class="col-12">
                                <label class="field-label">Fin de garantie</label>
                                <input type="date" class="form-control field-input" name="date_fin_garantie" value="{{ $equipement->date_fin_garantie?->format('Y-m-d') }}" id="f_date_fin_garantie" disabled>
                            </div>
                            <div class="col-12">
                                <label class="field-label">Valeur achat</label>
                                <div class="input-group">
                                    <input type="number" class="form-control field-input" name="valeur_achat" value="{{ $equipement->valeur_achat }}" id="f_valeur_achat" disabled>
                                    <span class="input-group-text bg-light border-start-0 small">FCFA</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="field-label">État <span class="text-danger">*</span></label>
                                <select class="form-select field-input" name="etat" id="f_etat" disabled required>
                                    @foreach(['bon' => 'Bon', 'passable' => 'Passable', 'mauvais' => 'Mauvais', 'avarie' => 'Avarié'] as $k => $v)
                                    <option value="{{ $k }}" {{ $equipement->etat === $k ? 'selected':'' }}>{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="fiche-actions" class="d-none text-end mt-2">
            <button type="button" class="btn btn-link text-muted text-decoration-none me-3" id="btn-cancel-edit">Annuler</button>
            <button type="submit" class="btn btn-success px-4" id="btn-save-fiche">
                <i class="bi bi-floppy me-1"></i> Enregistrer les modifications
            </button>
        </div>
    </form>
</div>

{{-- ══ TAB 2 : VIRTUALISATION ══ --}}
<div class="tab-pane fade" id="pane-virtua" role="tabpanel">
    <div class="row g-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius:12px">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h6 class="fw-bold mb-0"><i class="bi bi-box text-purple me-2"></i>Machines Virtuelles Hébergées ({{ $s->vms->count() }})</h6>
                </div>
                <div class="card-body p-4">
                    @if($s->vms->count())
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="small fw-bold">Code</th>
                                    <th class="small fw-bold">Nom / IP</th>
                                    <th class="small fw-bold">OS</th>
                                    <th class="small fw-bold text-center">RAM</th>
                                    <th class="small fw-bold text-center">Statut</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($s->vms as $vm)
                                <tr>
                                    <td class="small fw-bold">{{ $vm->equipement->code_inventaire }}</td>
                                    <td class="small">{{ $vm->nom_hote }} <br> <span class="text-muted" style="font-size:.7rem">{{ $vm->adresse_ip }}</span></td>
                                    <td class="small">{{ $vm->typeOs?->libelle ?: '—' }}</td>
                                    <td class="small text-center">{{ $vm->ram_capacite_go }} Go</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $statutColors[$vm->equipement->statut] ?? 'info' }}-subtle text-{{ $statutColors[$vm->equipement->statut] ?? 'info' }} small" style="font-size:.65rem">
                                            {{ strtoupper($vm->equipement->statut) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('parc-info.serveurs-virtuels.show', $vm->equipement_id) }}" class="btn btn-sm btn-light border-0"><i class="bi bi-eye"></i></a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted small mb-0">Aucune machine virtuelle n'est hébergée sur ce serveur.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══ TAB 3 : EMPLACEMENT ══ --}}
<div class="tab-pane fade" id="pane-affectation" role="tabpanel">
    <div class="card border-0 shadow-sm" style="border-radius:12px">
        <div class="card-body p-4">
            @if($aff)
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="rounded-circle bg-success bg-opacity-10 p-3"><i class="bi bi-geo-alt fs-4 text-success"></i></div>
                <div>
                    <h6 class="fw-bold mb-0">Emplacement Actuel</h6>
                    <div class="text-muted small">Depuis le {{ $aff->date_debut?->format('d/m/Y') }}</div>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle ms-auto px-3 py-2">Affectation Active</span>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="p-3 rounded-3 border bg-light">
                        <div class="text-muted small text-uppercase fw-bold mb-2" style="font-size:.65rem;letter-spacing:.5px">Local / Salle</div>
                        @if($aff->local)
                        <div class="fw-bold fs-5 mb-1">{{ $aff->local->libelle }}</div>
                        <div class="small text-muted">
                            <i class="bi bi-building me-1"></i> {{ $aff->local->etage?->batiment?->site?->libelle }} — {{ $aff->local->etage?->batiment?->libelle }} ({{ $aff->local->etage?->libelle }})
                        </div>
                        @else
                        <span class="text-muted small italic">Aucun local spécifié</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded-3 border bg-light">
                        <div class="text-muted small text-uppercase fw-bold mb-2" style="font-size:.65rem;letter-spacing:.5px">Rack / Baie</div>
                        @if($aff->posteTravail)
                        <div class="fw-bold fs-5 mb-1">{{ $aff->posteTravail->code }}</div>
                        <div class="small text-muted">{{ $aff->posteTravail->libelle }}</div>
                        @else
                        <span class="text-muted small italic">Non racké</span>
                        @endif
                    </div>
                </div>
                @if($aff->service)
                <div class="col-md-12">
                    <div class="p-3 rounded-3 border bg-light">
                        <div class="text-muted small text-uppercase fw-bold mb-2" style="font-size:.65rem;letter-spacing:.5px">Service Responsable</div>
                        <div class="fw-bold">{{ $aff->service->libelle }} ({{ $aff->direction?->libelle }})</div>
                    </div>
                </div>
                @endif
            </div>
            @else
            <div class="text-center py-5">
                <div class="mb-3"><i class="bi bi-geo-alt fs-1 text-muted opacity-50"></i></div>
                <p class="text-muted fw-semibold">Ce serveur n'est pas encore localisé (En Stock)</p>
                <button class="btn btn-primary btn-sm" id="btn-def-emplacement">
                    <i class="bi bi-geo-alt-fill me-1"></i> Définir l'emplacement
                </button> 
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ══ TAB : LICENCES ══ --}}
<div class="tab-pane fade" id="pane-licences" role="tabpanel">
    @include('parcinfo::informatique.ordinateurs.partials._licences')
</div>

{{-- ══ TAB 4 : JOURNAL ══ --}}
<div class="tab-pane fade" id="pane-historique-chg" role="tabpanel">
    <div class="card border-0 shadow-sm" style="border-radius:12px">
        <div class="card-body p-4">
            @if($equipement->historique->count())
            <div class="timeline">
                @foreach($equipement->historique->sortByDesc('date_changement') as $h)
                @php
                    $typeColors = ['STATUT'=>'primary','ETAT'=>'warning','AFFECTATION'=>'info'];
                    $tc = $typeColors[$h->type_changement] ?? 'secondary';
                @endphp
                <div class="timeline-item d-flex gap-3 mb-4">
                    <div class="timeline-dot bg-{{ $tc }} rounded-circle flex-shrink-0 mt-1" style="width:10px;height:10px;margin-top:6px"></div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-{{ $tc }}-subtle text-{{ $tc }} border border-{{ $tc }}-subtle small">{{ $h->type_changement }}</span>
                            <span class="text-muted small">{{ $h->date_changement?->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="small text-muted mb-1">{{ $h->motif }}</div>
                        @if($h->ancien_statut || $h->nouveau_statut)
                        <div class="small"><span class="text-muted">{{ $h->ancien_statut }}</span> <i class="bi bi-arrow-right mx-1 text-muted"></i> <span class="fw-semibold">{{ $h->nouveau_statut }}</span></div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-5 text-muted small italic">Aucun historique de changement.</div>
            @endif
        </div>
    </div>
</div>

</div>

@include('parcinfo::informatique.serveurs._wizard')

{{-- ── MODALE AFFECTATION ── --}}
<div class="modal fade" id="affectationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px">
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <div>
                    <h5 class="modal-title fw-bold mb-0">Nouvel Emplacement (Hébergement)</h5>
                    <small class="text-muted">{{ $equipement->code_inventaire }} — {{ $equipement->marque?->libelle }} {{ $equipement->modele }}</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="affectationForm">
                @csrf
                <div class="modal-body px-4 py-3">
                    <h6 class="fw-bold mb-3 small text-uppercase text-muted" style="letter-spacing:.5px">Type d'emplacement</h6>
                    <div class="row g-3 mb-4 justify-content-center">
                        <div class="col-8">
                            <label class="aff-type-card d-flex flex-column align-items-center justify-content-center gap-2 p-4 rounded-3 border cursor-pointer text-center position-relative" data-value="LOCAL">
                                <input type="radio" name="type_cible" value="LOCAL" class="d-none" checked>
                                <div class="aff-type-icon rounded-3 p-3 bg-light"><i class="bi bi-door-open fs-3 text-secondary"></i></div>
                                <h6 class="fw-bold mb-1">Sélectionner un local / salle</h6>
                                <small class="text-muted">Rechercher et associer un local de l'infrastructure</small>
                                <i class="bi bi-check-circle-fill text-primary position-absolute top-0 end-0 m-2 d-none check-icon" style="font-size:.9rem"></i>
                            </label>
                        </div>
                    </div>

                    {{-- Carte récapitulative Local --}}
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
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-link text-dark text-decoration-none" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4" id="btn-save-affectation">
                        <i class="bi bi-check-circle me-1"></i> Enregistrer l'emplacement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('parcinfo::informatique.ordinateurs._selection_modals')

@endsection

@push('css')
<style>
.section-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#344054; border-left:3px solid #0d6efd; padding-left:10px; display:flex; align-items:center; gap:8px; }
.section-num   { background:#0d6efd; color:#fff; font-size:.65rem; font-weight:700; border-radius:4px; padding:1px 6px; }
.field-label   { font-size:.78rem; font-weight:600; color:#475467; margin-bottom:4px; display:block; }
.field-input   { font-size:.875rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; }
.field-input:not(:disabled):focus { background:#fff; border-color:#0d6efd; box-shadow:0 0 0 3px rgba(13,110,253,.1); }
.field-input:disabled { background:#f1f5f9; color:#64748b; }
.timeline { border-left:2px solid #e2e8f0; padding-left:1.5rem; }
.timeline-dot { position:relative; left:-1.65rem; }
.nav-tabs .nav-link { border:none; border-bottom:2px solid transparent; color:#64748b; border-radius:0; }
.nav-tabs .nav-link.active { color:#0d6efd; border-bottom-color:#0d6efd; background:transparent; }
.bg-purple { background-color: #6f42c1; }
.text-purple { color: #6f42c1; }
.bg-purple-subtle { background-color: #e2d9f3; }
.border-purple-subtle { border-color: #d1c1eb; }
</style>
@endpush

@push('js')
<script type="module" src="{{ asset('js/modules/parc-info/serveurs/index.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modules/parc-info/ordinateurs/selection_modals.js') }}?v={{ time() }}"></script>
<script>
const equipementId = {{ $equipement->id }};
let editMode = false;

$(function () {
    $('.dropdown-item[data-statut]').on('click', function (e) {
        e.preventDefault();
        const statut = $(this).data('statut');
        const libelle = $(this).text();
        if (statut === '{{ $equipement->statut }}') return;

        Swal.fire({
            title: 'Changer le statut',
            html: `Nouveau statut : <b>${libelle}</b><br><br>Veuillez indiquer un motif :`,
            input: 'text',
            inputPlaceholder: 'Motif obligatoire...',
            showCancelButton: true,
            confirmButtonText: 'Enregistrer',
            cancelButtonText: 'Annuler',
            preConfirm: (value) => {
                if (!value.trim()) {
                    Swal.showValidationMessage('Le motif est obligatoire');
                    return false;
                }
                return value;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ route('parc-info.serveurs.update-statut', $equipement->id) }}`,
                    method: 'PATCH',
                    data: { statut: statut, motif: result.value },
                    success: (res) => {
                        if (res.success) {
                            Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 2000, showConfirmButton: false })
                                .then(() => window.location.reload());
                        }
                    },
                    error: (xhr) => Swal.fire('Erreur', xhr.responseJSON?.message || 'Une erreur est survenue.', 'error')
                });
            }
        });
    });

    $('#btn-desaffecter').on('click', function () {
        Swal.fire({
            title: 'Désaffecter l\'équipement',
            html: 'Cette action mettra l\'équipement <b>En stock</b>.<br><br>Veuillez indiquer un motif :',
            input: 'text',
            inputPlaceholder: 'Motif obligatoire...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Désaffecter',
            cancelButtonText: 'Annuler',
            preConfirm: (value) => {
                if (!value.trim()) {
                    Swal.showValidationMessage('Le motif est obligatoire');
                    return false;
                }
                return value;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ route('parc-info.serveurs.desaffecter', $equipement->id) }}`,
                    method: 'POST',
                    data: { motif: result.value },
                    success: (res) => {
                        if (res.success) {
                            Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 2000, showConfirmButton: false })
                                .then(() => window.location.reload());
                        }
                    },
                    error: (xhr) => Swal.fire('Erreur', xhr.responseJSON?.message || 'Une erreur est survenue.', 'error')
                });
            }
        });
    });

    function setEditMode(on) {
        editMode = on;
        $('.field-input').not('#f_code_inventaire').prop('disabled', !on);
        $('#fiche-actions').toggleClass('d-none', !on);
        $('.btn-add-ref').toggleClass('d-none', !on);
        $('#btn-edit-toggle').toggleClass('btn-primary', !on).toggleClass('btn-warning', on)
               .html(on ? '<i class="bi bi-x me-1"></i> Annuler' : '<i class="bi bi-pencil me-1"></i> Modifier');
    }

    $('#btn-edit-toggle').on('click', () => setEditMode(!editMode));
    $('#btn-cancel-edit').on('click', () => { setEditMode(false); location.reload(); });

    $('#ficheForm').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btn-save-fiche').prop('disabled', true).text('Enregistrement...');
        $.ajax({
            url: route('parc-info.serveurs.update', equipementId),
            method: 'PUT',
            data: $(this).serialize(),
            success: (res) => {
                if (res.success) {
                    Swal.fire({ icon:'success', title:'Mis à jour', timer:1500, showConfirmButton:false })
                        .then(() => location.reload());
                }
            },
            error: (xhr) => {
                $btn.prop('disabled', false).html('<i class="bi bi-floppy me-1"></i> Enregistrer');
                Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur serveur', 'error');
            }
        });
    });

    // ── Modale affectation ────────────────────────────────────────────────────
    function openAffModal() { $('#affectationModal').modal('show'); }
    $('#btn-nouvelle-affectation').on('click', openAffModal);
    $('#btn-def-emplacement').on('click', openAffModal);

    // Écoute de l'événement de sélection du local
    $(document).on('local:selected', function (e, local) {
        if (!$('#affectationModal').hasClass('show')) return;
        $('#affectationModal .aff-type-card').removeClass('selected');
        $('#affectationModal .aff-type-card[data-value="LOCAL"]').addClass('selected');
        $('#local-summary-code').text(local.code);
        $('#local-summary-libelle').text(local.libelle);
        $('#local-summary-type').text(local.type);
        $('#local-summary-etage').text(local.etage);
        $('#local-summary-batiment').text(local.batiment);
        $('#affectationModal #local_id').val(local.id);
        $('#affectationModal .aff-summary').addClass('d-none');
        $('#affectationModal #aff-local-summary').removeClass('d-none');
    });

    // Soumission du formulaire d'affectation
    $('#affectationForm').on('submit', function (e) {
        e.preventDefault();

        const localId = $('#affectationModal #local_id').val();
        if (!localId) {
            Swal.fire({ icon: 'warning', title: 'Attention', text: 'Veuillez sélectionner un local.', timer: 2500, showConfirmButton: false });
            return;
        }

        const $btn = $('#btn-save-affectation').prop('disabled', true)
            .html('<i class="bi bi-hourglass-split me-1"></i> Enregistrement...');
        const data = $(this).serialize() + `&equipement_id=${equipementId}`;
        $.post(route('parc-info.serveurs.store-affectation'), data, (res) => {
            if (res.success) {
                $('#affectationModal').modal('hide');
                Swal.fire({ icon: 'success', title: 'Emplacement enregistré', timer: 2000, showConfirmButton: false })
                    .then(() => location.reload());
            }
        }).fail((xhr) => {
            const msg = xhr.responseJSON?.errors
                ? Object.values(xhr.responseJSON.errors).flat().join('\n')
                : (xhr.responseJSON?.message ?? 'Erreur serveur');
            Swal.fire('Erreur', msg, 'error');
        }).always(() => $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Enregistrer l\'emplacement'));
    });

    // Reset modale à la fermeture
    $('#affectationModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
        $('#affectationModal .aff-type-card').removeClass('selected');
        $('#affectationModal .aff-summary').addClass('d-none');
        $('#affectationModal #local_id').val('');
    });

    // ── Ajout rapide de nomenclatures (QuickAdd) ──────────────────────────────
    function quickAdd(title, placeholder, routeName, targetSelectId) {
        Swal.fire({
            title: title,
            input: 'text',
            inputPlaceholder: placeholder,
            showCancelButton: true,
            confirmButtonText: 'Ajouter',
            cancelButtonText: 'Annuler',
            showLoaderOnConfirm: true,
            preConfirm: (val) => {
                if (!val) return Swal.showValidationMessage('Veuillez saisir une valeur');
                return $.post(route(routeName), { libelle: val, _token: '{{ csrf_token() }}' })
                    .then(res => res.data)
                    .catch(err => {
                        Swal.showValidationMessage(err.responseJSON?.message || 'Erreur serveur');
                    });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                const data = result.value;
                $(`#${targetSelectId}`).append(new Option(data.libelle, data.id, true, true));
                Swal.fire({ icon: 'success', title: 'Ajouté !', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
            }
        });
    }

    $('#btn-add-f-marque').on('click', () => quickAdd('Nouvelle marque', 'Ex: Dell, HP...', 'parc-info.ordinateurs.store-marque', 'f_marque_id'));
    $('#btn-add-f-ram').on('click', () => quickAdd('Nouveau type de RAM', 'Ex: DDR4, DDR5...', 'parc-info.ordinateurs.store-type-ram', 'f_ram_type_id'));
    $('#btn-add-f-os').on('click', () => quickAdd("Nouveau système d'exploitation", 'Ex: Windows Server 2022...', 'parc-info.ordinateurs.store-type-os', 'f_os_type_id'));
    $('#btn-add-f-disque').on('click', () => quickAdd('Nouveau type de disque', 'Ex: SSD NVMe...', 'parc-info.ordinateurs.store-type-disque', 'f_disque_type_id'));
    $('#btn-add-f-cpu').on('click', () => quickAdd('Nouveau type de CPU', 'Ex: Intel Xeon...', 'parc-info.ordinateurs.store-type-cpu', 'f_cpu_type_id'));

    // ── Associer Licence ──
    $('#form-associer-licence').on('submit', function (e) {
        e.preventDefault();
        const licenceId = $('#assoc_licence_id').val();
        if (!licenceId) {
            return;
        }

        const $btn = $('#btn-save-associer-licence');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>...');

        $.ajax({
            url: `/parc-info/informatique/licences/${licenceId}/affecter`,
            method: 'POST',
            data: {
                type_affectation: 'device',
                equipement_id: equipementId,
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message,
                        timer: 1500
                    }).then(() => location.reload());
                }
            },
            error: function (xhr) {
                Swal.fire('Erreur', xhr.responseJSON?.message || 'Une erreur est survenue', 'error');
                $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Associer');
            }
        });
    });

    // ── Désassocier Licence ──
    $(document).on('click', '.btn-desassocier-licence', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Désassocier la licence ?',
            text: "Cette licence ne sera plus active sur cet équipement.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, désassocier',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/parc-info/informatique/licences/affectations/${id}/desaffecter`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Succès', response.message, 'success').then(() => location.reload());
                        }
                    },
                    error: function (xhr) {
                        Swal.fire('Erreur', xhr.responseJSON?.message || 'Une erreur est survenue', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
