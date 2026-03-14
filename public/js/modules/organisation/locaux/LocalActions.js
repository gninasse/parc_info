/**
 * LocalActions.js
 * Handles Edit and Delete actions for Locaux.
 */
export class LocalActions {
    constructor(tableInstance, formInstance) {
        this.table = tableInstance;
        this.form = formInstance;
        this.initButtons();
    }

    initButtons() {
        $('#btn-add').click(() => {
            this.form.openForAdd();
        });

        $('#btn-edit').click(() => {
            const localId = this.table.getSelectedId();
            if (localId) this.editLocal(localId);
        });

        $('#btn-delete').click(() => {
            const localId = this.table.getSelectedId();
            if (localId) this.deleteLocal(localId);
        });
    }

    editLocal(localId) {
        $.ajax({
            url: route('organisation.locaux.show', localId),
            method: 'GET',
            success: (response) => {
                if (response.success) {
                    this.form.openForEdit(response.data);
                }
            },
            error: (xhr) => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Impossible de charger les données'
                });
            }
        });
    }

    deleteLocal(localId) {
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Cette action va supprimer ce local",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('organisation.locaux.destroy', localId),
                    method: 'DELETE',
                    success: (response) => {
                        if (response.success) {
                            this.table.refresh();
                            Swal.fire({
                                icon: 'success',
                                title: 'Supprimé',
                                text: response.message,
                                timer: 2000
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erreur',
                                text: response.message
                            });
                        }
                    },
                    error: (xhr) => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: xhr.responseJSON.message || 'Erreur lors de la suppression'
                        });
                    }
                });
            }
        });
    }
}
