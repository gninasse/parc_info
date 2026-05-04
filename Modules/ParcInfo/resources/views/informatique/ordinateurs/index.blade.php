@extends('parcinfo::layouts.master')

@section('header', 'Ordinateurs Fixes')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item active">Ordinateurs Fixes</li>
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
                <div class="rounded-3 bg-primary bg-opacity-10 p-3"><i class="bi bi-pc-display fs-4 text-primary"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Total Parc</div>
                    <div class="fw-bold fs-4" id="kpi-total">—</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-3"><i class="bi bi-check-circle fs-4 text-success"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">En Service</div>
                    <div class="fw-bold fs-4 text-success" id="kpi-service">—</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 p-3"><i class="bi bi-tools fs-4 text-warning"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">En Réparation</div>
                    <div class="fw-bold fs-4 text-warning" id="kpi-reparation">—</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-secondary bg-opacity-10 p-3"><i class="bi bi-archive fs-4 text-secondary"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">En Stock</div>
                    <div class="fw-bold fs-4 text-secondary" id="kpi-stock">—</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Filtres ── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Site géographique</label>
                <select class="form-select form-select-sm" id="filter-site">
                    <option value="">Tous les sites</option>
                    @foreach($sites as $s)
                        <option value="{{ $s->id }}">{{ $s->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Direction</label>
                <select class="form-select form-select-sm" id="filter-direction">
                    <option value="">Toutes les directions</option>
                    @foreach($directions as $d)
                        <option value="{{ $d->id }}">{{ $d->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Statut</label>
                <select class="form-select form-select-sm" id="filter-statut">
                    <option value="">Tous les statuts</option>
                    <option value="en_service">En service</option>
                    <option value="en_stock">En stock</option>
                    <option value="en_reparation">En réparation</option>
                    <option value="perdu">Perdu / Volé</option>
                    <option value="reforme">Réformé</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary btn-sm w-100" id="btn-apply-filters">
                    <i class="bi bi-funnel me-1"></i> Appliquer
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
        <h6 class="mb-0 fw-bold">Liste des Ordinateurs Fixes</h6>
        <button class="btn btn-primary btn-sm px-3" id="btn-add">
            <i class="bi bi-plus-lg me-1"></i> Ajouter un ordinateur
        </button>
    </div>
    <div class="card-body p-0">
        <div id="toolbar" class="px-3 pt-2 pb-1 d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" id="btn-edit" disabled title="Modifier">
                <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" id="btn-delete" disabled title="Supprimer">
                <i class="bi bi-trash"></i>
            </button>
        </div>
        <table id="ordinateurs-table"
               data-toggle="table"
               data-url="{{ route('parc-info.ordinateurs-fixes.data') }}"
               data-pagination="true"
               data-side-pagination="server"
               data-search="true"
               data-show-refresh="true"
               data-show-columns="true"
               data-toolbar="#toolbar"
               data-click-to-select="true"
               data-single-select="true"
               data-id-field="id"
               data-page-list="[10,25,50,100]"
               data-page-size="25"
               data-query-params="ordinateursQueryParams"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="code_inventaire" data-sortable="true" data-formatter="codeFormatter">Code Inventaire</th>
                    <th data-field="marque_modele" data-sortable="true">Marque & Modèle</th>
                    <th data-field="os">OS</th>
                    <th data-field="config">Config Matérielle</th>
                    <th data-field="statut" data-formatter="statutFormatter">Statut</th>
                    <th data-field="affectation">Affectation</th>
                    <th data-field="id" data-formatter="actionsFormatter" data-events="actionsEvents">Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('parcinfo::informatique.ordinateurs._wizard')
@include('parcinfo::informatique.ordinateurs._selection_modals')
@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/parc-info/ordinateurs/index.js') }}?v={{ time() }}"></script>
<script>
// Gestion des modales de sélection
$(document).ready(function() {
    let selectedEmploye = null;

    // Ouvrir modale employé
    $(document).on('click', '.aff-type-card[data-value="EMPLOYE"]', function(e) {
        e.preventDefault();
        $(this).find('input[type="radio"]').prop('checked', true);
        $('#employeSelectionModal').modal('show');
        loadEmployes();
    });

    // Charger employés
    function loadEmployes() {
        $('#emp-skeleton').removeClass('d-none');
        $('#emp-list').html('');
        
        const filters = {
            direction_id: $('#emp-filter-direction').val(),
            service_id: $('#emp-filter-service').val(),
            statut: $('#emp-filter-statut').val(),
            search: $('#emp-search').val()
        };

        $.get('/grh/employes/api', filters, function(data) {
            $('#emp-skeleton').addClass('d-none');
            const html = data.map(emp => `
                <tr data-id="${emp.id}" data-nom="${emp.nom_complet}" 
                    data-matricule="${emp.matricule}" data-poste="${emp.poste || '—'}" 
                    data-rattachement="${emp.rattachement || '—'}">
                    <td><input type="radio" name="emp_select" value="${emp.id}" class="form-check-input"></td>
                    <td><span class="emp-cell">${emp.matricule}</span></td>
                    <td><span class="emp-cell">${emp.nom_complet}</span></td>
                    <td><span class="emp-cell">${emp.poste || '—'}</span></td>
                    <td><span class="emp-cell">${emp.niveau || '—'}</span></td>
                    <td><span class="emp-cell">${emp.rattachement || '—'}</span></td>
                    <td><span class="badge bg-${emp.statut === 'actif' ? 'success' : 'secondary'}">${emp.statut}</span></td>
                </tr>
            `).join('');
            $('#emp-list').html(html);
            
            // Événements clic et double-clic
            $('#emp-list').off('click dblclick').on('click', '.emp-cell', function() {
                $(this).closest('tr').find('input[type="radio"]').prop('checked', true).trigger('change');
            }).on('dblclick', '.emp-cell', function() {
                $(this).closest('tr').find('input[type="radio"]').prop('checked', true).trigger('change');
                setTimeout(() => $('#emp-confirm').click(), 100);
            });
        });
    }

    // Sélection employé
    $(document).on('change', 'input[name="emp_select"]', function() {
        $('#emp-confirm').prop('disabled', false);
        const row = $(this).closest('tr');
        selectedEmploye = {
            id: row.data('id'),
            nom: row.data('nom'),
            matricule: row.data('matricule'),
            poste: row.data('poste'),
            rattachement: row.data('rattachement')
        };
    });

    // Confirmer employé
    $('#emp-confirm').on('click', function() {
        if (!selectedEmploye) return;
        
        $('#emp-summary-nom').text(selectedEmploye.nom);
        $('#emp-summary-matricule').text(selectedEmploye.matricule);
        $('#emp-summary-poste').text(selectedEmploye.poste);
        $('#emp-summary-rattachement').text(selectedEmploye.rattachement);
        $('#dossier_employe_id').val(selectedEmploye.id);
        
        // Masquer les autres cartes et vider les champs
        $('#aff-poste-summary, #aff-local-summary').addClass('d-none');
        $('#poste_travail_id, #local_id').val('');
        $('.aff-type-card').removeClass('selected');
        
        $('#aff-employe-summary').removeClass('d-none');
        $('#aff-skip-hint').addClass('d-none');
        $('.aff-type-card[data-value="EMPLOYE"]').addClass('selected');
        
        $('#employeSelectionModal').modal('hide');
    });

    // Filtres
    $('#emp-filter-direction, #emp-filter-service, #emp-filter-statut').on('change', loadEmployes);
    let searchTimeout;
    $('#emp-search').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(loadEmployes, 300);
    });

    // Ouvrir modale poste
    $(document).on('click', '.aff-type-card[data-value="POSTE"]', function(e) {
        e.preventDefault();
        $(this).find('input[type="radio"]').prop('checked', true);
        $('#posteSelectionModal').modal('show');
        loadPostes();
    });

    // Charger postes
    function loadPostes() {
        $('#poste-skeleton').removeClass('d-none');
        $('#poste-list').html('');
        
        const filters = {
            direction_id: $('#poste-filter-direction').val(),
            service_id: $('#poste-filter-service').val(),
            statut: $('#poste-filter-statut').val(),
            search: $('#poste-search').val()
        };

        $.get('/organisation/postes-travail/api', filters, function(data) {
            $('#poste-skeleton').addClass('d-none');
            const html = data.map(p => `
                <tr data-id="${p.id}" data-code="${p.code}" 
                    data-libelle="${p.libelle}" data-emplacement="${p.emplacement}">
                    <td><input type="radio" name="poste_select" value="${p.id}" class="form-check-input"></td>
                    <td><span class="poste-cell">${p.code}</span></td>
                    <td><span class="poste-cell">${p.libelle}</span></td>
                    <td><span class="poste-cell">${p.direction || '—'}</span></td>
                    <td><span class="poste-cell">${p.service || '—'}</span></td>
                    <td><span class="poste-cell">${p.emplacement}</span></td>
                    <td><span class="poste-cell">${p.occupant}</span></td>
                    <td><span class="badge bg-${p.statut === 'actif' ? 'success' : 'secondary'}">${p.statut}</span></td>
                </tr>
            `).join('');
            $('#poste-list').html(html);
            
            // Événements clic et double-clic
            $('#poste-list').off('click dblclick').on('click', '.poste-cell', function() {
                $(this).closest('tr').find('input[type="radio"]').prop('checked', true).trigger('change');
            }).on('dblclick', '.poste-cell', function() {
                $(this).closest('tr').find('input[type="radio"]').prop('checked', true).trigger('change');
                setTimeout(() => $('#poste-confirm').click(), 100);
            });
        });
    }

    // Sélection poste
    let selectedPoste = null;
    $(document).on('change', 'input[name="poste_select"]', function() {
        $('#poste-confirm').prop('disabled', false);
        const row = $(this).closest('tr');
        selectedPoste = {
            id: row.data('id'),
            code: row.data('code'),
            libelle: row.data('libelle'),
            emplacement: row.data('emplacement')
        };
    });

    // Confirmer poste
    $('#poste-confirm').on('click', function() {
        if (!selectedPoste) return;
        
        $('#poste-summary-code').text(selectedPoste.code);
        $('#poste-summary-libelle').text(selectedPoste.libelle);
        $('#poste-summary-emplacement').text(selectedPoste.emplacement);
        $('#poste_travail_id').val(selectedPoste.id);
        
        // Masquer les autres cartes et vider les champs
        $('#aff-employe-summary, #aff-local-summary').addClass('d-none');
        $('#dossier_employe_id, #local_id').val('');
        $('.aff-type-card').removeClass('selected');
        
        $('#aff-poste-summary').removeClass('d-none');
        $('#aff-skip-hint').addClass('d-none');
        $('.aff-type-card[data-value="POSTE"]').addClass('selected');
        
        $('#posteSelectionModal').modal('hide');
    });

    // Filtres postes
    $('#poste-filter-direction, #poste-filter-service, #poste-filter-statut').on('change', loadPostes);
    let posteSearchTimeout;
    $('#poste-search').on('input', function() {
        clearTimeout(posteSearchTimeout);
        posteSearchTimeout = setTimeout(loadPostes, 300);
    });

    // Ouvrir modale local
    $(document).on('click', '.aff-type-card[data-value="LOCAL"]', function(e) {
        e.preventDefault();
        $(this).find('input[type="radio"]').prop('checked', true);
        $('#localSelectionModal').modal('show');
        loadLocaux();
    });

    // Charger locaux
    function loadLocaux() {
        $('#local-skeleton').removeClass('d-none');
        $('#local-list').html('');
        
        const filters = {
            site_id: $('#local-filter-site').val(),
            batiment_id: $('#local-filter-batiment').val(),
            etage_id: $('#local-filter-etage').val(),
            search: $('#local-search').val()
        };

        $.get('/organisation/locaux/api', filters, function(data) {
            $('#local-skeleton').addClass('d-none');
            const html = data.map(l => `
                <tr data-id="${l.id}" data-code="${l.code}" 
                    data-libelle="${l.libelle}" data-type="${l.type || '—'}" 
                    data-etage="${l.etage || '—'}" data-batiment="${l.batiment || '—'}">
                    <td><input type="radio" name="local_select" value="${l.id}" class="form-check-input"></td>
                    <td><span class="local-cell">${l.code}</span></td>
                    <td><span class="local-cell">${l.libelle}</span></td>
                    <td><span class="local-cell">${l.type || '—'}</span></td>
                    <td><span class="local-cell">${l.superficie ? l.superficie + ' m²' : '—'}</span></td>
                    <td><span class="local-cell">${l.etage || '—'}</span></td>
                    <td><span class="local-cell">${l.batiment || '—'}</span></td>
                    <td><span class="local-cell">${l.site || '—'}</span></td>
                    <td><span class="badge bg-${l.statut === 'actif' ? 'success' : 'secondary'}">${l.statut}</span></td>
                </tr>
            `).join('');
            $('#local-list').html(html);
            
            // Événements clic et double-clic
            $('#local-list').off('click dblclick').on('click', '.local-cell', function() {
                $(this).closest('tr').find('input[type="radio"]').prop('checked', true).trigger('change');
            }).on('dblclick', '.local-cell', function() {
                $(this).closest('tr').find('input[type="radio"]').prop('checked', true).trigger('change');
                setTimeout(() => $('#local-confirm').click(), 100);
            });
        });
    }

    // Sélection local
    let selectedLocal = null;
    $(document).on('change', 'input[name="local_select"]', function() {
        $('#local-confirm').prop('disabled', false);
        const row = $(this).closest('tr');
        selectedLocal = {
            id: row.data('id'),
            code: row.data('code'),
            libelle: row.data('libelle'),
            type: row.data('type'),
            etage: row.data('etage'),
            batiment: row.data('batiment')
        };
    });

    // Confirmer local
    $('#local-confirm').on('click', function() {
        if (!selectedLocal) return;
        
        $('#local-summary-code').text(selectedLocal.code);
        $('#local-summary-libelle').text(selectedLocal.libelle);
        $('#local-summary-type').text(selectedLocal.type);
        $('#local-summary-etage').text(selectedLocal.etage);
        $('#local-summary-batiment').text(selectedLocal.batiment);
        $('#local_id').val(selectedLocal.id);
        
        // Masquer les autres cartes et vider les champs
        $('#aff-employe-summary, #aff-poste-summary').addClass('d-none');
        $('#dossier_employe_id, #poste_travail_id').val('');
        $('.aff-type-card').removeClass('selected');
        
        $('#aff-local-summary').removeClass('d-none');
        $('#aff-skip-hint').addClass('d-none');
        $('.aff-type-card[data-value="LOCAL"]').addClass('selected');
        
        $('#localSelectionModal').modal('hide');
    });

    // Filtres locaux
    $('#local-filter-site, #local-filter-batiment, #local-filter-etage').on('change', loadLocaux);
    let localSearchTimeout;
    $('#local-search').on('input', function() {
        clearTimeout(localSearchTimeout);
        localSearchTimeout = setTimeout(loadLocaux, 300);
    });

    // Charger services selon direction (pour employés et postes)
    $('#emp-filter-direction, #poste-filter-direction').on('change', function() {
        const directionId = $(this).val();
        const targetSelect = $(this).attr('id').includes('emp') ? '#emp-filter-service' : '#poste-filter-service';
        
        if (!directionId) {
            $(targetSelect).html('<option value="">Tous les services</option>');
            return;
        }
        
        $.get(`/organisation/directions/${directionId}/services`, function(services) {
            const options = '<option value="">Tous les services</option>' + 
                services.map(s => `<option value="${s.id}">${s.libelle}</option>`).join('');
            $(targetSelect).html(options);
        });
    });

    // Charger bâtiments selon site
    $('#local-filter-site').on('change', function() {
        const siteId = $(this).val();
        
        if (!siteId) {
            $('#local-filter-batiment').html('<option value="">Tous les bâtiments</option>');
            $('#local-filter-etage').html('<option value="">Tous les étages</option>');
            return;
        }
        
        $.get(`/organisation/sites/${siteId}/batiments`, function(batiments) {
            const options = '<option value="">Tous les bâtiments</option>' + 
                batiments.map(b => `<option value="${b.id}">${b.libelle}</option>`).join('');
            $('#local-filter-batiment').html(options);
            $('#local-filter-etage').html('<option value="">Tous les étages</option>');
        });
    });

    // Charger étages selon bâtiment
    $('#local-filter-batiment').on('change', function() {
        const batimentId = $(this).val();
        
        if (!batimentId) {
            $('#local-filter-etage').html('<option value="">Tous les étages</option>');
            return;
        }
        
        $.get(`/organisation/batiments/${batimentId}/etages`, function(etages) {
            const options = '<option value="">Tous les étages</option>' + 
                etages.map(e => `<option value="${e.id}">${e.libelle}</option>`).join('');
            $('#local-filter-etage').html(options);
        });
    });
});
</script>
@endpush
