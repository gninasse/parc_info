/**
 * form.js — brouillon de transfert (UX §5.2) : Source ⇄ Cible avec contrôle
 * immédiat, disponible SOURCE, scan express borné aux unités de la source
 * (via le PointageService partagé). Structurellement la sortie SANS
 * bénéficiaire ni affectation.
 */
import '../shared/formatters.js';
import { validerDocument } from '../shared/valider-document.js';

$(function () {
    const transfertId = $('#transfert-form').data('transfert-id') || null;
    const lignes = { articles: [], modeles: [] };

    const initSelectArticle = ($select, nature) => {
        $select.select2({
            theme: 'bootstrap-5',
            placeholder: 'Rechercher un article…',
            minimumInputLength: 1,
            ajax: {
                url: '/catalogue/api/articles',
                dataType: 'json',
                delay: 250,
                data: (params) => ({ q: params.term, ...(nature ? { nature } : {}) }),
                processResults: (res) => ({
                    results: res.data
                        .filter((a) => (nature ? a.nature === nature : !['equipement', 'licence'].includes(a.nature)))
                        .map((a) => ({ id: a.id, text: `${a.code} — ${a.nom}`, article: a })),
                }),
            },
        });
    };

    // Disponible du magasin SOURCE (réservations incluses)
    const majDisponible = ($tr, ligne) => {
        const magasinId = $('#t-source').val();
        if (!magasinId || !ligne.article) return;
        $.getJSON(route('stock.sorties.disponibilite'), { magasin_id: magasinId, article_id: ligne.article.id }, (res) => {
            const reservee = res.reservee > 0 ? ` <span class="text-muted">(dont ${res.reservee} dans d'autres brouillons)</span>` : '';
            $tr.find('.cellule-disponible').html(`${res.disponible}${reservee}`);
            $tr.find('.input-quantite').toggleClass('is-invalid', Number($tr.find('.input-quantite').val()) > res.disponible);
        });
    };

    const rangeeArticle = (collection, nature) => {
        const $tbody = nature === 'equipement' ? $('#table-lignes-modeles tbody') : $('#table-lignes-articles tbody');
        const ligne = { article: null, quantite: 1 };
        collection.push(ligne);

        const $tr = $(`
            <tr>
                <td><select class="form-select select-article"></select></td>
                <td><input type="number" class="form-control input-quantite" min="1" step="1" value="1"></td>
                ${nature === 'equipement' ? '' : '<td class="cellule-disponible small">—</td>'}
                <td><button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-ligne"><i class="fas fa-trash"></i></button></td>
            </tr>`);
        $tbody.append($tr);

        initSelectArticle($tr.find('.select-article'), nature === 'equipement' ? 'equipement' : null);

        $tr.find('.select-article').on('select2:select', (e) => {
            ligne.article = e.params.data.article;
            if (nature !== 'equipement') majDisponible($tr, ligne);
            recalculer();
        });

        $tr.find('.input-quantite').on('input', function () {
            ligne.quantite = parseFloat(this.value) || 0;
            if (nature !== 'equipement') majDisponible($tr, ligne);
            recalculer();
        });

        $tr.find('.btn-supprimer-ligne').on('click', () => {
            collection.splice(collection.indexOf(ligne), 1);
            $tr.remove();
            recalculer();
        });

        $tr.data('ligne', ligne);
        return $tr;
    };

    const recalculer = () => {
        let articles = 0; let unitesArticles = 0; let unitesModeles = 0; let prePointees = 0;

        $('#table-lignes-articles tbody tr').each(function () {
            const ligne = $(this).data('ligne');
            if (!ligne?.article) return;
            articles++;
            unitesArticles += ligne.quantite || 0;
        });

        $('#table-lignes-modeles tbody tr').each(function () {
            const ligne = $(this).data('ligne');
            if (!ligne?.article) return;
            unitesModeles += ligne.quantite || 0;
            prePointees += ligne.pre_pointes || 0;
        });

        $('#compteur-articles').text(articles);
        $('#compteur-equipements').text(unitesModeles);
        $('#recap-barre').html(
            `<strong>${articles}</strong> article(s) (${unitesArticles} u) · <strong>${unitesModeles}</strong> équipement(s)`
        );

        const resteAPointer = unitesModeles > prePointees;
        $('#btn-pointage').toggleClass('d-none', !resteAPointer || !transfertId);
        $('#btn-valider').toggleClass('d-none', resteAPointer || !transfertId);
    };

    const chargeUtile = () => {
        const toutes = [];
        $('#table-lignes-articles tbody tr, #table-lignes-modeles tbody tr').each(function () {
            const ligne = $(this).data('ligne');
            if (!ligne?.article) return;
            toutes.push({ article_id: ligne.article.id, quantite: ligne.quantite });
        });

        return {
            magasin_source_id: $('#t-source').val(),
            magasin_cible_id: $('#t-cible').val(),
            date_document: $('#t-date').val(),
            transporte_par_nom: $('#t-transporte-nom').val() || null,
            transporte_par_employe_id: $('#t-transporte-employe').val() || null,
            lignes: toutes,
        };
    };

    const afficherErreurs = (xhr) => {
        if (xhr.status === 422 && xhr.responseJSON?.errors) {
            Swal.fire({
                icon: 'error',
                title: 'Formulaire incomplet',
                html: Object.values(xhr.responseJSON.errors).flat().map((m) => $('<i>').text(m).html()).join('<br>'),
            });
            return;
        }
        Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Une erreur est survenue.' });
    };

    const enregistrer = (surSucces) => {
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
        $('#table-lignes-articles tbody tr').each(function () {
            const ligne = $(this).data('ligne');
            if (ligne?.article) majDisponible($(this), ligne);
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

    $('#btn-ajouter-article').on('click', () => rangeeArticle(lignes.articles, null));
    $('#btn-ajouter-modele').on('click', () => rangeeArticle(lignes.modeles, 'equipement'));

    $('#transfert-form').on('submit', (e) => {
        e.preventDefault();
        if (verifierCible()) enregistrer();
    });

    $('#btn-supprimer').on('click', () => {
        Swal.fire({
            title: 'Supprimer ce bon ?',
            text: `Le Brouillon #${transfertId} et ses lignes seront supprimés.`,
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
                    const $tr = rangeeArticle(lignes.modeles, 'equipement');
                    const ligne = $tr.data('ligne');
                    ligne.article = res.data.ligne.article;
                    ligne.quantite = 1;
                    ligne.pre_pointes = 1;
                    $tr.find('.select-article')
                        .append(new Option(`${ligne.article.code} — ${ligne.article.nom}`, ligne.article.id, true, true))
                        .trigger('change');
                    recalculer();
                    return { ok: true, libelle: res.message };
                } catch (xhr) {
                    return { ok: false, libelle: xhr.responseJSON?.message ?? 'Scan refusé' };
                }
            },
        });
    }

    // ── Restauration (mode édition) ───────────────────────────────────────
    (window.LIGNES_INITIALES ?? []).forEach((initiale) => {
        const nature = initiale.article?.nature === 'equipement' ? 'equipement' : null;
        const $tr = rangeeArticle(nature ? lignes.modeles : lignes.articles, nature);
        const ligne = $tr.data('ligne');
        ligne.article = initiale.article;
        ligne.quantite = initiale.quantite;
        ligne.pre_pointes = initiale.pre_pointes ?? 0;

        $tr.find('.select-article')
            .append(new Option(`${initiale.article.code} — ${initiale.article.nom}`, initiale.article.id, true, true))
            .trigger('change');
        $tr.find('.input-quantite').val(initiale.quantite);
        if (!nature) majDisponible($tr, ligne);
    });

    document.querySelectorAll('[data-bs-toggle="popover"]').forEach((el) => new bootstrap.Popover(el));

    recalculer();
});
