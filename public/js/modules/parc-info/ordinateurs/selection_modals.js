/**
 * selection_modals.js
 * Gère les 3 modales de sélection (Employé, Poste, Local).
 * 
 * Après confirmation, émet un événement jQuery personnalisé :
 *   - 'employe:selected'  → { id, nom, matricule, poste, rattachement }
 *   - 'poste:selected'    → { id, code, libelle, emplacement }
 *   - 'local:selected'    → { id, code, libelle, type, etage, batiment }
 *
 * Les consommateurs (wizard index.js ou show.blade.php) écoutent ces événements
 * via $(document).on('employe:selected', ...) pour peupler leurs propres champs.
 */

$(document).ready(function () {

    // ════════════════════════════════════════════════════════════════════════
    // MODALE EMPLOYÉ
    // ════════════════════════════════════════════════════════════════════════

    let selectedEmploye = null;

    function loadEmployes() {
        $('#emp-skeleton').removeClass('d-none');
        $('#emp-list').html('');
        $('#emp-confirm').prop('disabled', true);
        selectedEmploye = null;

        const filters = {
            direction_id: $('#emp-filter-direction').val(),
            service_id:   $('#emp-filter-service').val(),
            statut:       $('#emp-filter-statut').val(),
            search:       $('#emp-search').val(),
        };

        $.get('/grh/employes/api', filters)
            .done(function (data) {
                $('#emp-skeleton').addClass('d-none');
                if (!data.length) {
                    $('#emp-list').html('<tr><td colspan="7" class="text-center text-muted py-3">Aucun résultat</td></tr>');
                    return;
                }
                const html = data.map(emp => `
                    <tr data-id="${emp.id}"
                        data-nom="${emp.nom_complet}"
                        data-matricule="${emp.matricule}"
                        data-poste="${emp.poste || '—'}"
                        data-rattachement="${emp.rattachement || '—'}">
                        <td><input type="radio" name="emp_select" value="${emp.id}" class="form-check-input"></td>
                        <td>${emp.matricule}</td>
                        <td>${emp.nom_complet}</td>
                        <td>${emp.poste || '—'}</td>
                        <td>${emp.niveau || '—'}</td>
                        <td>${emp.rattachement || '—'}</td>
                        <td><span class="badge bg-${emp.statut === 'actif' ? 'success' : 'secondary'}">${emp.statut}</span></td>
                    </tr>`).join('');
                $('#emp-list').html(html);
            })
            .fail(function () {
                $('#emp-skeleton').addClass('d-none');
                $('#emp-list').html('<tr><td colspan="7" class="text-center text-danger py-3">Erreur de chargement</td></tr>');
            });
    }

    // Ouverture — chargement automatique
    $('#employeSelectionModal').on('show.bs.modal', function () {
        loadEmployes();
    });

    // Clic sur une ligne → sélection radio
    $(document).on('click', '#emp-list tr', function () {
        $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
    });

    // Double-clic → confirme directement
    $(document).on('dblclick', '#emp-list tr', function () {
        $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
        setTimeout(() => $('#emp-confirm').trigger('click'), 100);
    });

    // Changement de radio
    $(document).on('change', 'input[name="emp_select"]', function () {
        const row = $(this).closest('tr');
        selectedEmploye = {
            id:           row.data('id'),
            nom:          row.data('nom'),
            matricule:    row.data('matricule'),
            poste:        row.data('poste'),
            rattachement: row.data('rattachement'),
        };
        $('#emp-confirm').prop('disabled', false);
        $('#emp-list tr').removeClass('table-active');
        row.addClass('table-active');
    });

    // Confirmation
    $('#emp-confirm').on('click', function () {
        if (!selectedEmploye) return;
        $(document).trigger('employe:selected', [selectedEmploye]);
        $('#employeSelectionModal').modal('hide');
    });

    // Filtres employé
    $('#emp-filter-direction, #emp-filter-service, #emp-filter-statut').on('change', loadEmployes);
    let empSearchTimer;
    $('#emp-search').on('input', function () {
        clearTimeout(empSearchTimer);
        empSearchTimer = setTimeout(loadEmployes, 300);
    });

    // Cascade direction → service (employé)
    $('#emp-filter-direction').on('change', function () {
        const dirId = $(this).val();
        $('#emp-filter-service').html('<option value="">Tous les services</option>');
        if (!dirId) return;
        $.get(`/organisation/directions/${dirId}/services`, function (services) {
            const opts = services.map(s => `<option value="${s.id}">${s.libelle}</option>`).join('');
            $('#emp-filter-service').append(opts);
        });
    });

    // Reset modale employé
    $('#employeSelectionModal').on('hidden.bs.modal', function () {
        selectedEmploye = null;
        $('#emp-confirm').prop('disabled', true);
        $('#emp-filter-direction, #emp-filter-service, #emp-filter-statut').val('');
        $('#emp-search').val('');
    });


    // ════════════════════════════════════════════════════════════════════════
    // MODALE POSTE DE TRAVAIL
    // ════════════════════════════════════════════════════════════════════════

    let selectedPoste = null;

    function loadPostes() {
        $('#poste-skeleton').removeClass('d-none');
        $('#poste-list').html('');
        $('#poste-confirm').prop('disabled', true);
        selectedPoste = null;

        const filters = {
            direction_id: $('#poste-filter-direction').val(),
            service_id:   $('#poste-filter-service').val(),
            statut:       $('#poste-filter-statut').val(),
            search:       $('#poste-search').val(),
        };

        $.get('/organisation/postes-travail/api', filters)
            .done(function (data) {
                $('#poste-skeleton').addClass('d-none');
                if (!data.length) {
                    $('#poste-list').html('<tr><td colspan="8" class="text-center text-muted py-3">Aucun résultat</td></tr>');
                    return;
                }
                const html = data.map(p => `
                    <tr data-id="${p.id}"
                        data-code="${p.code}"
                        data-libelle="${p.libelle}"
                        data-emplacement="${p.emplacement || '—'}">
                        <td><input type="radio" name="poste_select" value="${p.id}" class="form-check-input"></td>
                        <td>${p.code}</td>
                        <td>${p.libelle}</td>
                        <td>${p.direction || '—'}</td>
                        <td>${p.service || '—'}</td>
                        <td>${p.emplacement || '—'}</td>
                        <td>${p.occupant || '—'}</td>
                        <td><span class="badge bg-${p.statut === 'actif' ? 'success' : 'secondary'}">${p.statut}</span></td>
                    </tr>`).join('');
                $('#poste-list').html(html);
            })
            .fail(function () {
                $('#poste-skeleton').addClass('d-none');
                $('#poste-list').html('<tr><td colspan="8" class="text-center text-danger py-3">Erreur de chargement</td></tr>');
            });
    }

    $('#posteSelectionModal').on('show.bs.modal', function () {
        loadPostes();
    });

    $(document).on('click', '#poste-list tr', function () {
        $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
    });

    $(document).on('dblclick', '#poste-list tr', function () {
        $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
        setTimeout(() => $('#poste-confirm').trigger('click'), 100);
    });

    $(document).on('change', 'input[name="poste_select"]', function () {
        const row = $(this).closest('tr');
        selectedPoste = {
            id:          row.data('id'),
            code:        row.data('code'),
            libelle:     row.data('libelle'),
            emplacement: row.data('emplacement'),
        };
        $('#poste-confirm').prop('disabled', false);
        $('#poste-list tr').removeClass('table-active');
        row.addClass('table-active');
    });

    $('#poste-confirm').on('click', function () {
        if (!selectedPoste) return;
        $(document).trigger('poste:selected', [selectedPoste]);
        $('#posteSelectionModal').modal('hide');
    });

    $('#poste-filter-direction, #poste-filter-service, #poste-filter-statut').on('change', loadPostes);
    let posteSearchTimer;
    $('#poste-search').on('input', function () {
        clearTimeout(posteSearchTimer);
        posteSearchTimer = setTimeout(loadPostes, 300);
    });

    // Cascade direction → service (poste)
    $('#poste-filter-direction').on('change', function () {
        const dirId = $(this).val();
        $('#poste-filter-service').html('<option value="">Tous les services</option>');
        if (!dirId) return;
        $.get(`/organisation/directions/${dirId}/services`, function (services) {
            const opts = services.map(s => `<option value="${s.id}">${s.libelle}</option>`).join('');
            $('#poste-filter-service').append(opts);
        });
    });

    $('#posteSelectionModal').on('hidden.bs.modal', function () {
        selectedPoste = null;
        $('#poste-confirm').prop('disabled', true);
        $('#poste-filter-direction, #poste-filter-service, #poste-filter-statut').val('');
        $('#poste-search').val('');
    });


    // ════════════════════════════════════════════════════════════════════════
    // MODALE LOCAL
    // ════════════════════════════════════════════════════════════════════════

    let selectedLocal = null;

    function loadLocaux() {
        $('#local-skeleton').removeClass('d-none');
        $('#local-list').html('');
        $('#local-confirm').prop('disabled', true);
        selectedLocal = null;

        const filters = {
            site_id:     $('#local-filter-site').val(),
            batiment_id: $('#local-filter-batiment').val(),
            etage_id:    $('#local-filter-etage').val(),
            search:      $('#local-search').val(),
        };

        $.get('/organisation/locaux/api', filters)
            .done(function (data) {
                $('#local-skeleton').addClass('d-none');
                if (!data.length) {
                    $('#local-list').html('<tr><td colspan="9" class="text-center text-muted py-3">Aucun résultat</td></tr>');
                    return;
                }
                const html = data.map(l => `
                    <tr data-id="${l.id}"
                        data-code="${l.code}"
                        data-libelle="${l.libelle}"
                        data-type="${l.type || '—'}"
                        data-etage="${l.etage || '—'}"
                        data-batiment="${l.batiment || '—'}">
                        <td><input type="radio" name="local_select" value="${l.id}" class="form-check-input"></td>
                        <td>${l.code}</td>
                        <td>${l.libelle}</td>
                        <td>${l.type || '—'}</td>
                        <td>${l.superficie ? l.superficie + ' m²' : '—'}</td>
                        <td>${l.etage || '—'}</td>
                        <td>${l.batiment || '—'}</td>
                        <td>${l.site || '—'}</td>
                        <td><span class="badge bg-${l.statut === 'actif' ? 'success' : 'secondary'}">${l.statut}</span></td>
                    </tr>`).join('');
                $('#local-list').html(html);
            })
            .fail(function () {
                $('#local-skeleton').addClass('d-none');
                $('#local-list').html('<tr><td colspan="9" class="text-center text-danger py-3">Erreur de chargement</td></tr>');
            });
    }

    $('#localSelectionModal').on('show.bs.modal', function () {
        loadLocaux();
    });

    $(document).on('click', '#local-list tr', function () {
        $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
    });

    $(document).on('dblclick', '#local-list tr', function () {
        $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
        setTimeout(() => $('#local-confirm').trigger('click'), 100);
    });

    $(document).on('change', 'input[name="local_select"]', function () {
        const row = $(this).closest('tr');
        selectedLocal = {
            id:       row.data('id'),
            code:     row.data('code'),
            libelle:  row.data('libelle'),
            type:     row.data('type'),
            etage:    row.data('etage'),
            batiment: row.data('batiment'),
        };
        $('#local-confirm').prop('disabled', false);
        $('#local-list tr').removeClass('table-active');
        row.addClass('table-active');
    });

    $('#local-confirm').on('click', function () {
        if (!selectedLocal) return;
        $(document).trigger('local:selected', [selectedLocal]);
        $('#localSelectionModal').modal('hide');
    });

    $('#local-filter-site, #local-filter-batiment, #local-filter-etage').on('change', loadLocaux);
    let localSearchTimer;
    $('#local-search').on('input', function () {
        clearTimeout(localSearchTimer);
        localSearchTimer = setTimeout(loadLocaux, 300);
    });

    // Cascade site → bâtiment → étage
    $('#local-filter-site').on('change', function () {
        const siteId = $(this).val();
        $('#local-filter-batiment').html('<option value="">Tous les bâtiments</option>');
        $('#local-filter-etage').html('<option value="">Tous les étages</option>');
        if (!siteId) return;
        $.get(`/organisation/sites/${siteId}/batiments`, function (batiments) {
            const opts = batiments.map(b => `<option value="${b.id}">${b.libelle}</option>`).join('');
            $('#local-filter-batiment').append(opts);
        });
    });

    $('#local-filter-batiment').on('change', function () {
        const batId = $(this).val();
        $('#local-filter-etage').html('<option value="">Tous les étages</option>');
        if (!batId) return;
        $.get(`/organisation/batiments/${batId}/etages`, function (etages) {
            const opts = etages.map(e => `<option value="${e.id}">${e.libelle}</option>`).join('');
            $('#local-filter-etage').append(opts);
        });
    });

    $('#localSelectionModal').on('hidden.bs.modal', function () {
        selectedLocal = null;
        $('#local-confirm').prop('disabled', true);
        $('#local-filter-site, #local-filter-batiment, #local-filter-etage').val('');
        $('#local-search').val('');
    });


    // ════════════════════════════════════════════════════════════════════════
    // GESTION DES CARTES TYPE (ouverture des modales)
    // Fonctionne dans le wizard (index) ET dans la modale affectationModal (show)
    // ════════════════════════════════════════════════════════════════════════

    $(document).on('click', '.aff-type-card', function (e) {
        e.preventDefault();
        const val = $(this).data('value');
        $(this).find('input[type="radio"]').prop('checked', true);

        if (val === 'EMPLOYE') { $('#employeSelectionModal').modal('show'); }
        else if (val === 'POSTE') { $('#posteSelectionModal').modal('show'); }
        else if (val === 'LOCAL') { $('#localSelectionModal').modal('show'); }
    });

});
