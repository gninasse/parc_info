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
import { validerEntree } from './validation.js';

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
        // Le bouton reset n'apparaît que sur une rangée enregistrée
        $rangee.find('.btn-reset-serie').toggleClass('d-none', etat !== 'ok');
    };

    const enregistrer = (tamponId, valeur, $rangee) =>
        $.ajax({
            url: route('stock.entrees.wizard.update', entreeId),
            method: 'PUT',
            data: {
                tampon_id: tamponId,
                numero_serie: valeur,
                etat: $rangee.find('.champ-etat').val(),
            },
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

    // L'état s'autosave dès qu'il change (rangée saisie ou non : il est
    // conservé et repris par la fiche à la validation)
    $('.champ-etat').on('change', function () {
        const $rangee = $(this).closest('.rangee-serie');
        enregistrer(Number($rangee.data('tampon-id')), $rangee.find('.champ-serie').val(), $rangee);
    });

    // ✕ Reset : efface le numéro enregistré (PUT vide), la rangée redevient à saisir
    $('.btn-reset-serie').on('click', function () {
        const $rangee = $(this).closest('.rangee-serie');
        const $champ = $rangee.find('.champ-serie');
        $champ.val('').removeClass('is-invalid');
        $rangee.find('.message-erreur').text('');
        enregistrer(Number($rangee.data('tampon-id')), '', $rangee)
            .done(() => $champ.trigger('focus'));
    });

    // ⌨ Entrée = rangée suivante DE LA MÊME LIGNE (puis on sort du champ)
    $('.champ-serie').on('keydown', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const $item = $(this).closest('.accordion-item');
        const champs = $item.find('.champ-serie').toArray();
        const suivant = champs[champs.indexOf(this) + 1];
        if (suivant) $(suivant).trigger('focus');
        else this.blur();
    });

    // ── Ligne active : celle qui reçoit les prochains scans ───────────────
    // Un bon peut porter plusieurs lignes « modèle × N » (par exemple un
    // portable Dell et un fixe HP) : le magasinier choisit laquelle il
    // référence, au lieu de subir l'ordre des accordéons.
    let ligneActiveId = null;

    const rangeesVides = (ligneId) => $(`.accordion-item[data-ligne-id="${ligneId}"] .rangee-serie`)
        .filter(function () {
            return $(this).find('.champ-serie').val().trim() === '';
        });

    const lignesDuBon = () => $('.accordion-item[data-ligne-id]').toArray()
        .map((item) => Number($(item).data('ligne-id')));

    const majLigneActive = (ligneId, { ouvrir = false } = {}) => {
        ligneActiveId = ligneId;

        $('.marqueur-actif').addClass('d-none');
        $('.btn-saisir-ici').removeClass('active');

        if (ligneId === null) {
            $('#ligne-active-libelle').text('Toutes les lignes sont complètes');
            $('#ligne-active-reste').text('');
            return;
        }

        const $item = $(`.accordion-item[data-ligne-id="${ligneId}"]`);
        $item.find('.marqueur-actif').removeClass('d-none');
        $item.find('.btn-saisir-ici').addClass('active');

        $('#ligne-active-libelle').text($item.find('.btn-saisir-ici').data('libelle') ?? '—');
        const reste = rangeesVides(ligneId).length;
        $('#ligne-active-reste').text(reste > 0 ? `${reste} n° de série à saisir` : 'ligne complète');

        if (ouvrir) {
            const collapse = $item.find('.accordion-collapse')[0];
            if (collapse) bootstrap.Collapse.getOrCreateInstance(collapse).show();
        }
    };

    /** Première ligne encore incomplète, en repartant de la ligne courante. */
    const prochaineLigneIncomplete = (depuis = null) => {
        const lignes = lignesDuBon();
        if (!lignes.length) return null;

        const debut = depuis === null ? 0 : Math.max(0, lignes.indexOf(depuis));
        for (let i = 0; i < lignes.length; i++) {
            const candidate = lignes[(debut + i) % lignes.length];
            if (rangeesVides(candidate).length > 0) return candidate;
        }
        return null;
    };

    $('.btn-saisir-ici').on('click', function () {
        majLigneActive(Number($(this).data('ligne-id')), { ouvrir: true });
        window.scanWizard?.refocus();
    });

    // Saisir manuellement dans une rangée bascule la ligne active dessus
    $('.champ-serie').on('focus', function () {
        const ligneId = Number($(this).closest('.accordion-item').data('ligne-id'));
        if (ligneId !== ligneActiveId) majLigneActive(ligneId);
    });

    majLigneActive(prochaineLigneIncomplete());

    // ── Champ scan global : remplit la ligne ACTIVE (S6) ──────────────────
    window.scanWizard = new StockScanField('#scan-wizard', {
        onScan: async (code) => {
            // Ligne complète (ou aucune choisie) : on bascule sur la suivante
            if (ligneActiveId === null || rangeesVides(ligneActiveId).length === 0) {
                const suivante = prochaineLigneIncomplete(ligneActiveId);
                if (suivante === null) {
                    return { ok: false, libelle: 'Toutes les rangées sont remplies' };
                }
                majLigneActive(suivante, { ouvrir: true });
            }

            const $vide = rangeesVides(ligneActiveId).first();

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

                // Ligne terminée : on enchaîne sur la suivante encore incomplète
                if (rangeesVides(ligneActiveId).length === 0) {
                    const suivante = prochaineLigneIncomplete(ligneActiveId);
                    majLigneActive(suivante, { ouvrir: suivante !== null });
                } else {
                    majLigneActive(ligneActiveId);
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

    // ── Validation (SW-VALIDER-ENT) ───────────────────────────────────────
    $('#btn-valider').on('click', () => {
        if (fileHorsLigne.size > 0) {
            Swal.fire({ icon: 'warning', title: 'Saisies en attente', text: `${fileHorsLigne.size} saisie(s) attendent le retour du réseau.` });
            return;
        }
        validerEntree(entreeId);
    });
});
