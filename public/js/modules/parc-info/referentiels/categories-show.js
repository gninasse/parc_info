/**
 * Configuration d'une Catégorie d'Équipement - Module Parc Info
 * Pattern: AJAX + Bootstrap Table + Modales
 */

document.addEventListener('DOMContentLoaded', function() {
    // ── CONFIGURATIONS DE LA CATÉGORIE ELLE-MÊME ──
    const $itemModal = new bootstrap.Modal('#item-modal');
    const $itemForm = $('#item-form');
    const $btnEditCategory = $('#btn-edit-category');
    const $btnSaveCategory = $('#btn-save');
    const $iconeSelect = $('#icone');
    const $iconPreview = $('#icon-preview');

    // Parse category ID from URL
    const pathParts = window.location.pathname.split('/');
    const currentCategoryId = pathParts[pathParts.length - 1] || pathParts[pathParts.length - 2];

    // Icon Preview
    $iconeSelect.on('change', function() {
        const iconClass = $(this).val() || 'bi-cpu';
        $iconPreview.attr('class', 'bi ' + iconClass + ' fs-4');
    });

    if ($btnEditCategory.length) {
        $btnEditCategory.on('click', function() {
            $itemForm[0].reset();
            $('#code-group').addClass('d-none'); // Code cannot be edited
            $('#code').prop('disabled', true);

            $.getJSON(route('parc-info.referentiels.categories.show', currentCategoryId) + '?json=1', function(res) {
                const d = res.data || res;
                $('#item-id').val(d.id);
                $itemForm.find('[name="libelle"]').val(d.libelle);
                $('#code').val(d.code);
                $iconeSelect.val(d.icone || 'bi-cpu').trigger('change');
                $('#modalLabel span').text('Modifier');
                $itemModal.show();
            }).fail(function() {
                Swal.fire('Erreur', 'Impossible de charger les données de la catégorie', 'error');
            });
        });
    }

    $itemForm.on('submit', function(e) {
        e.preventDefault();
        const id = $('#item-id').val();
        $btnSaveCategory.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');

        $.ajax({
            url: route('parc-info.referentiels.categories.update', id),
            method: 'PUT',
            data: $itemForm.serialize(),
            success: function(res) {
                if (res.success) {
                    $itemModal.hide();
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let msg = xhr.responseJSON?.message || '';
                if (Object.keys(errors).length > 0) {
                    msg = '';
                    Object.values(errors).forEach(e => msg += e[0] + '<br>');
                }
                Swal.fire('Erreur', msg || 'Une erreur est survenue', 'error');
            },
            complete: function() {
                $btnSaveCategory.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer');
            }
        });
    });


    // ── CONFIGURATIONS DES CHAMPS DYNAMIQUES ──
    const $fieldsTable = $('#fields-table');
    const $fieldModal = new bootstrap.Modal('#field-modal');
    const $fieldForm = $('#field-form');
    const $btnSaveField = $('#btn-save-field');

    const $btnAddField = $('#btn-add-field');
    const $btnEditField = $('#btn-edit-field');
    const $btnDeleteField = $('#btn-delete-field');

    const $fieldTypeSelect = $('#field_type_champ');
    const $selectSourceContainer = $('#select-source-container');
    const $dictSelectGroup = $('#dict-select-group');
    const $customJsonGroup = $('#custom-json-group');
    const $dictCodeSelect = $('#dict_code_select');
    const $customJsonVal = $('#custom_json_val');
    const $sourceOptionsHidden = $('#source_options');

    // Toggle fields based on selected field type
    $fieldTypeSelect.on('change', function() {
        const type = $(this).val();
        if (type === 'select') {
            $selectSourceContainer.removeClass('d-none');
        } else {
            $selectSourceContainer.addClass('d-none');
            $sourceOptionsHidden.val('');
        }
    });

    // Toggle source options type (Radio change)
    $('input[name="source_type"]').on('change', function() {
        const sourceType = $(this).val();
        if (sourceType === 'dict') {
            $dictSelectGroup.removeClass('d-none');
            $customJsonGroup.addClass('d-none');
        } else {
            $dictSelectGroup.addClass('d-none');
            $customJsonGroup.removeClass('d-none');
        }
    });

    // Handle row selections to enable/disable buttons
    $fieldsTable.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selections = $fieldsTable.bootstrapTable('getSelections');
        const hasOne = selections.length === 1;
        $btnEditField.prop('disabled', !hasOne);
        $btnDeleteField.prop('disabled', !hasOne);
    });

    // Add Field button click
    if ($btnAddField.length) {
        $btnAddField.on('click', function() {
            $fieldForm[0].reset();
            $('#field-id').val('');
            $('#field-code-group').removeClass('d-none');
            $('#field_code').prop('disabled', false);
            $('#fieldModalLabel span').text('Nouveau');
            
            // Set defaults
            $('#field_ordre_affichage').val('10');
            $('#field_ordre_colonne_liste').val('99');
            $('#field_afficher_dans_modal').prop('checked', true);
            $('#field_afficher_dans_show').prop('checked', true);
            $('#field_afficher_dans_liste').prop('checked', true);
            
            $fieldTypeSelect.trigger('change');
            $('input[name="source_type"][value="dict"]').prop('checked', true).trigger('change');
            $fieldModal.show();
        });
    }

    // Edit Field button click
    if ($btnEditField.length) {
        $btnEditField.on('click', function() {
            const row = $fieldsTable.bootstrapTable('getSelections')[0];
            if (row) {
                editFieldItem(row.id);
            }
        });
    }

    function editFieldItem(fieldId) {
        $.ajax({
            url: route('parc-info.referentiels.categories.fields.show', [currentCategoryId, fieldId]),
            method: 'GET',
            success: function(res) {
                if (res.success) {
                    const d = res.data;
                    $('#field-id').val(d.id);
                    $('#field_code').val(d.code).prop('disabled', true);
                    $('#field-code-group').addClass('d-none');
                    $('#field_libelle').val(d.libelle);
                    $fieldTypeSelect.val(d.type_champ).trigger('change');
                    $('#field_nom_panel').val(d.nom_panel);
                    $('#field_regles_validation').val(d.regles_validation);
                    $('#field_ordre_affichage').val(d.ordre_affichage);
                    $('#field_ordre_colonne_liste').val(d.ordre_colonne_liste);
                    
                    $('#field_afficher_dans_modal').prop('checked', !!d.afficher_dans_modal);
                    $('#field_afficher_dans_show').prop('checked', !!d.afficher_dans_show);
                    $('#field_afficher_dans_liste').prop('checked', !!d.afficher_dans_liste);

                    // Options source handling
                    if (d.type_champ === 'select' && d.source_options) {
                        if (d.source_options.startsWith('DICT:')) {
                            $('input[name="source_type"][value="dict"]').prop('checked', true).trigger('change');
                            $dictCodeSelect.val(d.source_options);
                            $customJsonVal.val('');
                        } else {
                            $('input[name="source_type"][value="custom"]').prop('checked', true).trigger('change');
                            $customJsonVal.val(d.source_options);
                            $dictCodeSelect.val('');
                        }
                    } else {
                        $('input[name="source_type"][value="dict"]').prop('checked', true).trigger('change');
                        $dictCodeSelect.val('');
                        $customJsonVal.val('');
                    }

                    $('#fieldModalLabel span').text('Modifier');
                    $fieldModal.show();
                }
            },
            error: function() {
                Swal.fire('Erreur', 'Impossible de charger la configuration du champ', 'error');
            }
        });
    }

    // Submit Field Form
    $fieldForm.on('submit', function(e) {
        e.preventDefault();

        // Validate options source if field type is select
        if ($fieldTypeSelect.val() === 'select') {
            const sourceType = $('input[name="source_type"]:checked').val();
            if (sourceType === 'dict') {
                const val = $dictCodeSelect.val();
                if (!val) {
                    Swal.fire('Erreur de validation', 'Veuillez sélectionner un dictionnaire de référence.', 'error');
                    return;
                }
                $sourceOptionsHidden.val(val);
            } else {
                const val = $customJsonVal.val().trim();
                if (!val) {
                    Swal.fire('Erreur de validation', 'Veuillez renseigner le tableau JSON de valeurs.', 'error');
                    return;
                }
                try {
                    const parsed = JSON.parse(val);
                    if (!Array.isArray(parsed)) {
                        throw new Error('Doit être un tableau');
                    }
                } catch(err) {
                    Swal.fire('Erreur de validation', 'Le tableau JSON de valeurs est invalide. Exemple: ["Option A", "Option B"]', 'error');
                    return;
                }
                $sourceOptionsHidden.val(val);
            }
        } else {
            $sourceOptionsHidden.val('');
        }

        const id = $('#field-id').val();
        const url = id 
            ? route('parc-info.referentiels.categories.fields.update', [currentCategoryId, id]) 
            : route('parc-info.referentiels.categories.fields.store', currentCategoryId);
        const method = id ? 'PUT' : 'POST';

        $btnSaveField.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');

        // Custom serialize to make sure checkboxes are sent properly as 1 or 0
        const dataArray = $fieldForm.serializeArray();
        
        // Ensure switches checkboxes are sent as boolean flags even if unchecked
        const checkboxNames = ['afficher_dans_modal', 'afficher_dans_show', 'afficher_dans_liste'];
        checkboxNames.forEach(name => {
            const index = dataArray.findIndex(item => item.name === name);
            if (index === -1) {
                dataArray.push({ name: name, value: '0' });
            } else {
                dataArray[index].value = '1';
            }
        });

        $.ajax({
            url: url,
            method: method,
            data: $.param(dataArray),
            success: function(res) {
                if (res.success) {
                    $fieldModal.hide();
                    Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500 });
                    $fieldsTable.bootstrapTable('refresh');
                    // Reset selection state
                    $btnEditField.prop('disabled', true);
                    $btnDeleteField.prop('disabled', true);
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let msg = xhr.responseJSON?.message || '';
                if (Object.keys(errors).length > 0) {
                    msg = '';
                    Object.values(errors).forEach(e => msg += e[0] + '<br>');
                }
                Swal.fire('Erreur', msg || 'Une erreur est survenue', 'error');
            },
            complete: function() {
                $btnSaveField.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer');
            }
        });
    });

    // Delete Field button click
    if ($btnDeleteField.length) {
        $btnDeleteField.on('click', function() {
            const row = $fieldsTable.bootstrapTable('getSelections')[0];
            if (!row) return;

            Swal.fire({
                title: 'Supprimer ce champ ?',
                text: "Ce champ technique ne sera plus disponible pour les équipements de cette catégorie. Les valeurs existantes pour les équipements créés seront conservées en base mais non visibles !",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Oui, supprimer'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: route('parc-info.referentiels.categories.fields.destroy', [currentCategoryId, row.id]),
                        method: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(res) {
                            if (res.success) {
                                Swal.fire('Supprimé !', res.message, 'success');
                                $fieldsTable.bootstrapTable('refresh');
                                $btnEditField.prop('disabled', true);
                                $btnDeleteField.prop('disabled', true);
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur lors de la suppression', 'error');
                        }
                    });
                }
            });
        });
    }
});
