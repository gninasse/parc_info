/**
 * Socle commun du module Achat.
 *
 * Chargé par le layout, avant toute pile @push('js'). Regroupe :
 *   - les formatters Bootstrap Table (window.*, hors DOMContentLoaded — PATTERNS §9.1) ;
 *   - les notifications SweetAlert normalisées ;
 *   - l'affichage des erreurs de validation en ligne (PATTERNS §9.6).
 *
 * Correction AN-16 : ces fonctions étaient auparavant redéfinies dans chaque
 * vue et chaque script, avec des variantes divergentes.
 */
(function (window, $) {
    'use strict';

    // ── Formatage ──────────────────────────────────────────────────────────

    const formatterMonnaie = new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XOF',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });

    window.prixFormatter = function (valeur) {
        if (valeur === null || valeur === undefined || valeur === '') return '-';
        return formatterMonnaie.format(valeur);
    };

    window.tauxFormatter = function (valeur) {
        if (valeur === null || valeur === undefined) return '-';
        return `${parseFloat(valeur).toString().replace('.', ',')} %`;
    };

    /** Attend une date ISO « AAAA-MM-JJ ». */
    window.dateFormatter = function (valeur) {
        if (!valeur) return '-';
        const parts = String(valeur).substring(0, 10).split('-');
        return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : valeur;
    };

    window.dateHeureFormatter = function (valeur) {
        if (!valeur) return '-';
        const date = new Date(valeur.replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return valeur;
        return date.toLocaleDateString('fr-FR', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit',
        });
    };

    window.typeArticleFormatter = function (valeur, ligne) {
        return `<span class="badge bg-light text-dark border">${ligne.type_label ?? valeur}</span>`;
    };

    window.actifFormatter = function (valeur) {
        return valeur
            ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Actif</span>'
            : '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Inactif</span>';
    };

    /** Badge d'un statut, à partir du libellé et de la couleur fournis par le serveur. */
    function badgeStatut(couleurs) {
        return function (valeur, ligne) {
            const couleur = couleurs[valeur] ?? 'secondary';
            const texte = ligne.statut_label ?? valeur;
            const classeTexte = couleur === 'warning' ? ' text-dark' : '';
            return `<span class="badge bg-${couleur}${classeTexte}">${texte}</span>`;
        };
    }

    window.statutBcFormatter = badgeStatut({
        brouillon: 'secondary',
        valide: 'primary',
        partiel: 'warning',
        livre: 'success',
        annule: 'danger',
        cloture: 'dark',
    });

    window.statutBlFormatter = badgeStatut({
        brouillon: 'secondary',
        wizard: 'warning',
        valide: 'success',
    });

    window.niveauStockFormatter = function (valeur, ligne) {
        const classeTexte = ligne.niveau_color === 'warning' ? ' text-dark' : '';
        return `<span class="badge bg-${ligne.niveau_color}${classeTexte}">${ligne.niveau_label}</span>`;
    };

    // ── Notifications ──────────────────────────────────────────────────────

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2600,
        timerProgressBar: true,
    });

    window.Achat = window.Achat || {};

    Achat.succes = function (message) {
        Toast.fire({ icon: 'success', title: message });
    };

    Achat.erreur = function (message) {
        Swal.fire({
            icon: 'error',
            title: 'Opération impossible',
            text: message || 'Une erreur est survenue.',
            confirmButtonText: 'Fermer',
        });
    };

    Achat.attention = function (message) {
        Swal.fire({ icon: 'warning', title: 'Attention', text: message, confirmButtonText: 'Compris' });
    };

    /** Confirmation d'une action destructrice ou irréversible (ENF-ERG-02). */
    Achat.confirmer = function (options) {
        return Swal.fire({
            title: options.titre,
            html: options.texte,
            icon: options.icone || 'warning',
            showCancelButton: true,
            confirmButtonText: options.confirmer || 'Confirmer',
            cancelButtonText: 'Annuler',
            confirmButtonColor: options.couleur || '#dc3545',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
        });
    };

    /**
     * Confirmation exigeant la saisie d'un motif (annulation, clôture).
     * Résout avec le motif saisi, ou null si l'utilisateur renonce.
     */
    Achat.demanderMotif = function (options) {
        return Swal.fire({
            title: options.titre,
            html: options.texte,
            icon: options.icone || 'warning',
            input: 'textarea',
            inputLabel: options.libelle || 'Motif',
            inputPlaceholder: options.placeholder || 'Précisez le motif…',
            inputAttributes: { 'aria-label': options.libelle || 'Motif' },
            showCancelButton: true,
            confirmButtonText: options.confirmer || 'Confirmer',
            cancelButtonText: 'Annuler',
            confirmButtonColor: options.couleur || '#ffc107',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            inputValidator: (valeur) => {
                if (!valeur || valeur.trim().length < 5) {
                    return 'Le motif est obligatoire et doit être explicite (5 caractères minimum).';
                }
                return null;
            },
        }).then((resultat) => (resultat.isConfirmed ? resultat.value : null));
    };

    // ── Erreurs de validation ──────────────────────────────────────────────

    Achat.effacerErreurs = function ($formulaire) {
        $formulaire.find('.is-invalid').removeClass('is-invalid');
        $formulaire.find('.invalid-feedback.erreur-serveur').remove();
    };

    /**
     * Convertit un chemin d'erreur Laravel en nom de champ HTML.
     * « lignes.0.quantite » → « lignes[0][quantite] »
     */
    function nomChampHtml(chemin) {
        const segments = chemin.split('.');

        if (segments.length === 1) {
            return chemin;
        }

        return segments[0] + segments.slice(1).map((s) => `[${s}]`).join('');
    }

    /**
     * Positionne les erreurs 422 sur les champs correspondants.
     * Les erreurs sans champ identifiable sont regroupées dans une alerte.
     */
    Achat.afficherErreurs = function ($formulaire, erreurs) {
        Achat.effacerErreurs($formulaire);

        const orphelines = [];

        $.each(erreurs || {}, function (chemin, messages) {
            const message = Array.isArray(messages) ? messages[0] : messages;

            let $champ = $formulaire.find(`[name="${chemin}"]`);

            if (!$champ.length) {
                $champ = $formulaire.find(`[name="${nomChampHtml(chemin)}"]`);
            }

            if ($champ.length) {
                $champ.addClass('is-invalid');
                $champ.last().after(
                    `<div class="invalid-feedback d-block erreur-serveur">${message}</div>`
                );
            } else {
                orphelines.push(message);
            }
        });

        if (orphelines.length) {
            Achat.erreur(orphelines.join('\n'));
        }
    };

    /** Extrait un message exploitable d'une réponse d'erreur jQuery. */
    Achat.messageErreur = function (xhr, defaut) {
        return xhr?.responseJSON?.message || defaut || 'Une erreur est survenue.';
    };

    /** Bascule un bouton en état de chargement et retourne sa restauration. */
    Achat.chargement = function ($bouton, libelle) {
        const contenuInitial = $bouton.html();
        $bouton.prop('disabled', true).html(
            `<span class="spinner-border spinner-border-sm me-2"></span>${libelle}`
        );
        return function restaurer() {
            $bouton.prop('disabled', false).html(contenuInitial);
        };
    };

    // ── Infobulles ─────────────────────────────────────────────────────────

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
            new bootstrap.Tooltip(element);
        });
    });

})(window, jQuery);
