/**
 * Socle commun du module Stock.
 *
 * Chargé par le layout, avant toute pile @push('js'). Regroupe :
 *   - les formatters Bootstrap Table (window.*, hors DOMContentLoaded — PATTERNS §9.1) ;
 *   - les notifications SweetAlert normalisées ;
 *   - l'affichage des erreurs de validation en ligne (PATTERNS §9.6).
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

    /** Attend une date ISO « AAAA-MM-JJ ». */
    window.dateFormatter = function (valeur) {
        if (!valeur) return '-';
        const parts = String(valeur).substring(0, 10).split('-');
        return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : valeur;
    };

    window.dateHeureFormatter = function (valeur) {
        if (!valeur) return '-';
        const date = new Date(String(valeur).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return valeur;
        return date.toLocaleDateString('fr-FR', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit',
        });
    };

    /** Statut d'alerte F2 : OK (vert), ALERTE (orange), RUPTURE (rouge). */
    window.statutAlerteFormatter = function (valeur) {
        const config = {
            OK: { couleur: 'success', libelle: 'OK' },
            ALERTE: { couleur: 'warning', libelle: 'Sous seuil' },
            RUPTURE: { couleur: 'danger', libelle: 'Rupture' },
        }[valeur] || { couleur: 'secondary', libelle: valeur };
        const classeTexte = config.couleur === 'warning' ? ' text-dark' : '';
        return `<span class="badge bg-${config.couleur}${classeTexte}">${config.libelle}</span>`;
    };

    /** Badge du type de mouvement, libellé et couleur fournis par le serveur. */
    window.typeMouvementFormatter = function (valeur, ligne) {
        const couleur = ligne.type_color || 'secondary';
        const classeTexte = couleur === 'warning' ? ' text-dark' : '';
        return `<span class="badge bg-${couleur}${classeTexte}">${ligne.type_label || valeur}</span>`;
    };

    window.origineFormatter = function (valeur) {
        const libelles = { MANUEL: 'Manuelle', BL: 'Livraison Achat', TRANSFERT: 'Transfert', INVENTAIRE: 'Inventaire' };
        return `<span class="badge bg-light text-dark border">${libelles[valeur] || valeur}</span>`;
    };

    window.magasinStatutFormatter = function (valeur) {
        return valeur === 'actif'
            ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Actif</span>'
            : '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Inactif</span>';
    };

    // ── Notifications ──────────────────────────────────────────────────────

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2600,
        timerProgressBar: true,
    });

    window.Stock = window.Stock || {};

    Stock.succes = function (message) {
        Toast.fire({ icon: 'success', title: message });
    };

    Stock.erreur = function (message) {
        Swal.fire({
            icon: 'error',
            title: 'Opération impossible',
            text: message || 'Une erreur est survenue.',
            confirmButtonText: 'Fermer',
        });
    };

    Stock.attention = function (message) {
        Swal.fire({ icon: 'warning', title: 'Attention', text: message, confirmButtonText: 'Compris' });
    };

    /** Confirmation d'une action destructrice ou irréversible. */
    Stock.confirmer = function (options) {
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

    /** Confirmation exigeant un motif ; résout avec le motif ou null. */
    Stock.demanderMotif = function (options) {
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

    Stock.effacerErreurs = function ($formulaire) {
        $formulaire.find('.is-invalid').removeClass('is-invalid');
        $formulaire.find('.invalid-feedback.erreur-serveur').remove();
    };

    function nomChampHtml(chemin) {
        const segments = chemin.split('.');

        if (segments.length === 1) {
            return chemin;
        }

        return segments[0] + segments.slice(1).map((s) => `[${s}]`).join('');
    }

    /** Positionne les erreurs 422 sur les champs correspondants. */
    Stock.afficherErreurs = function ($formulaire, erreurs) {
        Stock.effacerErreurs($formulaire);

        const orphelines = [];

        $.each(erreurs || {}, function (chemin, messages) {
            const message = Array.isArray(messages) ? messages[0] : messages;

            let $champ = $formulaire.find(`[name="${chemin}"]`);

            if (!$champ.length) {
                $champ = $formulaire.find(`[name="${nomChampHtml(chemin)}"]`);
            }

            if ($champ.length) {
                $champ.addClass('is-invalid');
                $('<div class="invalid-feedback erreur-serveur"></div>')
                    .text(message)
                    .insertAfter($champ);
            } else {
                orphelines.push(message);
            }
        });

        if (orphelines.length) {
            Stock.erreur(orphelines.join('\n'));
        }
    };

    /** Gestionnaire AJAX d'échec normalisé (422 en ligne, sinon Swal). */
    Stock.gererEchec = function ($formulaire) {
        return function (xhr) {
            if (xhr.status === 422) {
                const reponse = xhr.responseJSON || {};

                if (reponse.errors) {
                    Stock.afficherErreurs($formulaire, reponse.errors);
                    return;
                }

                Stock.erreur(reponse.message);
                return;
            }

            Stock.erreur(xhr.responseJSON?.message);
        };
    };
})(window, jQuery);
