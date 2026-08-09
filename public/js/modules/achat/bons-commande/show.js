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

    // ── Onglet Licences (D-13 : pré-écran A-05, M-04 service fait) ─────────

    /**
     * Pré-écran A-05 : « Réceptionner combien d'unités ? ». Le serveur dit
     * d'abord s'il est possible d'ouvrir (IA-9 : logiciel rattaché) — un
     * blocage se lit AVANT la saisie, avec son lien de correction.
     */
    $('#table-licences').on('click', '.btn-receptionner-licences', function () {
        const urlPreparer = $(this).data('url-preparer');
        const urlOuvrir = $(this).data('url-ouvrir');
        const designation = $(this).data('designation');

        $.getJSON(urlPreparer).done((contexte) => {
            if (!contexte.peut_ouvrir) {
                const lien = contexte.blocage?.url_correction;
                Swal.fire({
                    icon: 'warning',
                    title: 'Réception impossible',
                    html: `<p class="mb-2">${contexte.blocage?.message ?? ''}</p>`
                        + (lien ? `<a href="${lien}" target="_blank" rel="noopener">Corriger la fiche article →</a>` : ''),
                });
                return;
            }

            const reste = contexte.ligne.reste;

            Swal.fire({
                title: 'Réceptionner combien d\'unités ?',
                html: `<p class="mb-2 text-start">${designation}<br><small class="text-muted">Reste à livrer : ${reste}</small></p>`,
                input: 'number',
                inputValue: reste,
                inputAttributes: { min: 1, max: reste, step: 1, 'aria-label': 'Quantité à réceptionner' },
                showCancelButton: true,
                confirmButtonText: 'Commencer la saisie',
                cancelButtonText: 'Annuler',
                inputValidator: (valeur) => {
                    const n = Number(valeur);
                    if (!n || n <= 0) return 'Indiquez une quantité positive.';
                    if (n > reste) return `Le reste à livrer est de ${reste}.`;
                    return undefined;
                },
            }).then((r) => {
                if (!r.isConfirmed) return;

                // POST (transition d'état) : formulaire jetable plutôt qu'AJAX,
                // la réponse est une redirection vers le wizard plein écran.
                const $form = $('<form method="POST">')
                    .attr('action', urlOuvrir)
                    .append($('<input type="hidden" name="_token">').val($('meta[name="csrf-token"]').attr('content')))
                    .append($('<input type="hidden" name="quantite">').val(r.value));
                $('body').append($form);
                $form.trigger('submit');
            });
        });
    });

    /** M-04 — constat de service fait : une date, un commentaire, la ligne est soldée. */
    $('#table-licences').on('click', '.btn-service-fait', function () {
        const url = $(this).data('url');
        const designation = $(this).data('designation');

        Swal.fire({
            title: 'Constater le service fait',
            html: `<p class="text-start mb-2">${designation}</p>
                <label class="form-label small d-block text-start" for="sf-date">Date du service fait</label>
                <input type="date" id="sf-date" class="swal2-input mt-0" value="${new Date().toISOString().slice(0, 10)}">
                <label class="form-label small d-block text-start mt-2" for="sf-commentaire">Commentaire (facultatif)</label>
                <textarea id="sf-commentaire" class="swal2-textarea mt-0" placeholder="Conditions d'exécution, réserves…"></textarea>`,
            showCancelButton: true,
            confirmButtonText: 'Constater',
            cancelButtonText: 'Annuler',
            preConfirm: () => {
                const date = document.getElementById('sf-date').value;
                if (!date) {
                    Swal.showValidationMessage('Indiquez la date du service fait.');
                    return false;
                }
                return { date, commentaire: document.getElementById('sf-commentaire').value };
            },
        }).then((r) => {
            if (!r.isConfirmed) return;

            $.ajax({
                url,
                method: 'POST',
                data: JSON.stringify(r.value),
                contentType: 'application/json',
                dataType: 'json',
            })
                .done((reponse) => { window.location.href = reponse.data.redirection; })
                .fail((xhr) => Swal.fire({
                    icon: 'error',
                    title: 'Constat impossible',
                    text: xhr.responseJSON?.message ?? '',
                }));
        });
    });

    // Infobulles des badges et boutons grisés (diagnostics §0.3).
    $('[data-bs-toggle="tooltip"]').each((_, element) => new bootstrap.Tooltip(element));
});
