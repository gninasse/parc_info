/**
 * ArticleForm.js — modale duale à formulaire conditionnel (4 natures).
 *
 * Principe : les blocs [data-nature] ne sont visibles que pour la nature
 * sélectionnée ; leurs champs cachés sont VIDÉS et DISABLED pour ne jamais
 * partir au POST (les FormRequests rejettent de toute façon un POST forgé).
 */
export class ArticleForm {
    constructor(modalSelector, formSelector, tableInstance) {
        this.$modal = $(modalSelector);
        this.$form = $(formSelector);
        this.table = tableInstance;
        this.referentiels = null;
        this._initNatureCards();
        this._initSubmission();
        this._clearErrorsOnInput();
        this._initSelect2();
    }

    async openForAdd() {
        await this._chargerReferentiels();
        this._reset();
        $('#modal-title-text').text('Nouvel article');
        this._selectNature('consommable', false);
        this.$modal.modal('show');
    }

    async openForEdit(data) {
        await this._chargerReferentiels();
        this._reset();
        $('#article-id').val(data.id);
        $('#modal-title-text').text("Modifier l'article");
        this._remplir(data);
        $('#f-code').val(data.code).prop('disabled', true); // code immuable (C6)
        this._selectNature(data.nature, true);
        this.$modal.modal('show');
    }

    /** Dupliquer : pré-remplie SANS id ni code, nature déverrouillée. */
    async openForDuplicate(data) {
        await this._chargerReferentiels();
        this._reset();
        $('#modal-title-text').text("Dupliquer l'article");
        this._remplir(data);
        $('#f-code').val('').prop('disabled', false);
        this._selectNature(data.nature, false);
        this.$modal.modal('show');
    }

    _remplir(data) {
        $('#f-nom').val(data.nom);
        $('#f-categorie').val(data.categorie_id);
        $('#f-fournisseur').val(data.fournisseur_principal_id ?? '').trigger('change.select2');
        $('#f-marque').val(data.marque_id ?? '').trigger('change.select2');
        $('#f-modele').val(data.modele ?? '');
        $('#f-reference').val(data.reference_constructeur ?? '');
        $('#f-prix').val(data.prix_indicatif ?? '');
        $('#f-tva').val(data.taux_tva ?? 18);
        $('#f-compte').val(data.compte_comptable ?? '');
        $('#f-unite').val(data.unite_stock ?? '');
        $('#f-seuil').val(data.seuil_defaut ?? '');
        $('#f-categorie-equipement').val(data.categorie_equipement_id ?? '').trigger('change.select2');
        $('#f-logiciel').val(data.logiciel_id ?? '').trigger('change.select2');
        $('#f-compatibilites').val(data.compatibilites ?? []).trigger('change.select2');
        $('#f-notes').val(data.notes ?? '');
    }

    /** Charge une seule fois catégories/marques/catégories équip./logiciels. */
    _chargerReferentiels() {
        if (this.referentiels) return Promise.resolve(this.referentiels);

        return Promise.all([
            $.getJSON(route('catalogue.articles.categories-cascade')),
            $.getJSON(route('catalogue.articles.marques')),
            $.getJSON(route('catalogue.articles.categories-equipements')),
            $.getJSON(route('catalogue.articles.logiciels')),
        ]).then(([categories, marques, categoriesEquipements, logiciels]) => {
            this.referentiels = { categories: categories.data, marques: marques.data, categoriesEquipements: categoriesEquipements.data, logiciels: logiciels.data };
            this._peuplerSelects();
            return this.referentiels;
        }).catch(() => {
            Swal.fire({ icon: 'error', title: 'Erreur', text: 'Impossible de charger les référentiels du formulaire.' });
            throw new Error('referentiels');
        });
    }

    _peuplerSelects() {
        const $categorie = $('#f-categorie');
        this.referentiels.categories.forEach((racine) => {
            $categorie.append(new Option(racine.libelle, racine.id));
            racine.enfants.forEach((enfant) => {
                $categorie.append(new Option(`  └ ${enfant.libelle}`, enfant.id));
            });
        });

        const $marque = $('#f-marque');
        this.referentiels.marques.forEach((m) => $marque.append(new Option(m.libelle, m.id)));

        const $catEquip = $('#f-categorie-equipement');
        this.referentiels.categoriesEquipements.forEach((c) => $catEquip.append(new Option(c.libelle, c.id)));

        const $compat = $('#f-compatibilites');
        this.referentiels.categoriesEquipements.forEach((c) => $compat.append(new Option(c.libelle, c.code)));

        const $logiciel = $('#f-logiciel');
        this.referentiels.logiciels.forEach((l) => {
            const option = new Option(l.text, l.id);
            $(option).data('sous-titre', [l.editeur, l.type_licence].filter(Boolean).join(' — '));
            $logiciel.append(option);
        });
        this._initSelect2Logiciel();
    }

    _initSelect2() {
        const options = { theme: 'bootstrap-5', dropdownParent: this.$modal, width: '100%' };
        $('#f-fournisseur, #f-marque, #f-categorie-equipement').select2(options);
        $('#f-compatibilites').select2({ ...options, placeholder: 'Sélectionnez des catégories…' });
    }

    /** Select2 riche du logiciel : nom + « éditeur — type » en sous-titre. */
    _initSelect2Logiciel() {
        const rendu = (item) => {
            if (!item.id) return item.text;
            const sousTitre = $(item.element).data('sous-titre');
            const $rendu = $(`<div><div>${$('<span>').text(item.text).html()}</div></div>`);
            if (sousTitre) $rendu.append(`<div class="small text-muted">${$('<span>').text(sousTitre).html()}</div>`);
            return $rendu;
        };
        $('#f-logiciel').select2({
            theme: 'bootstrap-5',
            dropdownParent: this.$modal,
            width: '100%',
            templateResult: rendu,
        });
    }

    _initNatureCards() {
        this.$form.on('click', '.carte-nature', (e) => {
            const $carte = $(e.currentTarget);
            if ($carte.hasClass('verrouillee')) return;
            this._selectNature($carte.data('nature-carte'), false);
        });
    }

    _selectNature(nature, verrouillee) {
        $('.carte-nature').removeClass('selectionnee verrouillee');
        $('.carte-nature .cadenas-nature').addClass('d-none');
        const $carte = $(`.carte-nature[data-nature-carte="${nature}"]`);
        $carte.addClass('selectionnee');
        $carte.find('input[name="nature"]').prop('checked', true);

        if (verrouillee) {
            // C6 : nature immuable en édition — cadenas + tooltip
            $('.carte-nature').addClass('verrouillee');
            $carte.removeClass('verrouillee');
            $('.carte-nature.verrouillee .cadenas-nature').removeClass('d-none');
            $('.carte-nature').addClass('verrouillee');
        }

        this._basculerBlocs(nature);
    }

    /** Affiche les blocs de la nature ; vide ET désactive les champs cachés. */
    _basculerBlocs(nature) {
        $('[data-nature]').each((_, bloc) => {
            const $bloc = $(bloc);
            const natures = String($bloc.data('nature')).split(' ');
            const visible = natures.includes(nature);
            $bloc.toggleClass('d-none', !visible);
            $bloc.find('input, select, textarea').each((_, champ) => {
                const $champ = $(champ);
                $champ.prop('disabled', !visible);
                if (!visible) {
                    if ($champ.is('select[multiple]')) $champ.val([]).trigger('change.select2');
                    else if ($champ.is('select')) $champ.val('').trigger('change.select2');
                    else $champ.val('');
                }
            });
        });
    }

    _reset() {
        this.$form[0].reset();
        this._clearErrors();
        $('#article-id').val('');
        $('#f-code').prop('disabled', false);
        $('#f-tva').val(18);
        $('#f-compte').val('');
        $('#f-fournisseur, #f-marque, #f-categorie-equipement, #f-logiciel').val('').trigger('change.select2');
        $('#f-compatibilites').val([]).trigger('change.select2');
        new bootstrap.Tab(document.querySelector('#onglet-general-tab')).show();
    }

    _initSubmission() {
        this.$form.on('submit', (e) => {
            e.preventDefault();
            const id = $('#article-id').val();
            const url = id ? route('catalogue.articles.update', id) : route('catalogue.articles.store');
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
        let $premierChamp = null;

        $.each(errors, (field, messages) => {
            const nom = field.includes('.') ? `${field.split('.')[0]}[]` : field;
            const $field = this.$form.find(`[name="${nom}"]`);
            if (!$field.length) return;
            $field.addClass('is-invalid');
            // Après le conteneur Select2 le cas échéant
            const $ancre = $field.next('.select2').length ? $field.next('.select2') : $field;
            $ancre.after(`<div class="invalid-feedback d-block">${messages[0]}</div>`);
            if (!$premierChamp) $premierChamp = $field;
        });

        // Bascule automatique vers l'onglet contenant la première erreur
        if ($premierChamp) {
            const $onglet = $premierChamp.closest('.tab-pane');
            if ($onglet.length && !$onglet.hasClass('active')) {
                new bootstrap.Tab(document.querySelector(`[data-bs-target="#${$onglet.attr('id')}"]`)).show();
            }
        }
    }

    _clearErrors() {
        this.$form.find('.is-invalid').removeClass('is-invalid');
        this.$form.find('.invalid-feedback').remove();
    }

    _clearErrorsOnInput() {
        this.$form.on('input change', '.is-invalid', function () {
            $(this).removeClass('is-invalid');
            $(this).nextAll('.invalid-feedback').first().remove();
        });
    }
}
