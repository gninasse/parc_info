@extends('parcinfo::layouts.master')

@section('header', $equipement->code_inventaire)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.imprimantes.index') }}">Imprimantes</a></li>
    <li class="breadcrumb-item active">{{ $equipement->code_inventaire }}</li>
@endsection

@php
    $imp = $equipement->imprimante;
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
        <div class="d-flex flex-column flex-md-row align-items-center gap-4">
            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:80px;height:80px">
                <i class="bi bi-printer fs-1 text-primary"></i>
            </div>

            <div class="flex-grow-1 text-center text-md-start">
                <h4 class="fw-bold mb-1">{{ $equipement->code_inventaire }}</h4>
                <div class="text-muted mb-2">
                    <span class="badge bg-light text-dark border me-2">{{ $equipement->marque?->libelle ?? 'Marque inconnue' }}</span>
                    <span>Modèle : <strong class="text-dark">{{ $equipement->modele }}</strong></span>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                    <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }} border border-{{ $sc }}-subtle px-3 py-2 rounded-pill"><i class="bi bi-circle-fill me-1" style="font-size:0.6rem"></i>{{ $equipement->statut_label }}</span>
                    <span class="badge bg-{{ $ec }}-subtle text-{{ $ec }} border border-{{ $ec }}-subtle px-3 py-2 rounded-pill"><i class="bi bi-activity me-1"></i>État: {{ $equipement->etat_label }}</span>
                </div>
            </div>

            <div class="col-auto d-flex gap-2">
                <button class="btn btn-outline-danger btn-sm bg-white" id="btn-desaffecter" {{ !$aff ? 'disabled' : '' }}>
                    <i class="bi bi-x-circle me-1"></i> Désaffecter
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
    <li class="nav-item">
        <button class="nav-link active border-0 fw-semibold pb-3" id="fiche-tab" data-bs-toggle="tab" data-bs-target="#fiche" type="button" role="tab">
            <i class="bi bi-info-circle me-2"></i>Fiche Technique
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link border-0 fw-semibold pb-3" id="affectation-tab" data-bs-toggle="tab" data-bs-target="#affectation" type="button" role="tab">
            <i class="bi bi-geo-alt me-2"></i>Emplacement
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link border-0 fw-semibold pb-3" id="historique-tab" data-bs-toggle="tab" data-bs-target="#historique" type="button" role="tab">
            <i class="bi bi-clock-history me-2"></i>Historique
        </button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="fiche" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <form id="form-update">
                    @csrf
                    @method('PUT')
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Numéro de série</label>
                            <input type="text" class="form-control bg-light view-mode" value="{{ $equipement->numero_serie }}" readonly>
                            <input type="text" class="form-control edit-mode d-none" name="numero_serie" value="{{ $equipement->numero_serie }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Marque</label>
                            <input type="text" class="form-control bg-light view-mode" value="{{ $equipement->marque?->libelle ?? '—' }}" readonly>
                            <select class="form-select edit-mode d-none" name="marque_id">
                                @foreach($marques as $m)
                                    <option value="{{ $m->id }}" {{ $equipement->marque_id == $m->id ? 'selected' : '' }}>{{ $m->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Modèle</label>
                            <input type="text" class="form-control bg-light view-mode" value="{{ $equipement->modele }}" readonly>
                            <input type="text" class="form-control edit-mode d-none" name="modele" value="{{ $equipement->modele }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Technologie</label>
                            <input type="text" class="form-control bg-light view-mode" value="{{ $imp->typeImprimante?->libelle ?? '—' }}" readonly>
                            <select class="form-select edit-mode d-none" name="type_imprimante_id">
                                @foreach($typesImprimantes as $t)
                                    <option value="{{ $t->id }}" {{ $imp->type_imprimante_id == $t->id ? 'selected' : '' }}>{{ $t->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Adresse IP</label>
                            <input type="text" class="form-control bg-light view-mode" value="{{ $imp->adresse_ip ?? '—' }}" readonly>
                            <input type="text" class="form-control edit-mode d-none" name="adresse_ip" value="{{ $imp->adresse_ip }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-muted small">Couleur</label>
                            <div class="view-mode">{{ $imp->est_couleur ? 'Oui' : 'Non' }}</div>
                            <div class="form-check form-switch edit-mode d-none mt-1">
                                <input class="form-check-input" type="checkbox" name="est_couleur" value="1" {{ $imp->est_couleur ? 'checked' : '' }}>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-muted small">Multifonction</label>
                            <div class="view-mode">{{ $imp->est_multifonction ? 'Oui' : 'Non' }}</div>
                            <div class="form-check form-switch edit-mode d-none mt-1">
                                <input class="form-check-input" type="checkbox" name="est_multifonction" value="1" {{ $imp->est_multifonction ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>
                    <div class="d-none edit-mode mt-4 text-end">
                        <button type="button" class="btn btn-light me-2" id="btn-cancel-edit">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="affectation" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            @if($aff)
                <h6 class="fw-bold text-primary mb-3">Emplacement Actuel</h6>
                <div class="fs-5">{{ $aff->local?->nom_complet ?? '—' }}</div>
                <div class="text-muted small">Affecté le {{ $aff->date_debut?->format('d/m/Y') }}</div>
            @else
                <div class="text-center py-4 text-muted">Équipement en stock.</div>
            @endif
        </div>
    </div>

    <div class="tab-pane fade" id="historique" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold text-primary mb-3">Journal des modifications</h6>
            {{-- Tableau simplifié --}}
            <table class="table table-sm small">
                <thead><tr><th>Date</th><th>Type</th><th>Motif</th><th>Par</th></tr></thead>
                <tbody>
                    @foreach($equipement->historique->sortByDesc('date_changement') as $h)
                    <tr>
                        <td>{{ $h->date_changement->format('d/m/Y H:i') }}</td>
                        <td>{{ $h->type_changement }}</td>
                        <td>{{ $h->motif }}</td>
                        <td>{{ $h->utilisateur?->name }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>
$(document).ready(function() {
    let editMode = false;

    $('#btn-edit-toggle').on('click', function() {
        editMode = !editMode;
        $('.view-mode').toggleClass('d-none', editMode);
        $('.edit-mode').toggleClass('d-none', !editMode);
        $(this).toggleClass('btn-warning', editMode).html(editMode ? '<i class="bi bi-x"></i> Annuler' : '<i class="bi bi-pencil"></i> Modifier');
    });

    $('#btn-cancel-edit').on('click', () => $('#btn-edit-toggle').trigger('click'));

    $('#form-update').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: `{{ route('parc-info.imprimantes.update', $equipement->id) }}`,
            method: 'PUT',
            data: $(this).serialize(),
            success: () => window.location.reload(),
            error: () => Swal.fire('Erreur', 'Erreur lors de la mise à jour', 'error')
        });
    });

    $('#btn-desaffecter').on('click', function() {
        Swal.fire({
            title: 'Désaffecter ?',
            input: 'text',
            inputPlaceholder: 'Motif...',
            showCancelButton: true
        }).then(res => {
            if (res.isConfirmed) {
                $.post(`{{ route('parc-info.imprimantes.desaffecter', $equipement->id) }}`, { motif: res.value }, () => window.location.reload());
            }
        });
    });
});
</script>
@endpush
