/**
 * ArticleActions.js — boutons de toolbar (édition, duplication, toggle, suppression)
 */
export class ArticleActions {
    constructor(tableInstance, formInstance) {
        this.table = tableInstance;
        this.form = formInstance;
        this._initButtons();
    }

    _initButtons() {
        $('#btn-add').on('click', () => this.form.openForAdd());

        $('#btn-edit').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) this._charger(id, (data) => this.form.openForEdit(data));
        });

        $('#btn-duplicate').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) this._charger(id, (data) => this.form.openForDuplicate(data));
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

    _charger(id, ensuite) {
        $.ajax({
            url: route('catalogue.articles.show', id),
            method: 'GET',
            dataType: 'json',
            success: (res) => {
                if (res.success) ensuite(res.data);
            },
            error: () => Swal.fire({ icon: 'error', title: 'Erreur', text: 'Impossible de charger les données.' }),
        });
    }

    _confirmToggle(id) {
        Swal.fire({
            title: 'Changer le statut de cet article ?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Oui, changer',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: route('catalogue.articles.toggle-status', id),
                method: 'PATCH',
                dataType: 'json',
                success: (res) => {
                    if (res.success) {
                        this.table.refresh();
                        Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 2000, showConfirmButton: false });
                    }
                },
                error: (xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Changement de statut impossible.' }),
            });
        });
    }

    _confirmDelete(id) {
        Swal.fire({
            title: 'Supprimer cet article ?',
            text: 'Cette action est irréversible.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: route('catalogue.articles.destroy', id),
                method: 'DELETE',
                dataType: 'json',
                success: (res) => {
                    if (res.success) {
                        this.table.refresh();
                        Swal.fire({ icon: 'success', title: 'Supprimé', text: res.message, timer: 2000, showConfirmButton: false });
                    }
                },
                error: (xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Suppression impossible.' }),
            });
        });
    }
}
