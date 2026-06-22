/**
 * Gestion des Dictionnaires - Module Parc Info
 */

document.addEventListener('DOMContentLoaded', function() {
    const $table = $('#items-table');
    const $modal = new bootstrap.Modal('#item-modal');
    const $form = $('#item-form');
    const $btnSave = $('#btn-save');

    const $btnAdd = $('#btn-add');
    const $btnEdit = $('#btn-edit');
    const $btnManage = $('#btn-manage');
    const $btnDelete = $('#btn-delete');

    // ── SELECTION EVENT ──
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selections = $table.bootstrapTable('getSelections');
        const hasOne = selections.length === 1;

        if (hasOne) {
            const row = selections[0];
            $btnManage.prop('disabled', false);

            // Cannot delete or rename system dictionaries, but can edit description/libelle
            $btnEdit.prop('disabled', false);
            $btnDelete.prop('disabled', row.is_system);
        } else {
            $btnManage.prop('disabled', true);
            $btnEdit.prop('disabled', true);
            $btnDelete.prop('disabled', true);
        }
    });

    // ── ADD BUTTON ──
    if ($btnAdd.length) {
        $btnAdd.on('click', function() {
            $form[0].reset();
            $('#item-id').val('');
            $('#code').prop('disabled', false);
            $('#modalLabel span').text('Nouveau');
            $modal.show();
        });
    }

    // ── EDIT FUNCTION ──
    function editItem(row) {
        $form[0].reset();
        $('#item-id').val(row.id);
        $('#code').val(row.code).prop('disabled', true); // Code cannot be modified
        $('#libelle').val(row.libelle);
        $('#description').val(row.description);
        $('#modalLabel span').text('Modifier');
        $modal.show();
    }

    if ($btnEdit.length) {
        $btnEdit.on('click', function() {
            const row = $table.bootstrapTable('getSelections')[0];
            if (row) {
                editItem(row);
            }
        });
    }

    // ── MANAGE VALUES BUTTON ──
    if ($btnManage.length) {
        $btnManage.on('click', function() {
            const row = $table.bootstrapTable('getSelections')[0];
            if (row) {
                window.location.href = route('parc-info.referentiels.dictionnaires.valeurs.index', row.code);
            }
        });
    }

    // ── SUBMIT FORM ──
    $form.on('submit', function(e) {
        e.preventDefault();
        const id = $('#item-id').val();

        // Enable code field so it gets serialized when creating
        $('#code').prop('disabled', false);
        const formData = $form.serialize();
        if (id) {
            // Restore disabled state on edit
            $('#code').prop('disabled', true);
        }

        const url = id
            ? route('parc-info.referentiels.dictionnaires.update', id)
            : route('parc-info.referentiels.dictionnaires.store');
        const method = id ? 'PUT' : 'POST';

        $btnSave.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');

        $.ajax({
            url: url,
            method: method,
            data: formData,
            success: function(res) {
                if (res.success) {
                    $modal.hide();
                    Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500 });
                    $table.bootstrapTable('refresh');
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let msg = '';
                Object.values(errors).forEach(e => msg += e[0] + '<br>');
                Swal.fire('Erreur', msg || xhr.responseJSON?.message || 'Une erreur est survenue', 'error');
            },
            complete: function() {
                $btnSave.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer');
            }
        });
    });

    // ── DELETE BUTTON ──
    if ($btnDelete.length) {
        $btnDelete.on('click', function() {
            const row = $table.bootstrapTable('getSelections')[0];
            if (!row || row.is_system) {
                return;
            }

            Swal.fire({
                title: 'Supprimer ce dictionnaire ?',
                text: "Cette action supprimera également toutes ses valeurs de façon définitive !",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Oui, supprimer'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: route('parc-info.referentiels.dictionnaires.destroy', row.id),
                        method: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(res) {
                            if (res.success) {
                                Swal.fire('Supprimé !', res.message, 'success');
                                $table.bootstrapTable('refresh');
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
