@extends('parcinfo::layouts.master')

@section('header', 'Bons de Répartition')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item active">Bons de Répartition</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')

{{-- ── KPI Cards ── --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 p-3"><i class="bi bi-clipboard-check fs-4 text-primary"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Total Bons</div>
                    <div class="fw-bold fs-4" id="kpi-total">—</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 p-3"><i class="bi bi-hourglass-split fs-4 text-warning"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">En Cours</div>
                    <div class="fw-bold fs-4 text-warning" id="kpi-en-cours">—</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-3"><i class="bi bi-check2-all fs-4 text-success"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Clôturés</div>
                    <div class="fw-bold fs-4 text-success" id="kpi-clotures">—</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info bg-opacity-10 p-3"><i class="bi bi-list-check fs-4 text-info"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Lignes en attente</div>
                    <div class="fw-bold fs-4 text-info" id="kpi-attente">—</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Filtres ── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Fournisseur</label>
                <select class="form-select form-select-sm" id="filter-fournisseur">
                    <option value="">Tous</option>
                    @foreach($fournisseurs as $f)
                        <option value="{{ $f->id }}">{{ $f->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Statut</label>
                <select class="form-select form-select-sm" id="filter-statut">
                    <option value="">Tous</option>
                    <option value="en_cours">En cours</option>
                    <option value="cloture">Clôturé</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Du</label>
                <input type="date" class="form-control form-control-sm" id="filter-date-debut">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Au</label>
                <input type="date" class="form-control form-control-sm" id="filter-date-fin">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary btn-sm w-100" id="btn-apply-filters">
                    <i class="bi bi-funnel me-1"></i> Filtrer
                </button>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary btn-sm w-100" id="btn-reset-filters">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Table ── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">Liste des Bons de Répartition</h6>
        <button class="btn btn-primary btn-sm" id="btn-create-bon">
            <i class="bi bi-plus-lg me-1"></i> Nouveau Bon
        </button>
    </div>
    <div class="card-body p-0">
        <table id="bons-table"
               data-toggle="table"
               data-url="{{ route('parc-info.bons-repartition.data') }}"
               data-pagination="true"
               data-side-pagination="server"
               data-search="true"
               data-show-refresh="true"
               data-show-columns="true"
               data-click-to-select="false"
               data-id-field="id"
               data-page-list="[10,25,50]"
               data-page-size="25"
               data-query-params="bonsQueryParams"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="numero_bon" data-sortable="true" data-formatter="numeroBonFormatter">N° Bon</th>
                    <th data-field="date_bon" data-sortable="true">Date</th>
                    <th data-field="fournisseur">Fournisseur</th>
                    <th data-field="nb_lignes">Équipements</th>
                    <th data-field="progression" data-formatter="progressionFormatter">Progression</th>
                    <th data-field="statut" data-formatter="statutBonFormatter">Statut</th>
                    <th data-field="id" data-formatter="actionsBonFormatter">Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

{{-- ── Modal Nouveau Bon ── --}}
<div class="modal fade" id="modal-create-bon" tabindex="-1" aria-labelledby="modal-create-bon-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-create-bon" novalidate>
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modal-create-bon-label">
                        <i class="bi bi-clipboard-plus me-2 text-primary"></i>Nouveau Bon de Répartition
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Date du Bon <span class="text-danger">*</span></label>
                        <input type="date" name="date_bon" class="form-control" required
                               value="{{ now()->format('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fournisseur</label>
                        <select name="fournisseur_id" class="form-select">
                            <option value="">— Aucun fournisseur —</option>
                            @foreach($fournisseurs as $f)
                                <option value="{{ $f->id }}">{{ $f->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Observation</label>
                        <textarea name="observation" class="form-control" rows="3" placeholder="Remarques éventuelles..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-bon">
                        <i class="bi bi-check-lg me-1"></i> Créer le Bon
                    </button>
                </div>
            </form>
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
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script>
const ROUTE_STORE = "{{ route('parc-info.bons-repartition.store') }}";
const ROUTE_SHOW  = "{{ route('parc-info.bons-repartition.show', ':id') }}";
const ROUTE_DELETE = "{{ route('parc-info.bons-repartition.destroy', ':id') }}";
const ROUTE_IMPRIMER = "{{ route('parc-info.bons-repartition.imprimer', ':id') }}";

// ── Query Params ────────────────────────────────────────────────────────────
function bonsQueryParams(params) {
    return Object.assign(params, {
        fournisseur_id: document.getElementById('filter-fournisseur')?.value,
        statut:         document.getElementById('filter-statut')?.value,
        date_debut:     document.getElementById('filter-date-debut')?.value,
        date_fin:       document.getElementById('filter-date-fin')?.value,
    });
}

// ── Formatters ──────────────────────────────────────────────────────────────
function numeroBonFormatter(val, row) {
    return `<a href="${ROUTE_SHOW.replace(':id', row.id)}" class="fw-semibold text-decoration-none">
        <i class="bi bi-clipboard2-check me-1"></i>${val}
    </a>`;
}

function progressionFormatter(val, row) {
    const color = val === 100 ? 'success' : val > 0 ? 'warning' : 'secondary';
    return `<div class="d-flex align-items-center gap-2" style="min-width:120px">
        <div class="progress flex-grow-1" style="height:8px">
            <div class="progress-bar bg-${color}" style="width:${val}%"></div>
        </div>
        <small class="text-muted">${row.nb_signees}/${row.nb_lignes}</small>
    </div>`;
}

function statutBonFormatter(val, row) {
    const map = { 'Clôturé': 'success', 'En cours': 'warning', 'Vide': 'secondary' };
    const color = map[val] || 'secondary';
    return `<span class="badge bg-${color}-subtle text-${color} border border-${color}-subtle">${val}</span>`;
}

function actionsBonFormatter(val, row) {
    return `<div class="d-flex gap-1">
        <a href="${ROUTE_SHOW.replace(':id', val)}" class="btn btn-sm btn-outline-primary" title="Voir">
            <i class="bi bi-eye"></i>
        </a>
        <button class="btn btn-sm btn-outline-secondary btn-print-bon" data-id="${val}" title="Imprimer">
            <i class="bi bi-printer"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger btn-delete-bon" data-id="${val}" title="Supprimer">
            <i class="bi bi-trash"></i>
        </button>
    </div>`;
}

// ── Load table & KPIs ───────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const table = document.getElementById('bons-table');

    table.addEventListener('load-success.bs.table', (e) => {
        const { total, rows } = e.detail[0];
        document.getElementById('kpi-total').textContent = total;
        const enCours = rows.filter(r => r.statut === 'En cours').length;
        const clotures = rows.filter(r => r.statut === 'Clôturé').length;
        const attente = rows.reduce((acc, r) => acc + (r.nb_lignes - r.nb_signees), 0);
        document.getElementById('kpi-en-cours').textContent = enCours;
        document.getElementById('kpi-clotures').textContent = clotures;
        document.getElementById('kpi-attente').textContent = attente;
    });

    // Filters
    document.getElementById('btn-apply-filters').addEventListener('click', () => {
        $(table).bootstrapTable('refresh');
    });
    document.getElementById('btn-reset-filters').addEventListener('click', () => {
        document.getElementById('filter-fournisseur').value = '';
        document.getElementById('filter-statut').value = '';
        document.getElementById('filter-date-debut').value = '';
        document.getElementById('filter-date-fin').value = '';
        $(table).bootstrapTable('refresh');
    });

    // Create bon
    document.getElementById('btn-create-bon').addEventListener('click', () => {
        new bootstrap.Modal(document.getElementById('modal-create-bon')).show();
    });

    document.getElementById('form-create-bon').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btn-submit-bon');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Création...';

        try {
            const fd = new FormData(e.target);
            const res = await fetch(ROUTE_STORE, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: fd
            });
            const json = await res.json();
            if (json.success) {
                window.location.href = json.redirect;
            } else {
                toastError(json.message || 'Erreur lors de la création.');
            }
        } catch {
            toastError('Une erreur est survenue.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Créer le Bon';
        }
    });

    // Delete bon
    table.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-delete-bon');
        if (!btn) { return; }
        if (!confirm('Supprimer ce bon ? Cette action est irréversible.')) { return; }

        const id = btn.dataset.id;
        const res = await fetch(ROUTE_DELETE.replace(':id', id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json'
            }
        });
        const json = await res.json();
        if (json.success) {
            $(table).bootstrapTable('refresh');
        } else {
            alert(json.message || 'Suppression impossible.');
        }
    });

    // Print PDF in Modal
    table.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-print-bon');
        if (!btn) { return; }
        const id = btn.dataset.id;
        const pdfUrl = ROUTE_IMPRIMER.replace(':id', id);
        
        document.getElementById('pdf-viewer-iframe').src = pdfUrl;
        new bootstrap.Modal(document.getElementById('printPdfModal')).show();
    });

    // Print PDF from the Modal
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
});

function toastError(msg) {
    alert(msg);
}
</script>
@endpush
