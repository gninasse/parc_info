export class TransfertActions {
    constructor(tableInstance, formInstance) {
        this.table = tableInstance;
        this.form  = formInstance;
        this._initToolbarButtons();
        this._initConsultModalActions();
    }

    _initToolbarButtons() {
        $('#btn-add-transfert').on('click', () => this.form.openForAdd());

        $('#btn-view-transfert').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) {
                this._loadAndConsult(id);
            }
        });

        $('#btn-approve-transfert').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) {
                this._approveTransfert(id);
            }
        });

        $('#btn-reject-transfert').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) {
                this._rejectTransfert(id);
            }
        });

        $('#btn-cancel-transfert').on('click', () => {
            const id = this.table.getSelectedId();
            if (id) {
                this._cancelTransfert(id);
            }
        });
    }

    _initConsultModalActions() {
        $('#v-btn-approve').on('click', () => {
            const id = $('#v-id').val();
            if (id) {
                this._approveTransfert(id, '#viewTransfertModal');
            }
        });

        $('#v-btn-reject').on('click', () => {
            const id = $('#v-id').val();
            if (id) {
                this._rejectTransfert(id, '#viewTransfertModal');
            }
        });
    }

    _loadAndConsult(id) {
        $.ajax({
            url: route('stock.transferts.show', id),
            method: 'GET',
            success: (res) => {
                if (res.success) {
                    const data = res.data;
                    $('#v-id').val(data.id);
                    $('#v-numero').text(data.numero_transfert);
                    $('#v-statut-badge').html(window.transferStatutFormatter(data.statut));
                    $('#v-created-at').text(data.created_at);
                    $('#v-source-nom').text(data.magasin_source_nom);
                    $('#v-dest-nom').text(data.magasin_destination_nom);
                    $('#v-article-nom').text(data.article_designation);
                    $('#v-quantite').text(data.quantite);
                    $('#v-motif-creation').text(data.motif_creation);
                    $('#v-creator-nom').text(data.created_by_nom);

                    // Rejet info
                    if (data.statut === 'REJETE') {
                        $('#v-motif-rejet').text(data.motif_rejet ?? '');
                        $('#v-motif-rejet-wrapper').removeClass('d-none');
                    } else {
                        $('#v-motif-rejet-wrapper').addClass('d-none');
                    }

                    // Validator info
                    if (data.statut === 'VALIDE' || data.statut === 'REJETE') {
                        $('#v-validator-nom').text(data.valide_par_nom);
                        $('#v-validation-date').text(data.date_validation);
                        $('#v-validator-wrapper').removeClass('d-none');
                    } else {
                        $('#v-validator-wrapper').addClass('d-none');
                    }

                    // Show/hide action buttons inside modal
                    if (data.statut === 'EN_ATTENTE') {
                        $('.btn-action-admin').removeClass('d-none');
                    } else {
                        $('.btn-action-admin').addClass('d-none');
                    }

                    $('#viewTransfertModal').modal('show');
                }
            },
            error: () => Swal.fire({ icon: 'error', title: 'Erreur', text: 'Impossible de charger le bon de transfert.' })
        });
    }

    _approveTransfert(id, modalToHide = null) {
        Swal.fire({
            title: 'Valider ce transfert ?',
            text: 'Le stock sera débité du magasin expéditeur et crédité au magasin destinataire de façon immédiate.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor:  '#3085d6',
            confirmButtonText:  'Oui, valider',
            cancelButtonText:   'Annuler'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: route('stock.transferts.valider', id),
                method: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: (res) => {
                    if (res.success) {
                        if (modalToHide) $(modalToHide).modal('hide');
                        this.table.refresh();
                        Swal.fire({ icon: 'success', title: 'Validé', text: res.message, timer: 3000 });
                    }
                },
                error: (xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Une erreur est survenue lors de la validation.' })
            });
        });
    }

    _rejectTransfert(id, modalToHide = null) {
        Swal.fire({
            title: 'Rejeter ce transfert ?',
            text: 'Veuillez saisir le motif du rejet :',
            input: 'textarea',
            inputPlaceholder: 'Entrez le motif du rejet ici...',
            inputAttributes: {
                'aria-label': 'Motif du rejet'
            },
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor:  '#3085d6',
            confirmButtonText:  'Oui, rejeter',
            cancelButtonText:   'Annuler',
            inputValidator: (value) => {
                if (!value) {
                    return 'Vous devez saisir un motif pour rejeter le transfert !';
                }
            }
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: route('stock.transferts.rejeter', id),
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    motif_rejet: result.value
                },
                success: (res) => {
                    if (res.success) {
                        if (modalToHide) $(modalToHide).modal('hide');
                        this.table.refresh();
                        Swal.fire({ icon: 'success', title: 'Rejeté', text: res.message, timer: 2000 });
                    }
                },
                error: (xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Une erreur est survenue.' })
            });
        });
    }

    _cancelTransfert(id, modalToHide = null) {
        Swal.fire({
            title: 'Annuler ce transfert ?',
            text: 'Cette action est définitive.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Oui, annuler',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: route('stock.transferts.annuler', id),
                method: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: (res) => {
                    if (res.success) {
                        if (modalToHide) $(modalToHide).modal('hide');
                        this.table.refresh();
                        Swal.fire({ icon: 'success', title: 'Annulé', text: res.message, timer: 2000 });
                    }
                },
                error: (xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Impossible d\'annuler ce transfert.' })
            });
        });
    }
}
