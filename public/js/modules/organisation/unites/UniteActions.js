/**
 * UniteActions.js
 * Handles Edit and Delete actions for Unités de Mesure.
 */
export class UniteActions {
    constructor(tableInstance, formInstance) {
        this.table = tableInstance;
        this.form = formInstance;
        this.initButtons();
    }

    initButtons() {
        $('#btn-add-unite').click(() => {
            this.form.openForAdd();
        });

        $('#btn-edit-unite').click(() => {
            const unite = this.table.getSelected();
            if (unite) this.form.openForEdit(unite);
        });

        $('#btn-delete-unite').click(() => {
            const uniteId = this.table.getSelectedId();
            if (uniteId) this.deleteUnite(uniteId);
        });
    }

    deleteUnite(id) {
        Swal.fire({
            title: 'Êtes-vous sûr?',
            text: "Cette action désactivera l'unité!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonText: 'Annuler',
            confirmButtonText: 'Oui, supprimer!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('organisation.unites.destroy', id),
                    method: 'DELETE',
                    success: (response) => {
                        this.table.refresh();
                        Swal.fire('Supprimé!', response.message, 'success');
                    },
                    error: (xhr) => {
                        Swal.fire('Erreur', xhr.responseJSON?.message || 'Une erreur est survenue', 'error');
                    }
                });
            }
        });
    }
}
