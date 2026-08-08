/**
 * form.js — brouillon de sortie (UX §4.2).
 *
 * Lignes REGROUPÉES en une seule table (même parti pris que les entrées) :
 * articles quantitatifs et lignes « modèle × N » s'y côtoient, distingués
 * par leur badge de nature. L'article se choisit dans une MODALE
 * (SelecteurArticle) au lieu d'un Select2 de ligne. Le reste est inchangé :
 * motif à pilules, bénéficiaire 6 cartes, disponible temps réel avec
 * réservations, scan express pré-pointant l'unité.
 */
import '../shared/formatters.js';
import { SelecteurArticle } from '../shared/selecteur-article.js';
import { SelecteurBeneficiaire } from '../shared/beneficiaire.js';
import { validerDocument } from '../shared/valider-document.js';
import { ErreursFormulaire, toastSucces } from '../shared/erreurs-formulaire.js';

$(function () {
    const sortieId = $('#sortie-form').data('sortie-id') || null;

    // La vérité du formulaire : {article, quantite, pre_pointes}
    const lignes = [];

    const echapper = (t) => $('<span>').text(t ?? '—').html();

    // ── Disponible + réservations (information non bloquante) ─────────────
    const majDisponible = ($tr, ligne) => {
        const magasinId = $('#s-magasin').val();
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
                    ${estModele ? '<div class="small text-muted"><i class="bi bi-upc-scan me-1"></i>Modèle × N — unités pointées à l\'étape suivante</div>' : ''}
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

    // ── Récapitulatif + matrice de visibilité des boutons (UX §10) ────────
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

        // « Pointer » s'il reste des unités à pointer ; « Valider » sinon
        const resteAPointer = unitesModeles > prePointees;
        $('#btn-pointage').toggleClass('d-none', !resteAPointer || !sortieId);
        $('#btn-valider').toggleClass('d-none', resteAPointer || !sortieId);
    };

    // ── Sérialisation / soumission ────────────────────────────────────────
    const chargeUtile = () => ({
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
        lignes: lignes.map((ligne) => ({ article_id: ligne.article.id, quantite: ligne.quantite })),
    });

    const erreurs = new ErreursFormulaire('#sortie-form', {
        lignes: {
            conteneur: '#table-lignes tbody',
            libelle: (index) => {
                const ligne = lignes[index];
                return ligne?.article ? `Ligne ${index + 1} (${ligne.article.code})` : `Ligne ${index + 1}`;
            },
        },
        champs: {
            beneficiaire_type: { selecteur: '#cartes-beneficiaire', libelle: 'Bénéficiaire' },
            beneficiaire_direction_id: { selecteur: '#cartes-beneficiaire', libelle: 'Bénéficiaire' },
            beneficiaire_service_id: { selecteur: '#cartes-beneficiaire', libelle: 'Bénéficiaire' },
            beneficiaire_unite_id: { selecteur: '#cartes-beneficiaire', libelle: 'Bénéficiaire' },
            beneficiaire_poste_id: { selecteur: '#cartes-beneficiaire', libelle: 'Bénéficiaire' },
            beneficiaire_local_id: { selecteur: '#cartes-beneficiaire', libelle: 'Bénéficiaire' },
            beneficiaire_employe_id: { selecteur: '#cartes-beneficiaire', libelle: 'Bénéficiaire' },
            motif_type: { selecteur: '#pilules-motif', libelle: 'Motif' },
            motif_texte: { selecteur: '#s-motif-texte', libelle: 'Texte du motif' },
            remise_reelle_le: { selecteur: '#s-remise-reelle', libelle: 'Date et heure réelles de remise' },
        },
    });

    const afficherErreurs = (xhr) => erreurs.afficher(xhr);

    const enregistrer = (surSucces) => {
        const $btn = $('#btn-enregistrer');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Enregistrement…');

        $.ajax({
            url: sortieId ? route('stock.sorties.update', sortieId) : route('stock.sorties.store'),
            method: sortieId ? 'PUT' : 'POST',
            data: JSON.stringify(chargeUtile()),
            contentType: 'application/json',
            dataType: 'json',
            success: (res) => {
                if (!res.success) return;
                if (surSucces) { surSucces(res); return; }
                erreurs.effacer();
                if (!sortieId) { window.location.href = route('stock.sorties.edit', res.data.id); return; }
                toastSucces('Brouillon enregistré');
            },
            error: afficherErreurs,
            complete: () => $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Enregistrer le brouillon'),
        });
    };

    // ── Événements ────────────────────────────────────────────────────────
    $('#s-magasin').select2({ theme: 'bootstrap-5', allowClear: true, placeholder: '' });

    // Le disponible dépend du magasin
    $('#s-magasin').on('change', () => {
        $('#table-lignes tbody tr').each(function () {
            const ligne = $(this).data('ligne');
            if (ligne) majDisponible($(this), ligne);
        });
    });

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
            $('#beneficiaire-choisi').html(`<span class="badge bg-primary-subtle text-primary-emphasis">${echapper(libelle)}</span>`);
            $('.carte-beneficiaire').removeClass('selectionnee');
            $(`.carte-beneficiaire[data-type="${type}"]`).addClass('selectionnee');
        },
    });
    $('.carte-beneficiaire').on('click', function () {
        selecteurBeneficiaire.ouvrir($(this).data('type'));
    });

    // Sélecteur d'article en modale (remplace le Select2 de ligne)
    const selecteurArticle = new SelecteurArticle({
        onChoisi: (article) => ajouterLigne(article),
    });
    $('#btn-ajouter-article').on('click', () => selecteurArticle.ouvrir());

    $('#sortie-form').on('submit', (e) => { e.preventDefault(); enregistrer(); });

    $('#btn-supprimer').on('click', () => {
        Swal.fire({
            title: 'Supprimer ce bon ?',
            text: `Le Brouillon #${sortieId} et ses ${lignes.length} lignes seront supprimés.`,
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

    // ── Scan express : un scan = ligne « modèle × 1 » pré-pointée ─────────
    if (sortieId && document.getElementById('scan-express')) {
        new StockScanField('#scan-express', {
            onScan: async (code) => {
                try {
                    const res = await $.post(route('stock.sorties.scan-express', sortieId), { numero_serie: code });
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
