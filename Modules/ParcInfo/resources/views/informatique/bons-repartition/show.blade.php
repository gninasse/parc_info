@extends('parcinfo::layouts.master')

@section('header', 'Bon ' . $bon->numero_bon)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.bons-repartition.index') }}">Bons de Répartition</a></li>
    <li class="breadcrumb-item active">{{ $bon->numero_bon }}</li>
@endsection

@section('content')

@php
    $statut = $bon->statut_label;
    $progression = $bon->progression;
    $totalLignes = $bon->lignes->count();
    $signees = $bon->lignes->where('est_signe', true)->count();
@endphp

{{-- ── En-tête du Bon ── --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h4 class="fw-bold mb-1">
                            <i class="bi bi-clipboard2-check me-2 text-primary"></i>{{ $bon->numero_bon }}
                        </h4>
                        <span class="badge bg-{{ $statut['color'] }}-subtle text-{{ $statut['color'] }} border border-{{ $statut['color'] }}-subtle fs-6">
                            {{ $statut['label'] }}
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" id="btn-print-bon">
                            <i class="bi bi-printer me-1"></i>Imprimer
                        </button>
                        <button class="btn btn-primary btn-sm" id="btn-add-ligne">
                            <i class="bi bi-plus-lg me-1"></i>Ajouter un équipement
                        </button>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-sm-4">
                        <div class="text-muted small fw-semibold text-uppercase mb-1" style="font-size:.7rem">Date du Bon</div>
                        <div class="fw-semibold">{{ $bon->date_bon->format('d/m/Y') }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small fw-semibold text-uppercase mb-1" style="font-size:.7rem">Fournisseur</div>
                        <div class="fw-semibold">{{ $bon->fournisseur?->nom ?? '—' }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small fw-semibold text-uppercase mb-1" style="font-size:.7rem">Observation</div>
                        <div>{{ $bon->observation ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column justify-content-center">
                <div class="text-center mb-3">
                    <div class="display-4 fw-bold text-primary">{{ $signees }}<span class="fs-4 text-muted">/{{ $totalLignes }}</span></div>
                    <div class="text-muted small">Équipements réceptionnés</div>
                </div>
                <div class="progress" style="height:12px">
                    <div class="progress-bar bg-{{ $statut['color'] }}" style="width:{{ $progression }}%"></div>
                </div>
                <div class="text-center mt-2 small text-muted">{{ $progression }}% complété</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Tableau des Lignes ── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold">Équipements du Bon</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="lignes-table">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Équipement</th>
                        <th>Catégorie</th>
                        <th>Destination</th>
                        <th>Réceptionniste</th>
                        <th>Date Livraison</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bon->lignes as $i => $ligne)
                    <tr id="ligne-row-{{ $ligne->id }}" class="{{ $ligne->est_signe ? 'table-success bg-success bg-opacity-5' : '' }}">
                        <td class="text-muted small">{{ $i + 1 }}</td>
                        <td>
                            <div class="fw-semibold">{{ $ligne->equipement->code_inventaire }}</div>
                            <div class="text-muted small">N° {{ $ligne->equipement->numero_serie }}</div>
                            <div class="text-muted small">{{ trim(($ligne->equipement->marque?->libelle ?? '') . ' ' . $ligne->equipement->modele) }}</div>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary border">{{ $ligne->equipement->categorie?->libelle ?? '—' }}</span>
                        </td>
                        <td>
                            <div class="small">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $ligne->type_cible }}</span>
                            </div>
                            <div class="fw-semibold mt-1">{{ $ligne->cible_label }}</div>
                        </td>
                        <td>{{ $ligne->nom_receptionniste ?: '—' }}</td>
                        <td>{{ $ligne->date_livraison?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            @if($ligne->est_signe)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-check-circle me-1"></i>Signé
                                </span>
                                <div class="text-muted small mt-1">le {{ $ligne->date_signature?->format('d/m/Y H:i') }}</div>
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                    <i class="bi bi-hourglass me-1"></i>En attente
                                </span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                @if(!$ligne->est_signe)
                                    <button class="btn btn-sm btn-success btn-signer"
                                            data-id="{{ $ligne->id }}"
                                            data-code="{{ $ligne->equipement->code_inventaire }}"
                                            data-cible="{{ $ligne->cible_label }}"
                                            title="Confirmer réception">
                                        <i class="bi bi-pen"></i> Signer
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary btn-edit-ligne"
                                            data-id="{{ $ligne->id }}"
                                            data-type-cible="{{ $ligne->type_cible }}"
                                            data-direction-id="{{ $ligne->direction_id }}"
                                            data-service-id="{{ $ligne->service_id }}"
                                            data-nom="{{ $ligne->nom_receptionniste }}"
                                            data-date="{{ $ligne->date_livraison?->format('Y-m-d') }}"
                                            data-observation="{{ $ligne->observation }}"
                                            title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger btn-delete-ligne"
                                            data-id="{{ $ligne->id }}"
                                            title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @else
                                    @if($ligne->affectation_id)
                                        <span class="text-muted small">
                                            <i class="bi bi-link-45deg"></i>
                                            Affectation créée
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Aucun équipement dans ce bon.<br>
                            <button class="btn btn-primary btn-sm mt-2" id="btn-add-ligne-empty">
                                <i class="bi bi-plus me-1"></i>Ajouter le premier équipement
                            </button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Modal Ajouter Équipement ── --}}
<div class="modal fade" id="modal-add-ligne" tabindex="-1" aria-labelledby="modal-add-ligne-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="form-add-ligne" novalidate>
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modal-add-ligne-label">
                        <i class="bi bi-plus-circle me-2 text-primary"></i>Ajouter un Équipement
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Équipement (N° Série ou Code Inventaire) <span class="text-danger">*</span></label>
                        <input type="text" id="search-equipement" class="form-control"
                               placeholder="Rechercher par code inventaire, numéro de série..." autocomplete="off">
                        <div id="equipement-suggestions" class="list-group mt-1 shadow-sm" style="display:none; max-height:200px; overflow-y:auto; z-index:1060; position:relative"></div>
                        <input type="hidden" name="equipement_id" id="selected-equipement-id">
                        <div id="equipement-selected-info" class="alert alert-info mt-2 py-2" style="display:none">
                            <i class="bi bi-check-circle me-1"></i>
                            <span id="equipement-selected-text"></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Destination <span class="text-danger">*</span></label>
                        <select name="type_cible" id="type-cible-select" class="form-select" required>
                            <option value="">— Choisir le type —</option>
                            <option value="DIRECTION">Direction</option>
                            <option value="SERVICE">Service</option>
                        </select>
                    </div>

                    <div id="direction-group" class="mb-3" style="display:none">
                        <label class="form-label fw-semibold">Direction</label>
                        <select name="direction_id" id="direction-select" class="form-select">
                            <option value="">— Choisir une direction —</option>
                            @foreach($directions as $d)
                                <option value="{{ $d->id }}">{{ $d->libelle }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="service-group" class="mb-3" style="display:none">
                        <label class="form-label fw-semibold">Service</label>
                        <select name="service_id" id="service-select" class="form-select">
                            <option value="">— Choisir un service —</option>
                            @foreach($services as $s)
                                <option value="{{ $s->id }}" data-direction="{{ $s->direction_id }}">{{ $s->libelle }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nom du réceptionniste</label>
                            <input type="text" name="nom_receptionniste" class="form-control"
                                   placeholder="Nom et prénom du responsable...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date de livraison</label>
                            <input type="date" name="date_livraison" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3 mt-3">
                        <label class="form-label fw-semibold">Observation</label>
                        <textarea name="observation" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-ligne">
                        <i class="bi bi-plus-lg me-1"></i>Ajouter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Modal Modifier Ligne ── --}}
<div class="modal fade" id="modal-edit-ligne" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="form-edit-ligne" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil me-2"></i>Modifier la Ligne</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit-ligne-id">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Destination <span class="text-danger">*</span></label>
                        <select name="type_cible" id="edit-type-cible-select" class="form-select" required>
                            <option value="DIRECTION">Direction</option>
                            <option value="SERVICE">Service</option>
                        </select>
                    </div>
                    <div id="edit-direction-group" class="mb-3">
                        <label class="form-label fw-semibold">Direction</label>
                        <select name="direction_id" id="edit-direction-select" class="form-select">
                            <option value="">— Choisir une direction —</option>
                            @foreach($directions as $d)
                                <option value="{{ $d->id }}">{{ $d->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="edit-service-group" class="mb-3" style="display:none">
                        <label class="form-label fw-semibold">Service</label>
                        <select name="service_id" id="edit-service-select" class="form-select">
                            <option value="">— Choisir un service —</option>
                            @foreach($services as $s)
                                <option value="{{ $s->id }}" data-direction="{{ $s->direction_id }}">{{ $s->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nom du réceptionniste</label>
                            <input type="text" name="nom_receptionniste" id="edit-nom-receptionniste" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date de livraison</label>
                            <input type="date" name="date_livraison" id="edit-date-livraison" class="form-control">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-semibold">Observation</label>
                        <textarea name="observation" id="edit-observation" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Modal Signer ── --}}
<div class="modal fade" id="modal-signer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-success">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-pen me-2"></i>Confirmer la Réception</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-success border-success mb-3">
                    <strong>Équipement :</strong> <span id="signer-code-inventaire"></span><br>
                    <strong>Destination :</strong> <span id="signer-cible"></span>
                </div>
                <p class="text-muted">
                    En confirmant, vous créez automatiquement une <strong>affectation</strong> pour cet équipement
                    et son statut passera à <strong>En service</strong>.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="btn-confirm-signer">
                    <i class="bi bi-check-all me-1"></i>Confirmer la Réception
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal Impression PDF ── --}}
<div class="modal fade" id="printPdfModal" tabindex="-1" aria-labelledby="printPdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold" id="printPdfModalLabel">
                    <i class="bi bi-file-pdf me-2 text-white"></i>Aperçu du Bon de Répartition (Format Paysage)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="pdf-viewer-iframe" src="" style="width: 100%; height: 70vh; border: none;"></iframe>
            </div>
            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-1 px-3" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-sm btn-primary rounded-1 px-3" id="btn-modal-print">
                    <i class="bi bi-printer me-1"></i>Imprimer
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>
const BON_ID = {{ $bon->id }};
const ROUTE_ADD_LIGNE = "{{ route('parc-info.bons-repartition.lignes.add', $bon->id) }}";
const ROUTE_UPDATE_LIGNE = "{{ url('parc-info/informatique/bons-repartition') }}/{{ $bon->id }}/lignes/:ligneId";
const ROUTE_DELETE_LIGNE = "{{ url('parc-info/informatique/bons-repartition') }}/{{ $bon->id }}/lignes/:ligneId";
const ROUTE_SIGNER_LIGNE = "{{ url('parc-info/informatique/bons-repartition') }}/{{ $bon->id }}/lignes/:ligneId/signer";
const ROUTE_SEARCH_EQ = "{{ route('parc-info.search-equipements') }}";
const CSRF = document.querySelector('meta[name=csrf-token]').content;

// ── Type Cible toggle ────────────────────────────────────────────────────────
function toggleDestination(select, prefix = '') {
    const val = select.value;
    document.getElementById(prefix + 'direction-group').style.display = val === 'DIRECTION' ? '' : 'none';
    document.getElementById(prefix + 'service-group').style.display   = val === 'SERVICE'   ? '' : 'none';
}

document.getElementById('type-cible-select').addEventListener('change', function() {
    toggleDestination(this, '');
});
document.getElementById('edit-type-cible-select').addEventListener('change', function() {
    toggleDestination(this, 'edit-');
});

// ── Équipement search ────────────────────────────────────────────────────────
let searchTimeout;
document.getElementById('search-equipement').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    if (q.length < 2) {
        document.getElementById('equipement-suggestions').style.display = 'none';
        return;
    }
    searchTimeout = setTimeout(async () => {
        const res = await fetch(`${ROUTE_SEARCH_EQ}?q=${encodeURIComponent(q)}&stock_only=1`);
        const data = await res.json();
        const box = document.getElementById('equipement-suggestions');
        box.innerHTML = '';
        if (!data.length) {
            box.innerHTML = '<div class="list-group-item text-muted">Aucun équipement en stock trouvé</div>';
        } else {
            data.slice(0, 10).forEach(eq => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action';
                item.innerHTML = `<strong>${eq.code_inventaire}</strong> — ${eq.numero_serie} — <span class="text-muted">${eq.modele ?? ''}</span>`;
                item.addEventListener('click', () => {
                    document.getElementById('selected-equipement-id').value = eq.id;
                    document.getElementById('search-equipement').value = eq.code_inventaire + ' — ' + eq.numero_serie;
                    document.getElementById('equipement-selected-info').style.display = '';
                    document.getElementById('equipement-selected-text').textContent =
                        `${eq.code_inventaire} — ${eq.numero_serie} sélectionné`;
                    box.style.display = 'none';
                });
                box.appendChild(item);
            });
        }
        box.style.display = '';
    }, 300);
});

// ── Add Ligne ────────────────────────────────────────────────────────────────
function openAddModal() {
    document.getElementById('form-add-ligne').reset();
    document.getElementById('selected-equipement-id').value = '';
    document.getElementById('equipement-selected-info').style.display = 'none';
    document.getElementById('direction-group').style.display = 'none';
    document.getElementById('service-group').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modal-add-ligne')).show();
}

document.getElementById('btn-add-ligne').addEventListener('click', openAddModal);
document.getElementById('btn-add-ligne-empty')?.addEventListener('click', openAddModal);

document.getElementById('form-add-ligne').addEventListener('submit', async (e) => {
    e.preventDefault();
    const eqId = document.getElementById('selected-equipement-id').value;
    if (!eqId) { alert('Veuillez sélectionner un équipement.'); return; }

    const btn = document.getElementById('btn-submit-ligne');
    btn.disabled = true;
    try {
        const fd = new FormData(e.target);
        const res = await fetch(ROUTE_ADD_LIGNE, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            body: fd
        });
        const json = await res.json();
        if (json.success) {
            bootstrap.Modal.getInstance(document.getElementById('modal-add-ligne')).hide();
            location.reload();
        } else {
            alert(json.message || 'Erreur.');
        }
    } finally {
        btn.disabled = false;
    }
});

// ── Edit Ligne ───────────────────────────────────────────────────────────────
document.getElementById('lignes-table').addEventListener('click', (e) => {
    const editBtn = e.target.closest('.btn-edit-ligne');
    if (editBtn) {
        const id = editBtn.dataset.id;
        document.getElementById('edit-ligne-id').value = id;
        const typeCible = editBtn.dataset.typeCible;
        const sel = document.getElementById('edit-type-cible-select');
        sel.value = typeCible;
        toggleDestination(sel, 'edit-');

        document.getElementById('edit-direction-select').value = editBtn.dataset.directionId || '';
        document.getElementById('edit-service-select').value = editBtn.dataset.serviceId || '';
        document.getElementById('edit-nom-receptionniste').value = editBtn.dataset.nom || '';
        document.getElementById('edit-date-livraison').value = editBtn.dataset.date || '';
        document.getElementById('edit-observation').value = editBtn.dataset.observation || '';

        new bootstrap.Modal(document.getElementById('modal-edit-ligne')).show();
        return;
    }

    // Delete ligne
    const deleteBtn = e.target.closest('.btn-delete-ligne');
    if (deleteBtn) {
        if (!confirm('Retirer cet équipement du bon ?')) { return; }
        const id = deleteBtn.dataset.id;
        fetch(ROUTE_DELETE_LIGNE.replace(':ligneId', id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        }).then(r => r.json()).then(json => {
            if (json.success) { location.reload(); }
            else { alert(json.message); }
        });
    }
});

document.getElementById('form-edit-ligne').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('edit-ligne-id').value;
    const fd = new FormData(e.target);
    const data = {};
    fd.forEach((v, k) => { if (k !== '_method' && k !== '_token') { data[k] = v; } });

    const res = await fetch(ROUTE_UPDATE_LIGNE.replace(':ligneId', id), {
        method: 'PUT',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(data)
    });
    const json = await res.json();
    if (json.success) {
        bootstrap.Modal.getInstance(document.getElementById('modal-edit-ligne')).hide();
        location.reload();
    } else {
        alert(json.message || 'Erreur.');
    }
});

// ── Signer ───────────────────────────────────────────────────────────────────
let currentSignerLigneId = null;

document.getElementById('lignes-table').addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-signer');
    if (!btn) { return; }
    currentSignerLigneId = btn.dataset.id;
    document.getElementById('signer-code-inventaire').textContent = btn.dataset.code;
    document.getElementById('signer-cible').textContent = btn.dataset.cible;
    new bootstrap.Modal(document.getElementById('modal-signer')).show();
});

document.getElementById('btn-confirm-signer').addEventListener('click', async () => {
    if (!currentSignerLigneId) { return; }
    const btn = document.getElementById('btn-confirm-signer');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Traitement...';

    try {
        const res = await fetch(ROUTE_SIGNER_LIGNE.replace(':ligneId', currentSignerLigneId), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        });
        const json = await res.json();
        if (json.success) {
            bootstrap.Modal.getInstance(document.getElementById('modal-signer')).hide();
            location.reload();
        } else {
            alert(json.message || 'Erreur lors de la signature.');
        }
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-all me-1"></i>Confirmer la Réception';
    }
});

// ── Print PDF in Modal Iframe ──
document.getElementById('btn-print-bon').addEventListener('click', function() {
    const pdfUrl = "{{ route('parc-info.bons-repartition.imprimer', $bon->id) }}";
    document.getElementById('pdf-viewer-iframe').src = pdfUrl;
    new bootstrap.Modal(document.getElementById('printPdfModal')).show();
});

// ── Print PDF from the Modal ──
document.getElementById('btn-modal-print').addEventListener('click', function() {
    const iframe = document.getElementById('pdf-viewer-iframe');
    if (iframe) {
        try {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        } catch (e) {
            // Fallback: open in new tab and print if contentWindow.print is blocked by browser policies
            const w = window.open(iframe.src, '_blank');
            if (w) {
                w.print();
            }
        }
    }
});
</script>
@endpush
