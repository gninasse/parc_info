/**
 * form.js — brouillon de sortie (UX §4.2) : motif à pilules (urgence →
 * datetime, autre → texte), bénéficiaire 6 cartes + modales partagées,
 * disponible temps réel avec réservations, scan express (ligne modèle × 1
 * pré-pointée), barre collante (matrice UX §10).
 */
import '../shared/formatters.js';
import { SelecteurBeneficiaire } from '../shared/beneficiaire.js';
import { validerDocument } from '../shared/valider-document.js';

$(function () {
    const sortieId = $('#sortie-form').data('sortie-id') || null;
    const lignes = { articles: [], modeles: [] };

    // ── Select2 d'article (S11) ────────────────────────────────────────────
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

    // ── Disponible + réservations (information non bloquante) ────────────
    const majDisponible = ($tr, ligne) => {
        const magasinId = $('#s-magasin').val();
        if (!magasinId || !ligne.article) return;
        $.getJSON(route('stock.sorties.disponibilite'), { magasin_id: magasinId, article_id: ligne.article.id }, (res) => {
            const reservee = res.reservee > 0 ? ` <span class="text-muted">(dont ${res.reservee} dans d'autres brouillons)</span>` : '';
            $tr.find('.cellule-disponible').html(`${res.disponible}${reservee}`);
            $tr.find('.input-quantite').toggleClass('is-invalid', Number($tr.find('.input-quantite').val()) > res.disponible);
        });
    };

    // ── Rangées dynamiques ─────────────────────────────────────────────────
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

    // ── Récapitulatif + visibilité des boutons ─────────────────────────────
    const recalculer = () => {
        let articles = 0; let unitesArticles = 0; let unitesModeles = 0;

        $('#table-lignes-articles tbody tr').each(function () {
            const ligne = $(this).data('ligne');
            if (!ligne?.article) return;
            articles++;
            unitesArticles += ligne.quantite || 0;
        });

        let prePointees = 0;
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

        // « Pointer » si ≥1 unité non pré-pointée ; « Valider » sinon (matrice §10)
        const resteAPointer = unitesModeles > prePointees;
        $('#btn-pointage').toggleClass('d-none', !resteAPointer || !sortieId);
        $('#btn-valider').toggleClass('d-none', resteAPointer || !sortieId);
    };

    // ── Sérialisation / soumission ────────────────────────────────────────
    const chargeUtile = () => {
        const toutes = [];
        $('#table-lignes-articles tbody tr, #table-lignes-modeles tbody tr').each(function () {
            const ligne = $(this).data('ligne');
            if (!ligne?.article) return;
            toutes.push({ article_id: ligne.article.id, quantite: ligne.quantite });
        });

        return {
            magasin_id: $('#s-magasin').val(),
            date_document: $('#s-date').val(),
            motif_type: $('#s-motif-type').val(),
            motif_texte: $('#s-motif-texte').val() || null,
            remise_reelle_le: $('#s-remise-reelle').val() || null,
            beneficiaire_type: $('#s-beneficiaire-type').val(),
            beneficiaire_direction_id: $('#s-beneficiaire-direction').val() || null,
            beneficiaire_service_id: $('#s-beneficiaire-service').val() || null,
            beneficiaire_unite_id: $('#s-beneficiaire-unite').val() || null,
            beneficiaire_poste_id: $('#s-beneficiaire-poste').val() || null,
            beneficiaire_local_id: $('#s-beneficiaire-local').val() || null,
            beneficiaire_employe_id: $('#s-beneficiaire-employe').val() || null,
            remis_a_nom: $('#s-remis-a-nom').val() || null,
            remis_a_employe_id: $('#s-remis-a-employe').val() || null,
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
            url: sortieId ? route('stock.sorties.update', sortieId) : route('stock.sorties.store'),
            method: sortieId ? 'PUT' : 'POST',
            data: JSON.stringify(chargeUtile()),
            contentType: 'application/json',
            dataType: 'json',
            success: (res) => {
                if (!res.success) return;
                if (surSucces) { surSucces(res); return; }
                if (!sortieId) { window.location.href = route('stock.sorties.edit', res.data.id); return; }
                Swal.fire({ icon: 'success', title: 'Enregistré', timer: 2000, showConfirmButton: false });
            },
            error: afficherErreurs,
        });
    };

    // ── Événements ────────────────────────────────────────────────────────
    $('#s-magasin').select2({ theme: 'bootstrap-5', allowClear: true, placeholder: '' });

    $('#s-remis-a-employe').select2({
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
    const employeInitial = $('#s-remis-a-employe').data('selection');
    if (employeInitial) {
        $('#s-remis-a-employe')
            .append(new Option($('#s-remis-a-employe').data('selection-libelle'), employeInitial, true, true))
            .trigger('change');
    }

    $('#pilules-motif').on('click', '.pilule-motif', function () {
        $('.pilule-motif').removeClass('active');
        $(this).addClass('active');
        const motif = $(this).data('motif');
        $('#s-motif-type').val(motif);
        $('#bloc-urgence').toggleClass('d-none', motif !== 'urgence_hors_ouverture');
        $('#s-motif-texte').toggleClass('d-none', motif !== 'autre');
        if (motif === 'autre') $('#s-motif-texte').trigger('focus');
    });

    // Bénéficiaire : 6 cartes → modales partagées
    const selecteurBeneficiaire = new SelecteurBeneficiaire({
        onChoisi: ({ type, id, libelle }) => {
            $('#s-beneficiaire-type').val(type);
            ['direction', 'service', 'unite', 'poste', 'local', 'employe']
                .forEach((t) => $(`#s-beneficiaire-${t}`).val(t === type ? id : ''));
            $('#beneficiaire-choisi').html(`<span class="badge bg-primary-subtle text-primary-emphasis">${$('<i>').text(libelle).html()}</span>`);
            $('.carte-beneficiaire').removeClass('selectionnee');
            $(`.carte-beneficiaire[data-type="${type}"]`).addClass('selectionnee');
        },
    });

    $('.carte-beneficiaire').on('click', function () {
        selecteurBeneficiaire.ouvrir($(this).data('type'));
    });

    $('#btn-ajouter-article').on('click', () => rangeeArticle(lignes.articles, null));
    $('#btn-ajouter-modele').on('click', () => rangeeArticle(lignes.modeles, 'equipement'));

    $('#sortie-form').on('submit', (e) => { e.preventDefault(); enregistrer(); });

    $('#btn-supprimer').on('click', () => {
        Swal.fire({
            title: 'Supprimer ce bon ?',
            text: `Le Brouillon #${sortieId} et ses lignes seront supprimés.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Supprimer',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: route('stock.sorties.destroy', sortieId),
                method: 'DELETE',
                success: () => { window.location.href = route('stock.sorties.index'); },
                error: afficherErreurs,
            });
        });
    });

    $('#btn-pointage').on('click', () => {
        enregistrer(() => {
            $.post(route('stock.sorties.pointage', sortieId))
                .done((res) => { window.location.href = res.data.pointage_url; })
                .fail(afficherErreurs);
        });
    });

    $('#btn-valider').on('click', () => {
        enregistrer(() => validerDocument({
            routeValider: route('stock.sorties.valider', sortieId),
            type: 'sortie',
        }));
    });

    // ── Scan express (D17) : un scan = ligne modèle × 1 pré-pointée ───────
    if (sortieId && document.getElementById('scan-express')) {
        new StockScanField('#scan-express', {
            onScan: async (code) => {
                try {
                    const res = await $.post(route('stock.sorties.scan-express', sortieId), { numero_serie: code });
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
