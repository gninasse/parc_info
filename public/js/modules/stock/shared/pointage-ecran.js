/**
 * pointage-ecran.js — écran de pointage PARTAGÉ sorties/transferts (D14/D17).
 * Entièrement paramétré par window.POINTAGE.routes : aucune connaissance du
 * type de document. Le sélecteur d'unités (scan S6 inclus) est borné au
 * magasin source et au modèle de la ligne.
 */
import './formatters.js';
import { SelecteurUnites } from './selecteur-unites.js';
import { validerDocument } from './valider-document.js';

$(function () {
    const { routes, magasinSourceId } = window.POINTAGE;
    let { pointees, attendues } = window.POINTAGE;

    const majProgression = (statut) => {
        pointees = statut.pointees;
        attendues = statut.attendues;
        $('#progression-texte').text(`${pointees}/${attendues}`);
        $('#progression-barre').css('width', attendues > 0 ? `${Math.round((pointees * 100) / attendues)}%` : '0%');
        const complet = pointees >= attendues && attendues > 0;
        $('#btn-valider').prop('disabled', !complet)
            .attr('title', complet ? '' : `${attendues - pointees} unité(s) restant à pointer`);
    };

    const requete = (donnees) =>
        $.ajax({ url: routes.update, method: 'PUT', data: donnees, dataType: 'json' });

    const ajouterRangee = ($tbody, unite, tamponId) => {
        const e = (t) => $('<span>').text(t ?? '—').html();
        $tbody.append(`
            <tr data-tampon-id="${tamponId ?? ''}">
                <td class="font-monospace">${e(unite.code_inventaire)}</td>
                <td>${e(unite.modele)}</td>
                <td class="font-monospace">${e(unite.numero_serie)}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-depointer" data-tampon-id="${tamponId ?? ''}">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>`);
    };

    // ── Sélecteur d'unités par ligne (scan S6 embarqué) ───────────────────
    let ligneCourante = null;

    const selecteur = new SelecteurUnites({
        url: routes.unites,
        onChoisis: async (unites) => {
            const $item = $(`.accordion-item[data-ligne-id="${ligneCourante}"]`);
            const $tbody = $item.find('.liste-pointages');

            for (const unite of unites) {
                try {
                    const res = await requete({ action: 'pointer', ligne_id: ligneCourante, equipement_id: unite.id });
                    // le tampon créé est retrouvé au rechargement ; ici on suit la progression
                    ajouterRangee($tbody, unite, null);
                    majProgression(res.statut_pointage);
                    $item.find('.compteur-ligne').text(`${$tbody.find('tr').length}/${$item.data('quantite')}`);
                } catch (xhr) {
                    Swal.fire({ icon: 'error', title: 'Pointage refusé', text: xhr.responseJSON?.message ?? 'Unité refusée.' });
                    break;
                }
            }
            // Les rangées sans tampon_id (fraîchement pointées) : recharger pour les câbler
            if ($tbody.find('tr[data-tampon-id=""]').length) window.location.reload();
        },
    });

    $('.btn-choisir-unites').on('click', function () {
        ligneCourante = Number($(this).data('ligne-id'));
        const $item = $(this).closest('.accordion-item');
        const dejaChoisies = $('.liste-pointages tr').toArray()
            .map((tr) => Number($(tr).data('tampon-id'))).filter(Boolean);

        selecteur.ouvrir({
            url: routes.unites,
            params: { magasin_id: magasinSourceId, modele_id: $item.data('modele-id') },
            dejaChoisies,
        });
    });

    // ── Dépointer ──────────────────────────────────────────────────────────
    $(document).on('click', '.btn-depointer', function () {
        const tamponId = $(this).data('tampon-id');
        if (!tamponId) { window.location.reload(); return; }
        const $tr = $(this).closest('tr');

        requete({ action: 'depointer', tampon_id: tamponId })
            .done((res) => {
                const $item = $tr.closest('.accordion-item');
                $tr.remove();
                majProgression(res.statut_pointage);
                $item.find('.compteur-ligne').text(`${$item.find('.liste-pointages tr').length}/${$item.data('quantite')}`);
            })
            .fail((xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Dépointage impossible.' }));
    });

    // ── Retour brouillon (SW-RETOUR-BROUILLON, texte exact amendé) ────────
    $('#btn-retour-brouillon').on('click', () => {
        Swal.fire({
            title: 'Revenir au brouillon ?',
            html: `Les <strong>${pointees}</strong> unités pointées seront dépointées.<br>`
                + 'Les articles et quantités du bon sont conservés.<br>'
                + '<small class="text-muted">L\'action sera journalisée.</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Revenir',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.post(routes.retour)
                .done((res) => { window.location.href = res.data.edit_url; })
                .fail((xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Retour impossible.' }));
        });
    });

    // ── Validation (SW-VALIDER partagée) ──────────────────────────────────
    $('#btn-valider').on('click', () => validerDocument({
        routeValider: routes.valider,
        type: window.POINTAGE.typeDocument,
    }));
});
