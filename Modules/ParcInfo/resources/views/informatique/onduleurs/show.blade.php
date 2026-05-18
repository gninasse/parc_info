@extends('parcinfo::layouts.master')

@section('header', $equipement->code_inventaire)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.onduleurs.index') }}">Onduleurs</a></li>
    <li class="breadcrumb-item active">{{ $equipement->code_inventaire }}</li>
@endsection

@php
    $infra = $equipement->infrastructure;
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
                    <i class="bi bi-lightning-charge fs-2 text-primary"></i>
                </div>
            </div>
            <div class="col">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h4 class="fw-bold mb-0">{{ $equipement->marque?->libelle }} {{ $equipement->modele }}</h4>
                    <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }} border border-{{ $sc }}-subtle px-2 py-1">
                        {!! $equipement->statut_label !!}
                    </span>
                    <span class="badge bg-{{ $ec }}-subtle text-{{ $ec }} border border-{{ $ec }}-subtle px-2 py-1">
                        {{ ucfirst($equipement->etat) }}
                    </span>
                </div>
                <div class="d-flex gap-4 flex-wrap text-muted small">
                    <span><i class="bi bi-upc me-1"></i>{{ $equipement->code_inventaire }}</span>
                    <span><i class="bi bi-hash me-1"></i>{{ $equipement->numero_serie }}</span>
                    @if($infra?->puissance_va)
                    <span><i class="bi bi-lightning me-1"></i>{{ $infra->puissance_va }} VA</span>
                    @endif
                    @if($infra?->autonomie_minutes)
                    <span><i class="bi bi-clock me-1"></i>{{ $infra->autonomie_minutes }} min</span>
                    @endif
                </div>
            </div>
            <div class="col-auto d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-arrow-repeat me-1"></i> Statut
                    </button>
                    <ul class="dropdown-menu shadow border-0">
                        @foreach(['en_stock'=>'En stock','en_service'=>'En service','en_reparation'=>'En réparation','perdu'=>'Perdu','reforme'=>'Réformé'] as $k=>$v)
                        <li><a class="dropdown-item py-2" href="#" data-action="update-statut" data-value="{{ $k }}">{{ $v }}</a></li>
                        @endforeach
                    </ul>
                </div>
                @if($aff)
                <button class="btn btn-warning btn-sm" id="btn-desaffecter">
                    <i class="bi bi-x-circle me-1"></i> Désaffecter
                </button>
                @endif
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

    {{-- 1. FICHE TECHNIQUE --}}
    <div class="tab-pane fade show active" id="pane-fiche" role="tabpanel">
        <form id="ficheForm">
            @csrf
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white py-3 border-0">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-info-circle text-primary me-2"></i>Identification & Caractéristiques</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Code Inventaire</label>
                                    <input type="text" class="form-control bg-light" value="{{ $equipement->code_inventaire }}" readonly id="f_code_inventaire">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Numéro de Série</label>
                                    <input type="text" class="form-control" name="numero_serie" value="{{ $equipement->numero_serie }}" disabled id="f_numero_serie">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Marque</label>
                                    <select class="form-select" name="marque_id" disabled id="f_marque_id">
                                        <option value="">Sélectionner...</option>
                                        @foreach($marques as $m)
                                        <option value="{{ $m->id }}" {{ $m->id == $equipement->marque_id ? 'selected' : '' }}>{{ $m->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Modèle</label>
                                    <input type="text" class="form-control" name="modele" value="{{ $equipement->modele }}" disabled id="f_modele">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Type Infrastructure</label>
                                    <input type="text" class="form-control bg-light" value="{{ $infra->typeInfrastructure?->libelle ?? 'UPS' }}" disabled id="f_type_infra_id">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Puissance (VA)</label>
                                    <input type="number" class="form-control" name="puissance_va" value="{{ $infra->puissance_va }}" disabled id="f_puissance_va">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Autonomie (min)</label>
                                    <input type="number" class="form-control" name="autonomie_minutes" value="{{ $infra->autonomie_minutes }}" disabled id="f_autonomie_minutes">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Dernier changement batterie</label>
                                    <input type="date" class="form-control" name="date_dernier_remplacement_batterie" value="{{ $infra->date_dernier_remplacement_batterie?->format('Y-m-d') }}" disabled id="f_date_dernier_remplacement_batterie">
                                </div>
                                <div class="col-md-6 mt-auto">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="est_redondant" id="f_est_redondant" value="1" {{ $infra->est_redondant ? 'checked' : '' }} disabled>
                                        <label class="form-check-label small fw-bold text-muted" for="f_est_redondant">Équipement Redondant</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white py-3 border-0">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-calendar-event text-primary me-2"></i>Dates & État</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">Statut Actuel</label>
                                    <select class="form-select" name="statut" disabled id="f_statut">
                                        @foreach(['en_stock'=>'En stock','en_service'=>'En service','en_reparation'=>'En réparation','perdu'=>'Perdu/Volé','reforme'=>'Réformé'] as $k=>$v)
                                        <option value="{{ $k }}" {{ $equipement->statut == $k ? 'selected' : '' }}>{{ $v }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">État Général</label>
                                    <select class="form-select" name="etat" disabled id="f_etat">
                                        @foreach(['bon'=>'Bon','passable'=>'Passable','mauvais'=>'Mauvais','avarie'=>'Avarié'] as $k=>$v)
                                        <option value="{{ $k }}" {{ $equipement->etat == $k ? 'selected' : '' }}>{{ $v }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">Date d'acquisition</label>
                                    <input type="date" class="form-control" name="date_acquisition" value="{{ $equipement->date_acquisition?->format('Y-m-d') }}" disabled id="f_date_acquisition">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">Fin de garantie</label>
                                    <input type="date" class="form-control" name="date_fin_garantie" value="{{ $equipement->date_fin_garantie?->format('Y-m-d') }}" disabled id="f_date_fin_garantie">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="fiche-actions" class="d-none">
                        <div class="card border-0 shadow-sm bg-primary bg-opacity-10">
                            <div class="card-body p-3 d-grid gap-2">
                                <button type="button" class="btn btn-primary" id="btn-save-fiche"><i class="bi bi-floppy me-1"></i> Enregistrer les modifications</button>
                                <button type="button" class="btn btn-outline-secondary" id="btn-cancel-edit">Annuler</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- 2. AFFECTATION ACTUELLE --}}
    <div class="tab-pane fade" id="pane-affectation" role="tabpanel">
        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-body p-4">
                @if($aff)
                @php
                    $cible = match($aff->type_cible) {
                        'EMPLOYE' => ['icon' => 'bi-person-badge', 'label' => 'Employé', 'color' => 'primary'],
                        'POSTE' => ['icon' => 'bi-pc-display', 'label' => 'Poste de travail', 'color' => 'info'],
                        'LOCAL' => ['icon' => 'bi-door-open', 'label' => 'Local', 'color' => 'success'],
                        default => ['icon' => 'bi-question', 'label' => '—', 'color' => 'secondary'],
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
                    <div class="col-md-4"><div class="info-block"><div class="info-label">Emplacement</div><div class="info-value">{{ $aff->posteTravail->local?->nom_complet ?? '—' }}</div></div></div>
                    @endif
                    @if($aff->type_cible === 'LOCAL' && $aff->local)
                    <div class="col-md-6"><div class="info-block"><div class="info-label">Local</div><div class="info-value">{{ $aff->local->libelle }}</div></div></div>
                    <div class="col-md-6"><div class="info-block"><div class="info-label">Emplacement</div><div class="info-value">{{ $aff->local->nom_complet }}</div></div></div>
                    @endif
                    @if($aff->direction)
                    <div class="col-md-4"><div class="info-block"><div class="info-label">Direction</div><div class="info-value">{{ $aff->direction->libelle }}</div></div></div>
                    @endif
                    @if($aff->service)
                    <div class="col-md-4"><div class="info-block"><div class="info-label">Service</div><div class="info-value">{{ $aff->service->libelle }}</div></div></div>
                    @endif
                    @if($aff->unite)
                    <div class="col-md-4"><div class="info-block"><div class="info-label">Unité</div><div class="info-value">{{ $aff->unite->libelle }}</div></div></div>
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

    {{-- 3. HISTORIQUE AFFECTATIONS --}}
    <div class="tab-pane fade" id="pane-historique-aff" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-muted text-uppercase">
                            <th class="ps-4">Cible</th>
                            <th>Dates</th>
                            <th>Type</th>
                            <th>Service</th>
                            <th class="text-center">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($equipement->affectations as $a)
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-{{ match($a->type_cible){'EMPLOYE'=>'person-badge','POSTE'=>'pc-display','LOCAL'=>'door-open'} }} text-muted"></i>
                                    <div>
                                        <div class="fw-bold small">{{ match($a->type_cible){'EMPLOYE'=>$a->employe?->full_name,'POSTE'=>$a->posteTravail?->code,'LOCAL'=>$a->local?->libelle} }}</div>
                                        <div class="text-muted" style="font-size:.7rem">{{ $a->type_cible }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="small">
                                {{ $a->date_debut?->format('d/m/Y') }} → {{ $a->date_fin?->format('d/m/Y') ?? 'Aujourd\'hui' }}
                            </td>
                            <td><span class="badge bg-light text-dark border small">{{ $a->type_affectation }}</span></td>
                            <td class="small text-muted">{{ $a->service?->libelle ?? '—' }}</td>
                            <td class="text-center">
                                @if($a->statut)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">ACTIF</span>
                                @else
                                    <span class="badge bg-light text-muted border">CLOS</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted small">Aucun historique d'affectation</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 4. JOURNAL DES CHANGEMENTS --}}
    <div class="tab-pane fade" id="pane-historique-chg" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="timeline p-4">
                    @forelse($equipement->historique as $h)
                    <div class="d-flex gap-3 mb-4">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-{{ match($h->type_changement){'STATUT'=>'primary','ETAT'=>'warning','AFFECTATION'=>'info','TECHNIQUE'=>'dark'} }} bg-opacity-10 d-flex align-items-center justify-content-center" style="width:40px;height:40px">
                                <i class="bi bi-{{ match($h->type_changement){'STATUT'=>'arrow-repeat','ETAT'=>'activity','AFFECTATION'=>'person-plus','TECHNIQUE'=>'gear'} }} text-{{ match($h->type_changement){'STATUT'=>'primary','ETAT'=>'warning','AFFECTATION'=>'info','TECHNIQUE'=>'dark'} }}"></i>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h6 class="mb-0 fw-bold">{{ $h->type_changement }}</h6>
                                <small class="text-muted">{{ $h->date_changement->diffForHumans() }} ({{ $h->date_changement->format('d/m/Y H:i') }})</small>
                            </div>
                            <p class="small mb-1 text-dark">{{ $h->motif }}</p>
                            @if($h->ancien_statut || $h->nouveau_statut)
                                <div class="small">
                                    <span class="text-muted">{{ $h->ancien_statut ?? '—' }}</span>
                                    <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                    <span class="fw-bold">{{ $h->nouveau_statut }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-center py-4 text-muted small">Aucun événement enregistré</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODALE NOUVELLE AFFECTATION --}}
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
                <input type="hidden" name="equipement_id" value="{{ $equipement->id }}">
                <div class="modal-body px-4 py-3">
                    <h6 class="fw-bold mb-3 small text-uppercase text-muted" style="letter-spacing:.5px">Mode d'affectation</h6>
                    <div class="row g-3 mb-4">
                        @foreach([['EMPLOYE','bi-person-badge','Affecter à un employé'],['POSTE','bi-pc-display','Poste de travail'],['LOCAL','bi-door-open','Local']] as [$val,$icon,$label])
                        <div class="col-4">
                            <label class="aff-type-card d-flex flex-column align-items-center justify-content-center gap-2 p-3 rounded-3 border cursor-pointer text-center position-relative" data-value="{{ $val }}">
                                <input type="radio" name="type_cible" value="{{ $val }}" class="d-none">
                                <div class="aff-type-icon rounded-3 p-3 bg-light"><i class="bi {{ $icon }} fs-3 text-secondary"></i></div>
                                <small class="fw-semibold" style="font-size:.78rem">{{ $label }}</small>
                                <i class="bi bi-check-circle-fill text-primary position-absolute top-0 end-0 m-2 d-none check-icon" style="font-size:.9rem"></i>
                            </label>
                        </div>
                        @endforeach
                    </div>

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
                        <i class="bi bi-check-circle me-1"></i> Enregistrer l'affectation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODALES --}}
@include('parcinfo::informatique.ordinateurs._selection_modals')

@endsection

@push('css')
<style>
.info-block    { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:.75rem 1rem; }
.info-label    { font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:#94a3b8; margin-bottom:2px; }
.info-value    { font-size:.875rem; font-weight:600; color:#1e293b; }
.aff-type-card { cursor:pointer; transition:border-color .15s; }
.aff-type-card:hover { border-color:#0d6efd !important; }
.aff-type-card.selected { border-color:#0d6efd !important; background:#f0f6ff; }
.aff-type-card.selected .aff-type-icon { background:#dbeafe !important; }
.aff-type-card.selected .aff-type-icon i { color:#0d6efd !important; }
.aff-type-card.selected .check-icon { display:inline !important; }
</style>
@endpush

@push('js')
<script type="module" src="{{ asset('js/modules/parc-info/onduleurs/index.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modules/parc-info/ordinateurs/selection_modals.js') }}?v={{ time() }}"></script>

<script>
$(function(){
    // Mode édition fiche technique
    function setEditMode(on) {
        $('#ficheForm').find('input:not(#f_code_inventaire), select').prop('disabled', !on);
        $('#btn-edit-toggle').toggleClass('btn-primary btn-outline-secondary').html(on ? '<i class="bi bi-eye me-1"></i> Mode Lecture' : '<i class="bi bi-pencil me-1"></i> Modifier');
        $('#fiche-actions').toggleClass('d-none', !on);
    }

    $('#btn-edit-toggle').on('click', function(){
        setEditMode($('#ficheForm').find('input').first().prop('disabled'));
    });

    $('#btn-cancel-edit').on('click', () => location.reload());

    $('#btn-save-fiche').on('click', function(){
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> Enregistrement...');
        $.ajax({
            url: route('parc-info.onduleurs.update', '{{ $equipement->id }}'),
            method: 'PUT',
            data: $('#ficheForm').serialize(),
            success: (res) => {
                if(res.success){
                    Swal.fire({icon:'success', title:'Succès', text:res.message, timer:2000, showConfirmButton:false})
                    .then(() => location.reload());
                }
            },
            error: (xhr) => {
                $btn.prop('disabled', false).html('<i class="bi bi-floppy me-1"></i> Enregistrer les modifications');
                Swal.fire('Erreur', xhr.responseJSON?.message ?? 'Une erreur est survenue.', 'error');
            }
        });
    });

    // Mise à jour Statut (Header)
    $('[data-action="update-statut"]').on('click', function(e){
        e.preventDefault();
        const statut = $(this).data('value');
        Swal.fire({
            title: 'Changer le statut',
            text: 'Veuillez saisir le motif du changement :',
            input: 'textarea',
            inputPlaceholder: 'Ex: Panne batterie, Déploiement service...',
            showCancelButton: true,
            confirmButtonText: 'Mettre à jour',
            cancelButtonText: 'Annuler',
            preConfirm: (motif) => {
                if (!motif) { Swal.showValidationMessage('Le motif est obligatoire'); return false; }
                return motif;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('parc-info.onduleurs.update-statut', '{{ $equipement->id }}'),
                    method: 'PATCH',
                    data: { _token: '{{ csrf_token() }}', statut: statut, motif: result.value },
                    success: () => location.reload(),
                    error: () => Swal.fire('Erreur', 'Impossible de mettre à jour le statut.', 'error')
                });
            }
        });
    });

    // Désaffecter
    $('#btn-desaffecter').on('click', function(){
        Swal.fire({
            title: 'Désaffecter l\'équipement ?',
            text: 'L\'onduleur sera remis en stock. Indiquez le motif :',
            input: 'textarea',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Désaffecter',
            preConfirm: (motif) => {
                if (!motif) { Swal.showValidationMessage('Le motif est obligatoire'); return false; }
                return motif;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(route('parc-info.onduleurs.desaffecter', '{{ $equipement->id }}'), {
                    _token: '{{ csrf_token() }}', motif: result.value
                }, () => location.reload());
            }
        });
    });

    // Modale Affectation
    function openAffModal() {
        $('#affectationModal').modal('show');
    }

    $('#btn-nouvelle-affectation, #btn-nouvelle-affectation-2').on('click', openAffModal);

    $(document).on('employe:selected', function(e, emp) {
        if (!$('#affectationModal').hasClass('show')) return;

        $('#affectationModal .aff-type-card').removeClass('selected');
        $('#affectationModal .aff-type-card[data-value="EMPLOYE"]').addClass('selected')
            .find('input[type="radio"]').prop('checked', true);

        $('#emp-summary-nom').text(emp.nom ?? '—');
        $('#emp-summary-matricule').text(emp.matricule ?? '—');
        $('#emp-summary-poste').text(emp.poste ?? '—');
        $('#emp-summary-rattachement').text(emp.rattachement ?? '—');

        $('#affectationModal #dossier_employe_id').val(emp.id);
        $('#affectationModal #poste_travail_id, #affectationModal #local_id').val('');
        $('#affectationModal .aff-summary').addClass('d-none');
        $('#affectationModal #aff-employe-summary').removeClass('d-none');
    });

    $(document).on('poste:selected', function(e, poste) {
        if (!$('#affectationModal').hasClass('show')) return;

        $('#affectationModal .aff-type-card').removeClass('selected');
        $('#affectationModal .aff-type-card[data-value="POSTE"]').addClass('selected')
            .find('input[type="radio"]').prop('checked', true);

        $('#poste-summary-code').text(poste.code ?? '—');
        $('#poste-summary-libelle').text(poste.libelle ?? '—');
        $('#poste-summary-emplacement').text(poste.emplacement ?? '—');

        $('#affectationModal #poste_travail_id').val(poste.id);
        $('#affectationModal #dossier_employe_id, #affectationModal #local_id').val('');
        $('#affectationModal .aff-summary').addClass('d-none');
        $('#affectationModal #aff-poste-summary').removeClass('d-none');
    });

    $(document).on('local:selected', function(e, local) {
        if (!$('#affectationModal').hasClass('show')) return;

        $('#affectationModal .aff-type-card').removeClass('selected');
        $('#affectationModal .aff-type-card[data-value="LOCAL"]').addClass('selected')
            .find('input[type="radio"]').prop('checked', true);

        $('#local-summary-code').text(local.code ?? '—');
        $('#local-summary-libelle').text(local.libelle ?? '—');
        $('#local-summary-type').text(local.type ?? '—');
        $('#local-summary-etage').text(local.etage ?? '—');
        $('#local-summary-batiment').text(local.batiment ?? '—');

        $('#affectationModal #local_id').val(local.id);
        $('#affectationModal #dossier_employe_id, #affectationModal #poste_travail_id').val('');
        $('#affectationModal .aff-summary').addClass('d-none');
        $('#affectationModal #aff-local-summary').removeClass('d-none');
    });

    $('#affectationForm').on('submit', function(e){
        e.preventDefault();

        const typeCible = $('#affectationForm input[name="type_cible"]:checked').val();
        if (!typeCible) {
            Swal.fire({
                icon: 'warning',
                title: 'Attention',
                text: 'Veuillez sélectionner un type d\'affectation.',
                timer: 2500,
                showConfirmButton: false
            });
            return;
        }

        const $btn = $('#btn-save-affectation').prop('disabled', true)
            .html('<i class="bi bi-hourglass-split me-1"></i> Enregistrement...');

        $.post(route('parc-info.onduleurs.store-affectation'), $(this).serialize(), (res) => {
            if (res.success) {
                $('#affectationModal').modal('hide');
                Swal.fire({ icon: 'success', title: 'Affectation enregistrée', timer: 2000, showConfirmButton: false })
                    .then(() => location.reload());
            }
        }).fail((xhr) => {
            const msg = xhr.responseJSON?.errors
                ? Object.values(xhr.responseJSON.errors).flat().join('\n')
                : (xhr.responseJSON?.message ?? 'Erreur serveur');
            Swal.fire('Erreur', msg, 'error');
        }).always(() => {
            $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Enregistrer l\'affectation');
        });
    });

    $('#affectationModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
        $('#affectationModal .aff-type-card').removeClass('selected');
        $('#affectationModal .aff-summary').addClass('d-none');
        $('#affectationModal #dossier_employe_id, #affectationModal #poste_travail_id, #affectationModal #local_id').val('');
    });
});
</script>
@endpush
