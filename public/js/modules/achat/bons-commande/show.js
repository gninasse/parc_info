/**
 * show.js — fiche A-04 du bon de commande (SPEC_UX A-04).
 *
 * La barre d'actions est RENDUE par le serveur (grille ActionsBonCommande) :
 * ici on ne décide de rien, on branche les confirmations partagées (Swal) sur
 * les boutons présents, et la modale PDF sur l'impression. Au succès d'une
 * commande, on suit la redirection du serveur — l'état affiché vient toujours
 * d'un rendu frais, jamais d'une retouche DOM.
 */
import { ModalPdf } from '../shared/modal-pdf.js';
import { ActionsBc } from '../shared/actions-bc.js';

$(function () {
    const $barre = $('#barre-actions');
    if ($barre.length === 0) return;

    const bon = $barre.data('bon');

    const suivre = (reponse) => {
        const cible = reponse?.data?.redirection;
        window.location.href = cible || window.location.href;
        if (!cible) window.location.reload();
    };

    // ── Impression : modale iframe, jamais un onglet ───────────────────────

    $('#action-pdf').on('click', function () {
        ModalPdf.ouvrir({
            url: $(this).data('url'),
            titre: `Bon de commande ${bon.numero_affiche}`,
        });
    });

    // ── Commandes : chaque bouton POST porte son URL serveur ───────────────

    $('#action-supprimer').on('click', function () {
        ActionsBc.supprimer(bon, $(this).data('url'), () => {
            // Le bon n'existe plus : retour à la liste.
            window.location.href = route('achat.bons-commande.index');
        });
    });

    $('#action-reprendre').on('click', function () {
        ActionsBc.reprendre(bon, $(this).data('url'), suivre);
    });

    $('#action-renvoyer').on('click', function () {
        ActionsBc.renvoyer(bon, $(this).data('url'), suivre);
    });

    $('#action-valider').on('click', function () {
        ActionsBc.valider(
            bon,
            route('achat.bons-commande.signaux', bon.id),
            $(this).data('url'),
            suivre
        );
    });

    $('#action-annuler').on('click', function () {
        ActionsBc.annuler(bon, $(this).data('url'), suivre);
    });

    $('#action-cloturer').on('click', function () {
        ActionsBc.cloturer(bon, $(this).data('url'), suivre);
    });

    // Infobulles des badges et boutons grisés (diagnostics §0.3).
    $('[data-bs-toggle="tooltip"]').each((_, element) => new bootstrap.Tooltip(element));
});
