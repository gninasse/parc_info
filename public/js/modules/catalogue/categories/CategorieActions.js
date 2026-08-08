/**
 * CategorieActions.js — boutons de toolbar (édition, toggle en cascade, suppression)
 */
export class CategorieActions {
    constructor(tableInstance, formInstance) {
        this.table = tableInstance;
        this.form = formInstance;
        this._initButtons();
    }

    _initButtons() {
        $('#btn-add').on('click', () => this.form.openForAdd());

        $('#btn-edit').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) this._loadAndEdit(id);
        });

        $('#btn-toggle').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) this._confirmToggle(id);
        });

        $('#btn-delete').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) this._confirmDelete(id);
        });
    }

    _loadAndEdit(id) {
        $.ajax({
            url: route('catalogue.categories.show', id),
            method: 'GET',
            dataType: 'json',
            success: (res) => {
                if (res.success) this.form.openForEdit(res.data);
            },
            error: () => Swal.fire({ icon: 'error', title: 'Erreur', text: 'Impossible de charger les données.' }),
        });
    }

    _confirmToggle(id) {
        Swal.fire({
            title: 'Changer le statut de cette catégorie ?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Oui, changer',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (!result.isConfirmed) return;
            this._toggle(id, false);
        });
    }

    _toggle(id, cascade) {
        $.ajax({
            url: route('catalogue.categories.toggle-status', id),
            method: 'PATCH',
            data: cascade ? { cascade: 1 } : {},
            dataType: 'json',
            success: (res) => {
                // Parent avec sous-catégories actives : Swal à double option
                if (res.requires_confirmation) {
                    Swal.fire({
                        title: 'Désactiver aussi les sous-catégories ?',
                        text: res.message,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ffc107',
                        confirmButtonText: 'Oui, tout désactiver',
                        cancelButtonText: 'Annuler',
                    }).then((confirmation) => {
                        if (confirmation.isConfirmed) this._toggle(id, true);
                    });
                    return;
                }
                if (res.success) {
                    this.table.refresh();
                    Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 2000, showConfirmButton: false });
                }
            },
            error: (xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Changement de statut impossible.' }),
        });
    }

    _confirmDelete(id) {
        Swal.fire({
            title: 'Supprimer cette catégorie ?',
            text: 'Cette action est irréversible.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: route('catalogue.categories.destroy', id),
                method: 'DELETE',
                dataType: 'json',
                success: (res) => {
                    if (res.success) {
                        this.table.refresh();
                        Swal.fire({ icon: 'success', title: 'Supprimée', text: res.message, timer: 2000, showConfirmButton: false });
                    }
                },
                error: (xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Suppression impossible.' }),
            });
        });
    }
}
