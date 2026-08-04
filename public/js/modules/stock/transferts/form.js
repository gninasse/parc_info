/**
 * form.js — brouillon de transfert (UX §5.2).
 *
 * Lignes REGROUPÉES en une seule table (même parti pris que les entrées et
 * les sorties) ; l'article se choisit dans une MODALE (SelecteurArticle).
 * Spécificités du transfert : Source ⇄ Cible avec contrôle immédiat, le
 * disponible est celui de la SOURCE, le scan express est borné aux unités
 * de la source. Structurellement la sortie SANS bénéficiaire ni affectation.
 */
import '../shared/formatters.js';
import { SelecteurArticle } from '../shared/selecteur-article.js';
import { validerDocument } from '../shared/valider-document.js';

$(function () {
    const transfertId = $('#transfert-form').data('transfert-id') || null;

    // La vérité du formulaire : {article, quantite, pre_pointes}
    const lignes = [];

    const echapper = (t) => $('<span>').text(t ?? '—').html();

    // ── Disponible du magasin SOURCE (réservations incluses) ─────────────
    const majDisponible = ($tr, ligne) => {
        const magasinId = $('#t-source').val();
        if (!magasinId || !ligne.article || ligne.article.nature === 'equipement') return;

        $.getJSON(route('stock.sorties.disponibilite'), { magasin_id: magasinId, article_id: ligne.article.id }, (res) => {
            const reservee = res.reservee > 0
                ? ` <span class="text-muted">(dont ${res.reservee} dans d'autres brouillons)</span>`
                : '';
            $tr.find('.cellule-disponible').html(`${res.disponible}${reservee}`);
            $tr.find('.input-quantite').toggleClass('is-invalid', Number($tr.find('.input-quantite').val()) > res.disponible);
        });
    };

    // ── Rangées de la table unique ─────────────────────────────────────────
    const ajouterLigne = (article, quantite = 1, prePointes = 0) => {
        const ligne = { article, quantite, pre_pointes: prePointes };
        lignes.push(ligne);

        const estModele = article.nature === 'equipement';
        const $tr = $(`
            <tr>
                <td class="text-center">${window.natureBadgeFormatter(article.nature)}</td>
                <td>
                    <span class="font-monospace small text-muted">${echapper(article.code)}</span>
                    ${echapper(article.nom)}
                    ${estModele ? '<div class="small text-muted"><i class="bi bi-upc-scan me-1"></i>Modèle × N — unités de la source pointées à l\'étape suivante</div>' : ''}
                </td>
                <td><input type="number" class="form-control input-quantite" min="1" step="1" value="${quantite}"></td>
                <td class="cellule-disponible small">${estModele ? '<span class="text-muted">au pointage</span>' : '—'}</td>
                <td><button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-ligne"><i class="fas fa-trash"></i></button></td>
            </tr>`);
        $('#table-lignes tbody').append($tr);
        $tr.data('ligne', ligne);

        $tr.find('.input-quantite').on('input', function () {
            ligne.quantite = parseFloat(this.value) || 0;
            majDisponible($tr, ligne);
            recalculer();
        });

        // ⌨ Entrée sur la quantité de la dernière ligne = nouvelle ligne
        $tr.find('.input-quantite').on('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if ($tr.is($('#table-lignes tbody tr').last())) selecteurArticle.ouvrir();
            }
        });

        $tr.find('.btn-supprimer-ligne').on('click', () => {
            lignes.splice(lignes.indexOf(ligne), 1);
            $tr.remove();
            recalculer();
        });

        majDisponible($tr, ligne);
        recalculer();
        return $tr;
    };

    // ── Récapitulatif + visibilité des boutons ─────────────────────────────
    const recalculer = () => {
        let articles = 0; let unitesArticles = 0; let unitesModeles = 0; let prePointees = 0;

        lignes.forEach((ligne) => {
            if (ligne.article.nature === 'equipement') {
                unitesModeles += ligne.quantite || 0;
                prePointees += ligne.pre_pointes || 0;
            } else {
                articles++;
                unitesArticles += ligne.quantite || 0;
            }
        });

        $('#compteur-lignes').text(lignes.length);
        $('#recap-barre').html(
            `<strong>${articles}</strong> article(s) (${unitesArticles} u) · <strong>${unitesModeles}</strong> équipement(s)`
        );

        const resteAPointer = unitesModeles > prePointees;
        $('#btn-pointage').toggleClass('d-none', !resteAPointer || !transfertId);
        $('#btn-valider').toggleClass('d-none', resteAPointer || !transfertId);
    };

    // ── Sérialisation / soumission ────────────────────────────────────────
    const chargeUtile = () => ({
        magasin_source_id: $('#t-source').val(),
        magasin_cible_id: $('#t-cible').val(),
        date_document: $('#t-date').val(),
        transporte_par_nom: $('#t-transporte-nom').val() || null,
        transporte_par_employe_id: $('#t-transporte-employe').val() || null,
        lignes: lignes.map((ligne) => ({ article_id: ligne.article.id, quantite: ligne.quantite })),
    });

    const afficherErreurs = (xhr) => {
        if (xhr.status === 422 && xhr.responseJSON?.errors) {
            const erreurs = xhr.responseJSON.errors;
            const premiere = Object.keys(erreurs).find((c) => c.startsWith('lignes.'));
            if (premiere) {
                const index = Number(premiere.split('.')[1]);
                $('#table-lignes tbody tr').eq(index).addClass('table-danger');
                setTimeout(() => $('#table-lignes tbody tr').removeClass('table-danger'), 4000);
            }
            Swal.fire({
                icon: 'error',
                title: 'Formulaire incomplet',
                html: Object.values(erreurs).flat().map((m) => echapper(m)).join('<br>'),
            });
            return;
        }
        Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Une erreur est survenue.' });
    };

    const enregistrer = (surSucces) => {
        const $btn = $('#btn-enregistrer');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Enregistrement…');

        $.ajax({
            url: transfertId ? route('stock.transferts.update', transfertId) : route('stock.transferts.store'),
            method: transfertId ? 'PUT' : 'POST',
            data: JSON.stringify(chargeUtile()),
            contentType: 'application/json',
            dataType: 'json',
            success: (res) => {
                if (!res.success) return;
                if (surSucces) { surSucces(res); return; }
                if (!transfertId) { window.location.href = route('stock.transferts.edit', res.data.id); return; }
                Swal.fire({ icon: 'success', title: 'Enregistré', timer: 2000, showConfirmButton: false });
            },
            error: afficherErreurs,
            complete: () => $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Enregistrer le brouillon'),
        });
    };

    // ── Événements ────────────────────────────────────────────────────────
    $('#t-source, #t-cible').select2({ theme: 'bootstrap-5', allowClear: true, placeholder: '' });

    // Contrôle immédiat « identique à la source »
    const verifierCible = () => {
        const identiques = $('#t-source').val() && $('#t-source').val() === $('#t-cible').val();
        $('#erreur-cible').toggleClass('d-none', !identiques);
        $('#t-cible').next('.select2').find('.select2-selection').toggleClass('border-danger', identiques);
        return !identiques;
    };

    $('#t-source, #t-cible').on('change', () => {
        verifierCible();
        // Le disponible dépend de la source
        $('#table-lignes tbody tr').each(function () {
            const ligne = $(this).data('ligne');
            if (ligne) majDisponible($(this), ligne);
        });
    });

    $('#t-transporte-employe').select2({
        theme: 'bootstrap-5',
        allowClear: true,
        placeholder: 'Rechercher un employé…',
        ajax: {
            url: route('stock.beneficiaires.data'),
            dataType: 'json',
            delay: 250,
            data: (params) => ({ type: 'employe', q: params.term }),
            processResults: (res) => ({ results: res.data.map((e) => ({ id: e.id, text: `${e.libelle} (${e.code})` })) }),
        },
    });
    const employeInitial = $('#t-transporte-employe').data('selection');
    if (employeInitial) {
        $('#t-transporte-employe')
            .append(new Option($('#t-transporte-employe').data('selection-libelle'), employeInitial, true, true))
            .trigger('change');
    }

    // Sélecteur d'article en modale (remplace le Select2 de ligne)
    const selecteurArticle = new SelecteurArticle({
        onChoisi: (article) => ajouterLigne(article),
    });
    $('#btn-ajouter-article').on('click', () => selecteurArticle.ouvrir());

    $('#transfert-form').on('submit', (e) => {
        e.preventDefault();
        if (verifierCible()) enregistrer();
    });

    $('#btn-supprimer').on('click', () => {
        Swal.fire({
            title: 'Supprimer ce bon ?',
            text: `Le Brouillon #${transfertId} et ses ${lignes.length} lignes seront supprimés.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Supprimer',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: route('stock.transferts.destroy', transfertId),
                method: 'DELETE',
                success: () => { window.location.href = route('stock.transferts.index'); },
                error: afficherErreurs,
            });
        });
    });

    $('#btn-pointage').on('click', () => {
        if (!verifierCible()) return;
        enregistrer(() => {
            $.post(route('stock.transferts.pointage', transfertId))
                .done((res) => { window.location.href = res.data.pointage_url; })
                .fail(afficherErreurs);
        });
    });

    $('#btn-valider').on('click', () => {
        if (!verifierCible()) return;
        enregistrer(() => validerDocument({
            routeValider: route('stock.transferts.valider', transfertId),
            type: 'transfert',
        }));
    });

    // ── Scan express borné aux unités de la SOURCE ────────────────────────
    if (transfertId && document.getElementById('scan-express')) {
        new StockScanField('#scan-express', {
            onScan: async (code) => {
                try {
                    const res = await $.post(route('stock.transferts.scan-express', transfertId), { numero_serie: code });
                    ajouterLigne(res.data.ligne.article, 1, 1);
                    return { ok: true, libelle: res.message };
                } catch (xhr) {
                    return { ok: false, libelle: xhr.responseJSON?.message ?? 'Scan refusé' };
                }
            },
        });
    }

    // ── Restauration (mode édition) ───────────────────────────────────────
    (window.LIGNES_INITIALES ?? []).forEach((initiale) => {
        ajouterLigne(initiale.article, initiale.quantite, initiale.pre_pointes ?? 0);
    });

    document.querySelectorAll('[data-bs-toggle="popover"]').forEach((el) => new bootstrap.Popover(el));

    recalculer();
});
