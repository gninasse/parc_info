/**
 * erreurs-formulaire.js — affichage unifié des erreurs de validation du
 * module (UX §0.6). Remplace les Swal qui déversaient une liste de messages :
 * l'utilisateur voit désormais QUOI corriger et OÙ.
 *
 * Trois niveaux d'affichage simultanés :
 *   1. un bandeau récapitulatif en tête de formulaire, dont chaque entrée est
 *      cliquable et amène au champ fautif ;
 *   2. le champ lui-même en `is-invalid` avec son `invalid-feedback` ;
 *   3. pour les lignes dynamiques (`lignes.2.quantite`), la rangée surlignée
 *      et le message posé sous la bonne cellule.
 *
 * Les refus qui ne sont pas des erreurs de saisie (409 verrouillage, 403,
 * 500) prennent un bandeau distinct : ce n'est pas au magasinier de corriger
 * un champ dans ces cas-là.
 *
 * Usage :
 *   const erreurs = new ErreursFormulaire('#entree-form', {
 *       lignes: { conteneur: '#table-lignes tbody' },
 *       champs: { beneficiaire_type: { selecteur: '#cartes-beneficiaire', libelle: 'Bénéficiaire' } },
 *   });
 *   erreurs.afficher(xhr);
 */

/** Correspondance clé de ligne → contrôle, pour les tables dynamiques. */
const CHAMPS_LIGNE_PAR_DEFAUT = {
    quantite: '.input-quantite',
    cout_unitaire: '.input-cout',
    article_id: '.select-article',
    equipement_id: '.select-article',
    emplacement_local_id: '.select-emplacement',
};

const echapper = (texte) => $('<span>').text(texte ?? '').html();

export class ErreursFormulaire {
    /**
     * @param {string} formulaire  sélecteur du <form>
     * @param {{bandeau?: string, lignes?: {conteneur: string, champs?: object, libelle?: function},
     *          champs?: Object<string, {selecteur: string, libelle?: string}>}} options
     */
    constructor(formulaire, options = {}) {
        this.$form = $(formulaire);
        this.options = options;
        this.champsSpeciaux = options.champs ?? {};
        this.lignes = options.lignes ?? null;

        this.$bandeau = this._resoudreBandeau(options.bandeau);
        this._surCorrection();
    }

    /** Point d'entrée : reçoit la réponse jQuery en échec. */
    afficher(xhr) {
        this.effacer();

        if (xhr.status === 422 && xhr.responseJSON?.errors) {
            this._afficherValidation(xhr.responseJSON.errors);
            return;
        }

        // Refus métier ou technique : rien à corriger champ par champ
        const message = xhr.responseJSON?.message;
        const blocage = {
            409: { titre: 'Opération impossible', icone: 'bi-lock-fill', defaut: 'Ce bon n\'est plus modifiable.' },
            403: { titre: 'Droits insuffisants', icone: 'bi-shield-lock', defaut: 'Vous n\'avez pas les droits nécessaires pour cette action.' },
            0: { titre: 'Connexion perdue', icone: 'bi-wifi-off', defaut: 'Vérifiez votre connexion, puis réessayez.' },
        }[xhr.status] ?? { titre: 'Une erreur est survenue', icone: 'bi-exclamation-octagon', defaut: 'Réessayez ; si le problème persiste, signalez-le.' };

        this._rendreBandeau({
            classeBlocage: xhr.status === 409,
            icone: blocage.icone,
            titre: blocage.titre,
            corps: `<p class="mb-0 small">${echapper(message ?? blocage.defaut)}</p>`,
            action: xhr.responseJSON?.action ?? null,
        });
    }

    /** Efface bandeau, marques de champ et surlignages de ligne. */
    effacer() {
        this.$bandeau.addClass('d-none').empty();
        this.$form.find('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');
        this.$form.find('.select2-container').removeClass('est-invalide');
        this.$form.find('.bloc-invalide').removeClass('bloc-invalide');
        this.$form.find('.invalid-feedback.erreur-serveur, .message-ligne').remove();
        this.$form.find('tr.ligne-en-erreur').removeClass('ligne-en-erreur');
    }

    // ── Validation champ par champ ────────────────────────────────────────

    _afficherValidation(erreurs) {
        const entrees = Object.entries(erreurs).map(([cle, messages]) => ({
            cle,
            message: Array.isArray(messages) ? messages[0] : messages,
            ...this._localiser(cle),
        }));

        entrees.forEach((entree) => this._marquerChamp(entree));

        const nombre = entrees.length;
        const corps = `
            <ul>
                ${entrees.map((e, index) => `
                    <li>
                        <button type="button" class="lien-erreur" data-index="${index}">
                            <span class="champ-erreur">${echapper(e.libelle)}</span>
                            <span>${echapper(e.message)}</span>
                        </button>
                    </li>`).join('')}
            </ul>`;

        this._rendreBandeau({
            icone: 'bi-exclamation-triangle-fill',
            titre: nombre === 1
                ? '1 information à corriger'
                : `<span class="compteur">${nombre}</span> informations à corriger`,
            corps,
        });

        // Chaque entrée du récapitulatif ramène à son champ
        this.$bandeau.on('click', '.lien-erreur', (e) => {
            const entree = entrees[Number($(e.currentTarget).data('index'))];
            this._allerAuChamp(entree);
        });

        // Le premier champ fautif prend le focus, le bandeau reste visible
        const premier = entrees.find((e) => e.$controle?.length);
        this.$bandeau[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (premier) premier.$controle[0].focus({ preventScroll: true });
    }

    /**
     * Retrouve le contrôle correspondant à une clé d'erreur et son libellé
     * lisible. Gère les clés de ligne « lignes.2.quantite ».
     */
    _localiser(cle) {
        // 1. Champ déclaré explicitement par l'écran
        const special = this.champsSpeciaux[cle];
        if (special) {
            const $cible = $(special.selecteur);
            return { $controle: $cible, $ancre: $cible, libelle: special.libelle ?? this._humaniser(cle) };
        }

        // 2. Ligne dynamique
        const ligne = cle.match(/^lignes\.(\d+)\.(.+)$/);
        if (ligne && this.lignes) {
            const index = Number(ligne[1]);
            const champ = ligne[2];
            const $rangee = $(this.lignes.conteneur).find('tr').eq(index);
            const selecteur = (this.lignes.champs ?? CHAMPS_LIGNE_PAR_DEFAUT)[champ];
            const $controle = selecteur ? $rangee.find(selecteur) : $();

            const libelleLigne = this.lignes.libelle
                ? this.lignes.libelle(index)
                : `Ligne ${index + 1}`;

            return {
                $controle: $controle.length ? $controle : $rangee,
                $ancre: $rangee,
                $rangee,
                libelle: `${libelleLigne} · ${this._humaniser(champ)}`,
            };
        }

        // 3. Champ nommé du formulaire
        const $controle = this.$form.find(`[name="${cle}"]`);
        return { $controle, $ancre: $controle, libelle: this._libelleDuChamp($controle, cle) };
    }

    _marquerChamp({ $controle, $rangee, message }) {
        if ($rangee?.length) {
            $rangee.addClass('ligne-en-erreur');
        }

        if (!$controle?.length) return;

        const estChampDeSaisie = $controle.is('input, select, textarea');

        if (estChampDeSaisie) {
            $controle.addClass('is-invalid').attr('aria-invalid', 'true');

            // Select2 masque le contrôle réel : on souligne son rendu
            const $select2 = $controle.next('.select2-container');
            if ($select2.length) $select2.addClass('est-invalide');

            // Dans une rangée, le message se pose sous la cellule ; ailleurs
            // c'est un invalid-feedback classique
            const cible = $select2.length ? $select2 : $controle;
            if ($rangee?.length) {
                cible.after(`<span class="message-ligne">${echapper(message)}</span>`);
            } else {
                cible.after(`<div class="invalid-feedback erreur-serveur d-block">${echapper(message)}</div>`);
            }
            return;
        }

        // Bloc non saisissable (cartes de bénéficiaire, zone de dépôt)
        $controle.addClass('bloc-invalide');
        $controle.after(`<div class="invalid-feedback erreur-serveur d-block">${echapper(message)}</div>`);
    }

    _allerAuChamp({ $controle, $ancre }) {
        const $cible = ($controle?.length ? $controle : $ancre);
        if (!$cible?.length) return;

        $cible[0].scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Un Select2 s'ouvre, un champ normal prend le focus
        if ($controle?.hasClass('select2-hidden-accessible')) {
            $controle.select2('open');
        } else if ($controle?.is('input, select, textarea')) {
            setTimeout(() => $controle[0].focus({ preventScroll: true }), 180);
        }
    }

    // ── Rendu du bandeau ──────────────────────────────────────────────────

    _rendreBandeau({ icone, titre, corps, classeBlocage = false, action = null }) {
        const boutonAction = action?.url
            ? `<a href="${action.url}" class="btn btn-sm btn-outline-secondary mt-2">${echapper(action.label)}</a>`
            : '';

        this.$bandeau
            .toggleClass('bandeau-blocage', classeBlocage)
            .removeClass('d-none')
            .html(`
                <div class="titre-erreurs">
                    <i class="bi ${icone} text-${classeBlocage ? 'warning' : 'danger'}"></i>
                    <span>${titre}</span>
                </div>
                ${corps}
                ${boutonAction}
            `);
    }

    /** Le bandeau vit dans la modale s'il y en a une, sinon en tête de formulaire. */
    _resoudreBandeau(selecteur) {
        if (selecteur && $(selecteur).length) return $(selecteur);

        const $modale = this.$form.closest('.modal-content').find('.modal-body');
        if ($modale.length) {
            let $existant = $modale.children('.bandeau-erreurs');
            if (!$existant.length) {
                $existant = $('<div class="bandeau-erreurs d-none" role="alert" aria-live="assertive" tabindex="-1"></div>');
                $modale.prepend($existant);
            }
            return $existant;
        }

        let $bandeau = $('#erreurs-formulaire');
        if (!$bandeau.length) {
            $bandeau = $('<div id="erreurs-formulaire" class="bandeau-erreurs d-none" role="alert" aria-live="assertive" tabindex="-1"></div>');
            this.$form.prepend($bandeau);
        }
        return $bandeau;
    }

    // ── Nettoyage à la correction ─────────────────────────────────────────

    /** Corriger un champ efface sa marque — le bandeau se vide au fil de l'eau. */
    _surCorrection() {
        this.$form.on('input change', '.is-invalid', function () {
            const $champ = $(this);
            $champ.removeClass('is-invalid').removeAttr('aria-invalid');
            $champ.next('.select2-container').removeClass('est-invalide');
            $champ.closest('tr').removeClass('ligne-en-erreur').find('.message-ligne').remove();
            $champ.siblings('.invalid-feedback.erreur-serveur').remove();
            $champ.next('.select2-container').siblings('.invalid-feedback.erreur-serveur').remove();
        });
    }
}

/**
 * Bandeau de succès discret — pendant des erreurs, pour ne pas ouvrir une
 * Swal sur chaque enregistrement (S8 : les succès courants sont des toasts).
 */
export function toastSucces(message) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: message,
        showConfirmButton: false,
        timer: 2200,
        timerProgressBar: true,
    });
}
