export class InventaireActions {
    constructor(tableInstance, formInstance) {
        this.table = tableInstance;
        this.form  = formInstance;
        this._initToolbarButtons();
    }

    _initToolbarButtons() {
        $('#btn-add-inventaire').on('click', () => this.form.openForAdd());

        $('#btn-view-inventaire').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) {
                this._loadAndConsult(id);
            }
        });

        $('#btn-approve-inventaire').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) {
                this._approveInventaire(id);
            }
        });

        $('#btn-cancel-inventaire').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) {
                this._cancelInventaire(id);
            }
        });
    }

    _loadAndConsult(id) {
        $.ajax({
            url: route('stock.inventaires.show', id),
            method: 'GET',
            success: (res) => {
                if (res.success) {
                    const data = res.data;
                    $('#v-id').val(data.id);
                    $('#v-numero').text(data.numero_inventaire);
                    $('#v-statut-badge').html(window.inventaireStatutFormatter(data.statut));
                    $('#v-magasin-nom').text(data.magasin_nom);
                    $('#v-date-figeage').text(data.date_inventaire);
                    $('#v-creator-nom').text(data.created_by_nom);

                    if (data.statut === 'VALIDE') {
                        $('#v-date-cloture').text(data.date_cloture);
                        $('#v-validator-nom').text(data.valide_par_nom);
                        $('#v-cloture-wrapper, #v-validator-wrapper').removeClass('d-none');
                    } else {
                        $('#v-cloture-wrapper, #v-validator-wrapper').addClass('d-none');
                    }

                    // Render lines
                    const $tbody = $('#v-lignes-list');
                    $tbody.empty();

                    if (!data.lignes.length) {
                        $tbody.append('<tr><td colspan="7" class="text-center text-muted py-3">Aucun article dans ce magasin lors du figeage</td></tr>');
                    } else {
                        data.lignes.forEach(l => {
                            let ecartBadge = '<span class="text-muted">Aucun</span>';
                            if (l.ecart > 0) {
                                ecartBadge = `<span class="badge bg-info text-dark fw-bold">+${l.ecart} (Excédent)</span>`;
                            } else if (l.ecart < 0) {
                                ecartBadge = `<span class="badge bg-danger fw-bold">${l.ecart} (Déficit)</span>`;
                            }

                            $tbody.append(`
                                <tr>
                                    <td><code>${l.article_code}</code></td>
                                    <td><strong>${l.article_designation}</strong></td>
                                    <td class="text-end fw-semibold text-secondary">${l.quantite_theorique}</td>
                                    <td class="text-end fw-bold text-primary">${l.quantite_reelle}</td>
                                    <td class="text-end">${ecartBadge}</td>
                                    <td class="text-end text-muted">${l.cout_unitaire_reference}</td>
                                    <td class="text-end fw-bold ${l.ecart < 0 ? 'text-danger' : (l.ecart > 0 ? 'text-info' : 'text-muted')}">${l.valorisation_ecart}</td>
                                </tr>
                            `);
                        });
                    }

                    $('#viewInventaireModal').modal('show');
                }
            },
            error: () => Swal.fire({ icon: 'error', title: 'Erreur', text: 'Impossible de charger les détails.' })
        });
    }

    _approveInventaire(id) {
        Swal.fire({
            title: 'Clôturer et valider l\'inventaire ?',
            text: 'Les stocks seront automatiquement ajustés (mouvements de régularisation) et cette campagne sera fermée de façon irréversible.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor:  '#3085d6',
            confirmButtonText:  'Oui, clôturer',
            cancelButtonText:   'Annuler'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: route('stock.inventaires.valider', id),
                method: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: (res) => {
                    if (res.success) {
                        this.table.refresh();
                        Swal.fire({ icon: 'success', title: 'Clôturé & Validé', text: res.message, timer: 3000 });
                    }
                },
                error: (xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Impossible de valider la campagne.' })
            });
        });
    }

    _cancelInventaire(id) {
        Swal.fire({
            title: 'Annuler cette campagne d\'inventaire ?',
            text: 'Cette action est définitive.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Oui, annuler',
            cancelButtonText: 'Fermer'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: route('stock.inventaires.annuler', id),
                method: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: (res) => {
                    if (res.success) {
                        this.table.refresh();
                        Swal.fire({ icon: 'success', title: 'Annulé', text: res.message, timer: 2000 });
                    }
                },
                error: (xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Impossible d\'annuler.' })
            });
        });
    }
}
