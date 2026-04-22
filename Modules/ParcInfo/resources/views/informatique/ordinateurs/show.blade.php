@extends('parcinfo::layouts.master')

@section('header', $equipement->code_inventaire)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.ordinateurs-fixes.index') }}">Ordinateurs Fixes</a></li>
    <li class="breadcrumb-item active">{{ $equipement->code_inventaire }}</li>
@endsection

@php
    $o   = $equipement->ordinateur;
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
                    <i class="bi bi-pc-display fs-2 text-primary"></i>
                </div>
            </div>
            <div class="col">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h4 class="fw-bold mb-0">{{ $equipement->marque?->libelle }} {{ $equipement->modele }}</h4>
                    <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }} border border-{{ $sc }}-subtle px-2 py-1">
                        {{ $equipement->statut_label }}
                    </span>
                    <span class="badge bg-{{ $ec }}-subtle text-{{ $ec }} border border-{{ $ec }}-subtle px-2 py-1">
                        {{ ucfirst($equipement->etat) }}
                    </span>
                </div>
                <div class="d-flex gap-4 flex-wrap text-muted small">
                    <span><i class="bi bi-upc me-1"></i>{{ $equipement->code_inventaire }}</span>
                    <span><i class="bi bi-hash me-1"></i>{{ $equipement->numero_serie }}</span>
                    @if($o?->nom_hote)
                    <span><i class="bi bi-hdd-network me-1"></i>{{ $o->nom_hote }}</span>
                    @endif
                    @if($o?->typeOs)
                    <span><i class="bi bi-windows me-1"></i>{{ $o->typeOs->libelle }}</span>
                    @endif
                </div>
            </div>
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" id="btn-nouvelle-affectation">
                    <i class="bi bi-person-plus me-1"></i> Affecter
                </button>
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
        ['affectation','bi-person-badge',   'Affectation Actuelle'],
        ['historique-aff','bi-clock-history','Historique Affectations'],
        ['historique-chg','bi-journal-text', 'Journal des Changements'],
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

        {{-- Section 01 — Identification --}}
        <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
            <div class="card-body p-4">
                <h6 class="section-title mb-4"><span class="section-num">01</span> Identification</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="field-label">Code Inventaire <span class="text-danger">*</span></label>
                        <input type="text" class="form-control field-input" name="code_inventaire"
                               value="{{ $equipement->code_inventaire }}" id="f_code_inventaire" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Numéro de Série <span class="text-danger">*</span></label>
                        <input type="text" class="form-control field-input" name="numero_serie"
                               value="{{ $equipement->numero_serie }}" id="f_numero_serie" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Marque</label>
                        <div class="input-group">
                            <select class="form-select field-input" name="marque_id" id="f_marque_id" disabled>
                                <option value="">—</option>
                                @foreach($marques as $m)
                                <option value="{{ $m->id }}" {{ $equipement->marque_id == $m->id ? 'selected':'' }}>{{ $m->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Modèle <span class="text-danger">*</span></label>
                        <input type="text" class="form-control field-input" name="modele"
                               value="{{ $equipement->modele }}" id="f_modele" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Statut</label>
                        <select class="form-select field-input" name="statut" id="f_statut" disabled>
                            @foreach(['en_stock'=>'En stock','en_service'=>'En service','en_reparation'=>'En réparation','perdu'=>'Perdu / Volé','reforme'=>'Réformé'] as $v=>$l)
                            <option value="{{ $v }}" {{ $equipement->statut === $v ? 'selected':'' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">État</label>
                        <select class="form-select field-input" name="etat" id="f_etat" disabled>
                            @foreach(['bon'=>'Bon','passable'=>'Passable','mauvais'=>'Mauvais','avarie'=>'Avarié'] as $v=>$l)
                            <option value="{{ $v }}" {{ $equipement->etat === $v ? 'selected':'' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="field-label">Date d'acquisition</label>
                        <input type="date" class="form-control field-input" name="date_acquisition"
                               value="{{ $equipement->date_acquisition?->format('Y-m-d') }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="field-label">Mise en service</label>
                        <input type="date" class="form-control field-input" name="date_mise_en_service"
                               value="{{ $equipement->date_mise_en_service?->format('Y-m-d') }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="field-label">Fin de garantie</label>
                        <input type="date" class="form-control field-input" name="date_fin_garantie"
                               value="{{ $equipement->date_fin_garantie?->format('Y-m-d') }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="field-label">Valeur d'achat (FCFA)</label>
                        <input type="number" class="form-control field-input" name="valeur_achat"
                               value="{{ $equipement->valeur_achat }}" disabled>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 02 — Configuration matérielle --}}
        <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
            <div class="card-body p-4">
                <h6 class="section-title mb-4"><span class="section-num">02</span> Configuration Matérielle</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="field-label">Type PC</label>
                        <div class="btn-group w-100" role="group">
                            @foreach(['Portable','Fixe','Workstation'] as $t)
                            <input type="radio" class="btn-check" name="type_pc" id="f_type_pc_{{ $t }}"
                                   value="{{ $t }}" {{ $o?->type_pc === $t ? 'checked':'' }} disabled>
                            <label class="btn btn-outline-primary btn-sm" for="f_type_pc_{{ $t }}">{{ $t }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Processeur</label>
                        <input type="text" class="form-control field-input" name="processeur_model"
                               value="{{ $o?->processeur_model }}" placeholder="—" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Type CPU</label>
                        <select class="form-select field-input" name="cpu_type_id" disabled>
                            <option value="">—</option>
                            @foreach($typesCpu as $c)
                            <option value="{{ $c->id }}" {{ $o?->cpu_type_id == $c->id ? 'selected':'' }}>{{ $c->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="field-label">RAM (Go)</label>
                        <select class="form-select field-input" name="ram_capacite_go" disabled>
                            <option value="">—</option>
                            @foreach([4,8,16,32,64,128] as $r)
                            <option value="{{ $r }}" {{ $o?->ram_capacite_go == $r ? 'selected':'' }}>{{ $r }} Go</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="field-label">Type RAM</label>
                        <select class="form-select field-input" name="ram_type_id" disabled>
                            <option value="">—</option>
                            @foreach($typesRam as $r)
                            <option value="{{ $r->id }}" {{ $o?->ram_type_id == $r->id ? 'selected':'' }}>{{ $r->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="field-label">Stockage (Go)</label>
                        <input type="number" class="form-control field-input" name="stockage_capacite_go"
                               value="{{ $o?->stockage_capacite_go }}" placeholder="—" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="field-label">Type Disque</label>
                        <select class="form-select field-input" name="disque_type_id" disabled>
                            <option value="">—</option>
                            @foreach($typesDisque as $d)
                            <option value="{{ $d->id }}" {{ $o?->disque_type_id == $d->id ? 'selected':'' }}>{{ $d->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="field-label">Système d'exploitation</label>
                        <select class="form-select field-input" name="os_type_id" disabled>
                            <option value="">—</option>
                            @foreach($typesOs as $os)
                            <option value="{{ $os->id }}" {{ $o?->os_type_id == $os->id ? 'selected':'' }}>{{ $os->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="field-label">Nom d'hôte</label>
                        <input type="text" class="form-control field-input" name="nom_hote"
                               value="{{ $o?->nom_hote }}" placeholder="—" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">MAC Ethernet</label>
                        <input type="text" class="form-control field-input" name="adresse_mac_ethernet"
                               value="{{ $o?->adresse_mac_ethernet }}" placeholder="—" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">MAC WiFi</label>
                        <input type="text" class="form-control field-input" name="adresse_mac_wifi"
                               value="{{ $o?->adresse_mac_wifi }}" placeholder="—" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Domaine / Workgroup</label>
                        <input type="text" class="form-control field-input" name="domaine_workgroup"
                               value="{{ $o?->domaine_workgroup }}" placeholder="—" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="field-label d-block mb-2">Sécurité</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="support_tpm2" id="f_tpm2"
                                       {{ $o?->support_tpm2 ? 'checked':'' }} disabled>
                                <label class="form-check-label small" for="f_tpm2">TPM 2.0</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="support_secure_boot" id="f_sb"
                                       {{ $o?->support_secure_boot ? 'checked':'' }} disabled>
                                <label class="form-check-label small" for="f_sb">Secure Boot</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 03 — Licences --}}
        <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
            <div class="card-body p-4">
                <h6 class="section-title mb-4"><span class="section-num">03</span> Licences</h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="field-label">Licence Windows</label>
                        <select class="form-select field-input" name="licence_windows_type" disabled>
                            <option value="">—</option>
                            @foreach(['OEM','CLE','AUCUNE'] as $l)
                            <option value="{{ $l }}" {{ $o?->licence_windows_type === $l ? 'selected':'' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="field-label">Clé Windows</label>
                        <input type="text" class="form-control field-input font-monospace" name="licence_windows_cle"
                               value="{{ $o?->licence_windows_cle }}" placeholder="XXXXX-XXXXX-XXXXX-XXXXX-XXXXX" disabled>
                    </div>
                    <div class="col-md-2">
                        <label class="field-label">Licence Office</label>
                        <select class="form-select field-input" name="licence_office_type" disabled>
                            <option value="">—</option>
                            @foreach(['CLE','AUCUNE'] as $l)
                            <option value="{{ $l }}" {{ $o?->licence_office_type === $l ? 'selected':'' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="field-label">Clé Office</label>
                        <input type="text" class="form-control field-input font-monospace" name="licence_office_cle"
                               value="{{ $o?->licence_office_cle }}" placeholder="—" disabled>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions formulaire --}}
        <div class="d-none" id="fiche-actions">
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-link text-dark text-decoration-none" id="btn-cancel-edit">Annuler</button>
                <button type="submit" class="btn btn-primary px-4" id="btn-save-fiche">
                    <i class="bi bi-floppy me-1"></i> Enregistrer
                </button>
            </div>
        </div>
    </form>
</div>

{{-- ══ TAB 2 : AFFECTATION ACTUELLE ══ --}}
<div class="tab-pane fade" id="pane-affectation" role="tabpanel">
    <div class="card border-0 shadow-sm" style="border-radius:12px">
        <div class="card-body p-4">
            @if($aff)
            @php
                $cible = match($aff->type_cible) {
                    'EMPLOYE' => ['icon'=>'bi-person-badge','label'=>'Employé','color'=>'primary'],
                    'POSTE'   => ['icon'=>'bi-pc-display',  'label'=>'Poste de travail','color'=>'info'],
                    'LOCAL'   => ['icon'=>'bi-door-open',   'label'=>'Local','color'=>'success'],
                    default   => ['icon'=>'bi-question',    'label'=>'—','color'=>'secondary'],
                };
            @endphp
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="rounded-3 bg-{{ $cible['color'] }} bg-opacity-10 p-3">
                    <i class="bi {{ $cible['icon'] }} fs-4 text-{{ $cible['color'] }}"></i>
                </div>
                <div>
                    <div class="fw-bold">Affectation {{ $aff->type_affectation === 'PERMANENTE' ? 'Permanente' : 'Temporaire' }}</div>
                    <div class="text-muted small">Depuis le {{ $aff->date_debut?->format('d/m/Y') }}</div>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle ms-auto px-3 py-2">Active</span>
            </div>

            <div class="row g-3">
                @if($aff->type_cible === 'EMPLOYE' && $aff->employe)
                <div class="col-md-4"><div class="info-block"><div class="info-label">Matricule</div><div class="info-value">{{ $aff->employe->matricule }}</div></div></div>
                <div class="col-md-4"><div class="info-block"><div class="info-label">Nom</div><div class="info-value">{{ $aff->employe->nom }}</div></div></div>
                <div class="col-md-4"><div class="info-block"><div class="info-label">Prénom</div><div class="info-value">{{ $aff->employe->prenom }}</div></div></div>
                @endif
                @if($aff->type_cible === 'POSTE' && $aff->posteTravail)
                <div class="col-md-3"><div class="info-block"><div class="info-label">Code Poste</div><div class="info-value text-primary fw-bold">{{ $aff->posteTravail->code }}</div></div></div>
                <div class="col-md-5"><div class="info-block"><div class="info-label">Libellé</div><div class="info-value">{{ $aff->posteTravail->libelle }}</div></div></div>
                @endif
                @if($aff->type_cible === 'LOCAL' && $aff->local)
                <div class="col-md-6"><div class="info-block"><div class="info-label">Local</div><div class="info-value">{{ $aff->local->libelle }}</div></div></div>
                @endif
                @if($aff->direction)
                <div class="col-md-4"><div class="info-block"><div class="info-label">Direction</div><div class="info-value">{{ $aff->direction->libelle }}</div></div></div>
                @endif
                @if($aff->service)
                <div class="col-md-4"><div class="info-block"><div class="info-label">Service</div><div class="info-value">{{ $aff->service->libelle }}</div></div></div>
                @endif
                <div class="col-md-3"><div class="info-block"><div class="info-label">Date début</div><div class="info-value">{{ $aff->date_debut?->format('d/m/Y') }}</div></div></div>
                <div class="col-md-3"><div class="info-block"><div class="info-label">Date fin</div><div class="info-value">{{ $aff->date_fin?->format('d/m/Y') ?? 'Indéterminée' }}</div></div></div>
            </div>
            @else
            <div class="text-center py-5">
                <div class="mb-3"><i class="bi bi-person-x fs-1 text-muted opacity-50"></i></div>
                <p class="text-muted fw-semibold">Aucune affectation active</p>
                <button class="btn btn-primary btn-sm" id="btn-nouvelle-affectation-2">
                    <i class="bi bi-person-plus me-1"></i> Créer une affectation
                </button>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ══ TAB 3 : HISTORIQUE AFFECTATIONS ══ --}}
<div class="tab-pane fade" id="pane-historique-aff" role="tabpanel">
    <div class="card border-0 shadow-sm" style="border-radius:12px">
        <div class="card-body p-0">
            @if($equipement->affectations->count())
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3 small fw-bold text-uppercase text-muted">Type</th>
                            <th class="py-3 small fw-bold text-uppercase text-muted">Cible</th>
                            <th class="py-3 small fw-bold text-uppercase text-muted">Rattachement</th>
                            <th class="py-3 small fw-bold text-uppercase text-muted">Début</th>
                            <th class="py-3 small fw-bold text-uppercase text-muted">Fin</th>
                            <th class="py-3 small fw-bold text-uppercase text-muted">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($equipement->affectations->sortByDesc('date_debut') as $a)
                        <tr>
                            <td class="px-4">
                                <span class="badge bg-light text-dark border small">{{ $a->type_affectation ?? '—' }}</span>
                            </td>
                            <td>
                                @if($a->type_cible === 'EMPLOYE' && $a->employe)
                                    <div class="fw-semibold small">{{ $a->employe->full_name }}</div>
                                    <div class="text-muted" style="font-size:.75rem">{{ $a->employe->matricule }}</div>
                                @elseif($a->type_cible === 'POSTE' && $a->posteTravail)
                                    <div class="fw-semibold small text-primary">{{ $a->posteTravail->code }}</div>
                                    <div class="text-muted" style="font-size:.75rem">{{ $a->posteTravail->libelle }}</div>
                                @elseif($a->type_cible === 'LOCAL' && $a->local)
                                    <div class="fw-semibold small">{{ $a->local->libelle }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                {{ $a->direction?->libelle ?? ($a->service?->libelle ?? ($a->unite?->libelle ?? '—')) }}
                            </td>
                            <td class="small">{{ $a->date_debut?->format('d/m/Y') ?? '—' }}</td>
                            <td class="small">{{ $a->date_fin?->format('d/m/Y') ?? '<span class="text-muted">En cours</span>' }}</td>
                            <td>
                                @if($a->statut)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                @else
                                <span class="badge bg-secondary-subtle text-secondary border">Terminée</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-clock-history fs-1 opacity-25 d-block mb-2"></i>
                Aucun historique d'affectation.
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ══ TAB 4 : JOURNAL DES CHANGEMENTS ══ --}}
<div class="tab-pane fade" id="pane-historique-chg" role="tabpanel">
    <div class="card border-0 shadow-sm" style="border-radius:12px">
        <div class="card-body p-4">
            @if($equipement->historique->count())
            <div class="timeline">
                @foreach($equipement->historique->sortByDesc('date_changement') as $h)
                @php
                    $typeColors = ['STATUT'=>'primary','ETAT'=>'warning','AFFECTATION'=>'info','TECHNIQUE'=>'secondary'];
                    $tc = $typeColors[$h->type_changement] ?? 'secondary';
                @endphp
                <div class="timeline-item d-flex gap-3 mb-4">
                    <div class="timeline-dot bg-{{ $tc }} rounded-circle flex-shrink-0 mt-1" style="width:10px;height:10px;margin-top:6px"></div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-{{ $tc }}-subtle text-{{ $tc }} border border-{{ $tc }}-subtle small">{{ $h->type_changement }}</span>
                            <span class="text-muted small">{{ $h->date_changement?->format('d/m/Y H:i') }}</span>
                        </div>
                        @if($h->ancien_statut || $h->nouveau_statut)
                        <div class="small mb-1">
                            <span class="text-muted">{{ $h->ancien_statut }}</span>
                            <i class="bi bi-arrow-right mx-1 text-muted"></i>
                            <span class="fw-semibold">{{ $h->nouveau_statut }}</span>
                        </div>
                        @endif
                        <div class="small text-muted">{{ $h->motif }}</div>
                        @if($h->reference_document)
                        <div class="small text-muted"><i class="bi bi-paperclip me-1"></i>{{ $h->reference_document }}</div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-journal-text fs-1 opacity-25 d-block mb-2"></i>
                Aucun changement enregistré.
            </div>
            @endif
        </div>
    </div>
</div>

</div>{{-- end tab-content --}}

{{-- ── MODALE AFFECTATION ── --}}
<div class="modal fade" id="affectationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px">
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <div>
                    <h5 class="modal-title fw-bold mb-0">Nouvelle Affectation</h5>
                    <small class="text-muted">{{ $equipement->code_inventaire }} — {{ $equipement->marque?->libelle }} {{ $equipement->modele }}</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="affectationForm">
                @csrf
                <div class="modal-body px-4 py-3">
                    {{-- Type cible --}}
                    <h6 class="fw-bold mb-3 small text-uppercase text-muted" style="letter-spacing:.5px">Mode d'affectation</h6>
                    <div class="row g-3 mb-4">
                        @foreach([['EMPLOYE','bi-person-badge','Affecter à un employé'],['POSTE','bi-pc-display','Poste de travail'],['LOCAL','bi-door-open','Local']] as [$v,$ic,$lb])
                        <div class="col-4">
                            <label class="aff-type-card d-flex flex-column align-items-center justify-content-center gap-2 p-3 rounded-3 border cursor-pointer text-center position-relative" data-value="{{ $v }}">
                                <input type="radio" name="type_cible" value="{{ $v }}" class="d-none">
                                <div class="aff-type-icon rounded-3 p-3 bg-light"><i class="bi {{ $ic }} fs-3 text-secondary"></i></div>
                                <small class="fw-semibold" style="font-size:.78rem">{{ $lb }}</small>
                                <i class="bi bi-check-circle-fill text-primary position-absolute top-0 end-0 m-2 d-none check-icon" style="font-size:.9rem"></i>
                            </label>
                        </div>
                        @endforeach
                    </div>

                    {{-- Détail employé --}}
                    <div id="ma-employe" class="aff-detail d-none">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="field-label">Matricule</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control field-input" id="ma-employe-search" placeholder="Rechercher...">
                                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                </div>
                                <input type="hidden" name="dossier_employe_id" id="ma_employe_id">
                            </div>
                            <div class="col-md-7">
                                <label class="field-label">Nom & Prénoms</label>
                                <input type="text" class="form-control field-input" id="ma-employe-nom" readonly placeholder="—">
                            </div>
                        </div>
                    </div>

                    {{-- Détail poste --}}
                    <div id="ma-poste" class="aff-detail d-none">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="field-label">Recherche du poste</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control field-input" id="ma-poste-search" placeholder="Code ou libellé...">
                                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                </div>
                                <input type="hidden" name="poste_travail_id" id="ma_poste_id">
                            </div>
                            <div id="ma-poste-detail" class="col-12 d-none">
                                <div class="row g-2 p-2 bg-light rounded-3">
                                    <div class="col-3"><div class="text-muted" style="font-size:.7rem">CODE</div><div class="fw-bold small text-primary" id="ma-poste-code">—</div></div>
                                    <div class="col-3"><div class="text-muted" style="font-size:.7rem">LIBELLÉ</div><div class="small" id="ma-poste-libelle">—</div></div>
                                    <div class="col-3"><div class="text-muted" style="font-size:.7rem">SERVICE</div><div class="small" id="ma-poste-service">—</div></div>
                                    <div class="col-3"><div class="text-muted" style="font-size:.7rem">LOCAL</div><div class="small" id="ma-poste-local">—</div></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Détail local --}}
                    <div id="ma-local" class="aff-detail d-none">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="field-label">Sélectionner un local</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control field-input" id="ma-local-search" placeholder="Rechercher...">
                                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                </div>
                                <input type="hidden" name="local_id" id="ma_local_id">
                            </div>
                            <div class="col-md-4">
                                <label class="field-label">Niveau rattachement</label>
                                <select class="form-select field-input" name="niveau_rattachement">
                                    <option value="">—</option>
                                    <option value="DIRECTION">Direction</option>
                                    <option value="SERVICE">Service</option>
                                    <option value="UNITE">Unité</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="field-label">Structure</label>
                                <select class="form-select field-input" name="direction_id_aff">
                                    <option value="">—</option>
                                    @foreach($directions as $d)
                                    <option value="{{ $d->id }}">{{ $d->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Dates communes --}}
                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="field-label">Type d'affectation</label>
                            <select class="form-select field-input" name="type_affectation">
                                <option value="PERMANENTE">Permanente</option>
                                <option value="TEMPORAIRE">Temporaire</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Date de début <span class="text-danger">*</span></label>
                            <input type="date" class="form-control field-input" name="date_debut" required>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Date de fin (Optionnel)</label>
                            <input type="date" class="form-control field-input" name="date_fin">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-link text-dark text-decoration-none" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4" id="btn-save-affectation">
                        <i class="bi bi-check-circle me-1"></i> Enregistrer l'affectation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('css')
<style>
.section-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#344054; border-left:3px solid #0d6efd; padding-left:10px; display:flex; align-items:center; gap:8px; }
.section-num   { background:#0d6efd; color:#fff; font-size:.65rem; font-weight:700; border-radius:4px; padding:1px 6px; }
.field-label   { font-size:.78rem; font-weight:600; color:#475467; margin-bottom:4px; display:block; }
.field-input   { font-size:.875rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; }
.field-input:not(:disabled):focus { background:#fff; border-color:#0d6efd; box-shadow:0 0 0 3px rgba(13,110,253,.1); }
.field-input:disabled { background:#f1f5f9; color:#64748b; }
.info-block    { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:.75rem 1rem; }
.info-label    { font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:#94a3b8; margin-bottom:2px; }
.info-value    { font-size:.875rem; font-weight:600; color:#1e293b; }
.aff-type-card { cursor:pointer; transition:border-color .15s; }
.aff-type-card:hover { border-color:#0d6efd !important; }
.aff-type-card.selected { border-color:#0d6efd !important; background:#f0f6ff; }
.aff-type-card.selected .aff-type-icon { background:#dbeafe !important; }
.aff-type-card.selected .aff-type-icon i { color:#0d6efd !important; }
.aff-type-card.selected .check-icon { display:inline !important; }
.timeline { border-left:2px solid #e2e8f0; padding-left:1.5rem; }
.timeline-dot { position:relative; left:-1.65rem; }
.nav-tabs .nav-link { border:none; border-bottom:2px solid transparent; color:#64748b; border-radius:0; }
.nav-tabs .nav-link.active { color:#0d6efd; border-bottom-color:#0d6efd; background:transparent; }
.nav-tabs .nav-link:hover { color:#0d6efd; }
</style>
@endpush

@push('js')
<script>
const equipementId = {{ $equipement->id }};

$(function () {

    // ── Mode édition ──────────────────────────────────────────────────────────
    let editMode = false;

    function setEditMode(on) {
        editMode = on;
        $('#pane-fiche .field-input').prop('disabled', !on);
        $('#pane-fiche input[type="checkbox"]').prop('disabled', !on);
        $('#pane-fiche input[type="radio"]').prop('disabled', !on);
        $('#fiche-actions').toggleClass('d-none', !on);
        $('#btn-edit-toggle').toggleClass('btn-primary', !on).toggleClass('btn-warning', on)
            .html(on ? '<i class="bi bi-x me-1"></i> Annuler' : '<i class="bi bi-pencil me-1"></i> Modifier');
    }

    $('#btn-edit-toggle').on('click', () => setEditMode(!editMode));
    $('#btn-cancel-edit').on('click', () => { setEditMode(false); location.reload(); });

    // ── Sauvegarde fiche ──────────────────────────────────────────────────────
    $('#ficheForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#btn-save-fiche').prop('disabled', true).text('Enregistrement...');
        $.ajax({
            url   : route('parc-info.ordinateurs-fixes.update', equipementId),
            method: 'PUT',
            data  : $(this).serialize(),
            success: (res) => {
                if (res.success) {
                    setEditMode(false);
                    Swal.fire({ icon:'success', title:'Enregistré', text: res.message, timer:2000, showConfirmButton:false });
                }
            },
            error: (xhr) => {
                if (xhr.status === 422) {
                    Object.entries(xhr.responseJSON.errors ?? {}).forEach(([f, msgs]) => {
                        $(`#f_${f}`).addClass('is-invalid').after(`<div class="invalid-feedback">${msgs[0]}</div>`);
                    });
                } else {
                    Swal.fire('Erreur', xhr.responseJSON?.message ?? 'Erreur serveur', 'error');
                }
            },
            complete: () => $btn.prop('disabled', false).html('<i class="bi bi-floppy me-1"></i> Enregistrer'),
        });
    });

    // ── Modale affectation ────────────────────────────────────────────────────
    function openAffModal() { $('#affectationModal').modal('show'); }
    $('#btn-nouvelle-affectation, #btn-nouvelle-affectation-2').on('click', openAffModal);

    $(document).on('click', '.aff-type-card', function () {
        $('.aff-type-card').removeClass('selected');
        $(this).addClass('selected').find('input[type="radio"]').prop('checked', true);
        const val = $(this).data('value');
        $('.aff-detail').addClass('d-none');
        $(`#ma-${val.toLowerCase()}`).removeClass('d-none');
    });

    // Recherche employé
    let empT;
    $('#ma-employe-search').on('input', function () {
        clearTimeout(empT);
        const q = $(this).val();
        if (q.length < 2) return;
        empT = setTimeout(() => {
            $.get(route('parc-info.ordinateurs-fixes.search-employes'), { q }, (data) => {
                if (data.length === 1) {
                    $('#ma_employe_id').val(data[0].id);
                    $('#ma-employe-search').val(data[0].matricule);
                    $('#ma-employe-nom').val(`${data[0].nom} ${data[0].prenom}`);
                }
            });
        }, 300);
    });

    // Recherche poste
    let posteT;
    $('#ma-poste-search').on('input', function () {
        clearTimeout(posteT);
        const q = $(this).val();
        if (q.length < 2) return;
        posteT = setTimeout(() => {
            $.get(route('parc-info.ordinateurs-fixes.search-postes'), { q }, (data) => {
                if (data.length >= 1) {
                    const p = data[0];
                    $('#ma_poste_id').val(p.id);
                    $('#ma-poste-search').val(p.code);
                    $('#ma-poste-code').text(p.code);
                    $('#ma-poste-libelle').text(p.libelle);
                    $('#ma-poste-service').text(p.service ?? '—');
                    $('#ma-poste-local').text(p.local ?? '—');
                    $('#ma-poste-detail').removeClass('d-none');
                }
            });
        }, 300);
    });

    // Recherche local
    let localT;
    $('#ma-local-search').on('input', function () {
        clearTimeout(localT);
        const q = $(this).val();
        if (q.length < 2) return;
        localT = setTimeout(() => {
            $.get(route('parc-info.ordinateurs-fixes.search-locaux'), { q }, (data) => {
                if (data.length >= 1) {
                    $('#ma_local_id').val(data[0].id);
                    $('#ma-local-search').val(data[0].text);
                }
            });
        }, 300);
    });

    // Soumission affectation
    $('#affectationForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#btn-save-affectation').prop('disabled', true);
        const data = $(this).serialize() + `&equipement_id=${equipementId}`;
        $.post(route('parc-info.ordinateurs-fixes.store-affectation'), data, (res) => {
            if (res.success) {
                $('#affectationModal').modal('hide');
                Swal.fire({ icon:'success', title:'Affectation enregistrée', timer:2000, showConfirmButton:false })
                    .then(() => location.reload());
            }
        }).fail((xhr) => {
            Swal.fire('Erreur', xhr.responseJSON?.message ?? 'Erreur serveur', 'error');
        }).always(() => $btn.prop('disabled', false));
    });

    // Reset modale à la fermeture
    $('#affectationModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
        $('.aff-type-card').removeClass('selected');
        $('.aff-detail').addClass('d-none');
        $('#ma-poste-detail').addClass('d-none');
        $('#ma-employe-nom').val('');
    });
});
</script>
@endpush
