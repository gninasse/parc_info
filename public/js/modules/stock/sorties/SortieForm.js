export class SortieForm {
    constructor(modalSelector, formSelector, tableInstance) {
        this.$modal = $(modalSelector);
        this.$form  = $(formSelector);
        this.table  = tableInstance;
        this._initEvents();
        this._initSubmission();
        this._clearErrorsOnInput();
    }

    openForAdd() {
        this.$form[0].reset();
        this._clearErrors();
        
        // Trigger changes to reset visibility of conditional fields
        $('#s-type-aff').trigger('change');
        $('#s-type-cible').trigger('change');

        this.$modal.modal('show');
    }

    _initEvents() {
        // Toggle target fields
        $('#s-type-cible').on('change', function () {
            const type = $(this).val();
            
            // Hide all
            $('#wrapper-cible-employe, #wrapper-cible-service, #wrapper-cible-direction, #wrapper-cible-unite').addClass('d-none');
            $('.select-cible-field').prop('required', false);

            // Show selected
            if (type === 'EMPLOYE') {
                $('#wrapper-cible-employe').removeClass('d-none');
                $('#s-cible-emp').prop('required', true);
            } else if (type === 'SERVICE') {
                $('#wrapper-cible-service').removeClass('d-none');
                $('#s-cible-srv').prop('required', true);
            } else if (type === 'DIRECTION') {
                $('#wrapper-cible-direction').removeClass('d-none');
                $('#s-cible-dir').prop('required', true);
            } else if (type === 'UNITE') {
                $('#wrapper-cible-unite').removeClass('d-none');
                $('#s-cible-uni').prop('required', true);
            }
        });

        // Toggle asset fields
        $('#s-type-aff').on('change', function () {
            const type = $(this).val();

            // Hide both
            $('#wrapper-asset-equipement, #wrapper-asset-licence').addClass('d-none');
            $('#s-asset-eq, #s-asset-lic').prop('required', false).val('');

            if (type === 'EQUIPEMENT') {
                $('#wrapper-asset-equipement').removeClass('d-none');
                $('#s-asset-eq').prop('required', true);
            } else if (type === 'LICENCE') {
                $('#wrapper-asset-licence').removeClass('d-none');
                $('#s-asset-lic').prop('required', true);
            }
        });
    }

    _initSubmission() {
        this.$form.on('submit', (e) => {
            e.preventDefault();

            // Sync cible_id based on active type
            const type = $('#s-type-cible').val();
            let cibleId = '';
            if (type === 'EMPLOYE') {
                cibleId = $('#s-cible-emp').val();
            } else if (type === 'SERVICE') {
                cibleId = $('#s-cible-srv').val();
            } else if (type === 'DIRECTION') {
                cibleId = $('#s-cible-dir').val();
            } else if (type === 'UNITE') {
                cibleId = $('#s-cible-uni').val();
            }
            $('#s-cible-id').val(cibleId);

            const $btn = $('#btn-save-sortie-submit');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Traitement...');

            $.ajax({
                url: route('stock.sorties.store'),
                method: 'POST',
                data: this.$form.serialize(),
                success: (res) => {
                    if (res.success) {
                        this.$modal.modal('hide');
                        this.table.refresh();
                        Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 3000 });
                    }
                },
                error: (xhr) => {
                    if (xhr.status === 422) {
                        this._displayErrors(xhr.responseJSON.errors);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Impossible de valider la sortie.' });
                    }
                },
                complete: () => {
                    $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Valider la sortie');
                }
            });
        });
    }

    _displayErrors(errors) {
        this._clearErrors();
        $.each(errors, (field, messages) => {
            let $field = this.$form.find(`[name="${field}"]`);
            if (field === 'cible_id') {
                // If target selection error, highlight the active select field
                const type = $('#s-type-cible').val();
                if (type === 'EMPLOYE') $field = $('#s-cible-emp');
                if (type === 'SERVICE') $field = $('#s-cible-srv');
                if (type === 'DIRECTION') $field = $('#s-cible-dir');
                if (type === 'UNITE') $field = $('#s-cible-uni');
            }
            $field.addClass('is-invalid');
            $field.after(`<div class="invalid-feedback d-block">${messages[0]}</div>`);
        });
    }

    _clearErrors() {
        this.$form.find('.is-invalid').removeClass('is-invalid');
        this.$form.find('.invalid-feedback').remove();
    }

    _clearErrorsOnInput() {
        this.$form.on('input change', '.is-invalid', function () {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        });
    }
}
