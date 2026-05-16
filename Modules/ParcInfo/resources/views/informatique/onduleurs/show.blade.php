@extends('parcinfo::layouts.master')

@section('header', $equipement->code_inventaire)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.onduleurs.index') }}">Onduleurs</a></li>
    <li class="breadcrumb-item active">{{ $equipement->code_inventaire }}</li>
@endsection

@php
    $r   = $equipement->reseau;
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
                <i class="bi bi-hdd-network fs-1 text-primary"></i>
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
                    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill"><i class="bi bi-tag me-1"></i>{{ $r->typeReseau?->libelle ?? 'Type inconnu' }}</span>
                </div>
            </div>

            <div class="col-auto d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle bg-white" type="button" data-bs-toggle="dropdown">
                        Changer statut
                    </button>
                    <ul class="dropdown-menu shadow-sm border-0">
                        <li><a class="dropdown-item" href="#" data-statut="en_service">En service</a></li>
                        <li><a class="dropdown-item" href="#" data-statut="en_stock">En stock</a></li>
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
        ['fiche',      'bi-hdd-network',   'Fiche Technique'],
        ['ip',         'bi-diagram-3',     'Réseau & IP'],
        ['affectation','bi-geo-alt',       'Emplacement'],
        ['historique-chg','bi-journal-text', 'Journal'],
    ] as $i => $tab)
    <li class="nav-item" role="presentation">
        <button class="nav-link border-0 fw-semibold {{ $i==0?'active':'' }} pb-3" id="{{ $tab[0] }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $tab[0] }}" type="button" role="tab">
            <i class="bi {{ $tab[1] }} me-2"></i>{{ $tab[2] }}
        </button>
    </li>
    @endforeach
</ul>

<div class="tab-content">

    {{-- FICHE TECHNIQUE --}}
    <div class="tab-pane fade show active" id="fiche" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <form id="form-update">
                    @csrf
                    @method('PUT')

                    <h6 class="fw-bold mb-4 text-primary"><i class="bi bi-info-circle me-2"></i>Informations Générales</h6>
                    <div class="row g-4 mb-5">
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Numéro de série <span class="text-danger edit-only d-none">*</span></label>
                            <input type="text" class="form-control bg-light view-mode" value="{{ $equipement->numero_serie }}" readonly>
                            <input type="text" class="form-control edit-mode d-none" name="numero_serie" value="{{ $equipement->numero_serie }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Marque</label>
                            <input type="text" class="form-control bg-light view-mode" value="{{ $equipement->marque?->libelle ?? '—' }}" readonly>
                            <select class="form-select edit-mode d-none" name="marque_id">
                                <option value="">—</option>
                                @foreach($marques as $m)
                                    <option value="{{ $m->id }}" {{ $equipement->marque_id == $m->id ? 'selected' : '' }}>{{ $m->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Modèle <span class="text-danger edit-only d-none">*</span></label>
                            <input type="text" class="form-control bg-light view-mode" value="{{ $equipement->modele }}" readonly>
                            <input type="text" class="form-control edit-mode d-none" name="modele" value="{{ $equipement->modele }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-muted small">Type d'équipement</label>
                            <input type="text" class="form-control bg-light view-mode" value="{{ $r->typeReseau?->libelle ?? '—' }}" readonly>
                            <select class="form-select edit-mode d-none" name="type_reseau_id">
                                <option value="">—</option>
                                @foreach($typesReseaux as $t)
                                    <option value="{{ $t->id }}" {{ $r->type_reseau_id == $t->id ? 'selected' : '' }}>{{ $t->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Nombre de ports</label>
                            <input type="text" class="form-control bg-light view-mode" value="{{ $r->nb_ports ?? '—' }}" readonly>
                            <input type="number" class="form-control edit-mode d-none" name="nb_ports" value="{{ $r->nb_ports }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Vitesse max (Mbps)</label>
                            <input type="text" class="form-control bg-light view-mode" value="{{ $r->vitesse_max_mbps ? $r->vitesse_max_mbps . ' Mbps' : '—' }}" readonly>
                            <select class="form-select edit-mode d-none" name="vitesse_max_mbps">
                                <option value="" {{ !$r->vitesse_max_mbps ? 'selected' : '' }}>Non spécifié</option>
                                <option value="100" {{ $r->vitesse_max_mbps == 100 ? 'selected' : '' }}>100 Mbps (Fast Ethernet)</option>
                                <option value="1000" {{ $r->vitesse_max_mbps == 1000 ? 'selected' : '' }}>1 Gbps (Gigabit)</option>
                                <option value="10000" {{ $r->vitesse_max_mbps == 10000 ? 'selected' : '' }}>10 Gbps</option>
                                <option value="40000" {{ $r->vitesse_max_mbps == 40000 ? 'selected' : '' }}>40 Gbps</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-muted small">Support PoE</label>
                            <div class="view-mode mt-2">
                                @if($r->est_poe)
                                    <span class="badge bg-success"><i class="bi bi-lightning-charge me-1"></i>Oui</span>
                                @else
                                    <span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Non</span>
                                @endif
                            </div>
                            <div class="edit-mode d-none mt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="est_poe" value="1" {{ $r->est_poe ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Équipement Manageable</label>
                            <div class="view-mode mt-2">
                                @if($r->est_manageable)
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Oui</span>
                                @else
                                    <span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Non</span>
                                @endif
                            </div>
                            <div class="edit-mode d-none mt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="est_manageable" value="1" {{ $r->est_manageable ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Version Firmware</label>
                            <input type="text" class="form-control bg-light view-mode" value="{{ $r->version_firmware ?? '—' }}" readonly>
                            <input type="text" class="form-control edit-mode d-none" name="version_firmware" value="{{ $r->version_firmware }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-muted small">Date d'acquisition</label>
                            <input type="text" class="form-control bg-light view-mode" value="{{ $equipement->date_acquisition ? $equipement->date_acquisition->format('d/m/Y') : '—' }}" readonly>
                            <input type="date" class="form-control edit-mode d-none" name="date_acquisition" value="{{ $equipement->date_acquisition?->format('Y-m-d') }}">
                        </div>
                    </div>

                    {{-- Form Hidden inputs needed for update --}}
                    <input type="hidden" name="statut" value="{{ $equipement->statut }}" class="edit-mode d-none">
                    <input type="hidden" name="etat" value="{{ $equipement->etat }}" class="edit-mode d-none">
                    <input type="hidden" name="adresse_ip" value="{{ $r->adresse_ip }}" class="edit-mode d-none form-sync-ip">
                    <input type="hidden" name="masque_sous_reseau" value="{{ $r->masque_sous_reseau }}" class="edit-mode d-none form-sync-mask">
                    <input type="hidden" name="passerelle" value="{{ $r->passerelle }}" class="edit-mode d-none form-sync-gw">

                    <div class="d-none edit-mode bg-light p-3 rounded-3 text-end">
                        <button type="button" class="btn btn-light me-2" id="btn-cancel-edit">Annuler</button>
                        <button type="submit" class="btn btn-primary" id="btn-save-edit">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- RÉSEAU & IP --}}
    <div class="tab-pane fade" id="ip" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-4 text-primary"><i class="bi bi-diagram-3 me-2"></i>Configuration IP</h6>
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label text-muted small">Adresse IP (Management)</label>
                        <input type="text" class="form-control bg-light view-mode" value="{{ $r->adresse_ip ?? '—' }}" readonly>
                        <input type="text" class="form-control edit-mode d-none" id="edit_adresse_ip" value="{{ $r->adresse_ip }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted small">Masque de sous-réseau</label>
                        <input type="text" class="form-control bg-light view-mode" value="{{ $r->masque_sous_reseau ?? '—' }}" readonly>
                        <input type="text" class="form-control edit-mode d-none" id="edit_masque" value="{{ $r->masque_sous_reseau }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted small">Passerelle</label>
                        <input type="text" class="form-control bg-light view-mode" value="{{ $r->passerelle ?? '—' }}" readonly>
                        <input type="text" class="form-control edit-mode d-none" id="edit_passerelle" value="{{ $r->passerelle }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- AFFECTATION --}}
    <div class="tab-pane fade" id="affectation" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                @if($aff)
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <h6 class="fw-bold text-primary mb-0"><i class="bi bi-geo-alt me-2"></i>Emplacement Actuel</h6>
                    </div>

                    @if($aff->type_cible === 'LOCAL')
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Local / Salle technique</label>
                            <div class="fw-semibold fs-5">{{ $aff->local?->nom_complet ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Depuis le</label>
                            <div class="fw-semibold fs-5">{{ $aff->date_debut?->format('d/m/Y') ?? '—' }}</div>
                        </div>
                    </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px">
                            <i class="bi bi-box fs-3 text-muted"></i>
                        </div>
                        <h6 class="fw-bold">Équipement en stock</h6>
                        <p class="text-muted small mb-0">Cet équipement n'est actuellement pas déployé.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- HISTORIQUE --}}
    <div class="tab-pane fade" id="historique-chg" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-primary mb-4"><i class="bi bi-clock-history me-2"></i>Historique des changements</h6>
                @if($equipement->historique->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Ancien</th>
                                    <th>Nouveau</th>
                                    <th>Motif</th>
                                    <th>Utilisateur</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($equipement->historique->sortByDesc('date_changement') as $h)
                                <tr>
                                    <td>{{ $h->date_changement->format('d/m/Y H:i') }}</td>
                                    <td><span class="badge bg-secondary">{{ $h->type_changement }}</span></td>
                                    <td>{{ $h->ancien_statut ?? '-' }}</td>
                                    <td>{{ $h->nouveau_statut ?? '-' }}</td>
                                    <td>{{ $h->motif }}</td>
                                    <td>{{ $h->utilisateur?->name ?? 'Système' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">Aucun historique disponible.</div>
                @endif
            </div>
        </div>
    </div>

</div>

@endsection

@push('js')
<script>
$(document).ready(function() {
    let editMode = false;

    // Synchronisation des champs IP du tab 2 vers le form principal
    $('#edit_adresse_ip').on('input', function() { $('.form-sync-ip').val($(this).val()); });
    $('#edit_masque').on('input', function() { $('.form-sync-mask').val($(this).val()); });
    $('#edit_passerelle').on('input', function() { $('.form-sync-gw').val($(this).val()); });

    function setEditMode(on) {
        editMode = on;
        $('.view-mode').toggleClass('d-none', on);
        $('.edit-mode').toggleClass('d-none', !on);
        $('#btn-edit-toggle').toggleClass('btn-primary', !on).toggleClass('btn-warning', on)
                             .html(on ? '<i class="bi bi-x me-1"></i> Mode Vue' : '<i class="bi bi-pencil me-1"></i> Modifier');
    }

    $('#btn-edit-toggle').on('click', () => setEditMode(!editMode));
    $('#btn-cancel-edit').on('click', () => {
        document.getElementById('form-update').reset();
        setEditMode(false);
    });

    $('#form-update').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btn-save-edit');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');

        $.ajax({
            url: `{{ route('parc-info.onduleurs.update', $equipement->id) }}`,
            method: 'PUT',
            data: $(this).serialize(),
            success: (res) => {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500, showConfirmButton: false })
                        .then(() => window.location.reload());
                }
            },
            error: (xhr) => {
                Swal.fire('Erreur', 'Veuillez vérifier vos champs.', 'error');
                $btn.prop('disabled', false).text('Enregistrer les modifications');
            }
        });
    });

    // Statut & Désaffectation
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
                    url: `{{ route('parc-info.onduleurs.update-statut', $equipement->id) }}`,
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
                    url: `{{ route('parc-info.onduleurs.desaffecter', $equipement->id) }}`,
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
});
</script>
@endpush
