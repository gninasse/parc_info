@extends('parcinfo::layouts.master')

@section('header', $fournisseur->nom)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.fournisseurs.index') }}">Fournisseurs</a></li>
    <li class="breadcrumb-item active">{{ $fournisseur->nom }}</li>
@endsection

@php
    $typeLabels = [
        'Revendeur'   => 'Revendeur',
        'Editeur'     => 'Éditeur',
        'Distributeur'=> 'Distributeur',
        'Prestataire' => 'Prestataire',
    ];
    $displayType = $typeLabels[$fournisseur->type] ?? $fournisseur->type;
@endphp

@section('content')

{{-- ── HEADER CARD ── --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px">
    <div class="card-body p-4">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <div class="rounded-3 d-flex align-items-center justify-content-center bg-primary bg-opacity-10"
                     style="width:72px;height:72px">
                    <i class="bi bi-building fs-2 text-primary"></i>
                </div>
            </div>
            <div class="col">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h4 class="fw-bold mb-0" id="header-nom">{{ $fournisseur->nom }}</h4>
                    <span id="badge-status" class="badge bg-{{ $fournisseur->est_actif ? 'success' : 'danger' }}-subtle text-{{ $fournisseur->est_actif ? 'success' : 'danger' }} border border-{{ $fournisseur->est_actif ? 'success' : 'danger' }}-subtle px-2 py-1">
                        {{ $fournisseur->est_actif ? 'Actif' : 'Inactif' }}
                    </span>
                    @if($fournisseur->type)
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" id="header-badge-type">
                        {{ $displayType }}
                    </span>
                    @endif
                </div>
                <div class="d-flex gap-4 flex-wrap text-muted small">
                    <span><i class="bi bi-upc me-1"></i><span id="header-code">{{ $fournisseur->code }}</span></span>
                    <span><i class="bi bi-envelope me-1"></i><span id="header-email">{{ $fournisseur->email ?: '—' }}</span></span>
                    <span><i class="bi bi-telephone me-1"></i><span id="header-telephone">{{ $fournisseur->telephone ?: '—' }}</span></span>
                </div>
            </div>
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-outline-warning btn-sm" id="btn-toggle-status">
                    <i class="bi bi-toggle-on me-1"></i> Activer/Désactiver
                </button>
                <button class="btn btn-outline-danger btn-sm" id="btn-delete">
                    <i class="bi bi-trash me-1"></i> Supprimer
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
        ['fiche',    'bi-building',        'Fiche Info'],
        ['contacts', 'bi-people',          'Contacts'],
        ['licences', 'bi-file-lock',       'Licences'],
        ['contrats', 'bi-file-earmark-text','Contrats de Maintenance'],
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

    {{-- ══ TAB 1 : FICHE INFO ══ --}}
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
                            <label class="field-label">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control field-input" name="code"
                                   value="{{ $fournisseur->code }}" id="f_code" disabled required>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control field-input" name="nom"
                                   value="{{ $fournisseur->nom }}" id="f_nom" disabled required>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Type d'entité</label>
                            <select class="form-select field-input" name="type" id="f_type" disabled>
                                <option value="">—</option>
                                @foreach(['Revendeur'=>'Revendeur','Editeur'=>'Éditeur','Distributeur'=>'Distributeur','Prestataire'=>'Prestataire'] as $v=>$l)
                                <option value="{{ $v }}" {{ $fournisseur->type === $v ? 'selected':'' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Score de fiabilité (%)</label>
                            <input type="number" class="form-control field-input" name="fiabilite_score"
                                   value="{{ $fournisseur->fiabilite_score }}" id="f_fiabilite_score" disabled min="0" max="100">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 02 — Coordonnées --}}
            <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
                <div class="card-body p-4">
                    <h6 class="section-title mb-4"><span class="section-num">02</span> Coordonnées</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="field-label">Email principal</label>
                            <input type="email" class="form-control field-input" name="email"
                                   value="{{ $fournisseur->email }}" id="f_email" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label">Téléphone</label>
                            <input type="text" class="form-control field-input" name="telephone"
                                   value="{{ $fournisseur->telephone }}" id="f_telephone" disabled>
                        </div>
                        <div class="col-12">
                            <label class="field-label">Adresse</label>
                            <textarea class="form-control field-input" name="adresse" id="f_adresse" rows="2" disabled>{{ $fournisseur->adresse }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Code Postal</label>
                            <input type="text" class="form-control field-input" name="code_postal"
                                   value="{{ $fournisseur->code_postal }}" id="f_code_postal" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Ville</label>
                            <input type="text" class="form-control field-input" name="ville"
                                   value="{{ $fournisseur->ville }}" id="f_ville" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Pays</label>
                            <input type="text" class="form-control field-input" name="pays"
                                   value="{{ $fournisseur->pays }}" id="f_pays" disabled>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-none justify-content-end gap-2 mb-4" id="fiche-actions">
                <button type="button" class="btn btn-light" id="btn-cancel-edit">Annuler</button>
                <button type="submit" class="btn btn-primary px-4" id="btn-save-fiche">
                    <i class="bi bi-check-circle me-1"></i>Enregistrer
                </button>
            </div>
        </form>
    </div>

    {{-- ══ TAB 2 : CONTACTS ══ --}}
    <div class="tab-pane fade" id="pane-contacts" role="tabpanel">
        @include('parcinfo::informatique.fournisseurs.partials._contacts')
    </div>

    {{-- ══ TAB 3 : LICENCES ══ --}}
    <div class="tab-pane fade" id="pane-licences" role="tabpanel">
        @include('parcinfo::informatique.fournisseurs.partials._licences')
    </div>

    {{-- ══ TAB 4 : CONTRATS ══ --}}
    <div class="tab-pane fade" id="pane-contrats" role="tabpanel">
        @include('parcinfo::informatique.fournisseurs.partials._contrats')
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
.nav-tabs .nav-link { border:none; border-bottom:2px solid transparent; color:#64748b; border-radius:0; }
.nav-tabs .nav-link.active { color:#0d6efd; border-bottom-color:#0d6efd; background:transparent; }
.nav-tabs .nav-link:hover { color:#0d6efd; }
</style>
@endpush

@push('js')
<script>
    const fournisseurId = {{ $fournisseur->id }};
    const csrfToken = '{{ csrf_token() }}';
</script>
<script src="{{ asset('js/modules/parc-info/fournisseurs/show.js') }}?v={{ time() }}"></script>
@endpush
