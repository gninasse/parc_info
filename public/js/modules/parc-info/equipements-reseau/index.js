// Javascript module for Equipements Reseau
import { showAlert, confirmAction } from '../../../components/utilities.js';

// Global formatters for Bootstrap Table
window.equipementsReseauQueryParams = function(params) {
    return Object.assign(params, {
        site_id:        $('#filter-site').val(),
        direction_id:   $('#filter-direction').val(),
        statut:         $('#filter-statut').val(),
        type_reseau_id: $('#filter-type').val(),
        vitesse_port:   $('#filter-vitesse').val(),
    });
};

window.codeFormatter = (val, row) =>
    `<a href="${route('parc-info.equipements-reseau.show', row.id)}" class="fw-bold text-primary small text-decoration-none">${val}</a>`;

window.statutFormatter = (val, row) => {
    if (val === 'en_service') return '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill"><i class="bi bi-check-circle me-1"></i>En service</span>';
    if (val === 'en_stock') return '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill"><i class="bi bi-archive me-1"></i>En stock</span>';
    if (val === 'en_reparation') return '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill"><i class="bi bi-tools me-1"></i>En réparation</span>';
    if (val === 'perdu') return '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill"><i class="bi bi-question-circle me-1"></i>Perdu</span>';
    return `<span class="badge bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25 rounded-pill">${row.statut_label}</span>`;
};

window.nombrePortsFormatter = (val) => {
    return val ? `<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill">${val} ports</span>` : '<span class="text-muted">—</span>';
};

window.vitesseFormatter = (val) => {
    const colors = {
        '100Mbps': 'secondary',
        '1Gbps': 'info',
        '10Gbps': 'primary',
        '25Gbps': 'success',
        '40Gbps': 'success',
        '100Gbps': 'dark',
    };
    return val ? `<span class="badge bg-${colors[val] || 'light'} bg-opacity-10 text-${colors[val] || 'dark'} border border-${colors[val] || 'dark'} border-opacity-25 rounded-pill">${val}</span>` : '<span class="text-muted">—</span>';
};

window.actionsFormatter = (id) =>
    `<div class="d-flex gap-1 justify-content-end">
        <a href="${route('parc-info.equipements-reseau.show', id)}" class="btn btn-sm btn-outline-secondary border-0 rounded-circle" title="Voir"><i class="bi bi-eye"></i></a>
        <button class="btn btn-sm btn-outline-danger border-0 rounded-circle" data-action="delete" data-id="${id}" title="Supprimer"><i class="bi bi-trash"></i></button>
    </div>`;

window.actionsEvents = {
    'click [data-action="delete"]': (e, val, row) => deleteEquipementReseau(row.id),
};

// Wizard Module
const Wizard = (function() {
    let currentStep = 1;
    const modal = $('#equipementReseauModal');
    const form = $('#equipementReseauForm');

    function totalSteps() {
        const statut = $('input[name="statut"]:checked').val();
        return statut === 'en_stock' ? 2 : 3;
    }

    function updateNav() {
        const total = totalSteps();

        if (total === 2) {
            $('#nav-step-3').parent().addClass('opacity-25');
        } else {
            $('#nav-step-3').parent().removeClass('opacity-25');
        }

        $('#btn-prev').toggle(currentStep > 1);
        $('#btn-next').toggle(currentStep < total);
        $('#btn-submit').toggleClass('d-none', currentStep !== total);

        const isReparation = $('input[name="statut"]:checked').val() === 'en_reparation';
        $('#btn-save-reparation').toggleClass('d-none', !(isReparation && currentStep === 2));
        if (isReparation && currentStep === 2) {
            $('#btn-next').hide();
        }

        $('#wizard-progress').css('width', `${((currentStep - 1) / (total === 2 ? 1 : 2)) * 100}%`);

        $('.wizard-step-circle').removeClass('active bg-primary text-white border-primary').addClass('text-muted');
        $('.wizard-step-label').removeClass('text-primary fw-bold').addClass('text-muted');

        for (let i = 1; i <= currentStep; i++) {
            $(`.wizard-step-circle[data-step="${i}"]`).addClass('active bg-primary text-white border-primary').removeClass('text-muted');
            $(`.wizard-step-circle[data-step="${i}"]`).next('.wizard-step-label').addClass('text-primary fw-bold').removeClass('text-muted');
        }

        $('#wizard-subtitle').text(`Étape ${currentStep} sur ${total}`);
    }

    function goTo(step) {
        $('.wizard-step').addClass('d-none');
        $(`#step-${step}`).removeClass('d-none');
        currentStep = step;
        updateNav();
    }

    function validateStep(step) {
        let valid = true;
        form.find('.is-invalid').removeClass('is-invalid');

        if (step === 1) {
            if (!$('input[name="statut"]:checked').val()) {
                showAlert('Erreur', 'Veuillez sélectionner un statut initial', 'error');
                valid = false;
            }
        } else if (step === 2) {
            const required = ['numero_serie', 'modele', 'etat'];
            required.forEach(id => {
                if (!$(`#${id}`).val()) {
                    $(`#${id}`).addClass('is-invalid');
                    valid = false;
                }
            });
            if (!valid) showAlert('Erreur', 'Veuillez remplir les champs obligatoires (en rouge)', 'error');
        }
        return valid;
    }

    function reset() {
        form[0].reset();
        $('#eq_id, #dossier_employe_id, #poste_travail_id, #local_id, #type_cible').val('');
        $('#skip_affectation').val('0');
        $('.stat-card').removeClass('border-primary bg-primary bg-opacity-10');
        $('.aff-type-card').removeClass('border-primary bg-primary bg-opacity-10');
        $('[id^="summary-"]').addClass('d-none');
        $('.is-invalid').removeClass('is-invalid');
        $('#wizard-title').text('Ajouter un équipement réseau');

        $('#div_poe_budget').hide();
        $('#div_snmp_config').hide();

        goTo(1);
    }

    function submitForm(formData, isFromSubmit = false) {
        const id = $('#eq_id').val();
        const url = id ? route('parc-info.equipements-reseau.update', id) : route('parc-info.equipements-reseau.store');
        const method = id ? 'PUT' : 'POST';

        const btn = isFromSubmit ? $('#btn-submit') : $('#btn-save-reparation');
        const originalText = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Enregistrement...').prop('disabled', true);

        $.ajax({
            url: url,
            method: method,
            data: Object.fromEntries(formData),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                if (res.success) {
                    modal.modal('hide');
                    $('#equipements-reseau-table').bootstrapTable('refresh');
                    loadKpis();
                    showAlert('Succès', res.message, 'success');
                }
            },
            error: function(xhr) {
                btn.html(originalText).prop('disabled', false);
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let firstErrorStep = null;

                    for (const [key, msgs] of Object.entries(errors)) {
                        const input = $(`[name="${key}"]`);
                        if (input.length) {
                            input.addClass('is-invalid');
                            if (!input.next('.invalid-feedback').length) {
                                input.after(`<div class="invalid-feedback">${msgs[0]}</div>`);
                            } else {
                                input.next('.invalid-feedback').text(msgs[0]);
                            }

                            const stepDiv = input.closest('.wizard-step');
                            if (stepDiv.length && !firstErrorStep) {
                                firstErrorStep = parseInt(stepDiv.attr('id').replace('step-', ''));
                            }
                        }
                    }
                    if (firstErrorStep && firstErrorStep !== currentStep) goTo(firstErrorStep);
                    showAlert('Erreur de validation', 'Veuillez corriger les erreurs dans le formulaire.', 'error');
                } else {
                    showAlert('Erreur', 'Une erreur est survenue', 'error');
                }
            }
        });
    }

    return {
        init: function() {
            $('.stat-card').on('click', function() {
                $('.stat-card').removeClass('border-primary bg-primary bg-opacity-10');
                $(this).addClass('border-primary bg-primary bg-opacity-10');
                $(this).prev('input[type="radio"]').prop('checked', true);

                $('#etat').val('bon');
                if ($(this).prev().val() === 'en_reparation') $('#etat').val('mauvais');
            });

            $('#btn-next').on('click', () => {
                if (validateStep(currentStep)) goTo(currentStep + 1);
            });

            $('#btn-prev').on('click', () => {
                if (currentStep === 3) {
                    $('#type_cible, #dossier_employe_id, #poste_travail_id, #local_id').val('');
                    $('.aff-type-card').removeClass('border-primary bg-primary bg-opacity-10');
                    $('[id^="summary-"]').addClass('d-none');
                }
                goTo(currentStep - 1);
            });

            $('#btn-save-reparation').on('click', () => {
                if (validateStep(2)) {
                    $('#skip_affectation').val('1');
                    submitForm(new FormData(form[0]), false);
                }
            });

            form.on('submit', (e) => {
                e.preventDefault();
                if (currentStep === totalSteps() && validateStep(currentStep)) {
                    submitForm(new FormData(form[0]), true);
                }
            });

            $('#support_poe').on('change', function() {
                $('#div_poe_budget').toggle(this.checked);
            });
            $('#support_snmp').on('change', function() {
                $('#div_snmp_config').toggle(this.checked);
            });

            $('#btn-add-marque').on('click', () => {
                Swal.fire({
                    title: 'Nouvelle marque',
                    input: 'text',
                    showCancelButton: true,
                    confirmButtonText: 'Ajouter',
                    cancelButtonText: 'Annuler',
                    inputValidator: (value) => !value && 'Veuillez saisir un nom'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post(route('parc-info.equipements-reseau.store-marque'), {
                            libelle: result.value,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        }).done((res) => {
                            $('#marque_id').append(new Option(res.marque.libelle, res.marque.id, false, true));
                            showAlert('Succès', 'Marque ajoutée', 'success');
                        });
                    }
                });
            });

            $('#btn-add-type-reseau').on('click', () => {
                Swal.fire({
                    title: 'Nouveau type d\'équipement',
                    input: 'text',
                    showCancelButton: true,
                    confirmButtonText: 'Ajouter',
                    cancelButtonText: 'Annuler',
                    inputValidator: (value) => !value && 'Veuillez saisir un nom'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post(route('parc-info.equipements-reseau.store-type-reseau'), {
                            libelle: result.value,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        }).done((res) => {
                            $('#type_reseau_id').append(new Option(res.type.libelle, res.type.id, false, true));
                            showAlert('Succès', 'Type ajouté', 'success');
                        });
                    }
                });
            });

            modal.on('hidden.bs.modal', reset);

            // Écoute des sélections de la modale polymorphe (étape 3)
            $(document).on('employe:selected', (e, data) => {
                if ($('#step-3').hasClass('d-none')) return;
                $('#dossier_employe_id').val(data.id);
                $('#w-employe-name').text(data.text);
                $('#summary-employe').removeClass('d-none');
            });
            $(document).on('poste:selected', (e, data) => {
                if ($('#step-3').hasClass('d-none')) return;
                $('#poste_travail_id').val(data.id);
                $('#w-poste-name').text(data.text);
                $('#summary-poste').removeClass('d-none');
            });
            $(document).on('local:selected', (e, data) => {
                if ($('#step-3').hasClass('d-none')) return;
                $('#local_id').val(data.id);
                $('#w-local-name').text(data.text);
                $('#summary-local').removeClass('d-none');
            });

            $('.aff-type-card').on('click', function() {
                const type = $(this).data('type');
                $('#type_cible').val(type);

                $('.aff-type-card').removeClass('border-primary bg-primary bg-opacity-10');
                $(this).addClass('border-primary bg-primary bg-opacity-10');

                $('[id^="summary-"]').addClass('d-none');
                $('#dossier_employe_id, #poste_travail_id, #local_id').val('');

                if (type === 'EMPLOYE') $('#employeSelectionModal').modal('show');
                else if (type === 'POSTE') $('#posteSelectionModal').modal('show');
                else if (type === 'LOCAL') $('#localSelectionModal').modal('show');
            });

            $('.aff-reselect').on('click', function() {
                const type = $('#type_cible').val();
                if (type === 'EMPLOYE') $('#employeSelectionModal').modal('show');
                else if (type === 'POSTE') $('#posteSelectionModal').modal('show');
                else if (type === 'LOCAL') $('#localSelectionModal').modal('show');
            });
        },
        open: function() { reset(); modal.modal('show'); },
        openEdit: function(id) {
            reset();
            $('#wizard-title').text('Modifier l\'équipement réseau');
            $('#eq_id').val(id);

            $.get(route('parc-info.equipements-reseau.show-json', id), function(data) {
                $('#code_inventaire').val(data.code_inventaire);
                $('#numero_serie').val(data.numero_serie);
                $('#marque_id').val(data.marque_id);
                $('#modele').val(data.modele);
                $(`input[name="statut"][value="${data.statut}"]`).prop('checked', true).next('.stat-card').addClass('border-primary bg-primary bg-opacity-10');
                $('#etat').val(data.etat);
                if (data.date_acquisition) $('#date_acquisition').val(data.date_acquisition.split('T')[0]);
                if (data.date_mise_en_service) $('#date_mise_en_service').val(data.date_mise_en_service.split('T')[0]);
                if (data.date_fin_garantie) $('#date_fin_garantie').val(data.date_fin_garantie.split('T')[0]);
                $('#valeur_achat').val(data.valeur_achat);

                if (data.equipement_reseau) {
                    const er = data.equipement_reseau;
                    $('#type_reseau_id').val(er.type_reseau_id);
                    $('#nombre_ports').val(er.nombre_ports);
                    $('#vitesse_port').val(er.vitesse_port);
                    $('#adresse_ip_management').val(er.adresse_ip_management);
                    $('#firmware_version').val(er.firmware_version);
                    $('#support_poe').prop('checked', er.support_poe).trigger('change');
                    if (er.support_poe) $('#poe_budget_watts').val(er.poe_budget_watts);
                    $('#support_vlan').prop('checked', er.support_vlan);
                    $('#support_stp').prop('checked', er.support_stp);
                    $('#support_lacp').prop('checked', er.support_lacp);
                    $('#support_redundance').prop('checked', er.support_redundance);
                    $('#support_snmp').prop('checked', er.support_snmp).trigger('change');
                    if (er.support_snmp) {
                        $('#snmp_community').val(er.snmp_community);
                        $('#snmp_version').val(er.snmp_version);
                    }
                    $('#nombre_ports_uplink').val(er.nombre_ports_uplink);
                    $('#vlans_configures').val(er.vlans_configures);
                    $('#modele_reference').val(er.modele_reference);
                    $('#location_detail').val(er.location_detail);
                }

                modal.modal('show');
            });
        }
    };
})();

function deleteEquipementReseau(id) {
    confirmAction({
        title: 'Supprimer cet équipement ?',
        text: 'Cette action est irréversible et supprimera tout l\'historique associé.',
        confirmButtonText: 'Oui, supprimer',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: route('parc-info.equipements-reseau.destroy', id),
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(res) {
                    if (res.success) {
                        $('#equipements-reseau-table').bootstrapTable('refresh');
                        loadKpis();
                        showAlert('Supprimé', res.message, 'success');
                    }
                }
            });
        }
    });
}

function loadKpis() {
    $.get(route('parc-info.equipements-reseau.data'), { limit: 9999, offset: 0 }, function(res) {
        $('#kpi-total').text(res.total);
        let service = 0, repa = 0, stock = 0;
        res.rows.forEach(r => {
            if (r.statut === 'en_service') service++;
            if (r.statut === 'en_reparation') repa++;
            if (r.statut === 'en_stock') stock++;
        });
        $('#kpi-service').text(service);
        $('#kpi-reparation').text(repa);
        $('#kpi-stock').text(stock);
    });
}

$(function() {
    Wizard.init();

    $('#btn-add').on('click', () => Wizard.open());

    $('#btn-edit').on('click', () => {
        const sel = $('#equipements-reseau-table').bootstrapTable('getSelections');
        if (sel.length) window.location.href = route('parc-info.equipements-reseau.show', sel[0].id);
    });

    $('#btn-delete').on('click', () => {
        const sel = $('#equipements-reseau-table').bootstrapTable('getSelections');
        if (sel.length) deleteEquipementReseau(sel[0].id);
    });

    $('#equipements-reseau-table').on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function() {
        const sel = $(this).bootstrapTable('getSelections');
        $('#btn-edit, #btn-delete').prop('disabled', sel.length === 0);
    });

    $('#btn-apply-filters').on('click', () => $('#equipements-reseau-table').bootstrapTable('refresh'));
    $('#btn-reset-filters').on('click', () => {
        $('#filter-site, #filter-direction, #filter-statut, #filter-type, #filter-vitesse').val('');
        $('#equipements-reseau-table').bootstrapTable('refresh');
    });

    loadKpis();
});
