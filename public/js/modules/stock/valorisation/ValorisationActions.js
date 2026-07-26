export class ValorisationActions {
    constructor(tableInstance) {
        this.table = tableInstance;
        this._initToolbarButtons();
    }

    _initToolbarButtons() {
        $('#btn-add-snapshot').on('click', () => this._addSnapshot());

        $('#btn-view-snapshot').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) {
                this._loadAndConsult(id);
            }
        });
    }

    _addSnapshot() {
        Swal.fire({
            title: 'Calculer la valorisation globale ?',
            text: 'Cette action va générer un instantané figé de la valeur financière actuelle de votre stock (logique FIFO).',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Oui, lancer le calcul',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (!result.isConfirmed) return;

            // Loader
            Swal.fire({
                title: 'Calcul en cours...',
                text: 'Veuillez patienter',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: route('stock.valorisation.store'),
                method: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: (res) => {
                    if (res.success) {
                        this.table.refresh();
                        Swal.fire({
                            icon: 'success',
                            title: 'Calcul terminé',
                            text: res.message
                        });
                    }
                },
                error: (xhr) => {
                    Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Impossible de calculer la valorisation.' });
                }
            });
        });
    }

    _loadAndConsult(id) {
        $.ajax({
            url: route('stock.valorisation.show', id),
            method: 'GET',
            success: (res) => {
                if (res.success) {
                    const data = res.data;
                    $('#v-reference').text(data.reference);
                    $('#v-type-badge').html(window.typeFormatter(data.type));
                    $('#v-date-snapshot').text(window.dateFormatter(data.date_snapshot));
                    $('#v-creator').text(data.created_by);
                    $('#v-valeur-globale').text(data.valeur_totale_globale);

                    // Render lines
                    const $tbody = $('#v-lignes-list');
                    $tbody.empty();

                    if (!data.lignes.length) {
                        $tbody.append('<tr><td colspan="6" class="text-center text-muted py-3">Aucune ligne de stock n\'a été valorisée</td></tr>');
                    } else {
                        data.lignes.forEach(l => {
                            $tbody.append(`
                                <tr>
                                    <td><strong>${l.magasin}</strong></td>
                                    <td><code>${l.article_code}</code></td>
                                    <td><strong>${l.article_designation}</strong></td>
                                    <td class="text-end fw-semibold">${l.quantite}</td>
                                    <td class="text-end text-muted">${l.cout_unitaire_moyen}</td>
                                    <td class="text-end fw-bold text-success">${l.valeur_total}</td>
                                </tr>
                            `);
                        });
                    }

                    $('#viewSnapshotModal').modal('show');
                }
            },
            error: () => Swal.fire({ icon: 'error', title: 'Erreur', text: 'Impossible de charger le rapport.' })
        });
    }
}
