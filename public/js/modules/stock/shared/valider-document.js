/**
 * valider-document.js — SW-VALIDER-{ENT/SOR/TRF} partagée (S8, UX §0.4).
 * Récapitulatif chiffré obtenu par POST {route}?recap=1 (lecture pure),
 * confirmation → POST {jeton} (idempotence I9), redirection vers la fiche.
 * Un avertissement de seuil non bloquant (§7.4) s'affiche après validation.
 */
const jeton = (window.crypto?.randomUUID)
    ? window.crypto.randomUUID()
    : `jeton-${Date.now()}-${Math.floor(Math.random() * 1e9)}`;

const e = (t) => $('<i>').text(t ?? '—').html();

const RECAPS = {
    entree(r) {
        const morceaux = [
            `<strong>Bon d'entrée — ${e(r.magasin)}</strong>`,
            `${r.articles} article(s) (${r.unites_articles} u)`,
        ];
        if (r.fiches_creees > 0) morceaux.push(`<strong>${r.fiches_creees} fiches Parc Info seront créées</strong>`);
        if (r.rattachements > 0) morceaux.push(`${r.rattachements} unité(s) rattachée(s)`);
        if (r.fournisseur) morceaux.push(`Fournisseur ${e(r.fournisseur)}`);
        return morceaux.join(' · ');
    },
    sortie(r) {
        const morceaux = [
            `<strong>Bon de sortie — ${e(r.magasin)}</strong>`,
            `Bénéficiaire : ${e(r.beneficiaire)}`,
            `${r.articles} article(s) (${r.unites_articles} u)`,
        ];
        if (r.unites_equipements > 0) morceaux.push(`<strong>${r.unites_equipements} équipement(s) affecté(s)</strong>`);
        if (r.remis_a) morceaux.push(`Remis à ${e(r.remis_a)}`);
        return morceaux.join(' · ');
    },
    transfert(r) {
        const morceaux = [
            `<strong>Transfert ${e(r.magasin_source)} ⇄ ${e(r.magasin_cible)}</strong>`,
            `${r.articles} article(s) (${r.unites_articles} u)`,
        ];
        if (r.unites_equipements > 0) morceaux.push(`<strong>${r.unites_equipements} unité(s) changent de magasin</strong>`);
        if (r.transporte_par) morceaux.push(`Transporté par ${e(r.transporte_par)}`);
        return morceaux.join(' · ');
    },
};

export function validerDocument({ routeValider, type }) {
    $.post(routeValider, { recap: 1 }, (res) => {
        const r = res.recap;

        Swal.fire({
            icon: 'question',
            title: 'Valider ce bon ?',
            html: `${RECAPS[type](r)}<br><br>⚠ <em>Après validation, ce bon ne sera plus modifiable.</em>`,
            showCancelButton: true,
            confirmButtonText: '✓ Confirmer',
            cancelButtonText: 'Retour',
            showLoaderOnConfirm: true,
            allowOutsideClick: () => !Swal.isLoading(),
            preConfirm: () => $.post(routeValider, { jeton })
                .catch((xhr) => {
                    Swal.showValidationMessage(xhr.responseJSON?.message ?? 'Validation impossible.');
                    return false;
                }),
        }).then((result) => {
            if (!result.isConfirmed || !result.value) return;
            const reponse = result.value;

            // Avertissement de seuil non bloquant (§7.4)
            const alertes = reponse.recap?.alertes_seuil ?? [];
            if (alertes.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Bon validé — seuils franchis',
                    html: alertes.map((a) => `${a.statut === 'RUPTURE' ? '⛔' : '⚠'} ${e(a.article)} : ${a.quantite} restant(s)`).join('<br>'),
                    confirmButtonText: 'Continuer',
                }).then(() => { window.location.href = reponse.data.show_url; });
                return;
            }

            window.location.href = reponse.data.show_url;
        });
    });
}
