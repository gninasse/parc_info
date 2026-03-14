/**
 * ServiceActions.js
 * Handles Edit and Delete actions for Services.
 */
export class ServiceActions {
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
            const serviceId = this.table.getSelectedId();
            if (serviceId) this.editService(serviceId);
        });

        $('#btn-delete').click(() => {
            const serviceId = this.table.getSelectedId();
            if (serviceId) this.deleteService(serviceId);
        });
    }

    editService(serviceId) {
        $.ajax({
            url: route('organisation.services.show', serviceId),
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

    deleteService(serviceId) {
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Cette action va désactiver ce service",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('organisation.services.destroy', serviceId),
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
