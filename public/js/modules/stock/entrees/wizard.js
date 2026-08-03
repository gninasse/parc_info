/**
 * wizard.js — référencement des entrées (UX §3.3).
 *
 * Dérogation autosave S5 (localisée à cet écran) : chaque blur/scan déclenche
 * un PUT unitaire {tampon_id, numero_serie} ; indicateur « ✓ enregistré »
 * discret ; unicité contrôlée serveur à CHAQUE PUT.
 *
 * Perte réseau (amendement UX n°23) : les PUT échoués pour cause réseau
 * rejoignent une file locale (≤ stock.file_scans_max), bandeau orange avec
 * compteur, renvoi automatique au retour du réseau, garde de sortie chiffrée.
 */
import '../shared/formatters.js';

$(function () {
    const { entreeId, fileScansMax } = window.WIZARD;
    let { saisis, total } = window.WIZARD;

    const fileHorsLigne = new Map(); // tamponId → numero_serie (dernière valeur gagne)
    let renvoiEnCours = false;

    // ── Progression ────────────────────────────────────────────────────────
    const majProgression = (statutLigne) => {
        saisis = statutLigne.saisis;
        total = statutLigne.total;
        $('#progression-texte').text(`${saisis}/${total}`);
        $('#progression-barre').css('width', total > 0 ? `${Math.round((saisis * 100) / total)}%` : '0%');

        const $compteur = $(`.accordion-item .rangee-serie[data-tampon-id="${statutLigne.tampon_id}"]`)
            .closest('.accordion-item').find('.compteur-ligne');
        $compteur.text(`${statutLigne.ligne_saisis}/${statutLigne.ligne_total}`);

        const complet = saisis >= total && total > 0;
        $('#btn-valider').prop('disabled', !complet)
            .attr('title', complet ? '' : `${total - saisis} références manquantes`);
    };

    // ── Autosave unitaire ─────────────────────────────────────────────────
    const marquer = ($rangee, etat, message = '') => {
        const $champ = $rangee.find('.champ-serie');
        $champ.toggleClass('is-invalid', etat === 'erreur');
        $rangee.find('.message-erreur').text(message);
        $rangee.find('.indicateur-enregistre').toggleClass('visible', etat === 'ok');
    };

    const enregistrer = (tamponId, valeur, $rangee) =>
        $.ajax({
            url: route('stock.entrees.wizard.update', entreeId),
            method: 'PUT',
            data: { tampon_id: tamponId, numero_serie: valeur },
            dataType: 'json',
        })
            .done((res) => {
                if (!res.success) return;
                fileHorsLigne.delete(tamponId);
                majBandeauHorsLigne();
                marquer($rangee, valeur.trim() === '' ? 'vide' : 'ok');
                majProgression(res.statut_ligne);
                if (window.scanWizard) window.scanWizard.refocus();
            })
            .fail((xhr) => {
                if (xhr.status === 0) {
                    // Réseau perdu : la saisie rejoint la file, rien n'est perdu
                    if (fileHorsLigne.size >= fileScansMax && !fileHorsLigne.has(tamponId)) {
                        marquer($rangee, 'erreur', `File d'attente pleine (${fileScansMax}) — attendez le retour du réseau`);
                        return;
                    }
                    fileHorsLigne.set(tamponId, valeur);
                    majBandeauHorsLigne();
                    return;
                }
                marquer($rangee, 'erreur', xhr.responseJSON?.message ?? 'Enregistrement impossible');
            });

    $('.champ-serie').on('blur', function () {
        const $rangee = $(this).closest('.rangee-serie');
        const valeur = this.value;
        const dejaOk = $rangee.find('.indicateur-enregistre').hasClass('visible');
        if (valeur.trim() === '' && !dejaOk) return; // rangée jamais remplie : rien à faire
        enregistrer(Number($rangee.data('tampon-id')), valeur, $rangee);
    });

    // ⌨ Entrée = rangée suivante (le blur déclenche l'autosave)
    $('.champ-serie').on('keydown', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const champs = $('.champ-serie').toArray();
        const suivant = champs[champs.indexOf(this) + 1];
        if (suivant) $(suivant).trigger('focus');
        else this.blur();
    });

    // ── Champ scan global : remplit la première rangée vide (S6) ─────────
    window.scanWizard = new StockScanField('#scan-wizard', {
        onScan: async (code) => {
            const $vide = $('.rangee-serie').filter(function () {
                return $(this).find('.champ-serie').val().trim() === '';
            }).first();

            if (!$vide.length) {
                return { ok: false, libelle: 'Toutes les rangées sont remplies' };
            }

            $vide.find('.champ-serie').val(code);
            $vide[0].scrollIntoView({ block: 'center', behavior: 'smooth' });

            try {
                await enregistrer(Number($vide.data('tampon-id')), code, $vide);
                if ($vide.find('.champ-serie').hasClass('is-invalid')) {
                    return { ok: false, libelle: $vide.find('.message-erreur').text() };
                }
                return { ok: true, libelle: `${code} enregistré` };
            } catch {
                return fileHorsLigne.size > 0
                    ? { ok: true, libelle: `${code} en file d'attente (hors ligne)` }
                    : { ok: false, libelle: 'Enregistrement impossible' };
            }
        },
    });

    // ── Perte réseau (amendement n°23) ────────────────────────────────────
    const majBandeauHorsLigne = () => {
        $('#bandeau-hors-ligne').toggleClass('d-none', fileHorsLigne.size === 0);
        $('#hors-ligne-compteur').text(fileHorsLigne.size);
    };

    const renvoyerFile = async () => {
        if (renvoiEnCours || fileHorsLigne.size === 0) return;
        renvoiEnCours = true;

        for (const [tamponId, valeur] of [...fileHorsLigne]) {
            const $rangee = $(`.rangee-serie[data-tampon-id="${tamponId}"]`);
            try {
                await enregistrer(tamponId, valeur, $rangee);
            } catch {
                break; // toujours hors ligne : on réessaiera
            }
        }

        renvoiEnCours = false;
        majBandeauHorsLigne();
    };

    window.addEventListener('online', renvoyerFile);
    setInterval(renvoyerFile, 10000); // filet : renvoi périodique

    // Garde de sortie chiffrée : aucune saisie ne se perd sans prévenir
    window.addEventListener('beforeunload', (e) => {
        if (fileHorsLigne.size === 0) return;
        e.preventDefault();
        e.returnValue = `${fileHorsLigne.size} saisie(s) en attente de renvoi — quitter maintenant les perdrait.`;
    });

    // ── Import CSV / collage (MD-IMPORT) ──────────────────────────────────
    $('#btn-importer').on('click', () => {
        $('#import-contenu').val('');
        $('#import-fichier').val('');
        $('#import-rapport').addClass('d-none');
        $('#btn-import-appliquer').addClass('d-none');
        $('#importModal').modal('show');
    });

    const requeteImport = (mode) => {
        const donnees = new FormData();
        donnees.append('mode', mode);
        const fichier = $('#import-fichier')[0].files[0];
        if (fichier) donnees.append('fichier', fichier);
        else donnees.append('contenu', $('#import-contenu').val());

        return $.ajax({
            url: route('stock.entrees.wizard.import', entreeId),
            method: 'POST',
            data: donnees,
            processData: false,
            contentType: false,
            dataType: 'json',
        });
    };

    const afficherRapport = (rapport) => {
        $('#import-rapport').removeClass('d-none');
        $('#rapport-acceptes').text(rapport.acceptes.length);
        $('#rapport-doublons').text(rapport.doublons_tampon.length);
        $('#rapport-connus').text(rapport.deja_connus.length);
        $('#rapport-en-trop').text(rapport.en_trop.length);

        const details = [...rapport.doublons_tampon, ...rapport.deja_connus, ...rapport.en_trop]
            .map((r) => `<div><code>${$('<i>').text(r.numero).html()}</code> — ${$('<i>').text(r.detail).html()}</div>`)
            .join('');
        $('#rapport-details').html(details);

        const $appliquer = $('#btn-import-appliquer');
        $appliquer.toggleClass('d-none', rapport.acceptes.length === 0)
            .text(`Appliquer les ${rapport.acceptes.length} acceptés`);
    };

    $('#btn-import-analyser').on('click', () => {
        requeteImport('analyser')
            .done((res) => afficherRapport(res.rapport))
            .fail((xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Analyse impossible.' }));
    });

    $('#btn-import-appliquer').on('click', () => {
        requeteImport('appliquer')
            .done(() => window.location.reload()) // les rangées remplies + progression à jour
            .fail((xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Import impossible.' }));
    });

    // ── Retour brouillon (SW-RETOUR-BROUILLON, texte exact amendé) ────────
    $('#btn-retour-brouillon').on('click', () => {
        Swal.fire({
            title: 'Revenir au brouillon ?',
            html: `Les <strong>${saisis}</strong> références saisies seront perdues.<br>`
                + 'Les articles et quantités du bon sont conservés.<br>'
                + '<small class="text-muted">L\'action sera journalisée.</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Revenir',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.post(route('stock.entrees.retour-brouillon', entreeId))
                .done((res) => { window.location.href = res.data.edit_url; })
                .fail((xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Retour impossible.' }));
        });
    });

    // ── Validation (SW-VALIDER-ENT — service au commit E) ─────────────────
    $('#btn-valider').on('click', () => {
        if (fileHorsLigne.size > 0) {
            Swal.fire({ icon: 'warning', title: 'Saisies en attente', text: `${fileHorsLigne.size} saisie(s) attendent le retour du réseau.` });
            return;
        }
        window.dispatchEvent(new CustomEvent('stock:valider-entree'));
    });
});
