/**
 * CategorieForm.js — modale duale création/édition + validation + soumission AJAX
 */
export class CategorieForm {
    constructor(modalSelector, formSelector, tableInstance) {
        this.$modal = $(modalSelector);
        this.$form = $(formSelector);
        this.table = tableInstance;
        this._initSubmission();
        this._clearErrorsOnInput();
    }

    openForAdd() {
        this._chargerParents(null, null, () => {
            this._reset();
            $('#modal-title-text').text('Nouvelle catégorie');
            $('#f-code').val('');
            this.$modal.modal('show');
        });
    }

    openForEdit(data) {
        // On exclut la catégorie elle-même de la liste des parents possibles
        this._chargerParents(data.parent_id, data.id, () => {
            this._reset();
            $('#categorie-id').val(data.id);
            $('#modal-title-text').text('Modifier la catégorie');
            $('#f-libelle').val(data.libelle);
            $('#f-code').val(data.code);
            $('#f-parent').val(data.parent_id ?? '');
            this.$modal.modal('show');
        });
    }

    _chargerParents(selectedId, excludeId, ensuite) {
        $.ajax({
            url: route('catalogue.categories.parents'),
            method: 'GET',
            dataType: 'json',
            success: (res) => {
                const $select = $('#f-parent');
                $select.find('option:not(:first)').remove();
                (res.data ?? []).forEach((parent) => {
                    if (excludeId && Number(parent.id) === Number(excludeId)) return;
                    $select.append(new Option(parent.libelle, parent.id, false, Number(parent.id) === Number(selectedId)));
                });
                ensuite();
            },
            error: () => Swal.fire({ icon: 'error', title: 'Erreur', text: 'Impossible de charger les catégories parentes.' }),
        });
    }

    _reset() {
        this.$form[0].reset();
        this._clearErrors();
        $('#categorie-id').val('');
    }

    _initSubmission() {
        this.$form.on('submit', (e) => {
            e.preventDefault();
            const id = $('#categorie-id').val();
            const url = id
                ? route('catalogue.categories.update', id)
                : route('catalogue.categories.store');
            const method = id ? 'PUT' : 'POST';

            const $btn = $('#btn-save');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

            $.ajax({
                url,
                method,
                data: this.$form.serialize(),
                dataType: 'json',
                success: (res) => {
                    if (res.success) {
                        this.$modal.modal('hide');
                        this.table.refresh();
                        Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 2000, showConfirmButton: false });
                    }
                },
                error: (xhr) => {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        this._displayErrors(xhr.responseJSON.errors);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Une erreur est survenue.' });
                    }
                },
                complete: () => {
                    $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Enregistrer');
                },
            });
        });
    }

    _displayErrors(errors) {
        this._clearErrors();
        $.each(errors, (field, messages) => {
            const $field = this.$form.find(`[name="${field}"]`);
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
