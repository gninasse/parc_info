@extends('parcinfo::layouts.master')

@section('header', $equipement->code_inventaire)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.brassage.index') }}">Panneaux de Brassage</a></li>
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
                    <i class="bi bi-grid-3x3-gap fs-2 text-primary"></i>
                </div>
            </div>
            <div class="col">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h4 class="fw-bold mb-0">{{ $equipement->marque?->libelle ?? 'Marque inconnue' }} {{ $equipement->modele }}</h4>
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
                    @if($infra?->nb_ports)
                    <span><i class="bi bi-diagram-2 me-1"></i>{{ $infra->nb_ports }} ports</span>
                    @endif
                    @if($infra?->categorie_cable)
                    <span><i class="bi bi-link me-1"></i>{{ $infra->categorie_cable }}</span>
                    @endif
                </div>
            </div>
            <div class="col-auto d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-arrow-repeat me-1"></i> Statut
                    </button>
                    <ul class="dropdown-menu shadow border-0">
                        @foreach(['en_stock'=>'En stock','en_service'=>'En service','en_reparation'=>'En réparation','perdu'=>'Perdu/Volé','reforme'=>'Réformé'] as $k=>$v)
                        <li><a class="dropdown-item py-2" href="#" data-action="update-statut" data-value="{{ $k }}">{{ $v }}</a></li>
                        @endforeach
                    </ul>
                </div>
                @if($aff)
                <button class="btn btn-warning btn-sm" id="btn-desaffecter">
                    <i class="bi bi-x-circle me-1"></i> Désaffecter
                </button>
                @endif
                <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#localSelectionModal" id="btn-nouvelle-affectation">
                    <i class="bi bi-geo-alt me-1"></i> Affecter
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
        ['fiche',      'bi-diagram-3',     'Fiche Technique'],
        ['affectation','bi-geo-alt',       'Affectation Actuelle'],
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
                            <h6 class="mb-0 fw-bold"><i class="bi bi-info-circle text-primary me-2"></i>Identification & Spécifications</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Code Inventaire</label>
                                    <input type="text" class="form-control bg-light" value="{{ $equipement->code_inventaire }}" readonly id="f_code_inventaire">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Numéro de Série <span class="text-danger">*</span></label>
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
                                    <label class="form-label small fw-bold text-muted">Modèle <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="modele" value="{{ $equipement->modele }}" disabled id="f_modele">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Type Infrastructure</label>
                                    <input type="text" class="form-control bg-light" value="{{ $infra->typeInfrastructure?->libelle ?? 'BRASSAGE' }}" disabled id="f_type_infra_id">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Nombre de ports <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="nb_ports" value="{{ $infra->nb_ports }}" min="1" disabled id="f_nb_ports">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Catégorie Câble</label>
                                    <select class="form-select" name="categorie_cable" disabled id="f_categorie_cable">
                                        <option value="" {{ !$infra->categorie_cable ? 'selected' : '' }}>— Non spécifié —</option>
                                        <option value="Cat5e" {{ $infra->categorie_cable == 'Cat5e' ? 'selected' : '' }}>Cat5e</option>
                                        <option value="Cat6" {{ $infra->categorie_cable == 'Cat6' ? 'selected' : '' }}>Cat6</option>
                                        <option value="Cat6A" {{ $infra->categorie_cable == 'Cat6A' ? 'selected' : '' }}>Cat6A</option>
                                        <option value="Fibre" {{ $infra->categorie_cable == 'Fibre' ? 'selected' : '' }}>Fibre Optique</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Type Connecteur</label>
                                    <select class="form-select" name="type_connecteur" disabled id="f_type_connecteur">
                                        <option value="" {{ !$infra->type_connecteur ? 'selected' : '' }}>— Non spécifié —</option>
                                        <option value="RJ45" {{ $infra->type_connecteur == 'RJ45' ? 'selected' : '' }}>RJ45</option>
                                        <option value="LC" {{ $infra->type_connecteur == 'LC' ? 'selected' : '' }}>LC (Fibre)</option>
                                        <option value="SC" {{ $infra->type_connecteur == 'SC' ? 'selected' : '' }}>SC (Fibre)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Taille (U)</label>
                                    <input type="number" class="form-control" name="u_taille" value="{{ $infra->u_taille }}" min="1" disabled id="f_u_taille">
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
                        'LOCAL' => ['icon' => 'bi-door-open', 'label' => 'Local technique', 'color' => 'success'],
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
                    @if($aff->type_cible === 'LOCAL' && $aff->local)
                    <div class="col-md-6"><div class="info-block"><div class="info-label">Local / Salle</div><div class="info-value">{{ $aff->local->libelle }}</div></div></div>
                    <div class="col-md-6"><div class="info-block"><div class="info-label">Emplacement complet</div><div class="info-value">{{ $aff->local->nom_complet }}</div></div></div>
                    <div class="col-md-4"><div class="info-block"><div class="info-label">Code Local</div><div class="info-value text-primary fw-bold">{{ $aff->local->code }}</div></div></div>
                    <div class="col-md-4"><div class="info-block"><div class="info-label">Étage</div><div class="info-value">{{ $aff->local->etage?->libelle ?? '—' }}</div></div></div>
                    <div class="col-md-4"><div class="info-block"><div class="info-label">Bâtiment</div><div class="info-value">{{ $aff->local->etage?->batiment?->libelle ?? '—' }}</div></div></div>
                    @endif
                    <div class="col-md-6"><div class="info-block"><div class="info-label">Date début</div><div class="info-value">{{ $aff->date_debut?->format('d/m/Y') }}</div></div></div>
                    <div class="col-md-6"><div class="info-block"><div class="info-label">Date fin</div><div class="info-value">{{ $aff->date_fin?->format('d/m/Y') ?? 'Indéterminée' }}</div></div></div>
                </div>
                @else
                <div class="text-center py-5">
                    <div class="mb-3"><i class="bi bi-box fs-1 text-muted opacity-50"></i></div>
                    <p class="text-muted fw-semibold">L'équipement est actuellement en stock</p>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#localSelectionModal" id="btn-nouvelle-affectation-2">
                        <i class="bi bi-geo-alt me-1"></i> Créer une affectation
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
                            <th class="text-center">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($equipement->affectations->sortByDesc('date_debut') as $a)
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-door-open text-muted"></i>
                                    <div>
                                        <div class="fw-bold small">{{ $a->local?->nom_complet ?? '—' }}</div>
                                        <div class="text-muted" style="font-size:.7rem">LOCAL</div>
                                    </div>
                                </div>
                            </td>
                            <td class="small">
                                {{ $a->date_debut?->format('d/m/Y') }} → {{ $a->date_fin?->format('d/m/Y') ?? 'Aujourd\'hui' }}
                            </td>
                            <td><span class="badge bg-light text-dark border small">{{ $a->type_affectation }}</span></td>
                            <td class="text-center">
                                @if($a->statut)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">ACTIF</span>
                                @else
                                    <span class="badge bg-light text-muted border">CLOS</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-4 text-muted small">Aucun historique d'affectation</td></tr>
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
                    @forelse($equipement->historique->sortByDesc('date_changement') as $h)
                    <div class="d-flex gap-3 mb-4">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-{{ match($h->type_changement){'STATUT'=>'primary','ETAT'=>'warning','AFFECTATION'=>'info','TECHNIQUE'=>'dark', default=>'secondary'} }} bg-opacity-10 d-flex align-items-center justify-content-center" style="width:40px;height:40px">
                                <i class="bi bi-{{ match($h->type_changement){'STATUT'=>'arrow-repeat','ETAT'=>'activity','AFFECTATION'=>'person-plus','TECHNIQUE'=>'gear', default=>'circle'} }} text-{{ match($h->type_changement){'STATUT'=>'primary','ETAT'=>'warning','AFFECTATION'=>'info','TECHNIQUE'=>'dark', default=>'secondary'} }}"></i>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h6 class="mb-0 fw-bold">{{ $h->type_changement }}</h6>
                                <small class="text-muted">{{ $h->date_changement->diffForHumans() }} ({{ $h->date_changement->format('d/m/Y H:i') }})</small>
                            </div>
                            <p class="small mb-1 text-dark">{{ $h->motif }}</p>
                            @if($h->ancien_statut || $h->nouveau_statut || $h->ancien_etat || $h->nouvel_etat)
                                <div class="small">
                                    <span class="text-muted">{{ $h->ancien_statut ?? $h->ancien_etat ?? '—' }}</span>
                                    <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                    <span class="fw-bold">{{ $h->nouveau_statut ?? $h->nouvel_etat }}</span>
                                </div>
                            @endif
                            @if($h->utilisateur)
                                <div class="text-muted" style="font-size:0.7rem"><i class="bi bi-person me-1"></i>{{ $h->utilisateur->name }}</div>
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
.timeline { border-left: 2px solid #e9ecef; margin-left: 20px; padding-left: 20px; position: relative; }
.timeline > div { position: relative; }
.timeline > div .flex-shrink-0 { position: absolute; left: -40px; top: 0; background: white; padding: 2px; }
</style>
@endpush

@push('js')
<script type="module" src="{{ asset('js/modules/parc-info/brassage/index.js') }}?v={{ time() }}"></script>
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
        $('.is-invalid').removeClass('is-invalid');
        $.ajax({
            url: route('parc-info.brassage.update', '{{ $equipement->id }}'),
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
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};
                    Object.keys(errors).forEach(key => {
                        $(`#f_${key}`).addClass('is-invalid');
                    });
                    Swal.fire('Erreur', 'Veuillez vérifier les champs en rouge.', 'error');
                } else {
                    Swal.fire('Erreur', xhr.responseJSON?.message ?? 'Une erreur est survenue.', 'error');
                }
            }
        });
    });

    // Mise à jour Statut (Header)
    $('[data-action="update-statut"]').on('click', function(e){
        e.preventDefault();
        const statut = $(this).data('value');
        if (statut === '{{ $equipement->statut }}') return;
        
        Swal.fire({
            title: 'Changer le statut',
            text: 'Veuillez saisir le motif du changement :',
            input: 'textarea',
            inputPlaceholder: 'Ex: Panne, Remplacement, Stockage...',
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
                    url: route('parc-info.brassage.update-statut', '{{ $equipement->id }}'),
                    method: 'PATCH',
                    data: { _token: '{{ csrf_token() }}', statut: statut, motif: result.value },
                    success: () => location.reload(),
                    error: (xhr) => Swal.fire('Erreur', xhr.responseJSON?.message || 'Impossible de mettre à jour le statut.', 'error')
                });
            }
        });
    });

    // Désaffecter
    $('#btn-desaffecter').on('click', function(){
        Swal.fire({
            title: 'Désaffecter le panneau ?',
            text: 'L\'équipement sera remis en stock. Indiquez le motif :',
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
                $.post(route('parc-info.brassage.desaffecter', '{{ $equipement->id }}'), {
                    _token: '{{ csrf_token() }}', motif: result.value
                }, () => location.reload()).fail((xhr) => {
                    Swal.fire('Erreur', xhr.responseJSON?.message || 'Une erreur est survenue.', 'error');
                });
            }
        });
    });

    // Affectation directe
    $(document).on('local:selected', function(e, local) {
        if (!local || !local.id) return;
        
        // On s'assure que la modale de sélection de local est bien fermée
        $('#localSelectionModal').modal('hide');

        Swal.fire({
            title: 'Enregistrement...',
            text: 'Veuillez patienter',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.post(route('parc-info.brassage.store-affectation'), {
            _token: '{{ csrf_token() }}',
            equipement_id: '{{ $equipement->id }}',
            type_cible: 'LOCAL',
            local_id: local.id
        }, (res) => {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'Affectation enregistrée', timer: 2000, showConfirmButton: false })
                    .then(() => location.reload());
            }
        }).fail((xhr) => {
            const msg = xhr.responseJSON?.errors
                ? Object.values(xhr.responseJSON.errors).flat().join('\n')
                : (xhr.responseJSON?.message ?? 'Erreur lors de l\'affectation');
            Swal.fire('Erreur', msg, 'error');
        });
    });
});
</script>
@endpush
