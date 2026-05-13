@extends('parcinfo::layouts.master')

@section('header', $equipement->code_inventaire)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.mobiles.index') }}">Mobiles</a></li>
    <li class="breadcrumb-item active">{{ $equipement->code_inventaire }}</li>
@endsection

@php
    $m   = $equipement->mobile;
    $aff = $equipement->affectationActive;
    $statutColors = ['en_service'=>'success','en_stock'=>'secondary','en_reparation'=>'warning','perdu'=>'danger','reforme'=>'dark'];
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
                <div class="rounded-3 d-flex align-items-center justify-content-center bg-primary bg-opacity-10" style="width:72px;height:72px">
                    <i class="bi bi-phone-vibrate fs-2 text-primary"></i>
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
                    @if($m->statut_mdm === 'Enrôlé')
                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1"><i class="bi bi-shield-check me-1"></i>MDM</span>
                    @endif
                </div>
                <div class="d-flex gap-4 flex-wrap text-muted small">
                    <span><i class="bi bi-upc me-1"></i>{{ $equipement->code_inventaire }}</span>
                    <span><i class="bi bi-telephone me-1"></i>{{ $m->num_tel_associe ?: 'Pas de numéro' }}</span>
                    <span><i class="bi bi-hash me-1"></i>IMEI: {{ $m->imei_1 }}</span>
                </div>
            </div>
            <div class="col-auto d-flex gap-2">
                @if($aff)
                <button class="btn btn-outline-danger btn-sm bg-white" id="btn-desaffecter">
                    <i class="bi bi-x-circle me-1"></i> Désaffecter
                </button>
                @endif
                <button class="btn btn-primary btn-sm" id="btn-edit-toggle">
                    <i class="bi bi-pencil me-1"></i> Modifier
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Fiche Technique --}}
    <div class="col-md-8">
        <form id="ficheForm">
            @csrf
            @method('PUT')
            <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
                <div class="card-body p-4">
                    <h6 class="section-title mb-4"><span class="section-num">01</span> Spécifications du terminal</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="field-label">Type de terminal</label>
                            <select class="form-select field-input" name="type_mobile_id" id="f_type_mobile_id" disabled>
                                @foreach($typesMobiles as $t)
                                <option value="{{ $t->id }}" {{ $m->type_mobile_id == $t->id ? 'selected':'' }}>{{ $t->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label">N° Téléphone</label>
                            <input type="text" class="form-control field-input" name="num_tel_associe" value="{{ $m->num_tel_associe }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label">IMEI 1</label>
                            <input type="text" class="form-control field-input" name="imei_1" value="{{ $m->imei_1 }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label">IMEI 2</label>
                            <input type="text" class="form-control field-input" name="imei_2" value="{{ $m->imei_2 }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label">Version OS</label>
                            <input type="text" class="form-control field-input" name="version_os" value="{{ $m->version_os }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label">Statut MDM</label>
                            <select class="form-select field-input" name="statut_mdm" disabled>
                                <option value="Non enrôlé" {{ $m->statut_mdm === 'Non enrôlé' ? 'selected':'' }}>Non enrôlé</option>
                                <option value="Enrôlé" {{ $m->statut_mdm === 'Enrôlé' ? 'selected':'' }}>Enrôlé</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="field-label">État de l'équipement</label>
                            <select class="form-select field-input" name="etat" id="f_etat" disabled>
                                <option value="bon" {{ $equipement->etat === 'bon' ? 'selected':'' }}>Bon</option>
                                <option value="passable" {{ $equipement->etat === 'passable' ? 'selected':'' }}>Passable</option>
                                <option value="mauvais" {{ $equipement->etat === 'mauvais' ? 'selected':'' }}>Mauvais</option>
                                <option value="avarie" {{ $equipement->etat === 'avarie' ? 'selected':'' }}>Avarié / HS</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Batterie (mAh)</label>
                            <input type="number" class="form-control field-input" name="capacite_batterie_mah" value="{{ $m->capacite_batterie_mah }}" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">État écran</label>
                            <select class="form-select field-input" name="etat_ecran" disabled>
                                @foreach(['Parfait','Micro-rayures','Fissuré','Cassé'] as $e)
                                <option value="{{ $e }}" {{ $m->etat_ecran === $e ? 'selected':'' }}>{{ $e }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Coque protection</label>
                            <select class="form-select field-input" name="a_coque_protection" disabled>
                                <option value="1" {{ $m->a_coque_protection ? 'selected':'' }}>Oui</option>
                                <option value="0" {{ !$m->a_coque_protection ? 'selected':'' }}>Non</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div id="fiche-actions" class="d-none text-end mt-2">
                <button type="button" class="btn btn-link text-muted text-decoration-none me-3" onclick="location.reload()">Annuler</button>
                <button type="submit" class="btn btn-success px-4" id="btn-save-fiche">Enregistrer</button>
            </div>
        </form>
    </div>

    {{-- Affectation & Historique --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3 small text-uppercase text-muted">Affectation actuelle</h6>
                @if($aff)
                <div class="d-flex align-items-start gap-3 p-3 rounded-3 border bg-light">
                    <div class="rounded-circle bg-success bg-opacity-10 p-2"><i class="bi bi-person-check text-success"></i></div>
                    <div>
                        @if($aff->type_cible === 'EMPLOYE' && $aff->employe)
                        <div class="fw-bold">{{ $aff->employe->full_name }}</div>
                        <div class="small text-muted">{{ $aff->employe->matricule }}</div>
                        @else
                        <div class="fw-bold">{{ $aff->local?->libelle ?: 'Localisation inconnue' }}</div>
                        @endif
                        <div class="mt-1 small badge bg-white border text-dark">Depuis le {{ $aff->date_debut?->format('d/m/Y') }}</div>
                    </div>
                </div>
                @else
                <div class="text-center py-4 border rounded-3 border-dashed">
                    <p class="text-muted small mb-0">En stock / Non attribué</p>
                </div>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3 small text-uppercase text-muted">Dernières activités</h6>
                <div class="timeline-sm small">
                    @forelse($equipement->historique->take(5) as $h)
                    <div class="mb-2 pb-2 border-bottom last-border-0">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold text-primary">{{ $h->type_changement }}</span>
                            <span class="text-muted" style="font-size:.7rem">{{ $h->date_changement?->format('d/m H:i') }}</span>
                        </div>
                        <div class="text-truncate">{{ $h->motif }}</div>
                    </div>
                    @empty
                    <p class="text-muted italic small">Aucun historique.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('css')
<style>
.section-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#344054; border-left:3px solid #0d6efd; padding-left:10px; }
.section-num   { background:#0d6efd; color:#fff; font-size:.65rem; font-weight:700; border-radius:4px; padding:1px 6px; margin-right:4px; }
.field-label   { font-size:.78rem; font-weight:600; color:#475467; margin-bottom:4px; display:block; }
.field-input   { font-size:.875rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; }
.field-input:disabled { background:#f1f5f9; color:#64748b; }
.border-dashed { border-style: dashed !important; }
</style>
@endpush

@push('js')
<script>
$(function () {
    let editMode = false;
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
                    url: `{{ route('parc-info.mobiles.update-statut', $equipement->id) }}`,
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
                    url: `{{ route('parc-info.mobiles.desaffecter', $equipement->id) }}`,
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

    $('#btn-edit-toggle').on('click', function() {
        editMode = !editMode;
        $('.field-input').prop('disabled', !editMode);
        $('#fiche-actions').toggleClass('d-none', !editMode);
        $(this).toggleClass('btn-primary btn-warning').html(editMode ? 'Annuler' : 'Modifier');
    });

    $('#ficheForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: route('parc-info.mobiles.update', {{ $equipement->id }}),
            method: 'PUT',
            data: $(this).serialize(),
            success: () => { Swal.fire({ icon:'success', title:'Mis à jour', timer:1500, showConfirmButton:false }).then(() => location.reload()); }
        });
    });
});
</script>
@endpush
