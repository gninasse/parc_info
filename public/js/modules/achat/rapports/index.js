/**
 * index.js — A-07, page Rapports (D-16).
 *
 * Une carte se choisit, son aperçu se charge, ses exports suivent LES MÊMES
 * filtres. C'est le point de la cohérence contractuelle : ce qu'on lit à
 * l'écran est exactement ce que le fichier contiendra — même source, même
 * périmètre, même arrondi.
 */

const echapper = (texte) => $('<span>').text(texte ?? '—').html();

/** Un en-tête technique (`montant_ht`) devient lisible (« Montant ht »). */
const libelleColonne = (cle) => {
    const mots = String(cle).replace(/_/g, ' ');
    return mots.charAt(0).toUpperCase() + mots.slice(1);
};

/** Les montants s'alignent à droite et se séparent par milliers. */
const cellule = (valeur) => {
    if (valeur === null || valeur === undefined || valeur === '') return '<td class="text-muted">—</td>';

    if (typeof valeur === 'number') {
        return `<td class="text-end">${valeur.toLocaleString('fr-FR', { maximumFractionDigits: 2 })}</td>`;
    }

    return `<td>${echapper(valeur)}</td>`;
};

$(function () {
    let carteCourante = null;

    const filtres = () => ({
        du: $('#filter-du').val(),
        au: $('#filter-au').val(),
        fournisseur_id: $('#filter-fournisseur').val(),
        avec_regularisations: $('#filter-regularisations').is(':checked') ? 1 : 0,
    });

    const parametres = () => new URLSearchParams(
        Object.entries(filtres()).filter(([, valeur]) => valeur !== '' && valeur !== undefined)
    ).toString();

    const rendreTableau = (lignes) => {
        if (!lignes || lignes.length === 0) {
            return '<p class="text-center text-muted py-4 mb-0">Aucune donnée pour ce périmètre.</p>';
        }

        const colonnes = Object.keys(lignes[0]);

        return `<table class="table table-sm table-hover align-middle">
            <thead class="table-light"><tr>${colonnes.map((c) => `<th>${echapper(libelleColonne(c))}</th>`).join('')}</tr></thead>
            <tbody>${lignes.map((ligne) => `<tr>${colonnes.map((c) => cellule(ligne[c])).join('')}</tr>`).join('')}</tbody>
        </table>`;
    };

    /** La carte Signaux affiche 8 blocs, chacun avec son aide. */
    const rendreSignaux = (signaux) => Object.values(signaux).map((signal) => `
        <section class="mb-4">
            <h6 class="fw-bold mb-1">${echapper(signal.titre)}</h6>
            <p class="small text-muted fst-italic">${echapper(signal.aide)}</p>
            ${signal.lignes.length === 0
                ? '<p class="small text-success mb-0"><i class="bi bi-check-circle me-1"></i>Aucun signal sur ce périmètre.</p>'
                : rendreTableau(signal.lignes)}
        </section>`).join('');

    const charger = () => {
        if (!carteCourante) return;

        $('#bloc-apercu').removeClass('d-none');
        $('#apercu').html('<p class="text-center text-muted py-4 mb-0">Chargement…</p>');

        const url = carteCourante === 'signaux'
            ? `${route('achat.rapports.signaux')}?${parametres()}`
            : `${route('achat.rapports.donnees', carteCourante)}?${parametres()}`;

        $.getJSON(url)
            .done((reponse) => {
                $('#apercu-titre').text(reponse.titre ?? 'Signaux');

                // Les filtres sont RAPPELÉS au-dessus du tableau : on doit
                // savoir ce qu'on regarde, notamment les régularisations.
                $('#apercu-filtres').html(
                    Object.entries(reponse.filtres ?? {})
                        .map(([libelle, valeur]) => `<span class="me-3"><strong>${echapper(libelle)}</strong> : ${echapper(valeur)}</span>`)
                        .join('')
                );

                $('#apercu').html(
                    carteCourante === 'signaux'
                        ? rendreSignaux(reponse.signaux ?? {})
                        : rendreTableau(reponse.lignes)
                );
            })
            .fail((xhr) => {
                $('#apercu').html(
                    `<p class="text-center text-danger py-4 mb-0">${
                        xhr.status === 403
                            ? 'Votre profil n\'a pas accès à ce rapport.'
                            : 'Le rapport n\'a pas pu être chargé.'
                    }</p>`
                );
            });
    };

    $('#cartes').on('click', '.carte-rapport', function () {
        $('.carte-rapport').removeClass('active');
        $(this).addClass('active');
        carteCourante = $(this).data('carte');
        charger();

        // La carte Signaux n'a pas les mêmes formats d'export (XLSX/PDF).
        $('#exports button[data-format="csv"]').toggleClass('d-none', carteCourante === 'signaux');
    });

    $('#filter-du, #filter-au, #filter-fournisseur, #filter-regularisations').on('change', charger);

    $('#exports').on('click', 'button', function () {
        if (!carteCourante) return;

        const format = $(this).data('format');
        const base = carteCourante === 'signaux'
            ? route('achat.rapports.signaux-export')
            : route('achat.rapports.export', carteCourante);

        window.location.href = `${base}?${parametres()}&format=${format}`;
    });

    $('[data-bs-toggle="tooltip"]').each((_, element) => new bootstrap.Tooltip(element));
});
