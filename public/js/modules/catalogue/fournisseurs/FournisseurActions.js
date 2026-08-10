/**
 * FournisseurActions.js — boutons de toolbar (fiche, édition, toggle, suppression)
 */
export class FournisseurActions {
    constructor(tableInstance, formInstance) {
        this.table = tableInstance;
        this.form = formInstance;
        this._initButtons();
    }

    _initButtons() {
        $('#btn-add').on('click', () => this.form.openForAdd());

        $('#btn-show').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) window.location.href = route('catalogue.fournisseurs.show', id);
        });

        $('#btn-edit').on('click', () => {
            const id = this.table.getSelectedId();
            // L'édition se fait SUR LA FICHE, pas dans une modale posée
            // au-dessus du tableau : on modifie en voyant les contacts, les
            // articles et le journal du fournisseur, au lieu d'éditer à
            // l'aveugle. Le paramètre ?edit=1 demande à la fiche d'ouvrir la
            // modale d'emblée, sans clic supplémentaire.
            if (id) window.location.href = `${route('catalogue.fournisseurs.show', id)}?edit=1`;
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

    _confirmToggle(id) {
        Swal.fire({
            title: 'Changer le statut de ce fournisseur ?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Oui, changer',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: route('catalogue.fournisseurs.toggle-status', id),
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
            title: 'Supprimer ce fournisseur ?',
            text: 'Cette action est irréversible.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: route('catalogue.fournisseurs.destroy', id),
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
