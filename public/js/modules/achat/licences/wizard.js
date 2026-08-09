/**
 * wizard.js — A-05, saisie des clés de licence (SPEC_UX A-05, maquette P-05).
 *
 * Principe de sûreté : CHAQUE clé est enregistrée au serveur dès la frappe
 * d'Entrée (IA-8) — fermer la page ne perd rien. Le navigateur n'accumule
 * pas un état local qu'il faudrait sauver « à la fin » : il n'y a pas de fin
 * fiable, il y a des coupures réseau.
 *
 * Les doublons se voient EN DIRECT : le serveur les refuse à la saisie
 * (session et parc), l'écran pose l'erreur sur le champ.
 */

$(function () {
    const $wizard = $('#wizard-licences');
    if ($wizard.length === 0) return;

    const quantite = Number($wizard.data('quantite'));
    const urls = {
        saisir: $wizard.data('url-saisir'),
        importer: $wizard.data('url-importer'),
        finaliser: $wizard.data('url-finaliser'),
        abandonner: $wizard.data('url-abandonner'),
        // Le gabarit porte un 0 en dernier segment : on le remplace par l'id.
        supprimer: String($wizard.data('url-supprimer')),
    };

    const echapper = (t) => $('<span>').text(t ?? '').html();

    // ── État d'avancement : compteur + bouton Finaliser + diagnostic ───────

    const majAvancement = (saisies) => {
        $('#compteur-saisies').text(saisies);
        $('#ligne-saisie td:first').text(saisies + 1);

        const manquantes = quantite - saisies;
        const complet = manquantes <= 0;

        // Le bouton grisé DIT ce qui manque (SPEC_UX §0.3).
        $('#btn-finaliser').prop('disabled', !complet);
        $('#diagnostic-finaliser').text(complet ? '' : `${manquantes} clé(s) manquante(s)`);

        // Plus rien à saisir : la ligne de saisie disparaît.
        $('#ligne-saisie').toggleClass('d-none', complet);
    };

    const lignesSaisies = () => $('#table-cles tbody tr[data-tampon-id]').length;

    majAvancement(lignesSaisies());

    // ── Ajout d'une ligne au tableau (après acceptation serveur) ───────────

    const ajouterLigne = (donnees) => {
        const $tr = $(`
            <tr data-tampon-id="${donnees.id}">
                <td class="text-muted">${lignesSaisies() + 1}</td>
                <td>
                    <input type="text" class="form-control form-control-sm input-cle" value="${echapper(donnees.cle)}">
                    <div class="invalid-feedback d-block small text-danger cle-erreur"></div>
                </td>
                <td><input type="date" class="form-control form-control-sm input-activation" value="${donnees.date_activation ?? ''}"></td>
                <td><input type="date" class="form-control form-control-sm input-expiration" value="${donnees.date_expiration ?? ''}"></td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-cle" title="Retirer">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>`);

        $('#ligne-saisie').before($tr);
    };

    // ── Saisie : Entrée = enregistrer puis ligne suivante ──────────────────

    const enregistrerNouvelle = () => {
        const cle = $('#nouvelle-cle').val().trim();
        if (cle === '') return;

        $.ajax({
            url: urls.saisir,
            method: 'POST',
            data: JSON.stringify({
                cle,
                date_activation: $('#nouvelle-activation').val() || null,
                date_expiration: $('#nouvelle-expiration').val() || null,
            }),
            contentType: 'application/json',
            dataType: 'json',
        })
            .done((reponse) => {
                ajouterLigne(reponse.data);
                $('#nouvelle-cle').val('').removeClass('cle-invalide').trigger('focus');
                $('#nouvelle-cle-erreur').text('');
                majAvancement(reponse.data.saisies);
            })
            .fail((xhr) => {
                // Doublon ou garde : l'erreur se pose SUR le champ, la clé
                // reste à l'écran pour être corrigée.
                $('#nouvelle-cle').addClass('cle-invalide').trigger('select');
                $('#nouvelle-cle-erreur').text(xhr.responseJSON?.message ?? 'Clé refusée.');
            });
    };

    $('#nouvelle-cle').on('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            enregistrerNouvelle();
        }
    });

    // Sortir du champ enregistre aussi : personne ne perd une clé tapée.
    $('#nouvelle-cle').on('blur', () => {
        if ($('#nouvelle-cle').val().trim() !== '') enregistrerNouvelle();
    });

    // ── Correction d'une ligne existante ──────────────────────────────────

    $('#table-cles').on('change', '.input-cle, .input-activation, .input-expiration', function () {
        const $tr = $(this).closest('tr');
        const id = $tr.data('tampon-id');
        if (!id) return;

        $.ajax({
            url: urls.saisir,
            method: 'POST',
            data: JSON.stringify({
                tampon_id: id,
                cle: $tr.find('.input-cle').val().trim(),
                date_activation: $tr.find('.input-activation').val() || null,
                date_expiration: $tr.find('.input-expiration').val() || null,
            }),
            contentType: 'application/json',
            dataType: 'json',
        })
            .done(() => {
                $tr.find('.input-cle').removeClass('cle-invalide');
                $tr.find('.cle-erreur').text('');
            })
            .fail((xhr) => {
                $tr.find('.input-cle').addClass('cle-invalide');
                $tr.find('.cle-erreur').text(xhr.responseJSON?.message ?? 'Clé refusée.');
            });
    });

    $('#table-cles').on('click', '.btn-supprimer-cle', function () {
        const $tr = $(this).closest('tr');
        const id = $tr.data('tampon-id');

        $.ajax({
            url: urls.supprimer.replace(/\/0$/, `/${id}`),
            method: 'DELETE',
            dataType: 'json',
        }).done((reponse) => {
            $tr.remove();
            // Renumérotation des rangs affichés.
            $('#table-cles tbody tr[data-tampon-id]').each((index, tr) => {
                $(tr).find('td:first').text(index + 1);
            });
            majAvancement(reponse.data.saisies);
        });
    });

    // ── Date d'activation commune ─────────────────────────────────────────

    $('#btn-appliquer-toutes').on('click', () => {
        const date = $('#date-commune').val();
        if (!date) return;

        $('#nouvelle-activation').val(date);
        $('#table-cles tbody tr[data-tampon-id]').each((_, tr) => {
            $(tr).find('.input-activation').val(date).trigger('change');
        });
    });

    // ── Import en masse, avec rapport ─────────────────────────────────────

    $('#btn-lancer-import').on('click', function () {
        const texte = $('#import-texte').val();
        if (!texte.trim()) return;

        $(this).prop('disabled', true);

        $.ajax({
            url: urls.importer,
            method: 'POST',
            data: JSON.stringify({ texte, date_activation: $('#date-commune').val() || null }),
            contentType: 'application/json',
            dataType: 'json',
        })
            .done((reponse) => {
                const r = reponse.rapport;
                const morceaux = [`<strong>${r.acceptees}</strong> acceptée(s)`];
                if (r.doublons > 0) morceaux.push(`<strong>${r.doublons}</strong> doublon(s) ignoré(s)`);
                if (r.vides > 0) morceaux.push(`<strong>${r.vides}</strong> ligne(s) vide(s)`);
                if (r.hors_quantite > 0) morceaux.push(`<strong>${r.hors_quantite}</strong> au-delà de la quantité`);

                $('#import-rapport').removeClass('d-none').html(morceaux.join(' · '));

                // Le tableau est reconstruit depuis la vérité du serveur.
                $('#table-cles tbody tr[data-tampon-id]').remove();
                (reponse.data.lignes ?? []).forEach((ligne) => ajouterLigne(ligne));
                majAvancement(reponse.data.saisies);
                $('#import-texte').val('');
            })
            .always(() => $(this).prop('disabled', false));
    });

    // ── SW-03 finalisation / SW-05 retour en arrière ──────────────────────

    $('#btn-finaliser').on('click', () => {
        Swal.fire({
            title: 'Créer les licences ?',
            html: `<div class="text-start">
                <p class="mb-2"><strong>${quantite} licence(s)</strong> seront créées dans le Parc Informatique,
                rattachées à <strong>${echapper($wizard.data('designation'))}</strong>,
                au coût unitaire de <strong>${echapper($wizard.data('prix'))} FCFA HT</strong> (prix figé de la ligne).</p>
                <p class="mb-0"><strong>Action définitive.</strong></p>
            </div>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Créer les licences',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#198754',
        }).then((r) => {
            if (!r.isConfirmed) return;

            $('#btn-finaliser').prop('disabled', true);

            $.ajax({ url: urls.finaliser, method: 'POST', dataType: 'json' })
                .done((reponse) => {
                    // Compte-rendu de succès, puis retour à la fiche.
                    Swal.fire({
                        icon: 'success',
                        title: reponse.message,
                        confirmButtonText: 'Retour à la fiche du bon',
                    }).then(() => { window.location.href = reponse.data.redirection; });
                })
                .fail((xhr) => {
                    // ENF-FIA-04 : jamais de succès affiché sur un échec.
                    $('#btn-finaliser').prop('disabled', false);
                    Swal.fire({
                        icon: 'error',
                        title: 'Finalisation impossible',
                        text: xhr.responseJSON?.message ?? 'Aucune licence n\'a été créée.',
                    });
                });
        });
    });

    $('#btn-abandonner').on('click', () => {
        const saisies = lignesSaisies();

        Swal.fire({
            title: 'Revenir en arrière ?',
            text: saisies > 0
                ? `Les ${saisies} clé(s) saisies seront perdues. Aucune licence ne sera créée.`
                : 'Aucune licence ne sera créée.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Abandonner la saisie',
            cancelButtonText: 'Continuer la saisie',
        }).then((r) => {
            if (!r.isConfirmed) return;

            $.ajax({ url: urls.abandonner, method: 'POST', dataType: 'json' })
                .done((reponse) => { window.location.href = reponse.data.redirection; })
                .fail((xhr) => Swal.fire({
                    icon: 'error',
                    title: 'Action impossible',
                    text: xhr.responseJSON?.message ?? '',
                }));
        });
    });
});
