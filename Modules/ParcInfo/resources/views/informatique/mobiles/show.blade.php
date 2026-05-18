@extends('parcinfo::layouts.master')

@section('header', $equipement->code_inventaire)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.mobiles.index') }}">Mobiles & Tablettes</a></li>
    <li class="breadcrumb-item active">{{ $equipement->code_inventaire }}</li>
@endsection

@php
    $m   = $equipement->mobile;
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
                    <i class="bi bi-phone-vibrate fs-2 text-primary"></i>
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
                    @if($m?->imei_1)
                        <span><i class="bi bi-sim me-1"></i>IMEI: {{ $m->imei_1 }}</span>
                    @endif
                    @if($m?->num_tel_associe)
                        <span><i class="bi bi-telephone me-1"></i>{{ $m->num_tel_associe }}</span>
                    @endif
                </div>
            </div>
            <div class="col-auto d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline-primary btn-sm dropdown-toggle shadow-none" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-arrow-repeat me-1"></i> Statut
                    </button>
                    <ul class="dropdown-menu shadow border-0">
                        <li><a class="dropdown-item" href="#" data-statut="en_stock">En stock</a></li>
                        <li><a class="dropdown-item" href="#" data-statut="en_service">En service</a></li>
                        <li><a class="dropdown-item" href="#" data-statut="en_reparation">En réparation</a></li>
                        <li><a class="dropdown-item" href="#" data-statut="perdu">Perdu / Volé</a></li>
                        <li><a class="dropdown-item" href="#" data-statut="reforme">Réformé</a></li>
                    </ul>
                </div>
                @if($equipement->affectationActive)
                <button class="btn btn-warning btn-sm shadow-none" id="btn-desaffecter">
                    <i class="bi bi-x-circle me-1"></i> Désaffecter
                </button>
                @endif
                <button class="btn btn-outline-secondary btn-sm shadow-none" id="btn-nouvelle-affectation">
                    <i class="bi bi-person-plus me-1"></i> Affecter
                </button>
                <button class="btn btn-primary btn-sm shadow-none px-3" id="btn-edit-toggle">
                    <i class="bi bi-pencil me-1"></i> Modifier
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── TABS ── --}}
<ul class="nav nav-tabs border-0 mb-3" id="showTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active fw-semibold small px-4" id="tab-fiche" data-bs-toggle="tab" data-bs-target="#pane-fiche" type="button">
            <i class="bi bi-info-circle me-1"></i> Fiche Technique
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold small px-4" id="tab-affectation" data-bs-toggle="tab" data-bs-target="#pane-affectation" type="button">
            <i class="bi bi-person-badge me-1"></i> Affectation Actuelle
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold small px-4" id="tab-historique-aff" data-bs-toggle="tab" data-bs-target="#pane-historique-aff" type="button">
            <i class="bi bi-clock-history me-1"></i> Historique Affectations
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold small px-4" id="tab-historique-chg" data-bs-toggle="tab" data-bs-target="#pane-historique-chg" type="button">
            <i class="bi bi-journal-text me-1"></i> Journal des Changements
        </button>
    </li>
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
                <h6 class="section-title mb-4"><span class="section-num">01</span> Identification & Acquisition</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="field-label">Code Inventaire</label>
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
                        <select class="form-select field-input" name="marque_id" id="f_marque_id" disabled>
                            <option value="">—</option>
                            @foreach($marques as $mq)
                            <option value="{{ $mq->id }}" {{ $equipement->marque_id == $mq->id ? 'selected':'' }}>{{ $mq->libelle }}</option>
                            @endforeach
                        </select>
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

        {{-- Section 02 — Caractéristiques Techniques --}}
        <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="section-title mb-0"><span class="section-num">02</span> Caractéristiques Techniques</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary d-none btn-add-ref shadow-none" id="btn-add-type-mobile-show"><i class="bi bi-plus"></i></button>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="field-label">Type de Mobile</label>
                        <select class="form-select field-input" name="type_mobile_id" id="f_type_mobile_id" disabled>
                            <option value="">—</option>
                            @foreach($typesMobiles as $tm)
                            <option value="{{ $tm->id }}" {{ $m?->type_mobile_id == $tm->id ? 'selected':'' }}>{{ $tm->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">IMEI 1</label>
                        <input type="text" class="form-control field-input" name="imei_1" value="{{ $m?->imei_1 }}" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">IMEI 2</label>
                        <input type="text" class="form-control field-input" name="imei_2" value="{{ $m?->imei_2 }}" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">N° Tél associé</label>
                        <input type="text" class="form-control field-input" name="num_tel_associe" value="{{ $m?->num_tel_associe }}" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Version OS</label>
                        <input type="text" class="form-control field-input" name="version_os" value="{{ $m?->version_os }}" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Statut MDM</label>
                        <input type="text" class="form-control field-input" name="statut_mdm" value="{{ $m?->statut_mdm }}" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Batterie (mAh)</label>
                        <input type="number" class="form-control field-input" name="capacite_batterie_mah" value="{{ $m?->capacite_batterie_mah }}" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">État de l'écran</label>
                        <select class="form-select field-input" name="etat_ecran" disabled>
                            @foreach(['Intact','Rayé','Fissuré'] as $ee)
                            <option value="{{ $ee }}" {{ $m?->etat_ecran === $ee ? 'selected':'' }}>{{ $ee }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="a_coque_protection" id="f_coque"
                                   value="1" {{ $m?->a_coque_protection ? 'checked':'' }} disabled>
                            <label class="form-check-label small fw-bold" for="f_coque">Coque / Verre trempé</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="d-none" id="fiche-actions">
            <div class="d-flex justify-content-end gap-2 p-3">
                <button type="button" class="btn btn-link text-dark text-decoration-none fw-medium shadow-none" id="btn-cancel-edit">Annuler</button>
                <button type="submit" class="btn btn-primary px-4 shadow-none" id="btn-save-fiche">
                    <i class="bi bi-floppy me-1"></i> Enregistrer les modifications
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
                <p class="text-muted fw-semibold">Aucune affectation active pour cet équipement</p>
                <button class="btn btn-primary btn-sm shadow-none px-3" id="btn-nouvelle-affectation-2">
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
                            <th class="py-3 small fw-bold text-uppercase text-muted">Période</th>
                            <th class="py-3 small fw-bold text-uppercase text-muted">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($equipement->affectations->sortByDesc('id') as $a)
                        <tr>
                            <td class="px-4">
                                <span class="badge bg-light text-dark border small shadow-none">{{ $a->type_affectation ?? '—' }}</span>
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
                                {{ $a->direction?->libelle ?? ($a->service?->libelle ?? '—') }}
                            </td>
                            <td class="small">
                                <div><span class="text-muted">Début:</span> {{ $a->date_debut?->format('d/m/Y') }}</div>
                                <div><span class="text-muted">Fin:</span> {{ $a->date_fin?->format('d/m/Y') ?? 'En cours' }}</div>
                            </td>
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
                Aucun historique d'affectation disponible.
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
                            @if($h->utilisateur)
                            <span class="text-muted small ms-auto"><i class="bi bi-person me-1"></i>{{ $h->utilisateur->name }}</span>
                            @endif
                        </div>
                        @if($h->ancien_statut || $h->nouveau_statut)
                        <div class="small mb-1">
                            <span class="text-muted">{{ $h->ancien_statut }}</span>
                            <i class="bi bi-arrow-right mx-1 text-muted"></i>
                            <span class="fw-semibold">{{ $h->nouveau_statut }}</span>
                        </div>
                        @endif
                        <div class="small text-muted">{{ $h->motif }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-journal-text fs-1 opacity-25 d-block mb-2"></i>
                Aucun changement enregistré dans le journal.
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

                    {{-- Résumés --}}
                    <div id="aff-employe-summary" class="aff-summary d-none">
                        <div class="card border-primary bg-primary bg-opacity-10">
                            <div class="card-body p-3">
                                <h6 class="mb-2 fw-bold text-primary"><i class="bi bi-person-badge me-2"></i>Employé sélectionné</h6>
                                <div class="row g-2">
                                    <div class="col-md-6"><small class="text-muted d-block">Nom complet</small><strong id="emp-summary-nom">—</strong></div>
                                    <div class="col-md-3"><small class="text-muted d-block">Matricule</small><strong id="emp-summary-matricule">—</strong></div>
                                    <div class="col-md-3"><small class="text-muted d-block">Poste</small><span id="emp-summary-poste">—</span></div>
                                    <div class="col-md-12"><small class="text-muted d-block">Rattachement</small><span id="emp-summary-rattachement">—</span></div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="dossier_employe_id" id="dossier_employe_id">
                    </div>

                    <div id="aff-poste-summary" class="aff-summary d-none">
                        <div class="card border-primary bg-primary bg-opacity-10">
                            <div class="card-body p-3">
                                <h6 class="mb-2 fw-bold text-primary"><i class="bi bi-pc-display me-2"></i>Poste sélectionné</h6>
                                <div class="row g-2">
                                    <div class="col-md-3"><small class="text-muted d-block">Code</small><strong id="poste-summary-code">—</strong></div>
                                    <div class="col-md-5"><small class="text-muted d-block">Libellé</small><strong id="poste-summary-libelle">—</strong></div>
                                    <div class="col-md-4"><small class="text-muted d-block">Emplacement</small><span id="poste-summary-emplacement">—</span></div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="poste_travail_id" id="poste_travail_id">
                    </div>

                    <div id="aff-local-summary" class="aff-summary d-none">
                        <div class="card border-primary bg-primary bg-opacity-10">
                            <div class="card-body p-3">
                                <h6 class="mb-2 fw-bold text-primary"><i class="bi bi-door-open me-2"></i>Local sélectionné</h6>
                                <div class="row g-2">
                                    <div class="col-md-3"><small class="text-muted d-block">Code</small><strong id="local-summary-code">—</strong></div>
                                    <div class="col-md-4"><small class="text-muted d-block">Libellé</small><strong id="local-summary-libelle">—</strong></div>
                                    <div class="col-md-5"><small class="text-muted d-block">Emplacement</small><span id="local-summary-complet">—</span></div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="local_id" id="local_id">
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-link text-dark text-decoration-none fw-medium shadow-none" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-none" id="btn-save-affectation">
                        <i class="bi bi-check-circle me-1"></i> Enregistrer l'affectation
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
.info-block    { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:.75rem 1rem; }
.info-label    { font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:#94a3b8; margin-bottom:2px; }
.info-value    { font-size:.875rem; font-weight:600; color:#1e293b; }
.aff-type-card { transition:all .2s ease-in-out; border:1.5px solid #dee2e6 !important; }
.aff-type-card:hover { border-color:#0d6efd !important; transform:translateY(-2px); }
.aff-type-card.selected { border-color:#0d6efd !important; background:#f0f6ff; box-shadow:0 4px 12px rgba(13,110,253,.08); }
.aff-type-card.selected .aff-type-icon { background:#dbeafe !important; }
.aff-type-card.selected .aff-type-icon i { color:#0d6efd !important; }
.aff-type-card.selected .check-icon { display:inline !important; }
.timeline { border-left:2px solid #e2e8f0; padding-left:1.5rem; }
.timeline-dot { position:relative; left:-1.65rem; }
.nav-tabs .nav-link { border:none; border-bottom:2px solid transparent; color:#64748b; transition:all .2s; }
.nav-tabs .nav-link.active { color:#0d6efd; border-bottom-color:#0d6efd; background:transparent; }
.nav-tabs .nav-link:hover { color:#0d6efd; }
</style>
@endpush

@push('js')
<script>
const equipementId = {{ $equipement->id }};
const csrfToken = '{{ csrf_token() }}';

$(function () {
    // ── Mode édition ──────────────────────────────────────────────────────────
    let editMode = false;

    function setEditMode(on) {
        editMode = on;
        $('#pane-fiche .field-input').not("#f_code_inventaire").prop('disabled', !on);
        $('#pane-fiche input[type="checkbox"], #pane-fiche input[type="radio"]').prop('disabled', !on);
        $('#fiche-actions').toggleClass('d-none', !on);
        $('.btn-add-ref').toggleClass('d-none', !on);
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
            url   : route('parc-info.mobiles.update', equipementId),
            method: 'PUT',
            data  : $(this).serialize(),
            success: (res) => {
                if (res.success) {
                    setEditMode(false);
                    Swal.fire({ icon:'success', title:'Enregistré', text: res.message, timer:2000, showConfirmButton:false }).then(() => location.reload());
                }
            },
            error: (xhr) => {
                if (xhr.status === 422) {
                    $('.is-invalid').removeClass('is-invalid');
                    $('.invalid-feedback').remove();
                    Object.entries(xhr.responseJSON.errors ?? {}).forEach(([f, msgs]) => {
                        const input = $(`[name="${f}"]`);
                        input.addClass('is-invalid').after(`<div class="invalid-feedback">${msgs[0]}</div>`);
                    });
                } else {
                    Swal.fire('Erreur', xhr.responseJSON?.message ?? 'Erreur serveur', 'error');
                }
            },
            complete: () => $btn.prop('disabled', false).html('<i class="bi bi-floppy me-1"></i> Enregistrer les modifications'),
        });
    });

    // ── Modale affectation ────────────────────────────────────────────────────
    $('#btn-nouvelle-affectation, #btn-nouvelle-affectation-2').on('click', () => $('#affectationModal').modal('show'));

    $(document).on('employe:selected', function (e, emp) {
        if (!$('#affectationModal').hasClass('show')) return;
        $('#affectationModal .aff-type-card').removeClass('selected');
        $('#affectationModal .aff-type-card[data-value="EMPLOYE"]').addClass('selected')
            .find('input[type="radio"]').prop('checked', true);
        $('#emp-summary-nom').text(emp.nom);
        $('#emp-summary-matricule').text(emp.matricule);
        $('#emp-summary-poste').text(emp.poste);
        $('#emp-summary-rattachement').text(emp.rattachement);
        $('#affectationModal #dossier_employe_id').val(emp.id);
        $('#affectationModal #poste_travail_id, #affectationModal #local_id').val('');
        $('#affectationModal .aff-summary').addClass('d-none');
        $('#affectationModal #aff-employe-summary').removeClass('d-none');
    });

    $(document).on('poste:selected', function (e, poste) {
        if (!$('#affectationModal').hasClass('show')) return;
        $('#affectationModal .aff-type-card').removeClass('selected');
        $('#affectationModal .aff-type-card[data-value="POSTE"]').addClass('selected')
            .find('input[type="radio"]').prop('checked', true);
        $('#poste-summary-code').text(poste.code);
        $('#poste-summary-libelle').text(poste.libelle);
        $('#poste-summary-emplacement').text(poste.emplacement);
        $('#affectationModal #poste_travail_id').val(poste.id);
        $('#affectationModal #dossier_employe_id, #affectationModal #local_id').val('');
        $('#affectationModal .aff-summary').addClass('d-none');
        $('#affectationModal #aff-poste-summary').removeClass('d-none');
    });

    $(document).on('local:selected', function (e, local) {
        if (!$('#affectationModal').hasClass('show')) return;
        $('#affectationModal .aff-type-card').removeClass('selected');
        $('#affectationModal .aff-type-card[data-value="LOCAL"]').addClass('selected')
            .find('input[type="radio"]').prop('checked', true);
        $('#local-summary-code').text(local.code);
        $('#local-summary-libelle').text(local.libelle);
        $('#local-summary-complet').text(local.text);
        $('#affectationModal #local_id').val(local.id);
        $('#affectationModal #dossier_employe_id, #affectationModal #poste_travail_id').val('');
        $('#affectationModal .aff-summary').addClass('d-none');
        $('#affectationModal #aff-local-summary').removeClass('d-none');
    });

    $('#affectationForm').on('submit', function (e) {
        e.preventDefault();
        const hasCible = $(this).find('input[name="type_cible"]:checked').val();
        if (!hasCible) {
            Swal.fire({ icon: 'warning', title: 'Attention', text: 'Veuillez sélectionner un type d\'affectation.', timer: 2500, showConfirmButton: false });
            return;
        }

        const $btn = $('#btn-save-affectation').prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> Enregistrement...');
        const data = $(this).serialize() + `&equipement_id=${equipementId}`;
        $.post(route('parc-info.mobiles.store-affectation'), data, (res) => {
            if (res.success) {
                $('#affectationModal').modal('hide');
                Swal.fire({ icon: 'success', title: 'Affectation enregistrée', timer: 2000, showConfirmButton: false }).then(() => location.reload());
            }
        }).fail((xhr) => {
            Swal.fire('Erreur', xhr.responseJSON?.message ?? 'Erreur serveur', 'error');
        }).always(() => $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Enregistrer l\'affectation'));
    });

    // ── Changement de statut ──────────────────────────────────────────────────
    $(document).on('click', '.dropdown-item[data-statut]', function(e) {
        e.preventDefault();
        const nouveauStatut = $(this).data('statut');
        Swal.fire({
            title: 'Changer le statut',
            input: 'textarea',
            inputLabel: 'Motif du changement',
            inputPlaceholder: 'Expliquez la raison...',
            showCancelButton: true,
            confirmButtonText: 'Confirmer',
            preConfirm: (motif) => {
                if (!motif) { Swal.showValidationMessage('Le motif est obligatoire'); }
                return motif;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('parc-info.mobiles.update-statut', equipementId),
                    method: 'PATCH',
                    data: { statut: nouveauStatut, motif: result.value, _token: csrfToken },
                    success: (res) => Swal.fire('Succès', res.message, 'success').then(() => location.reload()),
                    error: (xhr) => Swal.fire('Erreur', xhr.responseJSON?.message || 'Une erreur est survenue', 'error')
                });
            }
        });
    });

    // ── Désaffectation ────────────────────────────────────────────────────────
    $('#btn-desaffecter').on('click', function() {
        Swal.fire({
            title: 'Désaffecter l\'équipement',
            text: 'L\'équipement sera remis en stock',
            input: 'textarea',
            inputLabel: 'Motif de la désaffectation',
            showCancelButton: true,
            confirmButtonText: 'Confirmer',
            confirmButtonColor: '#dc3545',
            preConfirm: (motif) => {
                if (!motif) { Swal.showValidationMessage('Le motif est obligatoire'); }
                return motif;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(route('parc-info.mobiles.desaffecter', equipementId), { motif: result.value, _token: csrfToken }, (res) => {
                    Swal.fire('Succès', res.message, 'success').then(() => location.reload());
                }).fail((xhr) => Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur', 'error'));
            }
        });
    });

    // ── Quick Add ─────────────────────────────────────────────────────────────
    $('#btn-add-type-mobile-show').on('click', function() {
        Swal.fire({
            title: 'Nouveau type de mobile',
            input: 'text',
            showCancelButton: true,
            confirmButtonText: 'Ajouter',
            preConfirm: (libelle) => {
                if (!libelle) { Swal.showValidationMessage('Le libelle est requis'); return; }
                return $.post(route('parc-info.mobiles.store-type-mobile'), { libelle, _token: csrfToken });
            }
        }).then(res => {
            if (res.isConfirmed && res.value?.success) {
                const t = res.value.data;
                $('#f_type_mobile_id').append(`<option value="${t.id}" selected>${t.libelle}</option>`).val(t.id);
            }
        });
    });
});
</script>
<script src="{{ asset('js/modules/parc-info/ordinateurs/selection_modals.js') }}?v={{ time() }}"></script>
@endpush
