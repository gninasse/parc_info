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
                        data-emplacement="${p.emplacement || '—'}"
                        data-local-id="${p.local_id || ''}"
                        data-local-site="${p.local_site || '—'}"
                        data-local-batiment="${p.local_batiment || '—'}"
                        data-local-etage="${p.local_etage || '—'}"
                        data-local-libelle="${p.local_libelle || '—'}"
                        data-local-code="${p.local_code || '—'}">
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
        if ($(this).closest('#poste-list').length) {
            $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
        }
    });

    $(document).on('dblclick', '#poste-list tr', function () {
        if ($(this).closest('#poste-list').length) {
            $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
            setTimeout(() => $('#poste-confirm').trigger('click'), 100);
        }
    });

    $(document).on('change', 'input[name="poste_select"]', function () {
        const row = $(this).closest('tr');
        selectedPoste = {
            id:             row.data('id'),
            code:           row.data('code'),
            libelle:        row.data('libelle'),
            emplacement:    row.data('emplacement'),
            local_id:       row.data('local-id'),
            local_site:     row.data('local-site'),
            local_batiment: row.data('local-batiment'),
            local_etage:    row.data('local-etage'),
            local_libelle:  row.data('local-libelle'),
            local_code:     row.data('local-code'),
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
                        data-batiment="${l.batiment || '—'}"
                        data-site="${l.site || '—'}">
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
        if ($(this).closest('#local-list').length) {
            $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
        }
    });

    $(document).on('dblclick', '#local-list tr', function () {
        if ($(this).closest('#local-list').length) {
            $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
            setTimeout(() => $('#local-confirm').trigger('click'), 100);
        }
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
            site:     row.data('site'),
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
        else if (val === 'DIRECTION') { $('#directionSelectionModal').modal('show'); }
        else if (val === 'SERVICE') { $('#serviceSelectionModal').modal('show'); }
        else if (val === 'UNITE') { $('#uniteSelectionModal').modal('show'); }
    });

    // ════════════════════════════════════════════════════════════════════════
    // MODALE DIRECTION
    // ════════════════════════════════════════════════════════════════════════

    let selectedDirection = null;

    function loadDirections() {
        $('#dir-skeleton').removeClass('d-none');
        $('#dir-list').html('');
        $('#dir-confirm').prop('disabled', true);
        selectedDirection = null;

        const filters = {
            search: $('#dir-search').val(),
        };

        $.get('/organisation/directions/api', filters)
            .done(function (data) {
                $('#dir-skeleton').addClass('d-none');
                if (!data.length) {
                    $('#dir-list').html('<tr><td colspan="5" class="text-center text-muted py-3">Aucun résultat</td></tr>');
                    return;
                }
                const html = data.map(dir => `
                    <tr data-id="${dir.id}"
                        data-nom="${dir.libelle}"
                        data-code="${dir.code}">
                        <td><input type="radio" name="dir_select" value="${dir.id}" class="form-check-input"></td>
                        <td>${dir.code}</td>
                        <td>${dir.libelle}</td>
                        <td>${dir.site}</td>
                        <td><span class="badge bg-${dir.statut === 'actif' ? 'success' : 'secondary'}">${dir.statut}</span></td>
                    </tr>`).join('');
                $('#dir-list').html(html);
            })
            .fail(function () {
                $('#dir-skeleton').addClass('d-none');
                $('#dir-list').html('<tr><td colspan="5" class="text-center text-danger py-3">Erreur de chargement</td></tr>');
            });
    }

    $('#directionSelectionModal').on('show.bs.modal', function () {
        loadDirections();
    });

    $(document).on('click', '#dir-list tr', function () {
        if ($(this).closest('#dir-list').length) {
            $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
        }
    });

    $(document).on('dblclick', '#dir-list tr', function () {
        if ($(this).closest('#dir-list').length) {
            $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
            setTimeout(() => $('#dir-confirm').trigger('click'), 100);
        }
    });

    $(document).on('change', 'input[name="dir_select"]', function () {
        const row = $(this).closest('tr');
        selectedDirection = {
            id:      row.data('id'),
            nom:     row.data('nom'),
            code:    row.data('code'),
        };
        $('#dir-confirm').prop('disabled', false);
        $('#dir-list tr').removeClass('table-active');
        row.addClass('table-active');
    });

    $('#dir-confirm').on('click', function () {
        if (!selectedDirection) return;
        $(document).trigger('direction:selected', [selectedDirection]);
        $('#directionSelectionModal').modal('hide');
    });

    let dirSearchTimer;
    $('#dir-search').on('input', function () {
        clearTimeout(dirSearchTimer);
        dirSearchTimer = setTimeout(loadDirections, 300);
    });

    $('#directionSelectionModal').on('hidden.bs.modal', function () {
        selectedDirection = null;
        $('#dir-confirm').prop('disabled', true);
        $('#dir-search').val('');
    });


    // ════════════════════════════════════════════════════════════════════════
    // MODALE SERVICE
    // ════════════════════════════════════════════════════════════════════════

    let selectedService = null;

    function loadServices() {
        $('#srv-skeleton').removeClass('d-none');
        $('#srv-list').html('');
        $('#srv-confirm').prop('disabled', true);
        selectedService = null;

        const filters = {
            direction_id: $('#srv-filter-direction').val(),
            search:       $('#srv-search').val(),
        };

        $.get('/organisation/services/api', filters)
            .done(function (data) {
                $('#srv-skeleton').addClass('d-none');
                if (!data.length) {
                    $('#srv-list').html('<tr><td colspan="5" class="text-center text-muted py-3">Aucun résultat</td></tr>');
                    return;
                }
                const html = data.map(srv => `
                    <tr data-id="${srv.id}"
                        data-nom="${srv.libelle}"
                        data-code="${srv.code}"
                        data-direction="${srv.direction}">
                        <td><input type="radio" name="srv_select" value="${srv.id}" class="form-check-input"></td>
                        <td>${srv.code}</td>
                        <td>${srv.libelle}</td>
                        <td>${srv.direction}</td>
                        <td><span class="badge bg-${srv.statut === 'actif' ? 'success' : 'secondary'}">${srv.statut}</span></td>
                    </tr>`).join('');
                $('#srv-list').html(html);
            })
            .fail(function () {
                $('#srv-skeleton').addClass('d-none');
                $('#srv-list').html('<tr><td colspan="5" class="text-center text-danger py-3">Erreur de chargement</td></tr>');
            });
    }

    $('#serviceSelectionModal').on('show.bs.modal', function () {
        loadServices();
    });

    $(document).on('click', '#srv-list tr', function () {
        if ($(this).closest('#srv-list').length) {
            $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
        }
    });

    $(document).on('dblclick', '#srv-list tr', function () {
        if ($(this).closest('#srv-list').length) {
            $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
            setTimeout(() => $('#srv-confirm').trigger('click'), 100);
        }
    });

    $(document).on('change', 'input[name="srv_select"]', function () {
        const row = $(this).closest('tr');
        selectedService = {
            id:        row.data('id'),
            nom:       row.data('nom'),
            code:      row.data('code'),
            direction: row.data('direction'),
        };
        $('#srv-confirm').prop('disabled', false);
        $('#srv-list tr').removeClass('table-active');
        row.addClass('table-active');
    });

    $('#srv-confirm').on('click', function () {
        if (!selectedService) return;
        $(document).trigger('service:selected', [selectedService]);
        $('#serviceSelectionModal').modal('hide');
    });

    $('#srv-filter-direction').on('change', loadServices);
    let srvSearchTimer;
    $('#srv-search').on('input', function () {
        clearTimeout(srvSearchTimer);
        srvSearchTimer = setTimeout(loadServices, 300);
    });

    $('#serviceSelectionModal').on('hidden.bs.modal', function () {
        selectedService = null;
        $('#srv-confirm').prop('disabled', true);
        $('#srv-filter-direction').val('');
        $('#srv-search').val('');
    });


    // ════════════════════════════════════════════════════════════════════════
    // MODALE UNITE
    // ════════════════════════════════════════════════════════════════════════

    let selectedUnite = null;

    function loadUnites() {
        $('#unt-skeleton').removeClass('d-none');
        $('#unt-list').html('');
        $('#unt-confirm').prop('disabled', true);
        selectedUnite = null;

        const filters = {
            direction_id: $('#unt-filter-direction').val(),
            service_id:   $('#unt-filter-service').val(),
            search:       $('#unt-search').val(),
        };

        $.get('/organisation/unites/api', filters)
            .done(function (data) {
                $('#unt-skeleton').addClass('d-none');
                if (!data.length) {
                    $('#unt-list').html('<tr><td colspan="6" class="text-center text-muted py-3">Aucun résultat</td></tr>');
                    return;
                }
                const html = data.map(unt => `
                    <tr data-id="${unt.id}"
                        data-nom="${unt.libelle}"
                        data-code="${unt.code}"
                        data-service="${unt.service}"
                        data-direction="${unt.direction}">
                        <td><input type="radio" name="unt_select" value="${unt.id}" class="form-check-input"></td>
                        <td>${unt.code}</td>
                        <td>${unt.libelle}</td>
                        <td>${unt.service}</td>
                        <td>${unt.direction}</td>
                        <td><span class="badge bg-${unt.statut === 'actif' ? 'success' : 'secondary'}">${unt.statut}</span></td>
                    </tr>`).join('');
                $('#unt-list').html(html);
            })
            .fail(function () {
                $('#unt-skeleton').addClass('d-none');
                $('#unt-list').html('<tr><td colspan="6" class="text-center text-danger py-3">Erreur de chargement</td></tr>');
            });
    }

    $('#uniteSelectionModal').on('show.bs.modal', function () {
        loadUnites();
    });

    $(document).on('click', '#unt-list tr', function () {
        if ($(this).closest('#unt-list').length) {
            $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
        }
    });

    $(document).on('dblclick', '#unt-list tr', function () {
        if ($(this).closest('#unt-list').length) {
            $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
            setTimeout(() => $('#unt-confirm').trigger('click'), 100);
        }
    });

    $(document).on('change', 'input[name="unt_select"]', function () {
        const row = $(this).closest('tr');
        selectedUnite = {
            id:        row.data('id'),
            nom:       row.data('nom'),
            code:      row.data('code'),
            service:   row.data('service'),
            direction: row.data('direction'),
        };
        $('#unt-confirm').prop('disabled', false);
        $('#unt-list tr').removeClass('table-active');
        row.addClass('table-active');
    });

    $('#unt-confirm').on('click', function () {
        if (!selectedUnite) return;
        $(document).trigger('unite:selected', [selectedUnite]);
        $('#uniteSelectionModal').modal('hide');
    });

    // Cascade direction → service in Unite Modal
    $('#unt-filter-direction').on('change', function () {
        const dirId = $(this).val();
        $('#unt-filter-service').html('<option value="">Tous les services</option>');
        loadUnites();
        if (!dirId) return;
        $.get(`/organisation/directions/${dirId}/services`, function (services) {
            const opts = services.map(s => `<option value="${s.id}">${s.libelle}</option>`).join('');
            $('#unt-filter-service').append(opts);
        });
    });

    $('#unt-filter-service').on('change', loadUnites);
    let untSearchTimer;
    $('#unt-search').on('input', function () {
        clearTimeout(untSearchTimer);
        untSearchTimer = setTimeout(loadUnites, 300);
    });
    $('#uniteSelectionModal').on('hidden.bs.modal', function () {
        selectedUnite = null;
        $('#unt-confirm').prop('disabled', true);
        $('#unt-filter-direction').val('');
        $('#unt-filter-service').html('<option value="">Tous les services</option>');
        $('#unt-search').val('');
    });

    // ════════════════════════════════════════════════════════════════════════
    // AFFECTATION SELECTIONS & WIZARD INTEGRATION
    // ════════════════════════════════════════════════════════════════════════

    $(document).on('direction:selected', function (e, dir) {
        const activeContainer = $('.wizard-step:visible, .modal.show');
        if (!activeContainer.length) return;

        activeContainer.find('.aff-type-card').removeClass('selected');
        activeContainer.find('.aff-type-card[data-value="DIRECTION"]').addClass('selected')
            .find('input[type="radio"]').prop('checked', true);

        // Update summaries
        $('#direction-summary-code').text(dir.code);
        $('#direction-summary-libelle').text(dir.nom);

        activeContainer.find('input[name="direction_id"]').val(dir.id);
        activeContainer.find('input[name="service_id"], input[name="unite_id"], input[name="dossier_employe_id"], input[name="poste_travail_id"]').val('');

        activeContainer.find('.aff-summary').addClass('d-none');
        activeContainer.find('#aff-direction-summary').removeClass('d-none');
        activeContainer.find('#emplacement-section').removeClass('d-none');
        $('#aff-skip-hint').addClass('d-none');

        // Reset and enable local selection
        activeContainer.find('.emp-site-display, .emp-batiment-display, .emp-etage-display, .emp-local-display').val('');
        activeContainer.find('input[name="local_id"]').val('');
        activeContainer.find('.emp-local-display')
            .removeClass('bg-light text-muted')
            .addClass('border-primary text-primary')
            .css('cursor', 'pointer');
    });

    $(document).on('service:selected', function (e, srv) {
        const activeContainer = $('.wizard-step:visible, .modal.show');
        if (!activeContainer.length) return;

        activeContainer.find('.aff-type-card').removeClass('selected');
        activeContainer.find('.aff-type-card[data-value="SERVICE"]').addClass('selected')
            .find('input[type="radio"]').prop('checked', true);

        // Update summaries
        $('#service-summary-code').text(srv.code);
        $('#service-summary-libelle').text(srv.nom);
        $('#service-summary-direction').text(srv.direction);

        activeContainer.find('input[name="service_id"]').val(srv.id);
        activeContainer.find('input[name="direction_id"]').val(srv.direction_id || '');
        activeContainer.find('input[name="unite_id"], input[name="dossier_employe_id"], input[name="poste_travail_id"]').val('');

        activeContainer.find('.aff-summary').addClass('d-none');
        activeContainer.find('#aff-service-summary').removeClass('d-none');
        activeContainer.find('#emplacement-section').removeClass('d-none');
        $('#aff-skip-hint').addClass('d-none');

        // Reset and enable local selection
        activeContainer.find('.emp-site-display, .emp-batiment-display, .emp-etage-display, .emp-local-display').val('');
        activeContainer.find('input[name="local_id"]').val('');
        activeContainer.find('.emp-local-display')
            .removeClass('bg-light text-muted')
            .addClass('border-primary text-primary')
            .css('cursor', 'pointer');
    });

    $(document).on('unite:selected', function (e, unt) {
        const activeContainer = $('.wizard-step:visible, .modal.show');
        if (!activeContainer.length) return;

        activeContainer.find('.aff-type-card').removeClass('selected');
        activeContainer.find('.aff-type-card[data-value="UNITE"]').addClass('selected')
            .find('input[type="radio"]').prop('checked', true);

        // Update summaries
        $('#unite-summary-code').text(unt.code);
        $('#unite-summary-libelle').text(unt.nom);
        $('#unite-summary-service').text(unt.service);

        activeContainer.find('input[name="unite_id"]').val(unt.id);
        activeContainer.find('input[name="direction_id"], input[name="service_id"], input[name="dossier_employe_id"], input[name="poste_travail_id"]').val('');

        activeContainer.find('.aff-summary').addClass('d-none');
        activeContainer.find('#aff-unite-summary').removeClass('d-none');
        activeContainer.find('#emplacement-section').removeClass('d-none');
        $('#aff-skip-hint').addClass('d-none');

        // Reset and enable local selection
        activeContainer.find('.emp-site-display, .emp-batiment-display, .emp-etage-display, .emp-local-display').val('');
        activeContainer.find('input[name="local_id"]').val('');
        activeContainer.find('.emp-local-display')
            .removeClass('bg-light text-muted')
            .addClass('border-primary text-primary')
            .css('cursor', 'pointer');
    });

    $(document).on('employe:selected', function (e, emp) {
        const activeContainer = $('.wizard-step:visible, .modal.show');
        if (!activeContainer.length) return;
        activeContainer.find('#emplacement-section').removeClass('d-none');
        $('#aff-skip-hint').addClass('d-none');

        // Reset and enable local selection
        activeContainer.find('.emp-site-display, .emp-batiment-display, .emp-etage-display, .emp-local-display').val('');
        activeContainer.find('input[name="local_id"]').val('');
        activeContainer.find('.emp-local-display')
            .removeClass('bg-light text-muted')
            .addClass('border-primary text-primary')
            .css('cursor', 'pointer');
    });

    $(document).on('poste:selected', function (e, poste) {
        const activeContainer = $('.wizard-step:visible, .modal.show');
        if (!activeContainer.length) return;
        activeContainer.find('#emplacement-section').removeClass('d-none');
        $('#aff-skip-hint').addClass('d-none');

        // Auto populate and lock if workstation has room
        if (poste.local_id) {
            activeContainer.find('.emp-site-display').val(poste.local_site);
            activeContainer.find('.emp-batiment-display').val(poste.local_batiment);
            activeContainer.find('.emp-etage-display').val(poste.local_etage);
            activeContainer.find('.emp-local-display')
                .val(`${poste.local_libelle} (${poste.local_code})`)
                .addClass('bg-light text-muted')
                .removeClass('border-primary text-primary')
                .css('cursor', 'not-allowed');
            activeContainer.find('input[name="local_id"]').val(poste.local_id);
        } else {
            // Otherwise reset and let user choose
            activeContainer.find('.emp-site-display, .emp-batiment-display, .emp-etage-display, .emp-local-display').val('');
            activeContainer.find('input[name="local_id"]').val('');
            activeContainer.find('.emp-local-display')
                .removeClass('bg-light text-muted')
                .addClass('border-primary text-primary')
                .css('cursor', 'pointer');
        }
    });

    // ════════════════════════════════════════════════════════════════════════
    // SELECTION DE LOCAL EN MODALE
    // ════════════════════════════════════════════════════════════════════════

    // Ouvrir la modale de sélection de local lors du clic sur le champ Bureau / Local
    $(document).on('click', '.emp-local-display', function () {
        const activeContainer = $('.wizard-step:visible, #affectationModal.show');
        if (activeContainer.length) {
            const hasPoste = activeContainer.find('input[name="poste_travail_id"]').val();
            if (hasPoste) {
                // Emplacement est verrouillé pour les postes de travail
                return;
            }
        }
        $('#localSelectionModal').modal('show');
    });

    // Peupler les champs d'affichage après sélection d'un local
    $(document).on('local:selected', function (e, local) {
        const activeContainer = $('.wizard-step:visible, #affectationModal.show');
        if (activeContainer.length) {
            activeContainer.find('.emp-site-display').val(local.site);
            activeContainer.find('.emp-batiment-display').val(local.batiment);
            activeContainer.find('.emp-etage-display').val(local.etage);
            activeContainer.find('.emp-local-display').val(`${local.libelle} (${local.code})`);
            activeContainer.find('input[name="local_id"]').val(local.id);
        } else {
            // Fiche équipement
            $('#f_local_id').html(new Option(local.libelle, local.id, true, true));
        }
    });

});
