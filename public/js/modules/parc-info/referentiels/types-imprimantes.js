/**
 * Gestion des Types d'Imprimante - Module Parc Info
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

window.urlFormatter = function (value) {
    if (!value) return '-';
    return `<a href="${value}" target="_blank" class="text-decoration-none"><i class="bi bi-link-45deg me-1"></i>Visiter</a>`;
};

window.statusFormatter = function (value) {
    return value 
        ? '<span class="badge bg-success">Actif</span>' 
        : '<span class="badge bg-danger">Inactif</span>';
};

document.addEventListener('DOMContentLoaded', function() {
    const $table = $('#items-table');
    const $modal = new bootstrap.Modal('#item-modal');
    const $form = $('#item-form');
    const $btnSave = $('#btn-save');
    
    const $btnAdd = $('#btn-add');
    const $btnEdit = $('#btn-edit');
    const $btnDelete = $('#btn-delete');

    // ── SELECTION EVENT ──
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selections = $table.bootstrapTable('getSelections');
        const hasOne = selections.length === 1;
        if ($btnEdit.length) $btnEdit.prop('disabled', !hasOne);
        if ($btnDelete.length) $btnDelete.prop('disabled', !hasOne);
    });

    // ── ADD BUTTON ──
    if ($btnAdd.length) {
        $btnAdd.on('click', function() {
            $form[0].reset();
            $('#item-id').val('');
            $('#modalLabel span').text('Nouveau');
            $modal.show();
        });
    }

    // ── EDIT FUNCTION ──
    function editItem(id) {
        $.ajax({
            url: route('parc-info.referentiels.types-imprimantes.show', id),
            method: 'GET',
            success: function(res) {
                if (res.success) {
                    const d = res.data;
                    $('#item-id').val(d.id);
                $form.find('[name="libelle"]').val(d.libelle);
                    $('#modalLabel span').text('Modifier');
                    $modal.show();
                }
            },
            error: function() {
                Swal.fire('Erreur', 'Impossible de charger les données', 'error');
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
            ? route('parc-info.referentiels.types-imprimantes.update', id) 
            : route('parc-info.referentiels.types-imprimantes.store');
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
                let msg = '';
                Object.values(errors).forEach(e => msg += e[0] + '<br>');
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
                title: 'Supprimer cet élément ?',
                text: "Cette action est définitive !",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Oui, supprimer'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: route('parc-info.referentiels.types-imprimantes.destroy', row.id),
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