/**
 * Saisie d'un bon de commande (création et modification).
 *
 * ENF-FIA-04 : la TVA est calculée à partir du taux propre à chaque article,
 * jamais d'un taux uniforme codé en dur. Le serveur reste seul juge du montant
 * enregistré ; l'affichage n'est qu'une prévisualisation.
 */
document.addEventListener('DOMContentLoaded', function () {
    const contexte = window.achatBonCommande;
    if (!contexte) return;

    const $formulaire = $('#bc-form');
    const $lignes = $('#lignes-container');
    const modaleFournisseur = new bootstrap.Modal('#modal-fournisseur');
    const modaleArticle = new bootstrap.Modal('#modal-article');

    let compteurLignes = 0;
    let ligneCiblee = null; // ligne dont on choisit l'article

    const echapper = (texte) => $('<div>').text(texte ?? '').html();
    const formatMontant = (valeur) => new Intl.NumberFormat('fr-FR', {
        style: 'currency', currency: 'XOF', maximumFractionDigits: 0,
    }).format(valeur || 0);

    // ── Fournisseur ────────────────────────────────────────────────────────
    $('#btn-choisir-fournisseur').on('click', function () {
        $('#recherche-fournisseur').val('').trigger('input');
        modaleFournisseur.show();
    });

    $('#recherche-fournisseur').on('input', function () {
        const requete = $(this).val().toLowerCase().trim();
        $('.ligne-fournisseur').each(function () {
            $(this).toggle(($(this).data('recherche') || '').includes(requete));
        });
    });

    $(document).on('click', '.ligne-fournisseur', function () {
        $('#fournisseur_id').val($(this).data('id')).removeClass('is-invalid');
        $('#fournisseur-libelle').val($(this).data('nom')).removeClass('is-invalid');
        $('#fournisseur-code').removeClass('d-none').find('span').text($(this).data('code'));
        $('#btn-vider-fournisseur').removeClass('d-none');
        $formulaire.find('.erreur-serveur').remove();
        modaleFournisseur.hide();
    });

    $('#btn-vider-fournisseur').on('click', function () {
        $('#fournisseur_id').val('');
        $('#fournisseur-libelle').val('');
        $('#fournisseur-code').addClass('d-none');
        $(this).addClass('d-none');
    });

    // ── Sélection d'article ────────────────────────────────────────────────
    function afficherArticles() {
        const requete = $('#recherche-article').val().toLowerCase().trim();
        const type = $('#filtre-type-article').val();

        const resultats = contexte.catalogue.filter(function (article) {
            const correspondTexte = article.designation.toLowerCase().includes(requete)
                || article.code_article.toLowerCase().includes(requete);
            const correspondType = !type || article.type_article === type;
            return correspondTexte && correspondType;
        });

        const $corps = $('#table-articles tbody').empty();

        if (!resultats.length) {
            $corps.append(
                '<tr><td colspan="5" class="text-center text-muted py-3">Aucun article ne correspond.</td></tr>'
            );
            return;
        }

        resultats.forEach(function (article) {
            $corps.append(`
                <tr class="ligne-article" style="cursor:pointer" data-id="${article.id}">
                    <td><span class="badge bg-light text-dark font-monospace">${echapper(article.code_article)}</span></td>
                    <td class="fw-bold">${echapper(article.designation)}</td>
                    <td><span class="badge bg-light text-dark border">${echapper(article.type_label)}</span></td>
                    <td class="text-center text-muted">${article.taux_tva} %</td>
                    <td class="text-end fw-semibold">${formatMontant(article.prix_indicatif)}</td>
                </tr>
            `);
        });
    }

    $('#recherche-article').on('input', afficherArticles);
    $('#filtre-type-article').on('change', afficherArticles);

    $lignes.on('click', '.btn-choisir-article', function () {
        ligneCiblee = $(this).closest('tr').data('index');
        $('#recherche-article').val('');
        $('#filtre-type-article').val('');
        afficherArticles();
        modaleArticle.show();
    });

    $(document).on('click', '.ligne-article', function () {
        if (ligneCiblee === null) return;

        const article = contexte.catalogue.find((a) => a.id === $(this).data('id'));
        if (!article) return;

        const $ligne = $(`#ligne-${ligneCiblee}`);

        // Un même article ne peut figurer qu'une fois (contrainte unique_article_par_bc).
        const dejaPresent = $lignes.find('.champ-article-id').filter(function () {
            return parseInt(this.value, 10) === article.id
                && $(this).closest('tr').data('index') !== ligneCiblee;
        }).length > 0;

        if (dejaPresent) {
            modaleArticle.hide();
            Achat.attention(
                `« ${article.designation} » figure déjà dans ce bon de commande. ` +
                'Modifiez plutôt la quantité de la ligne existante.'
            );
            return;
        }

        $ligne.find('.champ-article-id').val(article.id).removeClass('is-invalid');
        $ligne.find('.champ-taux-tva').val(article.taux_tva);
        $ligne.find('.article-designation').text(article.designation);
        $ligne.find('.article-code').text(`${article.code_article} — ${article.unite_mesure}`);
        $ligne.find('.article-details').removeClass('d-none');
        $ligne.find('.btn-choisir-article').html('<i class="fas fa-redo me-1"></i>Changer');
        $ligne.find('.affichage-taux').text(`${article.taux_tva} %`);

        if (!parseFloat($ligne.find('.champ-prix').val())) {
            $ligne.find('.champ-prix').val(article.prix_indicatif);
        }

        recalculer();
        modaleArticle.hide();
        ligneCiblee = null;
    });

    // ── Lignes ─────────────────────────────────────────────────────────────
    function ajouterLigne(donnees) {
        const index = compteurLignes++;
        const article = donnees
            ? contexte.catalogue.find((a) => a.id === donnees.article_id)
            : null;

        const designation = donnees?.designation ?? article?.designation ?? '';
        const code = donnees?.code_article ?? article?.code_article ?? '';
        const taux = donnees?.taux_tva ?? article?.taux_tva ?? 0;

        $lignes.append(`
            <tr id="ligne-${index}" data-index="${index}">
                <td>
                    <input type="hidden" name="lignes[${index}][article_id]" class="champ-article-id"
                           value="${donnees?.article_id ?? ''}">
                    <input type="hidden" name="lignes[${index}][taux_tva]" class="champ-taux-tva" value="${taux}">
                    <div class="d-flex align-items-center gap-3">
                        <button type="button" class="btn btn-xs btn-outline-primary btn-choisir-article rounded-1 text-nowrap">
                            ${donnees ? '<i class="fas fa-redo me-1"></i>Changer' : '<i class="fas fa-search me-1"></i>Choisir'}
                        </button>
                        <div class="article-details ${donnees ? '' : 'd-none'}">
                            <div class="fw-semibold article-designation text-dark"></div>
                            <div class="small text-muted article-code"></div>
                        </div>
                    </div>
                </td>
                <td>
                    <input type="number" name="lignes[${index}][quantite]" class="form-control form-control-sm text-center champ-quantite"
                           min="1" step="1" value="${donnees?.quantite ?? 1}" required style="max-width:90px;margin:0 auto">
                </td>
                <td>
                    <input type="number" name="lignes[${index}][prix_unitaire]" class="form-control form-control-sm text-end champ-prix"
                           min="0" step="1" value="${donnees?.prix_unitaire ?? 0}" required style="max-width:140px;margin-left:auto">
                </td>
                <td class="text-center text-muted affichage-taux">${taux} %</td>
                <td class="text-end fw-semibold montant-ligne">0 FCFA</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-retirer-ligne rounded-1" title="Retirer">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            </tr>
        `);

        const $ligne = $(`#ligne-${index}`);
        $ligne.find('.article-designation').text(designation);
        $ligne.find('.article-code').text(code ? `${code}` : '');

        recalculer();
    }

    $('#btn-ajouter-ligne').on('click', () => ajouterLigne());

    $lignes.on('click', '.btn-retirer-ligne', function () {
        if ($lignes.find('tr').length <= 1) {
            Achat.attention('Un bon de commande doit comporter au moins une ligne.');
            return;
        }
        $(this).closest('tr').remove();
        recalculer();
    });

    $lignes.on('input change', '.champ-quantite, .champ-prix', recalculer);

    // ── Totaux ─────────────────────────────────────────────────────────────
    function recalculer() {
        let totalHt = 0;
        let totalTva = 0;

        $lignes.find('tr').each(function () {
            const $ligne = $(this);
            const quantite = parseInt($ligne.find('.champ-quantite').val(), 10) || 0;
            const prix = parseFloat($ligne.find('.champ-prix').val()) || 0;
            const taux = parseFloat($ligne.find('.champ-taux-tva').val()) || 0;

            const montantHt = quantite * prix;
            totalHt += montantHt;
            totalTva += montantHt * (taux / 100);

            $ligne.find('.montant-ligne').text(formatMontant(montantHt));
        });

        $('#total-ht').text(formatMontant(totalHt));
        $('#total-tva').text(formatMontant(totalTva));
        $('#total-ttc').text(formatMontant(totalHt + totalTva));
    }

    // ── Initialisation ─────────────────────────────────────────────────────
    if (contexte.lignes && contexte.lignes.length) {
        contexte.lignes.forEach(ajouterLigne);
    } else {
        ajouterLigne();
    }

    // ── Enregistrement ─────────────────────────────────────────────────────
    $formulaire.on('submit', function (evenement) {
        evenement.preventDefault();
        Achat.effacerErreurs($formulaire);

        // Contrôles préalables, doublés côté serveur.
        if (!$('#fournisseur_id').val()) {
            $('#fournisseur-libelle').addClass('is-invalid');
            Achat.attention('Veuillez sélectionner un fournisseur.');
            return;
        }

        const lignesIncompletes = $lignes.find('.champ-article-id').filter(function () {
            return !this.value;
        });

        if (lignesIncompletes.length) {
            lignesIncompletes.closest('tr').find('.btn-choisir-article').addClass('is-invalid');
            Achat.attention('Chaque ligne doit référencer un article.');
            return;
        }

        const restaurer = Achat.chargement($('#btn-save'), 'Enregistrement…');
        const donnees = $formulaire.serialize()
            + (contexte.mode === 'modification' ? '&_method=PUT' : '');

        $.ajax({
            url: contexte.urlEnregistrement,
            method: 'POST',
            data: donnees,
        }).done(function (reponse) {
            if (!reponse.success) return;
            Achat.succes(reponse.message);
            setTimeout(() => { window.location.href = reponse.redirect; }, 600);
        }).fail(function (xhr) {
            restaurer();

            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                Achat.afficherErreurs($formulaire, xhr.responseJSON.errors);
                Achat.erreur('Certaines informations sont incorrectes. Les champs concernés sont signalés.');
            } else {
                Achat.erreur(Achat.messageErreur(xhr));
            }
        });
    });
});
