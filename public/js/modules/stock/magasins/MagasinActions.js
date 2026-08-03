/**
 * MagasinActions.js — toolbar (fiche, édition, toggle, suppression) avec les
 * SW-422 de garde « magasin garni » (bouton contextuel vers l'état des stocks).
 */
export class MagasinActions {
    constructor(tableInstance, formInstance) {
        this.table = tableInstance;
        this.form = formInstance;
        this._initButtons();
    }

    _initButtons() {
        $('#btn-add').on('click', () => this.form.openForAdd());

        $('#btn-show').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) window.location.href = route('stock.magasins.show', id);
        });

        $('#btn-edit').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) this.chargerEtEditer(id);
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

    chargerEtEditer(id) {
        $.ajax({
            url: route('stock.magasins.show', id),
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
            title: 'Changer le statut de ce magasin ?',
            text: 'Un magasin garni ne peut pas être désactivé.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Oui, changer',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: route('stock.magasins.toggle-status', id),
                method: 'PATCH',
                dataType: 'json',
                success: (res) => {
                    if (res.success) {
                        this.table.refresh();
                        Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 2000, showConfirmButton: false });
                    }
                },
                error: (xhr) => this._swalGarde(xhr, 'Changement de statut impossible.'),
            });
        });
    }

    _confirmDelete(id) {
        Swal.fire({
            title: 'Supprimer ce magasin ?',
            text: 'Cette action est irréversible.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: route('stock.magasins.destroy', id),
                method: 'DELETE',
                dataType: 'json',
                success: (res) => {
                    if (res.success) {
                        this.table.refresh();
                        Swal.fire({ icon: 'success', title: 'Supprimé', text: res.message, timer: 2000, showConfirmButton: false });
                    }
                },
                error: (xhr) => this._swalGarde(xhr, 'Suppression impossible.'),
            });
        });
    }

    /** SW-422 : message de garde + bouton contextuel « Voir l'état des stocks ». */
    _swalGarde(xhr, defaut) {
        const reponse = xhr.responseJSON ?? {};

        Swal.fire({
            icon: 'error',
            title: 'Opération refusée',
            text: reponse.message ?? defaut,
            showCancelButton: Boolean(reponse.action),
            cancelButtonText: 'Fermer',
            confirmButtonText: reponse.action?.label ?? 'Fermer',
        }).then((result) => {
            if (result.isConfirmed && reponse.action?.url) {
                window.location.href = reponse.action.url;
            }
        });
    }
}
