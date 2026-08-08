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

    // ── Onglet Documents (D-09 : M-05, M-08, pierres tombales) ─────────────

    const $onglet = $('#onglet-documents');

    if ($onglet.length > 0) {
        const echapper = (t) => $('<span>').text(t ?? '').html();

        const rendreLigne = (doc) => {
            if (doc.est_supprime) {
                // 🪦 La pierre tombale : grisée, motivée, signée (UX4-08).
                return `<tr class="table-light text-muted">
                    <td><span class="badge bg-secondary-subtle text-secondary-emphasis">${echapper(doc.type_label)}</span></td>
                    <td colspan="3">🪦 ${echapper(doc.pierre_tombale)}</td>
                    <td></td>
                </tr>`;
            }

            const boutons = [];
            if (doc.peut_telecharger) {
                boutons.push(`<a class="btn btn-sm btn-outline-secondary" href="${doc.url_telechargement}" title="Télécharger">
                    <i class="bi bi-download"></i></a>`);
            }
            if (doc.peut_supprimer) {
                boutons.push(`<button type="button" class="btn btn-sm btn-outline-danger" data-doc="${doc.id}"
                    data-nom="${echapper(doc.nom_original)}" data-motive="${doc.suppression_motivee ? 1 : 0}" title="Supprimer">
                    <i class="bi bi-trash"></i></button>`);
            }

            return `<tr>
                <td><span class="badge bg-secondary-subtle text-secondary-emphasis">${echapper(doc.type_label)}</span></td>
                <td><i class="bi ${doc.icone} me-1"></i>${echapper(doc.nom_original)} <small class="text-muted">(${doc.taille_lisible})</small></td>
                <td>${echapper(doc.depose_par)}</td>
                <td>${echapper(doc.depose_le)}</td>
                <td class="text-end"><div class="btn-group">${boutons.join('')}</div></td>
            </tr>`;
        };

        const recharger = () => $.getJSON($onglet.data('url-liste')).done((reponse) => {
            const docs = reponse.data ?? [];
            $('#table-documents tbody').html(docs.map(rendreLigne).join(''));
            $('#documents-vide').toggleClass('d-none', docs.length > 0);
            $('#table-documents').toggleClass('d-none', docs.length === 0);
            $('#compteur-documents').text(docs.length);
        });

        recharger();

        // M-05 — dépôt.
        $('#form-piece').on('submit', function (e) {
            e.preventDefault();

            const fichier = document.getElementById('piece-fichier').files[0];
            if (!fichier) return;

            const donnees = new FormData();
            donnees.append('type', $('#piece-types input:checked').val());
            donnees.append('fichier', fichier);

            $('#piece-erreur').addClass('d-none');
            $('#piece-deposer').prop('disabled', true);

            $.ajax({
                url: $onglet.data('url-depot'),
                method: 'POST',
                data: donnees,
                processData: false,
                contentType: false,
            })
                .done((reponse) => {
                    bootstrap.Modal.getInstance(document.getElementById('modal-piece'))?.hide();
                    this.reset();
                    recharger();
                    Swal.fire({ icon: 'success', title: reponse.message, timer: 2000, showConfirmButton: false, toast: true, position: 'top-end' });
                })
                .fail((xhr) => {
                    // 422 : les messages du serveur, posés dans la modale.
                    const erreurs = xhr.responseJSON?.errors;
                    const message = erreurs ? Object.values(erreurs).flat().join(' ') : (xhr.responseJSON?.message ?? 'Dépôt impossible.');
                    $('#piece-erreur').removeClass('d-none').text(message);
                })
                .always(() => $('#piece-deposer').prop('disabled', false));
        });

        // Suppression : Swal simple avant validation, M-08 motivée après.
        $('#table-documents').on('click', 'button[data-doc]', function () {
            const id = $(this).data('doc');
            const nom = $(this).data('nom');
            const motivee = $(this).data('motive') === 1;
            const url = `${$onglet.data('url-liste')}/${id}`;

            const executer = (motif) => $.ajax({
                url,
                method: 'DELETE',
                data: JSON.stringify(motif ? { motif } : {}),
                contentType: 'application/json',
                dataType: 'json',
            })
                .done((reponse) => {
                    recharger();
                    Swal.fire({ icon: 'success', title: reponse.message, timer: 2500, showConfirmButton: false, toast: true, position: 'top-end' });
                })
                .fail((xhr) => Swal.fire({ icon: 'error', title: 'Suppression impossible', text: xhr.responseJSON?.message ?? '' }));

            if (!motivee) {
                Swal.fire({
                    title: `Supprimer « ${nom} » ?`,
                    text: 'Le bon n\'est pas validé : la pièce sera réellement supprimée.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Supprimer',
                    cancelButtonText: 'Annuler',
                }).then((r) => { if (r.isConfirmed) executer(null); });
                return;
            }

            // M-08 : motif obligatoire, la ligne devient une pierre tombale.
            Swal.fire({
                title: `Supprimer « ${nom} » ?`,
                input: 'textarea',
                inputLabel: 'Motif de la suppression',
                inputPlaceholder: 'Pourquoi cette pièce sort-elle du dossier ?…',
                text: 'Le bon est validé : le fichier sera effacé mais la ligne restera au dossier, motivée et signée.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Supprimer avec trace',
                cancelButtonText: 'Annuler',
                inputValidator: (valeur) =>
                    (!valeur || valeur.trim().length < 5)
                        ? 'Indiquez le motif : une pièce d\'un bon validé ne disparaît pas sans explication.'
                        : undefined,
            }).then((r) => { if (r.isConfirmed) executer(r.value); });
        });
    }

    // Infobulles des badges et boutons grisés (diagnostics §0.3).
    $('[data-bs-toggle="tooltip"]').each((_, element) => new bootstrap.Tooltip(element));
});
