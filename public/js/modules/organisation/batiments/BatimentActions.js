/**
 * BatimentActions.js
 * Handles Edit and Delete actions for Batiments.
 */
export class BatimentActions {
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
            const batimentId = this.table.getSelectedId();
            if (batimentId) this.editBatiment(batimentId);
        });

        $('#btn-delete').click(() => {
            const batimentId = this.table.getSelectedId();
            if (batimentId) this.deleteBatiment(batimentId);
        });
    }

    editBatiment(batimentId) {
        $.ajax({
            url: route('organisation.batiments.show', batimentId),
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

    deleteBatiment(batimentId) {
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Cette action va désactiver ce bâtiment",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('organisation.batiments.destroy', batimentId),
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
