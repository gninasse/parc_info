/**
 * form.js — page brouillon du bon d'entrée (UX §3.2).
 *
 * Lignes dynamiques : onglet Articles (natures C/P, Select2 sur l'API
 * Catalogue — S11, coût pré-rempli du prix indicatif avec popover d'alerte
 * ±20 % calculée ici), onglet Équipements (lignes modèle × N nature E +
 * chips de rattachement via le sélecteur d'unités). Barre collante :
 * matrice de visibilité UX §10.
 */
import '../shared/formatters.js';
import { SelecteurUnites } from '../shared/selecteur-unites.js';
import { validerEntree } from './validation.js';

$(function () {
    const $form = $('#entree-form');
    const seuilAlerte = parseFloat($form.data('seuil-alerte-cout')) || 0.2;
    const entreeId = $form.data('entree-id') || null;

    // Lignes en mémoire — la vérité du formulaire (enregistrement au clic)
    const lignes = { articles: [], modeles: [], rattachements: [] };

    // ── Select2 d'article (S11 — API Catalogue) ────────────────────────────
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
                        // En contexte quantitatif, les natures E vivent dans l'onglet Équipements
                        .filter((a) => (nature ? a.nature === nature : !['equipement', 'licence'].includes(a.nature)))
                        .map((a) => ({ id: a.id, text: `${a.code} — ${a.nom}`, article: a })),
                }),
            },
            templateResult: (item) => {
                if (!item.article) return item.text;
                const a = item.article;
                return $(`
                    <div>
                        ${window.natureBadgeFormatter(a.nature)} <strong>${a.code}</strong> ${$('<i>').text(a.nom).html()}
                        <div class="small text-muted">Unité : ${a.unite_stock ?? '—'} — Prix indicatif : ${a.prix_indicatif ? Number(a.prix_indicatif).toLocaleString('fr-FR') + ' FCFA' : '—'}</div>
                    </div>`);
            },
        });
    };

    // ── Alerte de coût ±20 % (config stock.seuil_alerte_cout) ─────────────
    const coutSuspect = (cout, prixIndicatif) => {
        if (!prixIndicatif || cout === null || cout === '' || isNaN(cout)) return false;
        return Math.abs(cout - prixIndicatif) / prixIndicatif > seuilAlerte;
    };

    const majAlerteCout = ($input, prixIndicatif) => {
        const suspect = coutSuspect(parseFloat($input.val()), prixIndicatif);
        const $icone = $input.closest('td').find('.alerte-cout');
        $icone.toggleClass('d-none', !suspect);
        if (suspect) {
            $icone.attr('data-bs-content',
                `Coût saisi éloigné du prix indicatif (${Number(prixIndicatif).toLocaleString('fr-FR')}) — vérifiez`);
        }
    };

    // ── Rangées dynamiques ─────────────────────────────────────────────────
    const rangeeArticle = (collection, nature) => {
        const $tbody = nature === 'equipement' ? $('#table-lignes-modeles tbody') : $('#table-lignes-articles tbody');
        const ligne = { article: null, quantite: 1, cout_unitaire: null };
        collection.push(ligne);

        const $tr = $(`
            <tr>
                <td><select class="form-select select-article"></select></td>
                <td><input type="number" class="form-control input-quantite" min="1" step="1" value="1"></td>
                <td>
                    <div class="input-group">
                        <input type="number" class="form-control input-cout" min="0" step="any">
                        <span class="input-group-text alerte-cout d-none text-warning" data-bs-toggle="popover" data-bs-trigger="hover focus">⚠</span>
                    </div>
                </td>
                ${nature === 'equipement' ? '' : '<td class="text-end sous-total">—</td>'}
                <td><button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-ligne"><i class="fas fa-trash"></i></button></td>
            </tr>`);
        $tbody.append($tr);

        initSelectArticle($tr.find('.select-article'), nature === 'equipement' ? 'equipement' : null);

        $tr.find('.select-article').on('select2:select', (e) => {
            ligne.article = e.params.data.article;
            const prix = ligne.article.prix_indicatif ? parseFloat(ligne.article.prix_indicatif) : null;
            if (prix !== null && !$tr.find('.input-cout').val()) {
                $tr.find('.input-cout').val(prix);
                ligne.cout_unitaire = prix;
            }
            recalculer();
        });

        $tr.find('.input-quantite').on('input', function () {
            ligne.quantite = parseFloat(this.value) || 0;
            recalculer();
        });

        $tr.find('.input-cout').on('input', function () {
            ligne.cout_unitaire = this.value === '' ? null : parseFloat(this.value);
            majAlerteCout($(this), ligne.article?.prix_indicatif ? parseFloat(ligne.article.prix_indicatif) : null);
            recalculer();
        });

        // ⌨ Entrée sur la dernière cellule = nouvelle ligne (UX §3.2)
        $tr.find('.input-cout').on('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if ($tr.is($tbody.find('tr').last())) rangeeArticle(collection, nature);
            }
        });

        $tr.find('.btn-supprimer-ligne').on('click', () => {
            collection.splice(collection.indexOf(ligne), 1);
            $tr.remove();
            recalculer();
        });

        $tr.data('ligne', ligne);
        return $tr;
    };

    const ajouterChipRattachement = (unite) => {
        lignes.rattachements.push(unite);
        const $chip = $(`
            <span class="badge bg-light text-dark border chip-rattachement" data-id="${unite.id}">
                ${$('<i>').text(unite.code_inventaire).html()} (${$('<i>').text(unite.numero_serie ?? '—').html()})
                <button type="button" class="btn-close" style="font-size:.6em" aria-label="Retirer"></button>
            </span>`);
        $chip.find('.btn-close').on('click', () => {
            lignes.rattachements = lignes.rattachements.filter((u) => u.id !== unite.id);
            $chip.remove();
            recalculer();
        });
        $('#chips-rattachements').append($chip);
        recalculer();
    };

    // ── Récapitulatif + matrice de visibilité des boutons (UX §10) ────────
    const recalculer = () => {
        let totalArticles = 0; let unitesArticles = 0; let totalFcfa = 0;

        $('#table-lignes-articles tbody tr').each(function () {
            const ligne = $(this).data('ligne');
            if (!ligne?.article) return;
            totalArticles++;
            unitesArticles += ligne.quantite || 0;
            const sousTotal = (ligne.quantite || 0) * (ligne.cout_unitaire || 0);
            totalFcfa += sousTotal;
            $(this).find('.sous-total').text(sousTotal ? Number(sousTotal).toLocaleString('fr-FR') + ' FCFA' : '—');
        });

        let unitesModeles = 0;
        $('#table-lignes-modeles tbody tr').each(function () {
            const ligne = $(this).data('ligne');
            if (!ligne?.article) return;
            unitesModeles += ligne.quantite || 0;
            totalFcfa += (ligne.quantite || 0) * (ligne.cout_unitaire || 0);
        });

        $('#compteur-articles').text(totalArticles);
        $('#compteur-equipements').text(unitesModeles + lignes.rattachements.length);

        $('#recap-barre').html(
            `<strong>${totalArticles}</strong> article(s) (${unitesArticles} u) · `
            + `<strong>${unitesModeles}</strong> équipement(s) · `
            + `<strong>${lignes.rattachements.length}</strong> rattachement(s) · `
            + `<strong>${Number(totalFcfa).toLocaleString('fr-FR')} FCFA</strong>`
        );

        // « Saisir les numéros de série » si ≥1 ligne modèle × N, sinon « ✓ Valider »
        const aDesModeles = unitesModeles > 0;
        $('#btn-referencement').toggleClass('d-none', !aDesModeles || !entreeId);
        $('#btn-valider').toggleClass('d-none', aDesModeles || !entreeId);
    };

    // ── Sérialisation et soumission (enregistrer au clic) ─────────────────
    const chargeUtile = () => {
        const toutes = [];

        $('#table-lignes-articles tbody tr, #table-lignes-modeles tbody tr').each(function () {
            const ligne = $(this).data('ligne');
            if (!ligne?.article) return;
            toutes.push({
                article_id: ligne.article.id,
                quantite: ligne.quantite,
                cout_unitaire: ligne.cout_unitaire,
            });
        });

        lignes.rattachements.forEach((unite) => {
            toutes.push({ equipement_id: unite.id, quantite: 1 });
        });

        return {
            magasin_id: $('#e-magasin').val(),
            date_document: $('#e-date').val(),
            nature: $('#e-nature').val(),
            fournisseur_id: $('#e-fournisseur').val() || null,
            reference_externe: $('#e-reference').val() || null,
            observation_type: $('#e-observation-type').val() || null,
            observation: $('#e-observation').val() || null,
            lignes: toutes,
        };
    };

    const enregistrer = (surSucces) => {
        const $btn = $('#btn-enregistrer');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Enregistrement…');

        $.ajax({
            url: entreeId ? route('stock.entrees.update', entreeId) : route('stock.entrees.store'),
            method: entreeId ? 'PUT' : 'POST',
            data: JSON.stringify(chargeUtile()),
            contentType: 'application/json',
            dataType: 'json',
            success: (res) => {
                if (!res.success) return;
                if (surSucces) { surSucces(res); return; }
                if (!entreeId) { window.location.href = route('stock.entrees.edit', res.data.id); return; }
                Swal.fire({ icon: 'success', title: 'Enregistré', timer: 2000, showConfirmButton: false });
            },
            error: (xhr) => afficherErreurs(xhr),
            complete: () => $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Enregistrer le brouillon'),
        });
    };

    const afficherErreurs = (xhr) => {
        // SW-422 : erreurs mappées champ par champ, bascule vers l'onglet fautif
        if (xhr.status === 422 && xhr.responseJSON?.errors) {
            const erreurs = xhr.responseJSON.errors;
            const premiere = Object.keys(erreurs)[0];
            if (premiere.startsWith('lignes.')) {
                const index = Number(premiere.split('.')[1]);
                const nbArticles = $('#table-lignes-articles tbody tr').length;
                const onglet = index >= nbArticles ? '#onglet-equipements' : '#onglet-articles';
                $(`button[data-bs-target="${onglet}"]`).tab('show');
            }
            Swal.fire({ icon: 'error', title: 'Formulaire incomplet', html: Object.values(erreurs).flat().map((m) => $('<i>').text(m).html()).join('<br>') });
            return;
        }
        Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Une erreur est survenue.' });
    };

    // ── Événements de page ────────────────────────────────────────────────
    $('#e-magasin, #e-fournisseur').select2({ theme: 'bootstrap-5', allowClear: true, placeholder: '' });

    $('#pilules-observation').on('click', '.pilule-motif', function () {
        $('.pilule-motif').removeClass('active');
        $(this).addClass('active');
        const motif = $(this).data('motif');
        $('#e-observation-type').val(motif);
        // « Autre » déplie le texte requis
        $('#e-observation').toggleClass('d-none', motif !== 'autre' && !$('#e-observation').val());
        if (motif === 'autre') $('#e-observation').removeClass('d-none').trigger('focus');
    });

    $('#btn-ajouter-article').on('click', () => rangeeArticle(lignes.articles, null));
    $('#btn-ajouter-modele').on('click', () => rangeeArticle(lignes.modeles, 'equipement'));

    const selecteur = new SelecteurUnites({
        url: route('stock.equipements.disponibles'),
        onChoisis: (unites) => unites.forEach(ajouterChipRattachement),
    });
    $('#btn-choisir-unites').on('click', () => selecteur.ouvrir({
        dejaChoisies: lignes.rattachements.map((u) => u.id),
    }));

    $form.on('submit', (e) => { e.preventDefault(); enregistrer(); });

    $('#btn-supprimer').on('click', () => {
        const nbLignes = $('#table-lignes-articles tbody tr, #table-lignes-modeles tbody tr').length + lignes.rattachements.length;
        Swal.fire({
            title: 'Supprimer ce bon ?',
            text: `Le Brouillon #${entreeId} et ses ${nbLignes} lignes seront supprimés.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Supprimer',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: route('stock.entrees.destroy', entreeId),
                method: 'DELETE',
                dataType: 'json',
                success: () => { window.location.href = route('stock.entrees.index'); },
                error: (xhr) => afficherErreurs(xhr),
            });
        });
    });

    // « Saisir les numéros de série » : enregistre PUIS passe en référencement (commit B)
    $('#btn-referencement').on('click', () => {
        enregistrer(() => {
            $.post(route('stock.entrees.referencement', entreeId))
                .done((res) => { window.location.href = res.data?.wizard_url ?? route('stock.entrees.wizard', entreeId); })
                .fail((xhr) => afficherErreurs(xhr));
        });
    });

    // « ✓ Valider » directement (sans équipements) : enregistre puis SW-VALIDER-ENT
    $('#btn-valider').on('click', () => {
        enregistrer((res) => validerEntree(entreeId ?? res.data.id));
    });

    // ── Restauration des lignes existantes (mode édition) ─────────────────
    (window.LIGNES_INITIALES ?? []).forEach((initiale) => {
        if (initiale.equipement_id) {
            ajouterChipRattachement({
                id: initiale.equipement_id,
                code_inventaire: initiale.equipement?.code_inventaire,
                numero_serie: initiale.equipement?.numero_serie,
            });
            return;
        }

        const nature = initiale.article?.nature === 'equipement' ? 'equipement' : null;
        const $tr = rangeeArticle(nature ? lignes.modeles : lignes.articles, nature);
        const ligne = $tr.data('ligne');
        ligne.article = initiale.article;
        ligne.quantite = initiale.quantite;
        ligne.cout_unitaire = initiale.cout_unitaire;

        $tr.find('.select-article')
            .append(new Option(`${initiale.article.code} — ${initiale.article.nom}`, initiale.article.id, true, true))
            .trigger('change');
        $tr.find('.input-quantite').val(initiale.quantite);
        $tr.find('.input-cout').val(initiale.cout_unitaire ?? '');
        majAlerteCout($tr.find('.input-cout'), initiale.article?.prix_indicatif ? parseFloat(initiale.article.prix_indicatif) : null);
    });

    // Popovers (en-tête + alertes de coût, délégation car lignes dynamiques)
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach((el) => new bootstrap.Popover(el));
    $(document).on('mouseenter', '.alerte-cout:not(.d-none)', function () {
        bootstrap.Popover.getOrCreateInstance(this).show();
    }).on('mouseleave', '.alerte-cout', function () {
        bootstrap.Popover.getInstance(this)?.hide();
    });

    recalculer();
});
