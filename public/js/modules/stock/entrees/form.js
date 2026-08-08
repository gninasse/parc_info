/**
 * form.js — page brouillon du bon d'entrée.
 *
 * Lignes REGROUPÉES en une seule table (dérogation UX §3.2 arbitrée) :
 * articles C/P (quantitatifs), articles E (« modèle × N » — n° de série à
 * l'étape suivante) et rattachements d'unités existantes s'y côtoient,
 * distingués par leur badge de nature. L'article se choisit dans une MODALE
 * (SelecteurArticle) au lieu d'un Select2 de ligne ; l'alerte de coût ±20 %
 * reste calculée depuis le prix indicatif renvoyé par l'API (S11).
 */
import '../shared/formatters.js';
import { SelecteurArticle } from '../shared/selecteur-article.js';
import { SelecteurUnites } from '../shared/selecteur-unites.js';
import { validerEntree } from './validation.js';
import { ErreursFormulaire, toastSucces } from '../shared/erreurs-formulaire.js';

$(function () {
    const $form = $('#entree-form');
    const seuilAlerte = parseFloat($form.data('seuil-alerte-cout')) || 0.2;
    const entreeId = $form.data('entree-id') || null;

    // La vérité du formulaire : {article, quantite, cout_unitaire} XOR {unite}
    const lignes = [];

    const echapper = (t) => $('<span>').text(t ?? '—').html();

    // ── Alerte de coût ±20 % (config stock.seuil_alerte_cout) ─────────────
    const coutSuspect = (cout, prixIndicatif) => {
        if (!prixIndicatif || cout === null || cout === '' || isNaN(cout)) return false;
        return Math.abs(cout - prixIndicatif) / prixIndicatif > seuilAlerte;
    };

    const majAlerteCout = ($tr, ligne) => {
        const prix = ligne.article?.prix_indicatif ? parseFloat(ligne.article.prix_indicatif) : null;
        const suspect = coutSuspect(ligne.cout_unitaire, prix);
        const $icone = $tr.find('.alerte-cout');
        $icone.toggleClass('d-none', !suspect);
        if (suspect) {
            $icone.attr('data-bs-content',
                `Coût saisi éloigné du prix indicatif (${Number(prix).toLocaleString('fr-FR')}) — vérifiez`);
        }
    };

    // ── Rangées de la table unique ─────────────────────────────────────────
    const ajouterLigneArticle = (article, quantite = 1, coutUnitaire = null) => {
        const ligne = {
            article,
            quantite,
            cout_unitaire: coutUnitaire ?? (article.prix_indicatif ? parseFloat(article.prix_indicatif) : null),
        };
        lignes.push(ligne);

        const estModele = article.nature === 'equipement';
        const $tr = $(`
            <tr>
                <td class="text-center">${window.natureBadgeFormatter(article.nature)}</td>
                <td>
                    <span class="font-monospace small text-muted">${echapper(article.code)}</span>
                    ${echapper(article.nom)}
                    ${estModele ? '<div class="small text-muted"><i class="bi bi-upc-scan me-1"></i>Modèle × N — n° de série à l\'étape suivante</div>' : ''}
                </td>
                <td><input type="number" class="form-control input-quantite" min="1" step="1" value="${quantite}"></td>
                <td>
                    <div class="input-group">
                        <input type="number" class="form-control input-cout" min="0" step="any" value="${ligne.cout_unitaire ?? ''}">
                        <span class="input-group-text alerte-cout d-none text-warning" data-bs-toggle="popover" data-bs-trigger="hover focus">⚠</span>
                    </div>
                </td>
                <td class="text-end sous-total">—</td>
                <td><button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-ligne"><i class="fas fa-trash"></i></button></td>
            </tr>`);
        $('#table-lignes tbody').append($tr);
        $tr.data('ligne', ligne);

        $tr.find('.input-quantite').on('input', function () {
            ligne.quantite = parseFloat(this.value) || 0;
            recalculer();
        });

        $tr.find('.input-cout').on('input', function () {
            ligne.cout_unitaire = this.value === '' ? null : parseFloat(this.value);
            majAlerteCout($tr, ligne);
            recalculer();
        });

        // ⌨ Entrée sur la dernière cellule = nouvelle ligne (la modale s'ouvre)
        $tr.find('.input-cout').on('keydown', (e) => {
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

        majAlerteCout($tr, ligne);
        recalculer();
        return $tr;
    };

    const ajouterLigneUnite = (unite) => {
        if (lignes.some((l) => l.unite?.id === unite.id)) return;
        const ligne = { unite, quantite: 1, cout_unitaire: null };
        lignes.push(ligne);

        const $tr = $(`
            <tr>
                <td class="text-center"><span class="badge bg-dark" title="Unité existante rattachée">E</span></td>
                <td>
                    <span class="font-monospace small text-muted">${echapper(unite.code_inventaire)}</span>
                    ${echapper(unite.modele)}
                    <div class="small text-muted"><i class="bi bi-link-45deg me-1"></i>Rattachement — n° de série ${echapper(unite.numero_serie)}</div>
                </td>
                <td><input type="number" class="form-control" value="1" disabled aria-label="Quantité (unité)"></td>
                <td>
                    <div class="input-group">
                        <input type="number" class="form-control input-cout" min="0" step="any" placeholder="—">
                    </div>
                </td>
                <td class="text-end sous-total">—</td>
                <td><button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-ligne"><i class="fas fa-trash"></i></button></td>
            </tr>`);
        $('#table-lignes tbody').append($tr);
        $tr.data('ligne', ligne);

        $tr.find('.input-cout').on('input', function () {
            ligne.cout_unitaire = this.value === '' ? null : parseFloat(this.value);
            recalculer();
        });

        $tr.find('.btn-supprimer-ligne').on('click', () => {
            lignes.splice(lignes.indexOf(ligne), 1);
            $tr.remove();
            recalculer();
        });

        recalculer();
    };

    // ── Récapitulatif + matrice de visibilité des boutons (UX §10) ────────
    const recalculer = () => {
        let articles = 0; let unitesArticles = 0; let unitesModeles = 0;
        let rattachements = 0; let totalFcfa = 0;

        $('#table-lignes tbody tr').each(function () {
            const ligne = $(this).data('ligne');
            if (!ligne) return;

            const sousTotal = (ligne.quantite || 0) * (ligne.cout_unitaire || 0);
            totalFcfa += sousTotal;
            $(this).find('.sous-total').text(sousTotal ? Number(sousTotal).toLocaleString('fr-FR') + ' FCFA' : '—');

            if (ligne.unite) {
                rattachements++;
            } else if (ligne.article.nature === 'equipement') {
                unitesModeles += ligne.quantite || 0;
            } else {
                articles++;
                unitesArticles += ligne.quantite || 0;
            }
        });

        $('#compteur-lignes').text(lignes.length);
        $('#recap-barre').html(
            `<strong>${articles}</strong> article(s) (${unitesArticles} u) · `
            + `<strong>${unitesModeles}</strong> équipement(s) · `
            + `<strong>${rattachements}</strong> rattachement(s) · `
            + `<strong>${Number(totalFcfa).toLocaleString('fr-FR')} FCFA</strong>`
        );

        // « Saisir les numéros de série » si ≥1 ligne modèle × N, sinon « ✓ Valider »
        const aDesModeles = unitesModeles > 0;
        $('#btn-referencement').toggleClass('d-none', !aDesModeles || !entreeId);
        $('#btn-valider').toggleClass('d-none', aDesModeles || !entreeId);
    };

    // ── Sérialisation et soumission (enregistrer au clic) ─────────────────
    const chargeUtile = () => ({
        magasin_id: $('#e-magasin').val(),
        date_document: $('#e-date').val(),
        nature: $('#e-nature').val(),
        fournisseur_id: $('#e-fournisseur').val() || null,
        reference_externe: $('#e-reference').val() || null,
        observation_type: $('#e-observation-type').val() || null,
        observation: $('#e-observation').val() || null,
        lignes: lignes.map((ligne) => (ligne.unite
            ? { equipement_id: ligne.unite.id, quantite: 1, cout_unitaire: ligne.cout_unitaire }
            : { article_id: ligne.article.id, quantite: ligne.quantite, cout_unitaire: ligne.cout_unitaire })),
    });

    // Affichage unifié des erreurs (bandeau + champs + lignes surlignées)
    const erreurs = new ErreursFormulaire('#entree-form', {
        lignes: {
            conteneur: '#table-lignes tbody',
            libelle: (index) => {
                const ligne = lignes[index];
                return ligne?.article
                    ? `Ligne ${index + 1} (${ligne.article.code})`
                    : `Ligne ${index + 1}`;
            },
        },
        champs: {
            observation: { selecteur: '#e-observation', libelle: 'Observation' },
            observation_type: { selecteur: '#pilules-observation', libelle: 'Type d\'observation' },
        },
    });

    const afficherErreurs = (xhr) => erreurs.afficher(xhr);

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
                erreurs.effacer();
                if (!entreeId) { window.location.href = route('stock.entrees.edit', res.data.id); return; }
                toastSucces('Brouillon enregistré');
            },
            error: (xhr) => afficherErreurs(xhr),
            complete: () => $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Enregistrer le brouillon'),
        });
    };

    // ── Événements de page ────────────────────────────────────────────────
    $('#e-magasin, #e-fournisseur').select2({ theme: 'bootstrap-5', allowClear: true, placeholder: '' });

    $('#pilules-observation').on('click', '.pilule-motif', function () {
        $('.pilule-motif').removeClass('active');
        $(this).addClass('active');
        const motif = $(this).data('motif');
        $('#e-observation-type').val(motif);
        $('#e-observation').toggleClass('d-none', motif !== 'autre' && !$('#e-observation').val());
        if (motif === 'autre') $('#e-observation').removeClass('d-none').trigger('focus');
    });

    // Sélecteur d'article en modale (remplace le Select2 de ligne)
    const selecteurArticle = new SelecteurArticle({
        onChoisi: (article) => ajouterLigneArticle(article),
    });
    $('#btn-ajouter-article').on('click', () => selecteurArticle.ouvrir());

    // Rattachement d'unités existantes (mêmes lignes, badge dédié)
    const selecteurUnites = new SelecteurUnites({
        url: route('stock.equipements.disponibles'),
        onChoisis: (unites) => unites.forEach(ajouterLigneUnite),
    });
    $('#btn-choisir-unites').on('click', () => selecteurUnites.ouvrir({
        dejaChoisies: lignes.filter((l) => l.unite).map((l) => l.unite.id),
        params: $('#e-nature').val() === 'retour' ? { nature: 'retour' } : {},
    }));

    $form.on('submit', (e) => { e.preventDefault(); enregistrer(); });

    $('#btn-supprimer').on('click', () => {
        Swal.fire({
            title: 'Supprimer ce bon ?',
            text: `Le Brouillon #${entreeId} et ses ${lignes.length} lignes seront supprimés.`,
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

    // « Saisir les numéros de série » : enregistre PUIS passe en référencement
    $('#btn-referencement').on('click', () => {
        enregistrer(() => {
            $.post(route('stock.entrees.referencement', entreeId))
                .done((res) => { window.location.href = res.data?.wizard_url ?? route('stock.entrees.wizard', entreeId); })
                .fail((xhr) => afficherErreurs(xhr));
        });
    });

    // « ✓ Valider » directement (sans équipements) : SW-VALIDER-ENT
    $('#btn-valider').on('click', () => {
        enregistrer((res) => validerEntree(entreeId ?? res.data.id));
    });

    // ── Restauration des lignes existantes (mode édition) ─────────────────
    (window.LIGNES_INITIALES ?? []).forEach((initiale) => {
        if (initiale.equipement_id) {
            ajouterLigneUnite({
                id: initiale.equipement_id,
                code_inventaire: initiale.equipement?.code_inventaire,
                numero_serie: initiale.equipement?.numero_serie,
                modele: initiale.equipement?.modele,
            });
            return;
        }
        ajouterLigneArticle(initiale.article, initiale.quantite, initiale.cout_unitaire);
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
