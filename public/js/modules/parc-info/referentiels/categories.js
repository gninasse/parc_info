/**
 * Gestion des Catégories d'Équipement - Module Parc Info
 * Pattern: AJAX + Bootstrap Table + Modales
 */

window.dateFormatter = function (value) {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};

document.addEventListener('DOMContentLoaded', function() {
    const $table = $('#items-table');
    const $modal = new bootstrap.Modal('#item-modal');
    const $form = $('#item-form');
    const $btnSave = $('#btn-save');
    
    const $btnAdd = $('#btn-add');
    const $btnEdit = $('#btn-edit');
    const $btnDelete = $('#btn-delete');
    const $btnView = $('#btn-view');
    const $iconeSelect = $('#icone');
    const $iconPreview = $('#icon-preview');

    // ── ICON SELECT PREVIEW ──
    $iconeSelect.on('change', function() {
        const iconClass = $(this).val() || 'bi-cpu';
        $iconPreview.attr('class', 'bi ' + iconClass + ' fs-4');
    });

    // ── SELECTION EVENT ──
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selections = $table.bootstrapTable('getSelections');
        const hasOne = selections.length === 1;
        if ($btnEdit.length) $btnEdit.prop('disabled', !hasOne);
        if ($btnDelete.length) $btnDelete.prop('disabled', !hasOne);
        if ($btnView.length) $btnView.prop('disabled', !hasOne);
    });

    // ── ADD BUTTON ──
    if ($btnAdd.length) {
        $btnAdd.on('click', function() {
            $form[0].reset();
            $('#item-id').val('');
            $('#code-group').removeClass('d-none');
            $('#code').prop('disabled', false);
            $('#modalLabel span').text('Nouvelle');
            $iconeSelect.trigger('change');
            $modal.show();
        });
    }

    // ── CONFIG/VIEW BUTTON ──
    if ($btnView.length) {
        $btnView.on('click', function() {
            const row = $table.bootstrapTable('getSelections')[0];
            if (row) {
                window.location.href = route('parc-info.referentiels.categories.show', row.id);
            }
        });
    }

    // ── EDIT FUNCTION ──
    function editItem(id) {
        $.ajax({
            url: route('parc-info.referentiels.categories.show', id),
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            success: function(res) {
                // If the route returns the full view normally, we request json
                if (res.id) {
                    $('#item-id').val(res.id);
                    $form.find('[name="libelle"]').val(res.libelle);
                    $('#code').val(res.code).prop('disabled', true);
                    $('#code-group').addClass('d-none');
                    $iconeSelect.val(res.icone || 'bi-cpu').trigger('change');
                    $('#modalLabel span').text('Modifier');
                    $modal.show();
                } else if (res.success) {
                    const d = res.data;
                    $('#item-id').val(d.id);
                    $form.find('[name="libelle"]').val(d.libelle);
                    $('#code').val(d.code).prop('disabled', true);
                    $('#code-group').addClass('d-none');
                    $iconeSelect.val(d.icone || 'bi-cpu').trigger('change');
                    $('#modalLabel span').text('Modifier');
                    $modal.show();
                }
            },
            error: function() {
                // Try fetching show endpoint but format accept headers to get JSON
                $.getJSON(route('parc-info.referentiels.categories.show', id) + '?json=1', function(data) {
                    const d = data.data || data;
                    $('#item-id').val(d.id);
                    $form.find('[name="libelle"]').val(d.libelle);
                    $('#code').val(d.code).prop('disabled', true);
                    $('#code-group').addClass('d-none');
                    $iconeSelect.val(d.icone || 'bi-cpu').trigger('change');
                    $('#modalLabel span').text('Modifier');
                    $modal.show();
                }).fail(function() {
                    Swal.fire('Erreur', 'Impossible de charger les données', 'error');
                });
            }
        });
    }

    if ($btnEdit.length) {
        $btnEdit.on('click', function() {
            const row = $table.bootstrapTable('getSelections')[0];
            if (row) editItem(row.id);
        });
    }

    // ── SUBMIT FORM ──
    $form.on('submit', function(e) {
        e.preventDefault();
        const id = $('#item-id').val();
        const url = id 
            ? route('parc-info.referentiels.categories.update', id) 
            : route('parc-info.referentiels.categories.store');
        const method = id ? 'PUT' : 'POST';
        
        $btnSave.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');

        $.ajax({
            url: url,
            method: method,
            data: $form.serialize(),
            success: function(res) {
                if (res.success) {
                    $modal.hide();
                    Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500 });
                    $table.bootstrapTable('refresh');
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
                $btnSave.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer');
            }
        });
    });

    // ── DELETE BUTTON ──
    if ($btnDelete.length) {
        $btnDelete.on('click', function() {
            const row = $table.bootstrapTable('getSelections')[0];
            if (!row) return;

            Swal.fire({
                title: 'Supprimer cette catégorie ?',
                text: "Tous les champs personnalisés configurés ainsi que les permissions de cette catégorie seront supprimés ! Cette action est irréversible !",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Oui, supprimer'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: route('parc-info.referentiels.categories.destroy', row.id),
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
