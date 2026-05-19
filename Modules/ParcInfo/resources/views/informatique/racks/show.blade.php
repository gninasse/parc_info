@extends('parcinfo::layouts.master')

@section('header', $equipement->code_inventaire)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.racks.index') }}">Baies & Racks</a></li>
    <li class="breadcrumb-item active">{{ $equipement->code_inventaire }}</li>
@endsection

@php
    $infra = $equipement->infrastructure;
    $aff = $equipement->affectationActive;
    $statutColors = [
        'en_service' => 'success',
        'en_stock' => 'secondary',
        'en_reparation' => 'warning',
        'perdu' => 'danger',
        'reforme' => 'dark',
    ];
    $etatColors = ['bon' => 'success', 'passable' => 'warning', 'mauvais' => 'danger', 'avarie' => 'danger'];
    $sc = $statutColors[$equipement->statut] ?? 'info';
    $ec = $etatColors[$equipement->etat] ?? 'secondary';
    $statutLabels = [
        'en_service' => 'En service',
        'en_stock' => 'En stock',
        'en_reparation' => 'En reparation',
        'perdu' => 'Perdu / Vole',
        'reforme' => 'Reforme',
    ];
@endphp

@section('content')
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px">
    <div class="card-body p-4">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <div class="rounded-3 d-flex align-items-center justify-content-center bg-primary bg-opacity-10" style="width:72px;height:72px">
                    <i class="bi bi-hdd-stack fs-2 text-primary"></i>
                </div>
            </div>
            <div class="col">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h4 class="fw-bold mb-0">{{ $equipement->marque?->libelle }} {{ $equipement->modele }}</h4>
                    <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }} border border-{{ $sc }}-subtle px-2 py-1">{{ $statutLabels[$equipement->statut] ?? ucfirst($equipement->statut) }}</span>
                    <span class="badge bg-{{ $ec }}-subtle text-{{ $ec }} border border-{{ $ec }}-subtle px-2 py-1">{{ ucfirst($equipement->etat) }}</span>
                </div>
                <div class="d-flex gap-4 flex-wrap text-muted small">
                    <span><i class="bi bi-upc me-1"></i>{{ $equipement->code_inventaire }}</span>
                    <span><i class="bi bi-hash me-1"></i>{{ $equipement->numero_serie }}</span>
                    @if($infra?->u_capacite_totale)
                    <span><i class="bi bi-arrows-vertical me-1"></i>{{ $infra->u_capacite_totale }} U</span>
                    @endif
                    @if(!is_null($infra?->nb_prises_pdu))
                    <span><i class="bi bi-plug me-1"></i>{{ $infra->nb_prises_pdu }} PDU</span>
                    @endif
                </div>
            </div>
            <div class="col-auto d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-arrow-repeat me-1"></i> Statut
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" data-statut="en_stock">En stock</a></li>
                        <li><a class="dropdown-item" href="#" data-statut="en_service">En service</a></li>
                        <li><a class="dropdown-item" href="#" data-statut="en_reparation">En réparation</a></li>
                        <li><a class="dropdown-item" href="#" data-statut="perdu">Perdu</a></li>
                        <li><a class="dropdown-item" href="#" data-statut="reforme">Réformé</a></li>
                    </ul>
                </div>
                @if($aff)
                <button class="btn btn-warning btn-sm" id="btn-desaffecter">
                    <i class="bi bi-x-circle me-1"></i> Désaffecter
                </button>
                @endif
                <button class="btn btn-outline-secondary btn-sm" id="btn-nouvelle-affectation">
                    <i class="bi bi-geo-alt me-1"></i> Affecter
                </button>
                <button class="btn btn-primary btn-sm" id="btn-edit-toggle">
                    <i class="bi bi-pencil me-1"></i> Modifier
                </button>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs border-0 mb-3" id="showTabs" role="tablist">
    @foreach([
        ['fiche', 'bi-hdd-stack', 'Fiche Technique'],
        ['affectation', 'bi-geo-alt', 'Localisation Actuelle'],
        ['historique-aff', 'bi-clock-history', 'Historique Emplacements'],
        ['historique-chg', 'bi-journal-text', 'Journal des Changements'],
    ] as [$id, $icon, $label])
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $loop->first ? 'active' : '' }} fw-semibold small px-3"
                id="tab-{{ $id }}" data-bs-toggle="tab" data-bs-target="#pane-{{ $id }}" type="button" role="tab">
            <i class="bi {{ $icon }} me-1"></i>{{ $label }}
        </button>
    </li>
    @endforeach
</ul>

<div class="tab-content" id="showTabsContent">
    <div class="tab-pane fade show active" id="pane-fiche" role="tabpanel">
        <form id="ficheForm">
            @csrf
            @method('PUT')
            <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
                <div class="card-body p-4">
                    <h6 class="section-title mb-4"><span class="section-num">01</span> Identification</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="field-label">Code Inventaire</label>
                            <input type="text" class="form-control field-input" id="f_code_inventaire" value="{{ $equipement->code_inventaire }}" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Numéro de Série</label>
                            <input type="text" class="form-control field-input" name="numero_serie" id="f_numero_serie" value="{{ $equipement->numero_serie }}" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Marque</label>
                            <select class="form-select field-input" name="marque_id" id="f_marque_id" disabled>
                                <option value="">—</option>
                                @foreach($marques as $m)
                                <option value="{{ $m->id }}" {{ $equipement->marque_id == $m->id ? 'selected' : '' }}>{{ $m->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Modèle</label>
                            <input type="text" class="form-control field-input" name="modele" id="f_modele" value="{{ $equipement->modele }}" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Statut</label>
                            <select class="form-select field-input" name="statut" id="f_statut" disabled>
                                @foreach(['en_stock'=>'En stock','en_service'=>'En service','en_reparation'=>'En réparation','perdu'=>'Perdu / Volé','reforme'=>'Réformé'] as $v => $l)
                                <option value="{{ $v }}" {{ $equipement->statut === $v ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">État</label>
                            <select class="form-select field-input" name="etat" id="f_etat" disabled>
                                @foreach(['bon'=>'Bon','passable'=>'Passable','mauvais'=>'Mauvais','avarie'=>'Avarié'] as $v => $l)
                                <option value="{{ $v }}" {{ $equipement->etat === $v ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Date d'acquisition</label>
                            <input type="date" class="form-control field-input" name="date_acquisition" value="{{ $equipement->date_acquisition?->format('Y-m-d') }}" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Mise en service</label>
                            <input type="date" class="form-control field-input" name="date_mise_en_service" value="{{ $equipement->date_mise_en_service?->format('Y-m-d') }}" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Fin de garantie</label>
                            <input type="date" class="form-control field-input" name="date_fin_garantie" value="{{ $equipement->date_fin_garantie?->format('Y-m-d') }}" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Valeur d'achat (FCFA)</label>
                            <input type="number" class="form-control field-input" name="valeur_achat" value="{{ $equipement->valeur_achat }}" disabled>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
                <div class="card-body p-4">
                    <h6 class="section-title mb-4"><span class="section-num">02</span> Caractéristiques Techniques</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="field-label">Type d'infrastructure</label>
                            <select class="form-select field-input" id="f_type_infra_id" disabled>
                                @foreach($typesInfra as $type)
                                <option value="{{ $type->id }}" {{ $infra?->type_infra_id == $type->id ? 'selected' : '' }}>{{ $type->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Capacité (U)</label>
                            <input type="number" class="form-control field-input" name="u_capacite_totale" id="f_u_capacite_totale" value="{{ $infra?->u_capacite_totale }}" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="field-label">Prises PDU</label>
                            <input type="number" class="form-control field-input" name="nb_prises_pdu" id="f_nb_prises_pdu" value="{{ $infra?->nb_prises_pdu }}" disabled>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="est_redondant" id="f_est_redondant" value="1" {{ $infra?->est_redondant ? 'checked' : '' }} disabled>
                                <label class="form-check-label field-label mb-0" for="f_est_redondant">Redondance Alim</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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

    <div class="tab-pane fade" id="pane-affectation" role="tabpanel">
        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-body p-4">
                @if($aff && $aff->local)
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="rounded-3 bg-success bg-opacity-10 p-3">
                        <i class="bi bi-door-open fs-4 text-success"></i>
                    </div>
                    <div>
                        <div class="fw-bold">Installation physique active</div>
                        <div class="text-muted small">Depuis le {{ $aff->date_debut?->format('d/m/Y') }}</div>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle ms-auto px-3 py-2">Active</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-4"><div class="info-block"><div class="info-label">Code local</div><div class="info-value">{{ $aff->local->code }}</div></div></div>
                    <div class="col-md-4"><div class="info-block"><div class="info-label">Libellé</div><div class="info-value">{{ $aff->local->libelle }}</div></div></div>
                    <div class="col-md-4"><div class="info-block"><div class="info-label">Type</div><div class="info-value">{{ $aff->local->type_local_label }}</div></div></div>
                    <div class="col-md-8"><div class="info-block"><div class="info-label">Emplacement complet</div><div class="info-value">{{ $aff->local->nom_complet }}</div></div></div>
                    <div class="col-md-4"><div class="info-block"><div class="info-label">Date début</div><div class="info-value">{{ $aff->date_debut?->format('d/m/Y') }}</div></div></div>
                </div>
                @else
                <div class="text-center py-5">
                    <div class="mb-3"><i class="bi bi-geo-alt fs-1 text-muted opacity-50"></i></div>
                    <p class="text-muted fw-semibold">Aucune localisation active</p>
                    <button class="btn btn-primary btn-sm" id="btn-nouvelle-affectation-2">
                        <i class="bi bi-geo-alt me-1"></i> Créer une affectation
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="pane-historique-aff" role="tabpanel">
        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-body p-0">
                @if($equipement->affectations->count())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4 py-3 small fw-bold text-uppercase text-muted">Type</th>
                                <th class="py-3 small fw-bold text-uppercase text-muted">Local</th>
                                <th class="py-3 small fw-bold text-uppercase text-muted">Début</th>
                                <th class="py-3 small fw-bold text-uppercase text-muted">Fin</th>
                                <th class="py-3 small fw-bold text-uppercase text-muted">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($equipement->affectations->sortByDesc('id') as $a)
                            <tr>
                                <td class="px-4"><span class="badge bg-light text-dark border small">{{ $a->type_affectation ?? '—' }}</span></td>
                                <td>
                                    @if($a->local)
                                    <div class="fw-semibold small">{{ $a->local->libelle }}</div>
                                    <div class="text-muted" style="font-size:.75rem">{{ $a->local->nom_complet }}</div>
                                    @else
                                    <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="small">{{ $a->date_debut?->format('d/m/Y') ?? '—' }}</td>
                                <td class="small">{{ $a->date_fin?->format('d/m/Y') ?? 'En cours' }}</td>
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
                    Aucun historique d'emplacement.
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="pane-historique-chg" role="tabpanel">
        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-body p-4">
                @if($equipement->historique->count())
                <div class="timeline">
                    @foreach($equipement->historique->sortByDesc('date_changement') as $h)
                    @php
                        $typeColors = ['STATUT' => 'primary', 'ETAT' => 'warning', 'AFFECTATION' => 'info', 'TECHNIQUE' => 'secondary'];
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
                            @if($h->ancien_etat || $h->nouvel_etat)
                            <div class="small mb-1">
                                <span class="text-muted">{{ $h->ancien_etat }}</span>
                                <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                <span class="fw-semibold">{{ $h->nouvel_etat }}</span>
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
                    Aucun changement enregistré.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

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
                        <div class="col-12">
                            <label class="aff-type-card d-flex flex-column align-items-center justify-content-center gap-2 p-3 rounded-3 border cursor-pointer text-center position-relative" data-value="LOCAL">
                                <input type="radio" name="type_cible" value="LOCAL" class="d-none">
                                <div class="aff-type-icon rounded-3 p-3 bg-light"><i class="bi bi-door-open fs-3 text-secondary"></i></div>
                                <small class="fw-semibold" style="font-size:.78rem">Affecter à un local</small>
                                <i class="bi bi-check-circle-fill text-primary position-absolute top-0 end-0 m-2 d-none check-icon" style="font-size:.9rem"></i>
                            </label>
                        </div>
                    </div>

                    <div id="aff-local-summary" class="aff-summary d-none">
                        <div class="card border-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0"><i class="bi bi-door-open text-primary me-2"></i>Local sélectionné</h6>
                                    <button type="button" class="btn btn-sm btn-link text-primary text-decoration-none" id="btn-change-local">Changer</button>
                                </div>
                                <div class="row g-2 mt-2">
                                    <div class="col-md-2"><small class="text-muted d-block">Code</small><strong id="local-summary-code">—</strong></div>
                                    <div class="col-md-4"><small class="text-muted d-block">Libellé</small><strong id="local-summary-libelle">—</strong></div>
                                    <div class="col-md-2"><small class="text-muted d-block">Type</small><span id="local-summary-type">—</span></div>
                                    <div class="col-md-2"><small class="text-muted d-block">Étage</small><span id="local-summary-etage">—</span></div>
                                    <div class="col-md-2"><small class="text-muted d-block">Bâtiment</small><span id="local-summary-batiment">—</span></div>
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

@include('parcinfo::informatique.ordinateurs._selection_modals')
@endsection

@push('css')
<style>
.section-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#344054; border-left:3px solid #0d6efd; padding-left:10px; display:flex; align-items:center; gap:8px; }
.section-num { background:#0d6efd; color:#fff; font-size:.65rem; font-weight:700; border-radius:4px; padding:1px 6px; }
.field-label { font-size:.78rem; font-weight:600; color:#475467; margin-bottom:4px; display:block; }
.field-input { font-size:.875rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; }
.field-input:not(:disabled):focus { background:#fff; border-color:#0d6efd; box-shadow:0 0 0 3px rgba(13,110,253,.1); }
.field-input:disabled { background:#f1f5f9; color:#64748b; }
.info-block { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:.75rem 1rem; }
.info-label { font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:#94a3b8; margin-bottom:2px; }
.info-value { font-size:.875rem; font-weight:600; color:#1e293b; }
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
<script src="{{ asset('js/modules/parc-info/ordinateurs/selection_modals.js') }}?v={{ time() }}"></script>
<script>
const equipementId = "{{ $equipement->id }}";

$(function () {
    let editMode = false;

    function setEditMode(on) {
        editMode = on;
        $('#pane-fiche .field-input').not('#f_code_inventaire, #f_type_infra_id').prop('disabled', !on);
        $('#pane-fiche input[type="checkbox"]').prop('disabled', !on);
        $('#fiche-actions').toggleClass('d-none', !on);
        $('#btn-edit-toggle').toggleClass('btn-primary', !on).toggleClass('btn-warning', on)
            .html(on ? '<i class="bi bi-x me-1"></i> Annuler' : '<i class="bi bi-pencil me-1"></i> Modifier');
    }

    $('#btn-edit-toggle').on('click', () => setEditMode(!editMode));
    $('#btn-cancel-edit').on('click', () => { setEditMode(false); location.reload(); });

    $('#ficheForm').on('submit', function (e) {
        e.preventDefault();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        const $btn = $('#btn-save-fiche').prop('disabled', true).text('Enregistrement...');

        $.ajax({
            url: route('parc-info.racks.update', equipementId),
            method: 'PUT',
            data: $(this).serialize(),
            success: (res) => {
                if (res.success) {
                    setEditMode(false);
                    Swal.fire({ icon: 'success', title: 'Enregistré', text: res.message, timer: 2000, showConfirmButton: false })
                        .then(() => location.reload());
                }
            },
            error: (xhr) => {
                if (xhr.status === 422) {
                    Object.entries(xhr.responseJSON.errors ?? {}).forEach(([f, msgs]) => {
                        const $field = $(`#f_${f}`);
                        if ($field.length) {
                            $field.addClass('is-invalid').after(`<div class="invalid-feedback d-block">${msgs[0]}</div>`);
                        }
                    });
                } else {
                    Swal.fire('Erreur', xhr.responseJSON?.message ?? 'Erreur serveur', 'error');
                }
            },
            complete: () => $btn.prop('disabled', false).html('<i class="bi bi-floppy me-1"></i> Enregistrer'),
        });
    });

    function openAffModal() { $('#affectationModal').modal('show'); }
    $('#btn-nouvelle-affectation, #btn-nouvelle-affectation-2').on('click', openAffModal);

    $(document).on('local:selected', function (_e, local) {
        if (!$('#affectationModal').hasClass('show')) return;
        $('#affectationModal .aff-type-card').removeClass('selected');
        $('#affectationModal .aff-type-card[data-value="LOCAL"]').addClass('selected')
            .find('input[type="radio"]').prop('checked', true);
        $('#local-summary-code').text(local.code);
        $('#local-summary-libelle').text(local.libelle);
        $('#local-summary-type').text(local.type);
        $('#local-summary-etage').text(local.etage);
        $('#local-summary-batiment').text(local.batiment);
        $('#affectationModal #local_id').val(local.id);
        $('#affectationModal .aff-summary').addClass('d-none');
        $('#affectationModal #aff-local-summary').removeClass('d-none');
    });

    $('#btn-change-local').on('click', () => $('#localSelectionModal').modal('show'));

    $('#affectationForm').on('submit', function (e) {
        e.preventDefault();
        if (!$('#affectationModal #local_id').val()) {
            Swal.fire({ icon: 'warning', title: 'Attention', text: 'Veuillez sélectionner un local.', timer: 2500, showConfirmButton: false });
            return;
        }

        const $btn = $('#btn-save-affectation').prop('disabled', true)
            .html('<i class="bi bi-hourglass-split me-1"></i> Enregistrement...');

        $.post(route('parc-info.racks.store-affectation'), $(this).serialize(), (res) => {
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
        $('#affectationModal #local_id').val('');
    });

    $(document).on('click', '.dropdown-item[data-statut]', function (e) {
        e.preventDefault();
        const nouveauStatut = $(this).data('statut');

        Swal.fire({
            title: 'Changer le statut',
            input: 'textarea',
            inputLabel: 'Motif du changement',
            inputPlaceholder: 'Expliquez la raison du changement de statut...',
            showCancelButton: true,
            confirmButtonText: 'Confirmer',
            cancelButtonText: 'Annuler',
            preConfirm: (motif) => {
                if (!motif) {
                    Swal.showValidationMessage('Le motif est obligatoire');
                }
                return motif;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('parc-info.racks.update-statut', equipementId),
                    method: 'PATCH',
                    data: {
                        statut: nouveauStatut,
                        motif: result.value,
                        _token: '{{ csrf_token() }}'
                    },
                    success: (response) => Swal.fire('Succès', response.message, 'success').then(() => location.reload()),
                    error: (xhr) => Swal.fire('Erreur', xhr.responseJSON?.message || 'Une erreur est survenue', 'error')
                });
            }
        });
    });

    $('#btn-desaffecter').on('click', function () {
        Swal.fire({
            title: 'Désaffecter l\'équipement',
            text: 'L\'équipement sera mis en stock',
            input: 'textarea',
            inputLabel: 'Motif de la désaffectation',
            inputPlaceholder: 'Expliquez la raison...',
            showCancelButton: true,
            confirmButtonText: 'Confirmer',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#dc3545',
            preConfirm: (motif) => {
                if (!motif) {
                    Swal.showValidationMessage('Le motif est obligatoire');
                }
                return motif;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('parc-info.racks.desaffecter', equipementId),
                    method: 'POST',
                    data: {
                        motif: result.value,
                        _token: '{{ csrf_token() }}'
                    },
                    success: (response) => Swal.fire('Succès', response.message, 'success').then(() => location.reload()),
                    error: (xhr) => Swal.fire('Erreur', xhr.responseJSON?.message || 'Une erreur est survenue', 'error')
                });
            }
        });
    });
});
</script>
@endpush
