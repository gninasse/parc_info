/**
 * Gestion du Catalogue d'Articles - Module Achat
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

window.priceFormatter = function (value) {
    if (value === null || value === undefined) return '-';
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(value);
};

window.statusFormatter = function (value) {
    return value 
        ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Actif</span>' 
        : '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Inactif</span>';
};

window.typeFormatter = function (value, row) {
    return `<span class="badge bg-secondary">${row.type_label}</span>`;
};

document.addEventListener('DOMContentLoaded', function() {
    const $table = $('#items-table');
    const $modal = new bootstrap.Modal('#item-modal');
    const $form = $('#item-form');
    const $btnSave = $('#btn-save');
    
    const $btnAdd = $('#btn-add');
    const $btnEdit = $('#btn-edit');
    const $btnDelete = $('#btn-delete');
    const $btnToggle = $('#btn-toggle');
    const $btnDuplicate = $('#btn-duplicate');

    // ── FILTRES RECHERCHE ──
    $('#filter-type, #filter-marque, #filter-categorie, #filter-status').on('change', function() {
        $table.bootstrapTable('refresh');
    });

    // Passer les filtres à l'AJAX
    $table.bootstrapTable('refreshOptions', {
        queryParams: function(params) {
            params.type_article = $('#filter-type').val();
            params.marque_id = $('#filter-marque').val();
            params.categorie_equipement_id = $('#filter-categorie').val();
            params.actif = $('#filter-status').val();
            return params;
        }
    });

    // ── GESTION DES CHAMPS CONDITIONNELS (TABS) ──
    function ajusterChampsParType(type) {
        // Cacher tous les blocs conditionnels
        $('#group-categorie').addClass('d-none');
        $('#group-seuil-alerte').addClass('d-none');
        $('#group-duree-validite').addClass('d-none');

        // Afficher selon le type
        if (type === 'equipement') {
            $('#group-categorie').removeClass('d-none');
        } else if (type === 'consommable') {
            $('#group-seuil-alerte').removeClass('d-none');
        } else if (type === 'licence') {
            $('#group-duree-validite').removeClass('d-none');
        }
    }

    $form.find('[name="type_article"]').on('change', function() {
        ajusterChampsParType($(this).val());
    });

    // ── SELECTION EVENT ──
    $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
        const selections = $table.bootstrapTable('getSelections');
        const hasOne = selections.length === 1;
        $btnEdit.prop('disabled', !hasOne);
        $btnDelete.prop('disabled', !hasOne);
        $btnToggle.prop('disabled', !hasOne);
        $btnDuplicate.prop('disabled', !hasOne);
    });

    // ── ADD BUTTON ──
    if ($btnAdd.length) {
        $btnAdd.on('click', function() {
            $form[0].reset();
            $('#item-id').val('');
            
            // Activer le premier onglet
            const firstTab = document.querySelector('#item-modal-tabs button[data-bs-target="#tab-general"]');
            if (firstTab) bootstrap.Tab.getInstance(firstTab)?.show() || new bootstrap.Tab(firstTab).show();
            
            // Forcer le type par défaut et ajuster les champs
            $form.find('[name="type_article"]').val('equipement').trigger('change');
            
            $('#modalLabel span').text('Nouveau');
            $modal.show();
        });
    }

    // ── EDIT FUNCTION ──
    function editItem(id) {
        $.ajax({
            url: route('achat.articles.show', id),
            method: 'GET',
            success: function(res) {
                if (res.success) {
                    const d = res.article;
                    $('#item-id').val(d.id);
                    $form.find('[name="code_article"]').val(d.code_article);
                    $form.find('[name="designation"]').val(d.designation);
                    $form.find('[name="description"]').val(d.description);
                    $form.find('[name="type_article"]').val(d.type_article).trigger('change');
                    $form.find('[name="reference_constructeur"]').val(d.reference_constructeur);
                    $form.find('[name="marque_id"]').val(d.marque_id);
                    $form.find('[name="categorie_equipement_id"]').val(d.categorie_equipement_id);
                    $form.find('[name="fournisseur_prefere_id"]').val(d.fournisseur_prefere_id);
                    $form.find('[name="prix_indicatif"]').val(d.prix_indicatif);
                    $form.find('[name="unite_mesure"]').val(d.unite_mesure);
                    $form.find('[name="taux_tva"]').val(d.taux_tva);
                    $form.find('[name="compte_comptable"]').val(d.compte_comptable);
                    $form.find('[name="seuil_alerte"]').val(d.seuil_alerte);
                    $form.find('[name="duree_validite_mois"]').val(d.duree_validite_mois);
                    $form.find('[name="url_fiche_technique"]').val(d.url_fiche_technique);
                    
                    // Activer le premier onglet
                    const firstTab = document.querySelector('#item-modal-tabs button[data-bs-target="#tab-general"]');
                    if (firstTab) bootstrap.Tab.getInstance(firstTab)?.show() || new bootstrap.Tab(firstTab).show();

                    $('#modalLabel span').text('Modifier');
                    $modal.show();
                }
            },
            error: function() {
                Swal.fire('Erreur', 'Impossible de charger les données de l\'article', 'error');
            }
        });
    }

    if ($btnEdit.length) {
        $btnEdit.on('click', function() {
            const row = $table.bootstrapTable('getSelections')[0];
            if (row) editItem(row.id);
        });
    }

    // Double-clic sur une ligne pour éditer
    $table.on('dbl-click-row.bs.table', function(e, row) {
        editItem(row.id);
    });

    // ── SUBMIT FORM ──
    $form.on('submit', function(e) {
        e.preventDefault();
        const id = $('#item-id').val();
        const url = id 
            ? route('achat.articles.update', id) 
            : route('achat.articles.store');
        
        // On utilise FormData pour supporter les uploads d'images
        const formData = new FormData($form[0]);
        if (id) {
            // Emuler PUT en AJAX avec FormData
            formData.append('_method', 'PUT');
        }

        $btnSave.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');

        $.ajax({
            url: url,
            method: 'POST', // Toujours POST pour supporter FormData
            data: formData,
            processData: false,
            contentType: false,
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

    // ── DUPLICATE BUTTON ──
    if ($btnDuplicate.length) {
        $btnDuplicate.on('click', function() {
            const row = $table.bootstrapTable('getSelections')[0];
            if (!row) return;

            $.ajax({
                url: route('achat.articles.dupliquer', row.id),
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Dupliqué !', res.message, 'success');
                        $table.bootstrapTable('refresh');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur lors de la duplication', 'error');
                }
            });
        });
    }

    // ── TOGGLE ACTIVE BUTTON ──
    if ($btnToggle.length) {
        $btnToggle.on('click', function() {
            const row = $table.bootstrapTable('getSelections')[0];
            if (!row) return;

            $.ajax({
                url: route('achat.articles.toggle-actif', row.id),
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Statut modifié !', res.message, 'success');
                        $table.bootstrapTable('refresh');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur lors du changement de statut', 'error');
                }
            });
        });
    }

    // ── DELETE BUTTON ──
    if ($btnDelete.length) {
        $btnDelete.on('click', function() {
            const row = $table.bootstrapTable('getSelections')[0];
            if (!row) return;

            Swal.fire({
                title: 'Supprimer cet article ?',
                text: "Cette action peut désactiver l'article s'il est déjà lié à des commandes !",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Oui, supprimer'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: route('achat.articles.destroy', row.id),
                        method: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(res) {
                            if (res.success) {
                                Swal.fire('Résultat', res.message, 'success');
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
