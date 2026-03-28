/**
 * PosteTravailActions.js
 * Handles Edit and Delete actions for PosteTravails.
 */
export class PosteTravailActions {
    constructor(tableInstance, formInstance) {
        this.table = tableInstance;
        this.form = formInstance;
        this.initButtons();
    }

    initButtons() {
        $('#btn-add').off('click').on('click', () => {
            this.form.openForAdd();
        });

        $('#btn-edit').off('click').on('click', () => {
            const posteId = this.table.getSelectedId();
            if (posteId) this.editPosteTravail(posteId);
        });

        $('#btn-delete').off('click').on('click', () => {
            const posteId = this.table.getSelectedId();
            if (posteId) this.deletePosteTravail(posteId);
        });
    }

    editPosteTravail(posteId) {
        // Form now handles fetching and setting up chained selects
        this.form.openForEdit({ id: posteId });
    }

    deletePosteTravail(posteId) {
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Cette action va désactiver ce poste de travail",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, désactiver',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('organisation.postes.destroy', posteId),
                    method: 'DELETE',
                    success: (response) => {
                        if (response.success) {
                            this.table.refresh();
                            Swal.fire({
                                icon: 'success',
                                title: 'Désactivé',
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
