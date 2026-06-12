@extends('parcinfo::layouts.master')

@section('header', $consommable->nom)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.consommables.index') }}">Consommables</a></li>
    <li class="breadcrumb-item active">{{ $consommable->code }}</li>
@endsection

@section('content')

{{-- ── HEADER CARD ── --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px">
    <div class="card-body p-4">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <div class="rounded-3 d-flex align-items-center justify-content-center bg-primary bg-opacity-10"
                     style="width:72px;height:72px">
                    <i class="bi bi-box-seam fs-2 text-primary"></i>
                </div>
            </div>
            <div class="col">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h4 class="fw-bold mb-0" id="header-nom">{{ $consommable->nom }}</h4>
                    <span id="badge-status" class="badge bg-{{ $consommable->est_actif ? 'success' : 'danger' }}-subtle text-{{ $consommable->est_actif ? 'success' : 'danger' }} border border-{{ $consommable->est_actif ? 'success' : 'danger' }}-subtle px-2 py-1">
                        {{ $consommable->est_actif ? 'Actif' : 'Inactif' }}
                    </span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" id="header-badge-type">
                        {{ $consommable->typeConsommable->nom }}
                    </span>
                    @php
                        $stockBadgeColors = [
                            'RUPTURE' => 'danger',
                            'ALERTE' => 'warning',
                            'SURSTOCK' => 'info',
                            'NORMAL' => 'success',
                        ];
                        $sbc = $stockBadgeColors[$consommable->statut_stock] ?? 'secondary';
                    @endphp
                    <span id="badge-stock-status" class="badge bg-{{ $sbc }}-subtle text-{{ $sbc }} border border-{{ $sbc }}-subtle px-2 py-1">
                        Stock: {{ $consommable->statut_stock }}
                    </span>
                </div>
                <div class="d-flex gap-4 flex-wrap text-muted small">
                    <span><i class="bi bi-barcode me-1"></i>Code: <span id="header-code" class="fw-semibold">{{ $consommable->code }}</span></span>
                    <span><i class="bi bi-archive me-1"></i>Stock: <span id="header-stock" class="fw-semibold">{{ $consommable->quantite_stock_actuel }} {{ $consommable->typeConsommable->unite_stock }}s</span></span>
                    <span><i class="bi bi-currency-euro me-1"></i>Valeur: <span id="header-valeur" class="fw-semibold text-success">{{ number_format($consommable->valeur_stock, 2, ',', ' ') }} €</span></span>
                    <span><i class="bi bi-building me-1"></i>Fournisseur: <span id="header-fournisseur" class="fw-semibold">{{ $consommable->fournisseur->nom }}</span></span>
                </div>
            </div>
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-outline-success btn-sm" id="btn-open-appro">
                    <i class="bi bi-plus-circle me-1"></i> Approvisionner
                </button>
                <button class="btn btn-outline-primary btn-sm" id="btn-open-consommer">
                    <i class="bi bi-minus-circle me-1"></i> Sortie Stock
                </button>
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
        ['fiche',      'bi-info-circle', 'Fiche Info'],
        ['mouvements', 'bi-clock-history', 'Historique Mouvements'],
        ['affectations','bi-arrow-left-right', 'Affectations'],
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
                    <h6 class="section-title mb-4"><span class="section-num">01</span> Identification & Description</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="field-label">Code Article <span class="text-danger">*</span></label>
                            <input type="text" class="form-control field-input" name="code"
                                   value="{{ $consommable->code }}" id="f_code" disabled required>
                        </div>
                        <div class="col-md-8">
                            <label class="field-label">Désignation <span class="text-danger">*</span></label>
                            <input type="text" class="form-control field-input" name="nom"
                                   value="{{ $consommable->nom }}" id="f_nom" disabled required>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Type de consommable <span class="text-danger">*</span></label>
                            <select class="form-select field-input" name="type_consommable_id" id="f_type_consommable_id" disabled required>
                                @foreach($types as $t)
                                    <option value="{{ $t->id }}" {{ $consommable->type_consommable_id == $t->id ? 'selected' : '' }}>{{ $t->nom }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Marque</label>
                            <select class="form-select field-input" name="marque_id" id="f_marque_id" disabled>
                                <option value="">Générique</option>
                                @foreach($marques as $m)
                                    <option value="{{ $m->id }}" {{ $consommable->marque_id == $m->id ? 'selected' : '' }}>{{ $m->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Modèle / Référence</label>
                            <input type="text" class="form-control field-input" name="modele_reference"
                                   value="{{ $consommable->modele_reference }}" id="f_modele_reference" disabled placeholder="—">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 02 — Seuils & Tarifs --}}
            <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
                <div class="card-body p-4">
                    <h6 class="section-title mb-4"><span class="section-num">02</span> Gestion des Seuils & Tarifs</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="field-label">Stock Minimum <span class="text-danger">*</span></label>
                            <input type="number" class="form-control field-input" name="quantite_stock_min"
                                   value="{{ $consommable->quantite_stock_min }}" id="f_quantite_stock_min" disabled required min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="field-label">Stock Maximum <span class="text-danger">*</span></label>
                            <input type="number" class="form-control field-input" name="quantite_stock_max"
                                   value="{{ $consommable->quantite_stock_max }}" id="f_quantite_stock_max" disabled required min="1">
                        </div>
                        <div class="col-md-3">
                            <label class="field-label">Coût Unitaire (€) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control field-input" name="cout_unitaire"
                                   value="{{ $consommable->cout_unitaire }}" id="f_cout_unitaire" disabled required step="0.01" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="field-label">Fournisseur Principal <span class="text-danger">*</span></label>
                            <select class="form-select field-input" name="fournisseur_principal_id" id="f_fournisseur_principal_id" disabled required>
                                @foreach($fournisseurs as $f)
                                    <option value="{{ $f->id }}" {{ $consommable->fournisseur_principal_id == $f->id ? 'selected' : '' }}>{{ $f->nom }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 03 — Notes --}}
            <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
                <div class="card-body p-4">
                    <h6 class="section-title mb-4"><span class="section-num">03</span> Notes & Description</h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="field-label">Notes, compatibilités et instructions</label>
                            <textarea class="form-control field-input" name="notes" id="f_notes" rows="4" disabled placeholder="Indiquez ici les détails de compatibilité matérielle, etc...">{{ $consommable->notes }}</textarea>
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

    {{-- ══ TAB 2 : HISTORIQUE MOUVEMENTS ══ --}}
    <div class="tab-pane fade" id="pane-mouvements" role="tabpanel">
        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4 py-3 small fw-bold text-uppercase text-muted">Date</th>
                                <th class="py-3 small fw-bold text-uppercase text-muted">Type mouvement</th>
                                <th class="py-3 small fw-bold text-uppercase text-muted">Quantité</th>
                                <th class="py-3 small fw-bold text-uppercase text-muted">Utilisateur</th>
                                <th class="py-3 small fw-bold text-uppercase text-muted">Cible / Détails</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($consommable->mouvementsStock->sortByDesc('date_mouvement') as $mvt)
                            <tr>
                                <td class="px-4 small">
                                    {{ $mvt->date_mouvement->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    @if($mvt->type_mouvement === 'Achat')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Approvisionnement</span>
                                    @else
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">Consommation</span>
                                    @endif
                                </td>
                                <td class="fw-bold">
                                    <span class="{{ $mvt->type_mouvement === 'Achat' ? 'text-success' : 'text-danger' }}">
                                        {{ $mvt->type_mouvement === 'Achat' ? '+' : '-' }}{{ $mvt->quantite }}
                                    </span>
                                </td>
                                <td class="small">
                                    {{ $mvt->utilisateur->name }}
                                </td>
                                <td class="small">
                                    @if($mvt->equipement)
                                        <span class="fw-semibold text-primary" title="Équipement"><i class="bi bi-pc-display me-1"></i>{{ $mvt->equipement->code }}</span>
                                        <span class="text-muted">({{ $mvt->equipement->modele }})</span>
                                    @elseif($mvt->employe)
                                        <span class="fw-semibold text-secondary" title="Employé"><i class="bi bi-person-badge me-1"></i>{{ $mvt->employe->nom }} {{ $mvt->employe->prenom }}</span>
                                    @elseif($mvt->service)
                                        <span class="fw-semibold text-info" title="Service"><i class="bi bi-building me-1"></i>{{ $mvt->service->libelle }}</span>
                                    @elseif($mvt->unite)
                                        <span class="fw-semibold text-dark" title="Unité"><i class="bi bi-door-open me-1"></i>{{ $mvt->unite->libelle }}</span>
                                    @else
                                        <span class="text-muted">— (Générique)</span>
                                    @endif
                                    @if($mvt->raison || $mvt->reference_commande)
                                        <div class="text-muted small mt-1">{{ $mvt->raison ?: "Commande Ref: {$mvt->reference_commande}" }}</div>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-clock-history fs-1 opacity-25 d-block mb-2"></i>
                                    Aucun mouvement de stock enregistré.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ TAB 3 : AFFECTATIONS ══ --}}
    <div class="tab-pane fade" id="pane-affectations" role="tabpanel">
        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4 py-3 small fw-bold text-uppercase text-muted">Équipement cible</th>
                                <th class="py-3 small fw-bold text-uppercase text-muted">Quantité</th>
                                <th class="py-3 small fw-bold text-uppercase text-muted">Date affectation</th>
                                <th class="py-3 small fw-bold text-uppercase text-muted">Prochain remplacement</th>
                                <th class="py-3 small fw-bold text-uppercase text-muted">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($consommable->affectations->sortByDesc('id') as $aff)
                            <tr>
                                <td class="px-4">
                                    @if($aff->equipement)
                                        <a href="{{ route('parc-info.ordinateurs.show', $aff->equipement->id) }}" class="fw-bold text-primary text-decoration-none">
                                            <i class="bi bi-pc-display me-1"></i>{{ $aff->equipement->code }}
                                        </a>
                                        <span class="text-muted small">({{ $aff->equipement->modele }})</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="fw-bold">{{ $aff->quantite_fournie }}</td>
                                <td class="small">{{ $aff->date_affectation->format('d/m/Y') }}</td>
                                <td class="small">
                                    @if($aff->date_remplacement_prochain_prevu)
                                        @php
                                            $diff = now()->diffInDays($aff->date_remplacement_prochain_prevu, false);
                                            $class = $diff < 0 ? 'text-danger fw-bold' : ($diff <= 7 ? 'text-warning fw-bold' : 'text-success');
                                        @endphp
                                        <span class="{{ $class }}">
                                            {{ $aff->date_remplacement_prochain_prevu->format('d/m/Y') }}
                                            @if($diff < 0)
                                                (En retard)
                                            @elseif($diff == 0)
                                                (Aujourd'hui)
                                            @else
                                                (dans {{ $diff }} j.)
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $aff->notes ?: '—' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-person-x fs-1 opacity-25 d-block mb-2"></i>
                                    Aucune affectation active pour cet article.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

@include('parcinfo::informatique.consommables._modal_mouvements')
@include('parcinfo::shared._modal_selection_equipement')
@include('parcinfo::informatique.ordinateurs._selection_modals')

@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/select2/css/select2-bootstrap-5-theme.min.css') }}">
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
    const consommableId = {{ $consommable->id }};
    const csrfToken = '{{ csrf_token() }}';
</script>
<script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
<script src="{{ asset('js/modules/parc-info/ordinateurs/selection_modals.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modules/parc-info/consommables/show.js') }}?v={{ time() }}"></script>
@endpush
