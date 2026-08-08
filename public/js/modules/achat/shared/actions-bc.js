/**
 * actions-bc.js — les COMMANDES du bon de commande (Swal + POST), partagées
 * entre la liste A-02 (toolbar sur sélection) et la fiche A-04 (barre
 * d'actions). Un seul endroit décrit chaque confirmation : si SW-02 change,
 * il change partout.
 *
 * Chaque flux reçoit un objet `bon` ({id, numero_affiche, nb_lignes}) et un
 * rappel `apres(reponse)` exécuté au succès — la liste rafraîchit son tableau,
 * la fiche suit la redirection du serveur. Le serveur reste l'unique juge :
 * il revérifie permission et état à chaque POST.
 */

const echapper = (texte) => $('<span>').text(texte ?? '').html();

const executer = (url, methode, donnees = {}) => $.ajax({
    url,
    method: methode,
    data: JSON.stringify(donnees),
    contentType: 'application/json',
    dataType: 'json',
});

/** Échec réseau ou refus métier : message du serveur, blocages détaillés. */
const afficherRefus = (xhr) => {
    const reponse = xhr.responseJSON ?? {};
    const detail = Array.isArray(reponse.blocages) && reponse.blocages.length > 0
        ? `<ul class="text-start mb-0">${reponse.blocages.map((b) => `<li>${echapper(b.message)}</li>`).join('')}</ul>`
        : null;

    Swal.fire({
        icon: 'error',
        title: xhr.status === 409 ? 'Le bon a changé d\'état' : 'Action impossible',
        html: detail,
        text: detail ? undefined : (reponse.message ?? 'Action impossible.'),
    });
};

const toastSucces = (message) => Swal.fire({
    icon: 'success',
    title: message,
    timer: 2000,
    showConfirmButton: false,
    toast: true,
    position: 'top-end',
});

const commander = (url, methode, donnees, apres) => executer(url, methode, donnees)
    .done((reponse) => {
        toastSucces(reponse.message);
        apres?.(reponse);
    })
    .fail(afficherRefus);

export const ActionsBc = {

    /** SW-04 — suppression d'un brouillon : réelle, sans trace. */
    supprimer(bon, url, apres) {
        Swal.fire({
            title: `Supprimer le ${bon.numero_affiche} ?`,
            text: `Ses ${bon.nb_lignes} ligne(s) seront supprimées. Cette action ne laisse pas de trace : un brouillon n'engage rien.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Supprimer',
            cancelButtonText: 'Annuler',
        }).then((r) => {
            if (r.isConfirmed) commander(url, 'DELETE', {}, apres);
        });
    },

    /** Reprise par l'auteur : il défait sa propre soumission (SFD §7.1). */
    reprendre(bon, url, apres) {
        Swal.fire({
            title: 'Reprendre ce bon ?',
            text: `${bon.numero_affiche} repassera en brouillon et redeviendra modifiable.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Reprendre',
            cancelButtonText: 'Annuler',
        }).then((r) => {
            if (r.isConfirmed) commander(url, 'POST', {}, apres);
        });
    },

    /** M-06 — renvoi motivé : sans motif, l'auteur devine. */
    renvoyer(bon, url, apres) {
        Swal.fire({
            title: 'Renvoyer le bon en brouillon ?',
            input: 'textarea',
            inputLabel: 'Motif du renvoi',
            inputPlaceholder: 'Ce que l\'auteur doit corriger…',
            inputAttributes: { 'aria-label': 'Motif du renvoi' },
            text: 'Le bon repassera en brouillon chez son auteur, qui pourra le corriger et le soumettre à nouveau.',
            showCancelButton: true,
            confirmButtonText: 'Renvoyer en brouillon',
            cancelButtonText: 'Annuler',
            inputValidator: (valeur) =>
                (!valeur || valeur.trim().length < 5)
                    ? 'Indiquez le motif du renvoi : l\'auteur doit savoir quoi corriger.'
                    : undefined,
        }).then((r) => {
            if (r.isConfirmed) commander(url, 'POST', { motif: r.value }, apres);
        });
    },

    /**
     * SW-02 — le Swal ENRICHI du visa (UX2-08, UX4-03, UX4-07) : chiffres du
     * bon, contexte de dépense, signaux de vigilance. Les signaux viennent du
     * serveur et ne bloquent jamais. Repli gracieux si l'endpoint échoue : un
     * incident réseau ne bloque pas le visa.
     */
    valider(bon, urlSignaux, urlValider, apres) {
        const fcfa = (v) => Number(v ?? 0).toLocaleString('fr-FR', { maximumFractionDigits: 0 });

        $.getJSON(urlSignaux)
            .then((signaux) => {
                const infos = signaux.bon ?? {};
                const morceaux = [];

                morceaux.push(`<p class="mb-2">${echapper(infos.numero_affiche)} · ${infos.nb_lignes ?? 0} ligne(s) · <strong>${fcfa(infos.montant_ttc)} FCFA TTC</strong> · ${echapper(infos.fournisseur ?? '')}</p>`);

                if (signaux.cumul) {
                    morceaux.push(`<p class="mb-2">📊 ${signaux.cumul.rang_du_mois}ᵉ bon de ce fournisseur ce mois-ci — cumul : ${fcfa(signaux.cumul.cumul_ttc_mois)} FCFA TTC</p>`);
                }

                (signaux.ecarts_prix ?? []).forEach((ecart) => {
                    morceaux.push(`<p class="mb-1 text-warning">⚠ Ligne ${ecart.numero} : ${ecart.ecart_pct > 0 ? '+' : ''}${ecart.ecart_pct} % vs dernier payé (${fcfa(ecart.reference)} FCFA HT)</p>`);
                });

                if (signaux.fournisseur_recent) {
                    const fr = signaux.fournisseur_recent;
                    morceaux.push(`<p class="mb-1 text-warning">⚠ Fournisseur créé au Catalogue il y a ${fr.anciennete_jours} jour(s)${fr.premier_bc ? ' — premier bon de commande' : ''}</p>`);
                }

                if (signaux.auto_validation) {
                    morceaux.push('<p class="mb-1 text-warning">⚠ Vous avez saisi ce bon vous-même : la validation sera marquée « auto-validation »</p>');
                }

                morceaux.push('<p class="mb-0 mt-2">Le bon recevra son numéro définitif et <strong>ne pourra plus être modifié</strong>.</p>');

                return Swal.fire({
                    title: 'Valider le bon de commande ?',
                    html: `<div class="text-start">${morceaux.join('')}</div>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Valider le bon',
                    cancelButtonText: 'Annuler',
                    confirmButtonColor: '#198754',
                });
            })
            .catch(() => Swal.fire({
                title: 'Valider le bon de commande ?',
                text: `${bon.numero_affiche} recevra son numéro définitif et ne pourra plus être modifié.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Valider le bon',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#198754',
            }))
            .then((r) => {
                if (r?.isConfirmed) commander(urlValider, 'POST', {}, apres);
            });
    },

    /**
     * M-07 — annulation d'un bon validé sans réception : le motif est
     * obligatoire, un document officiel ne disparaît pas sans explication.
     */
    annuler(bon, url, apres) {
        Swal.fire({
            title: `Annuler le ${bon.numero_affiche} ?`,
            input: 'textarea',
            inputLabel: 'Motif de l\'annulation',
            inputPlaceholder: 'Pourquoi ce bon est-il annulé ?…',
            inputAttributes: { 'aria-label': 'Motif de l\'annulation' },
            text: 'Le bon restera consultable, marqué ANNULÉ, et son PDF portera le filigrane. Cette action est définitive.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Annuler le bon',
            cancelButtonText: 'Retour',
            inputValidator: (valeur) =>
                (!valeur || valeur.trim().length < 5)
                    ? 'Indiquez le motif de l\'annulation.'
                    : undefined,
        }).then((r) => {
            if (r.isConfirmed) commander(url, 'POST', { motif: r.value }, apres);
        });
    },

    /** M-03 — clôture du reliquat : on renonce au reste à livrer, motivé. */
    cloturer(bon, url, apres) {
        Swal.fire({
            title: `Clôturer le reliquat du ${bon.numero_affiche} ?`,
            input: 'textarea',
            inputLabel: 'Motif de la clôture',
            inputPlaceholder: 'Pourquoi renoncer au reste à livrer ?…',
            inputAttributes: { 'aria-label': 'Motif de la clôture' },
            text: 'Le reste à livrer sera abandonné et le bon passera CLÔTURÉ. Cette action est définitive.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Clôturer',
            cancelButtonText: 'Retour',
            inputValidator: (valeur) =>
                (!valeur || valeur.trim().length < 5)
                    ? 'Indiquez le motif de la clôture.'
                    : undefined,
        }).then((r) => {
            if (r.isConfirmed) commander(url, 'POST', { motif: r.value }, apres);
        });
    },
};
