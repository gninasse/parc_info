/**
 * pieces-jointes.js — carte des pièces jointes d'un bon (diligence 5),
 * commune aux entrées, sorties et transferts. Envoi multiple, aperçu des
 * PDF/images dans la modale d'impression, téléchargement contrôlé,
 * suppression (brouillon uniquement).
 */
import { ModalPdf } from './modal-pdf.js';

$(function () {
    const $carte = $('#carte-pieces-jointes');
    if (!$carte.length) return;

    const type = $carte.data('type');
    const documentId = $carte.data('document-id');
    const modifiable = Boolean($carte.data('modifiable'));

    const urlBase = `/stock/documents/${type}/${documentId}`;
    const echapper = (t) => $('<span>').text(t ?? '').html();

    const rendre = (pieces) => {
        const $liste = $('#pj-liste').empty();
        $('#pj-compteur').text(pieces.length);
        $('#pj-vide').toggleClass('d-none', pieces.length > 0);

        pieces.forEach((piece) => {
            const apercu = piece.est_pdf || piece.est_image
                ? `<button type="button" class="btn btn-sm btn-link p-0 pj-apercu" data-url="${piece.url}" data-nom="${echapper(piece.nom)}" data-pdf="${piece.est_pdf ? '1' : ''}">Aperçu</button>`
                : '';

            $liste.append(`
                <div class="col-md-6">
                    <div class="border rounded p-2 d-flex align-items-center gap-2">
                        <i class="bi ${piece.icone} fs-4 text-secondary"></i>
                        <div class="flex-grow-1 min-width-0">
                            <div class="text-truncate">${echapper(piece.nom)}</div>
                            <div class="small text-muted">
                                ${piece.taille} · ${echapper(piece.ajoute_par ?? '—')} · ${piece.ajoute_le}
                            </div>
                        </div>
                        <div class="d-flex gap-1">
                            ${apercu}
                            <a href="${piece.url}" class="btn btn-sm btn-outline-secondary" download
                               data-bs-toggle="tooltip" title="Télécharger"><i class="bi bi-download"></i></a>
                            ${modifiable ? `<button type="button" class="btn btn-sm btn-outline-danger pj-supprimer" data-id="${piece.id}" data-bs-toggle="tooltip" title="Supprimer"><i class="bi bi-trash"></i></button>` : ''}
                        </div>
                    </div>
                </div>`);
        });
    };

    const charger = () => $.getJSON(urlBase, (res) => rendre(res.data ?? []));

    // ── Ajout ─────────────────────────────────────────────────────────────
    $('#pj-ajouter').on('click', () => $('#pj-fichiers').trigger('click'));

    $('#pj-fichiers').on('change', function () {
        if (!this.files.length) return;

        const donnees = new FormData();
        Array.from(this.files).forEach((fichier) => donnees.append('fichiers[]', fichier));

        $('#pj-progression').removeClass('d-none');

        $.ajax({
            url: urlBase,
            method: 'POST',
            data: donnees,
            processData: false,
            contentType: false,
            dataType: 'json',
        })
            .done((res) => {
                charger();
                Swal.fire({ icon: 'success', title: res.message, timer: 2000, showConfirmButton: false });
            })
            .fail((xhr) => {
                const erreurs = xhr.responseJSON?.errors;
                Swal.fire({
                    icon: 'error',
                    title: 'Envoi refusé',
                    html: erreurs
                        ? Object.values(erreurs).flat().map((m) => echapper(m)).join('<br>')
                        : (xhr.responseJSON?.message ?? 'Envoi impossible.'),
                });
            })
            .always(() => {
                $('#pj-progression').addClass('d-none');
                $('#pj-fichiers').val('');
            });
    });

    // ── Aperçu (réutilise la modale d'impression) ────────────────────────
    $('#pj-liste').on('click', '.pj-apercu', function () {
        ModalPdf.ouvrir({
            urlBase: $(this).data('url'),
            titre: $(this).data('nom'),
            avecEquipements: false,
        });
    });

    // ── Suppression ───────────────────────────────────────────────────────
    $('#pj-liste').on('click', '.pj-supprimer', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Supprimer cette pièce jointe ?',
            text: 'Le fichier sera définitivement effacé.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Supprimer',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({ url: `${urlBase}/${id}`, method: 'DELETE', dataType: 'json' })
                .done(() => charger())
                .fail((xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Suppression impossible.' }));
        });
    });

    charger();
});
