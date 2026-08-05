/**
 * form.js — A-03 étape ①, saisie d'un brouillon de bon de commande.
 *
 * Partage des rôles, non négociable (IA-1) : ce fichier PRÉVISUALISE les
 * totaux pour que l'utilisateur voie l'effet de sa frappe, mais les montants
 * qui font foi sont ceux que le serveur renvoie à l'enregistrement. Après
 * chaque sauvegarde, l'affichage est remplacé par la réponse serveur.
 *
 * De même pour les valeurs figées (IA-2) : une ligne déjà enregistrée est
 * rendue depuis les données du serveur et n'est jamais re-synchronisée sur le
 * Catalogue, même si l'utilisateur rouvre la modale.
 */
import { NATURES } from '../../catalogue/formatters.js';
import { ModaleArticles } from './modale-articles.js';

const echapper = (texte) => $('<span>').text(texte ?? '').html();

const fcfa = (valeur) =>
    Number(valeur || 0).toLocaleString('fr-FR', { maximumFractionDigits: 2 });

$(function () {
    const $form = $('#bon-form');
    if ($form.length === 0) return;

    const config = {
        bonId: $form.data('bon-id') || null,
        updatedAt: $form.data('updated-at') || null,
        seuilEcart: Number($form.data('seuil-ecart')) || 20,
        urlStore: $form.data('url-store'),
        urlUpdate: $form.data('url-update'),
        urlReferencePrix: $form.data('url-reference-prix'),
        urlArticles: $form.data('url-articles'),
        urlListe: $form.data('url-liste'),
        urlRecapitulatif: $form.data('url-recapitulatif') || null,
        modeleUrlRecapitulatif: $form.data('modele-url-recapitulatif'),
    };

    /**
     * État des lignes en mémoire. `id` non nul = ligne déjà enregistrée, dont
     * designation / nature / taux ont été FIGÉS côté serveur.
     */
    let lignes = (window.ACHAT_LIGNES_EXISTANTES || []).map((ligne) => ({ ...ligne }));
    let modifie = false;

    // ── Rendu ──────────────────────────────────────────────────────────────

    const badgeNature = (nature) => {
        const definition = NATURES[nature];
        if (!definition) return echapper(nature);
        return `<span class="badge ${definition.classes}" style="${definition.style ?? ''}"
                     title="${definition.libelle}">${definition.icone}${definition.court}</span>`;
    };

    const rendreLigne = (ligne, index) => {
        const sousTotal = (Number(ligne.quantite) || 0) * (Number(ligne.prix_unitaire_ht) || 0);

        // Garde préventive : une licence sans logiciel rattaché ne pourra pas
        // être réceptionnée. On le dit MAINTENANT, pas au moment du wizard
        // quand il sera trop tard (UX2-10).
        const alerteLicence = ligne.nature === 'licence' && !ligne.logiciel_id
            ? `<div class="small text-danger mt-1" data-role="alerte-licence">
                   <i class="bi bi-exclamation-triangle-fill"></i>
                   Aucun logiciel rattaché — la réception sera impossible.
                   <a href="${echapper(ligne.url_article || '#')}" target="_blank" rel="noopener">Corriger au Catalogue →</a>
               </div>`
            : '';

        return `<tr data-index="${index}">
            <td>${badgeNature(ligne.nature)}</td>
            <td>
                <div>${echapper(ligne.designation)}</div>
                <div class="small text-muted" data-role="reference-prix"></div>
                ${alerteLicence}
            </td>
            <td class="text-end">
                <input type="number" class="form-control form-control-sm text-end champ-qte"
                       data-champ="quantite" value="${ligne.quantite}" min="0.01" step="0.01"
                       aria-label="Quantité">
            </td>
            <td class="text-end">
                <input type="number" class="form-control form-control-sm text-end champ-prix"
                       data-champ="prix_unitaire_ht" value="${ligne.prix_unitaire_ht}" min="0" step="0.01"
                       aria-label="Prix négocié HT">
            </td>
            <td class="text-center">
                <span class="badge bg-secondary-subtle text-secondary-emphasis pilule-tva"
                      data-role="pilule-tva" role="button" tabindex="0"
                      title="Cliquer pour modifier le taux">${Number(ligne.taux_tva)} %</span>
                <input type="number" class="form-control form-control-sm text-center champ-tva d-none"
                       data-champ="taux_tva" value="${ligne.taux_tva}" min="0" max="100" step="0.01"
                       aria-label="Taux de TVA">
            </td>
            <td class="text-end" data-role="sous-total">${fcfa(sousTotal)}</td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-danger" data-role="supprimer"
                        aria-label="Retirer la ligne" title="Retirer la ligne">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>`;
    };

    const rendre = () => {
        $('#lignes-corps').html(lignes.map(rendreLigne).join(''));
        $('#lignes-vides').toggleClass('d-none', lignes.length > 0);
        $('#compteur-lignes').text(lignes.length);

        // Filtre interne du tableau : utile seulement sur les longs bons (UX3-05)
        $('#filtre-lignes').toggleClass('d-none', lignes.length < 10);

        // Fournisseur verrouillé dès qu'une ligne existe : changer de
        // fournisseur laisserait des lignes d'un autre catalogue de prix.
        const verrouille = lignes.length > 0;
        $('#a-fournisseur').prop('disabled', verrouille);
        $('#aide-fournisseur-verrouille').toggleClass('d-none', !verrouille);

        rafraichirPied();
        lignes.forEach((_, index) => chargerReferencePrix(index));
    };

    /**
     * Prévisualisation des totaux. Elle applique la même règle que le serveur
     * (arrondi ligne à ligne, TVA par ligne), mais n'a valeur que d'estimation
     * jusqu'à l'enregistrement — la mention à côté le dit à l'utilisateur.
     */
    const rafraichirPied = () => {
        let ht = 0;
        let tva = 0;
        let unites = 0;

        lignes.forEach((ligne) => {
            const ligneHt = Math.round((Number(ligne.quantite) || 0) * (Number(ligne.prix_unitaire_ht) || 0) * 100) / 100;
            ht += ligneHt;
            tva += Math.round(ligneHt * (Number(ligne.taux_tva) || 0)) / 100;
            unites += Number(ligne.quantite) || 0;
        });

        ht = Math.round(ht * 100) / 100;
        tva = Math.round(tva * 100) / 100;

        $('#pied-lignes').text(`${lignes.length} ligne(s)`);
        $('#pied-unites').text(`${fcfa(unites)} unité(s)`);
        $('#pied-ht').text(`${fcfa(ht)} FCFA HT`);
        $('#pied-ttc').text(`${fcfa(ht + tva)} FCFA TTC`);

        // « Continuer » reste fermé tant qu'il n'y a rien à récapituler, avec
        // le diagnostic en infobulle (SPEC_UX §0.3).
        const complet = lignes.length > 0 && $('#a-fournisseur').val() !== '';
        $('#btn-continuer')
            .prop('disabled', !complet)
            .attr('title', complet ? '' : (lignes.length === 0 ? 'Ajoutez au moins une ligne' : 'Choisissez un fournisseur'));
    };

    /**
     * PO-01 — référence de prix et pilule d'écart. Le calcul de l'écart est
     * fait par le SERVEUR : c'est la même règle qui alimentera le
     * récapitulatif et le rapport Signaux.
     */
    const chargerReferencePrix = (index) => {
        const ligne = lignes[index];
        if (!ligne || !ligne.article_id) return;

        const url = config.urlReferencePrix.replace('__ID__', ligne.article_id);
        const $cellule = $(`#lignes-corps tr[data-index="${index}"] [data-role="reference-prix"]`);

        $.getJSON(`${url}?prix=${encodeURIComponent(ligne.prix_unitaire_ht || 0)}`, (reponse) => {
            const morceaux = [];

            if (reponse.reference) {
                morceaux.push(`réf. ${fcfa(reponse.reference)}`);
            }

            if (reponse.ecart && reponse.ecart.depasse_seuil) {
                const signe = reponse.ecart.ecart_pct > 0 ? '+' : '';
                morceaux.push(
                    `<span class="badge bg-warning text-dark">⚠ ${signe}${reponse.ecart.ecart_pct} % vs ${
                        reponse.origine_reference === 'dernier_paye' ? 'dernier payé' : 'prix indicatif'
                    }</span>`
                );
            }

            if (reponse.modification_recente) {
                morceaux.push(
                    `<span class="text-muted" title="Prix indicatif modifié au Catalogue le ${
                        echapper(reponse.modification_recente.date)
                    }">réf. modifiée le ${echapper(reponse.modification_recente.date)}</span>`
                );
            }

            $cellule.html(morceaux.join(' · '));
        });
    };

    // ── Interactions sur les lignes ────────────────────────────────────────

    $('#lignes-corps').on('input', 'input[data-champ]', function () {
        const index = Number($(this).closest('tr').data('index'));
        lignes[index][$(this).data('champ')] = $(this).val();
        modifie = true;

        const sousTotal = (Number(lignes[index].quantite) || 0) * (Number(lignes[index].prix_unitaire_ht) || 0);
        $(this).closest('tr').find('[data-role="sous-total"]').text(fcfa(sousTotal));
        rafraichirPied();
    });

    // La pilule de TVA redevient un champ au clic, et repilule au blur (UX-04)
    $('#lignes-corps').on('click keypress', '[data-role="pilule-tva"]', function (evenement) {
        if (evenement.type === 'keypress' && !['Enter', ' '].includes(evenement.key)) return;
        $(this).addClass('d-none').closest('td').find('.champ-tva').removeClass('d-none').trigger('focus');
    });

    $('#lignes-corps').on('blur', '.champ-tva', function () {
        const $cellule = $(this).closest('td');
        $(this).addClass('d-none');
        $cellule.find('[data-role="pilule-tva"]').removeClass('d-none').text(`${Number($(this).val())} %`);

        chargerReferencePrix(Number($(this).closest('tr').data('index')));
    });

    // Le prix change : l'écart doit être réévalué par le serveur.
    $('#lignes-corps').on('change', '[data-champ="prix_unitaire_ht"]', function () {
        chargerReferencePrix(Number($(this).closest('tr').data('index')));
    });

    // Suppression sans confirmation : c'est un brouillon (SPEC_UX A-03).
    $('#lignes-corps').on('click', '[data-role="supprimer"]', function () {
        lignes.splice(Number($(this).closest('tr').data('index')), 1);
        modifie = true;
        rendre();
    });

    $('#filtre-lignes').on('input', function () {
        const terme = $(this).val().toLowerCase();
        $('#lignes-corps tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(terme));
        });
    });

    // ── En-tête ────────────────────────────────────────────────────────────

    $('#pilules-observation').on('click', '.pilule-motif', function () {
        const motif = $(this).data('motif');
        const dejaActif = $(this).hasClass('active');

        $('.pilule-motif').removeClass('active');

        if (dejaActif) {
            $('#a-observation-type').val('');
            $('#a-observation-texte').addClass('d-none');
        } else {
            $(this).addClass('active');
            $('#a-observation-type').val(motif);
            // « Autre » exige un texte ; « Sur demande de service » ouvre le
            // champ Service demandeur (SPEC_UX A-03).
            $('#a-observation-texte').toggleClass('d-none', motif !== 'autre');
            if (motif === 'sur_demande') $('#a-service').trigger('focus');
        }

        modifie = true;
    });

    $form.on('input change', 'input, select, textarea', () => { modifie = true; });
    $('#a-fournisseur').on('change', rafraichirPied);

    // ── M-01 : ajout d'articles ────────────────────────────────────────────

    const modale = new ModaleArticles({
        urlArticles: config.urlArticles,
        fournisseurPrefereId: () => $('#a-fournisseur').val(),
        onAjouter: (articles) => {
            articles.forEach((article) => {
                lignes.push({
                    id: null, // nouvelle ligne : le serveur figera les valeurs
                    article_id: article.id,
                    designation: article.nom,
                    nature: article.nature,
                    quantite: 1,
                    // Pré-remplissage depuis le prix indicatif ; l'acheteur
                    // saisit ensuite le prix réellement négocié.
                    prix_unitaire_ht: Number(article.prix_indicatif) || 0,
                    taux_tva: Number(article.taux_tva) || 18,
                    logiciel_id: article.logiciel_id,
                });
            });

            modifie = true;
            rendre();
        },
    });
    modale.initialiser();

    // ── Enregistrement ─────────────────────────────────────────────────────

    const charge = () => ({
        fournisseur_id: $('#a-fournisseur').val(),
        date_document: $('#a-date').val(),
        est_regularisation: $('#a-regularisation').val(),
        service_demandeur_id: $('#a-service').val() || null,
        reference_demande: $('#a-reference-demande').val() || null,
        observation_type: $('#a-observation-type').val() || null,
        observation_texte: $('#a-observation-texte').val() || null,
        updated_at: config.updatedAt,
        lignes: lignes.map((ligne) => ({
            id: ligne.id,
            article_id: ligne.article_id,
            quantite: ligne.quantite,
            prix_unitaire_ht: ligne.prix_unitaire_ht,
            taux_tva: ligne.taux_tva,
        })),
    });

    const afficherErreurs = (erreurs) => {
        $('#lignes-corps .is-invalid, #bon-form .is-invalid').removeClass('is-invalid');

        const messages = Object.entries(erreurs || {}).map(([champ, textes]) => {
            // Erreur de ligne : on la pose sur le champ fautif du tableau,
            // pour que l'utilisateur voie OÙ corriger (SPEC_UX §15.2).
            const surLigne = champ.match(/^lignes\.(\d+)\.(\w+)$/);
            if (surLigne) {
                $(`#lignes-corps tr[data-index="${surLigne[1]}"] [data-champ="${surLigne[2]}"]`).addClass('is-invalid');
                return `Ligne ${Number(surLigne[1]) + 1} : ${textes[0]}`;
            }

            $(`#bon-form [name="${champ}"]`).addClass('is-invalid');
            return textes[0];
        });

        $('#erreurs-liste').html(messages.map((m) => `<li>${echapper(m)}</li>`).join(''));
        $('#erreurs-formulaire').toggleClass('d-none', messages.length === 0);

        if (messages.length > 0) {
            document.getElementById('erreurs-formulaire').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    };

    const enregistrer = () => {
        const $bouton = $('#btn-enregistrer');
        // Anti-double-soumission (SPEC_UX §0.3)
        if ($bouton.prop('disabled')) return Promise.resolve(false);
        $bouton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Enregistrement…');

        return $.ajax({
            url: config.bonId ? config.urlUpdate : config.urlStore,
            method: config.bonId ? 'PUT' : 'POST',
            data: JSON.stringify(charge()),
            contentType: 'application/json',
            dataType: 'json',
        })
            .then((reponse) => {
                $('#erreurs-formulaire').addClass('d-none');
                modifie = false;

                // Les montants et l'état renvoyés par le serveur REMPLACENT la
                // prévisualisation : c'est le serveur qui a raison (IA-1).
                appliquerEtatServeur(reponse.data);

                Swal.fire({
                    icon: 'success',
                    title: reponse.message,
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                });

                return true;
            })
            .catch((xhr) => {
                if (xhr.status === 422) {
                    afficherErreurs(xhr.responseJSON?.errors);
                } else if (xhr.status === 409 && xhr.responseJSON?.conflit) {
                    conflitDEdition(xhr.responseJSON);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: xhr.responseJSON?.message ?? 'Enregistrement impossible.',
                    });
                }

                return false;
            })
            .always(() => {
                $bouton.prop('disabled', false).html('<i class="bi bi-save me-1"></i>Enregistrer le brouillon');
            });
    };

    /** Remplace l'affichage par la vérité du serveur. */
    const appliquerEtatServeur = (donnees) => {
        if (!donnees) return;

        config.bonId = donnees.id;
        config.updatedAt = donnees.updated_at;
        config.urlUpdate = config.urlUpdate || `${config.urlStore}/${donnees.id}`;
        config.urlRecapitulatif = config.urlRecapitulatif
            || config.modeleUrlRecapitulatif.replace('__ID__', donnees.id);

        // La sortie est désormais sans perte : l'encart de renvoi éventuel
        // n'a plus lieu d'être puisque le brouillon vient d'être repris.
        $('#encart-renvoi').addClass('d-none');

        lignes = donnees.lignes.map((ligne) => ({ ...ligne }));

        $('#bandeau-brouillon').text(`${donnees.numero_affiche} — modifiable, non engageant, sans numéro`);
        $('#pied-ht').text(`${fcfa(donnees.montant_ht)} FCFA HT`);
        $('#pied-ttc').text(`${fcfa(donnees.montant_ttc)} FCFA TTC`);

        rendre();
    };

    /**
     * Conflit d'édition : jamais de fusion silencieuse (UX3-05). L'utilisateur
     * choisit explicitement entre recharger et écraser.
     */
    const conflitDEdition = (reponse) => {
        Swal.fire({
            icon: 'warning',
            title: 'Brouillon modifié entre-temps',
            text: reponse.message,
            showCancelButton: true,
            confirmButtonText: 'Recharger',
            cancelButtonText: 'Écraser avec ma version',
        }).then((resultat) => {
            if (resultat.isConfirmed) {
                window.location.reload();
                return;
            }

            // Écrasement assumé : on repart de la version du serveur comme
            // jeton, ce qui fera passer le prochain envoi.
            config.updatedAt = reponse.data?.updated_at ?? null;
            enregistrer();
        });
    };

    $('#btn-enregistrer').on('click', enregistrer);

    $('#btn-continuer').on('click', () => {
        // L'étape ② travaille sur des données ENREGISTRÉES : on sauvegarde
        // d'abord, sinon le récapitulatif décrirait autre chose que le bon.
        // C'est aussi ce qui donne son identifiant à un tout nouveau brouillon.
        enregistrer().then((ok) => {
            if (!ok) return;

            const url = config.urlRecapitulatif
                || config.modeleUrlRecapitulatif.replace('__ID__', config.bonId);
            window.location.href = url;
        });
    });

    // PO-02 — décomposition des totaux, l'outil de diagnostic quand un total
    // surprend (UX3-03).
    $('#btn-decomposition').on('click', () => {
        const parTaux = {};

        lignes.forEach((ligne) => {
            const taux = Number(ligne.taux_tva) || 0;
            const ht = Math.round((Number(ligne.quantite) || 0) * (Number(ligne.prix_unitaire_ht) || 0) * 100) / 100;
            parTaux[taux] = parTaux[taux] || { ht: 0, tva: 0 };
            parTaux[taux].ht += ht;
            parTaux[taux].tva += Math.round(ht * taux) / 100;
        });

        const lignesHtml = Object.entries(parTaux)
            .sort((a, b) => Number(a[0]) - Number(b[0]))
            .map(([taux, valeurs]) =>
                `<tr><td>${taux} %</td><td class="text-end">${fcfa(valeurs.ht)}</td>
                 <td class="text-end">${fcfa(valeurs.tva)}</td>
                 <td class="text-end">${fcfa(valeurs.ht + valeurs.tva)}</td></tr>`)
            .join('');

        Swal.fire({
            title: 'Décomposition des totaux',
            html: `<table class="table table-sm">
                    <thead><tr><th>Taux</th><th class="text-end">Base HT</th><th class="text-end">TVA</th><th class="text-end">TTC</th></tr></thead>
                    <tbody>${lignesHtml || '<tr><td colspan="4" class="text-muted">Aucune ligne</td></tr>'}</tbody>
                   </table>`,
            confirmButtonText: 'Fermer',
        });
    });

    // Garde de sortie : ne pas perdre une saisie par un clic malheureux.
    window.addEventListener('beforeunload', (evenement) => {
        if (!modifie) return;
        evenement.preventDefault();
        evenement.returnValue = '';
    });

    rendre();
});
