export class MagasinActions {
    constructor(tableInstance, formInstance) {
        this.table = tableInstance;
        this.form  = formInstance;
        this._initButtons();
        this._initResponsiblesAndRightsSubmit();
    }

    _initButtons() {
        $('#btn-add').on('click', () => this.form.openForAdd());

        $('#btn-edit').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) {
                this._loadAndEdit(id);
            }
        });

        $('#btn-delete').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) {
                this._confirmDelete(id);
            }
        });

        $('#btn-manage-responsibles').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) {
                this._openResponsibles(id);
            }
        });

        $('#btn-manage-rights').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) {
                this._openRights(id);
            }
        });

        // Toggle user/role field inside Rights Modal
        $('#d-type-sujet').on('change', function() {
            const type = $(this).val();
            if (type === 'ROLE') {
                $('#d-sujet-role-wrapper').removeClass('d-none');
                $('#d-sujet-user-wrapper').addClass('d-none');
                $('#d-sujet-user').val('');
            } else {
                $('#d-sujet-role-wrapper').addClass('d-none');
                $('#d-sujet-user-wrapper').removeClass('d-none');
                $('#d-sujet-role').val('');
            }
        });
    }

    _loadAndEdit(id) {
        $.ajax({
            url: route('stock.magasins.show', id),
            method: 'GET',
            success: (res) => {
                if (res.success) {
                    this.form.openForEdit(res.data);
                }
            },
            error: () => Swal.fire({ icon: 'error', title: 'Erreur', text: 'Impossible de charger les données.' })
        });
    }

    _confirmDelete(id) {
        Swal.fire({
            title: 'Supprimer ce magasin ?',
            text: 'Cette action est définitive.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor:  '#3085d6',
            confirmButtonText:  'Oui, supprimer',
            cancelButtonText:   'Annuler'
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: route('stock.magasins.destroy', id),
                method: 'DELETE',
                success: (res) => {
                    if (res.success) {
                        this.table.refresh();
                        Swal.fire({ icon: 'success', title: 'Supprimé', text: res.message, timer: 2000 });
                    }
                },
                error: (xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Suppression impossible.' })
            });
        });
    }

    // --- RESPONSABLES ---
    _openResponsibles(id) {
        $('#responsible-form')[0].reset();
        $('#resp-magasin-id').val(id);
        
        $.ajax({
            url: route('stock.magasins.show', id),
            method: 'GET',
            success: (res) => {
                if (res.success) {
                    $('#resp-magasin-nom').text(res.data.nom);
                    this._renderResponsibles(id, res.data.responsables);
                    $('#responsiblesModal').modal('show');
                }
            },
            error: () => Swal.fire({ icon: 'error', title: 'Erreur', text: 'Impossible de charger les responsables.' })
        });
    }

    _renderResponsibles(magasinId, list) {
        const $tbody = $('#responsibles-list');
        $tbody.empty();
        
        if (!list.length) {
            $tbody.append('<tr><td colspan="4" class="text-center text-muted py-2">Aucun responsable affecté</td></tr>');
            return;
        }

        list.forEach(r => {
            const roleBadge = r.role === 'principal' ? '<span class="badge bg-warning text-white">Principal</span>' : '<span class="badge bg-light text-dark">Adjoint</span>';
            const period = `Du ${this._formatDate(r.date_debut)} ${r.date_fin ? 'au ' + this._formatDate(r.date_fin) : 'actuellement'}`;
            
            const btnDelete = `<button class="btn btn-xs btn-outline-danger btn-delete-resp rounded-1" data-id="${r.id}" data-magasin-id="${magasinId}">
                <i class="fas fa-trash-alt"></i>
            </button>`;
            
            $tbody.append(`
                <tr>
                    <td><strong>${r.employe_nom}</strong></td>
                    <td>${roleBadge}</td>
                    <td>${period}</td>
                    <td class="text-end">${btnDelete}</td>
                </tr>
            `);
        });
    }

    // --- DROITS ---
    _openRights(id) {
        $('#right-form')[0].reset();
        $('#d-type-sujet').trigger('change');
        $('#rights-magasin-id').val(id);

        $.ajax({
            url: route('stock.magasins.show', id),
            method: 'GET',
            success: (res) => {
                if (res.success) {
                    $('#rights-magasin-nom').text(res.data.nom);
                    this._renderRights(id, res.data.droits);
                    $('#rightsModal').modal('show');
                }
            },
            error: () => Swal.fire({ icon: 'error', title: 'Erreur', text: 'Impossible de charger les droits d\'accès.' })
        });
    }

    _renderRights(magasinId, list) {
        const $tbody = $('#rights-list');
        $tbody.empty();
        
        if (!list.length) {
            $tbody.append('<tr><td colspan="3" class="text-center text-muted py-2">Aucun droit configuré</td></tr>');
            return;
        }

        list.forEach(d => {
            const typeBadge = d.type_sujet === 'ROLE' ? '<span class="badge bg-info text-white">Rôle</span>' : '<span class="badge bg-primary text-white">Utilisateur</span>';
            
            let perms = [];
            if (d.peut_lire) perms.push('<span class="badge bg-light text-dark">Lire</span>');
            if (d.peut_entrer_stock) perms.push('<span class="badge bg-light text-dark">Entrer</span>');
            if (d.peut_sortir_stock) perms.push('<span class="badge bg-light text-dark">Sortir</span>');
            if (d.peut_transferer) perms.push('<span class="badge bg-light text-dark">Transférer</span>');
            if (d.peut_inventorier) perms.push('<span class="badge bg-light text-dark">Inventorier</span>');
            if (d.peut_administrer) perms.push('<span class="badge bg-danger text-white">Admin</span>');

            const permStr = perms.join(' ') || '<span class="text-muted">Aucune permission</span>';

            const btnDelete = `<button class="btn btn-xs btn-outline-danger btn-delete-right rounded-1" data-id="${d.id}" data-magasin-id="${magasinId}">
                <i class="fas fa-trash-alt"></i>
            </button>`;
            
            $tbody.append(`
                <tr>
                    <td>${typeBadge} <strong>${d.sujet_nom}</strong></td>
                    <td>${permStr}</td>
                    <td class="text-end">${btnDelete}</td>
                </tr>
            `);
        });
    }

    // --- FORM SUBMIT HANDLERS ---
    _initResponsiblesAndRightsSubmit() {
        // Responsible Form Submit
        $('#responsible-form').on('submit', (e) => {
            e.preventDefault();
            const magasinId = $('#resp-magasin-id').val();
            const $btn = $('#btn-save-resp');
            
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Ajout...');
            
            $.ajax({
                url: route('stock.magasins.responsables.store', magasinId),
                method: 'POST',
                data: $('#responsible-form').serialize(),
                success: (res) => {
                    if (res.success) {
                        $('#responsible-form')[0].reset();
                        // reload list
                        this._reloadResponsiblesList(magasinId);
                        Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500 });
                    }
                },
                error: (xhr) => {
                    Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Impossible d\'ajouter le responsable.' });
                },
                complete: () => {
                    $btn.prop('disabled', false).html('<i class="fas fa-plus me-1"></i> Ajouter le responsable');
                }
            });
        });

        // Delete Responsible
        $(document).on('click', '.btn-delete-resp', (e) => {
            const $btn = $(e.currentTarget);
            const id = $btn.data('id');
            const magasinId = $btn.data('magasin-id');
            
            Swal.fire({
                title: 'Retirer ce responsable ?',
                text: 'Cette action révoquera les droits d\'accès liés au responsable.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Oui, retirer',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (!result.isConfirmed) return;
                $.ajax({
                    url: route('stock.magasins.responsables.destroy', { magasin: magasinId, responsable: id }),
                    method: 'DELETE',
                    success: (res) => {
                        if (res.success) {
                            this._reloadResponsiblesList(magasinId);
                            Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500 });
                        }
                    },
                    error: () => Swal.fire({ icon: 'error', title: 'Erreur', text: 'Impossible de supprimer.' })
                });
            });
        });

        // Right Form Submit
        $('#right-form').on('submit', (e) => {
            e.preventDefault();
            const magasinId = $('#rights-magasin-id').val();
            const $btn = $('#btn-save-right');

            const typeSujet = $('#d-type-sujet').val();
            const sujetId = typeSujet === 'ROLE' ? $('#d-sujet-role').val() : $('#d-sujet-user').val();

            if (!sujetId) {
                Swal.fire({ icon: 'warning', title: 'Attention', text: 'Veuillez sélectionner un sujet (rôle ou utilisateur).' });
                return;
            }

            const data = {
                type_sujet: typeSujet,
                sujet_id: sujetId,
                peut_lire: $('#p-lire').is(':checked') ? 1 : 0,
                peut_entrer_stock: $('#p-entrer').is(':checked') ? 1 : 0,
                peut_sortir_stock: $('#p-sortir').is(':checked') ? 1 : 0,
                peut_transferer: $('#p-transferer').is(':checked') ? 1 : 0,
                peut_inventorier: $('#p-inventorier').is(':checked') ? 1 : 0,
                peut_administrer: $('#p-administrer').is(':checked') ? 1 : 0,
            };

            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

            $.ajax({
                url: route('stock.magasins.droits.store', magasinId),
                method: 'POST',
                data: data,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: (res) => {
                    if (res.success) {
                        $('#right-form')[0].reset();
                        $('#d-type-sujet').val(typeSujet).trigger('change');
                        this._reloadRightsList(magasinId);
                        Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500 });
                    }
                },
                error: (xhr) => {
                    Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Impossible d\'enregistrer les droits.' });
                },
                complete: () => {
                    $btn.prop('disabled', false).html('<i class="fas fa-plus me-1"></i> Enregistrer les droits');
                }
            });
        });

        // Delete Right
        $(document).on('click', '.btn-delete-right', (e) => {
            const $btn = $(e.currentTarget);
            const id = $btn.data('id');
            const magasinId = $btn.data('magasin-id');
            
            Swal.fire({
                title: 'Retirer ces droits d\'accès ?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Oui, retirer',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (!result.isConfirmed) return;
                $.ajax({
                    url: route('stock.magasins.droits.destroy', { magasin: magasinId, droit: id }),
                    method: 'DELETE',
                    success: (res) => {
                        if (res.success) {
                            this._reloadRightsList(magasinId);
                            Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500 });
                        }
                    },
                    error: () => Swal.fire({ icon: 'error', title: 'Erreur', text: 'Impossible de supprimer.' })
                });
            });
        });
    }

    _reloadResponsiblesList(magasinId) {
        $.ajax({
            url: route('stock.magasins.show', magasinId),
            method: 'GET',
            success: (res) => {
                if (res.success) {
                    this._renderResponsibles(magasinId, res.data.responsables);
                }
            }
        });
    }

    _reloadRightsList(magasinId) {
        $.ajax({
            url: route('stock.magasins.show', magasinId),
            method: 'GET',
            success: (res) => {
                if (res.success) {
                    this._renderRights(magasinId, res.data.droits);
                }
            }
        });
    }

    _formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR');
    }
}
