/**
 * validation.js — SW-VALIDER-ENT partagée entre le brouillon (validation
 * directe sans équipements) et le wizard (phase 2 complète).
 *
 * Récapitulatif chiffré exact (UX §3.3) : « Bon d'entrée — MAG-… · 2 articles
 * (35 u) · 15 fiches Parc Info seront créées · 1 unité rattachée ·
 * Fournisseur X · ⚠ non modifiable après validation ». Anti-double-soumission
 * par jeton (I9) : un uuid par affichage de l'écran.
 */
const jeton = (window.crypto?.randomUUID)
    ? window.crypto.randomUUID()
    : `jeton-${Date.now()}-${Math.floor(Math.random() * 1e9)}`;

export function validerEntree(entreeId) {
    // La route est en POST : le récapitulatif s'obtient avec recap=1 (sans écriture)
    $.post(route('stock.entrees.valider', entreeId), { recap: 1 }, (res) => {
        const r = res.recap;
        const e = (t) => $('<i>').text(t ?? '—').html();

        const morceaux = [
            `<strong>Bon d'entrée — ${e(r.magasin)}</strong>`,
            `${r.articles} article(s) (${r.unites_articles} u)`,
        ];
        if (r.fiches_creees > 0) morceaux.push(`<strong>${r.fiches_creees} fiches Parc Info seront créées</strong>`);
        if (r.rattachements > 0) morceaux.push(`${r.rattachements} unité(s) rattachée(s)`);
        if (r.fournisseur) morceaux.push(`Fournisseur ${e(r.fournisseur)}`);

        Swal.fire({
            icon: 'question',
            title: 'Valider ce bon ?',
            html: `${morceaux.join(' · ')}<br><br>⚠ <em>Après validation, ce bon ne sera plus modifiable.</em>`,
            showCancelButton: true,
            confirmButtonText: '✓ Confirmer',
            cancelButtonText: 'Retour',
            showLoaderOnConfirm: true,
            allowOutsideClick: () => !Swal.isLoading(),
            preConfirm: () => $.post(route('stock.entrees.valider', entreeId), { jeton })
                .catch((xhr) => {
                    Swal.showValidationMessage(xhr.responseJSON?.message ?? 'Validation impossible.');
                    return false;
                }),
        }).then((result) => {
            if (!result.isConfirmed || !result.value) return;
            window.location.href = result.value.data.show_url;
        });
    });
}
