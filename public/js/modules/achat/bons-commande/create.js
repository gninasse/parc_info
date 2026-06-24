/**
 * Création / Modification d'un Bon de Commande - Module Achat
 * Pattern: Formulaire Dynamique AJAX + Modales de Sélection
 */

document.addEventListener('DOMContentLoaded', function() {
    const $container = $('#lines-container');
    const $btnAdd = $('#btn-add-line');
    const $form = $('#form-create-bc');
    const $btnSave = $('#btn-save');
    
    // Totaux Elements
    const $totalHt = $('#total-ht');
    const $totalTva = $('#total-tva');
    const $totalGeneral = $('#total-general');

    // Supplier Select Elements
    const $modalFournisseur = $('#modal-select-fournisseur');
    const $inputFournisseurId = $('#fournisseur_id');
    const $displayFournisseurName = $('#fournisseur_name_display');
    const $btnChooseFournisseur = $('#btn-choose-fournisseur');
    const $btnClearFournisseur = $('#btn-clear-fournisseur');
    const $displayFournisseurCode = $('#fournisseur_code_display');
    const $textFournisseurCode = $('#fournisseur_code_text');
    const $searchFournisseur = $('#search-fournisseur');

    // Article Select Elements
    const $modalArticle = $('#modal-select-article');
    const $searchArticle = $('#search-article');
    const $filterCategory = $('#filter-article-category');
    const $tableModalArticles = $('#table-modal-articles tbody');

    let lineIndex = 0;
    let activeRowIndex = null; // Stocke la ligne active lors de la sélection d'un article

    // Formatter de prix local
    function formatXOF(value) {
        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(value);
    }

    // ── GESTION DU FOURNISSEUR ──

    // Ouvrir modale fournisseur
    $btnChooseFournisseur.on('click', function() {
        $searchFournisseur.val('');
        filterFournisseurs();
        $modalFournisseur.modal('show');
    });

    // Recherche en temps réel dans la modale fournisseur
    $searchFournisseur.on('input', function() {
        filterFournisseurs();
    });

    function filterFournisseurs() {
        const query = $searchFournisseur.val().toLowerCase().trim();
        $modalFournisseur.find('.supplier-row').each(function() {
            const searchText = $(this).data('search') || '';
            if (searchText.includes(query)) {
                $(this).removeClass('d-none');
            } else {
                $(this).addClass('d-none');
            }
        });
    }

    // Sélection d'un fournisseur
    $modalFournisseur.on('click', '.supplier-row', function() {
        const id = $(this).data('id');
        const nom = $(this).data('nom');
        const code = $(this).data('code');

        $inputFournisseurId.val(id);
        $displayFournisseurName.val(nom).removeClass('is-invalid');
        $('#err-fournisseur').text('');
        $textFournisseurCode.text(code);
        $displayFournisseurCode.removeClass('d-none');
        $btnClearFournisseur.removeClass('d-none');

        $modalFournisseur.modal('hide');
    });

    // Vider la sélection fournisseur
    $btnClearFournisseur.on('click', function() {
        $inputFournisseurId.val('');
        $displayFournisseurName.val('');
        $textFournisseurCode.text('');
        $displayFournisseurCode.addClass('d-none');
        $btnClearFournisseur.addClass('d-none');
    });


    // ── GESTION DES ARTICLES (MODALE DE SÉLECTION DÉDIÉE) ──

    // Ouvrir modale article pour une ligne spécifique
    $container.on('click', '.btn-choose-article-row', function() {
        const $row = $(this).closest('.bc-line-row');
        activeRowIndex = $row.data('index');
        
        $searchArticle.val('');
        $filterCategory.val('');
        renderModalArticles();
        $modalArticle.modal('show');
    });

    // Filtres articles dans la modale
    $searchArticle.on('input', function() {
        renderModalArticles();
    });
    $filterCategory.on('change', function() {
        renderModalArticles();
    });

    // Rendu local de la liste des articles dans la modale
    function renderModalArticles() {
        const query = $searchArticle.val().toLowerCase().trim();
        const category = $filterCategory.val();

        let html = '';
        const filtered = window.articlesCatalogue.filter(a => {
            const matchesQuery = a.designation.toLowerCase().includes(query) || a.code_article.toLowerCase().includes(query);
            const matchesCategory = !category || a.type_label.toLowerCase().includes(category) || a.id === parseInt(category); // fallback simple
            // Pour de la robustesse, filtrer par type_article si disponible
            return matchesQuery && (category === '' || a.type_label.toLowerCase().includes(category.toLowerCase()));
        });

        if (filtered.length === 0) {
            html = `<tr><td colspan="4" class="text-center py-3 text-muted">Aucun article trouvé.</td></tr>`;
        } else {
            filtered.forEach(a => {
                html += `
                    <tr class="article-row-modal cursor-pointer" data-id="${a.id}" data-designation="${a.designation}" data-code="${a.code_article}" data-price="${a.prix_indicatif}">
                        <td><span class="badge bg-light text-dark font-monospace">${a.code_article}</span></td>
                        <td class="fw-bold">${a.designation}</td>
                        <td><span class="badge bg-secondary">${a.type_label}</span></td>
                        <td class="text-end fw-semibold">${formatXOF(a.prix_indicatif)}</td>
                    </tr>
                `;
            });
        }

        $tableModalArticles.html(html);
    }

    // Sélection d'un article depuis la modale
    $modalArticle.on('click', '.article-row-modal', function() {
        if (activeRowIndex === null) return;

        const id = $(this).data('id');
        const designation = $(this).data('designation');
        const code = $(this).data('code');
        const price = parseFloat($(this).data('price')) || 0;

        const $row = $(`#bc-row-${activeRowIndex}`);
        $row.find('.line-article-id').val(id).removeClass('is-invalid');
        $row.find('.article-designation-text').text(designation);
        $row.find('.article-code-text').text('Code : ' + code);
        $row.find('.article-details-container').removeClass('d-none');
        $row.find('.btn-choose-article-row').html('<i class="fas fa-redo me-1"></i>Changer');
        
        // Pré-remplir le prix unitaire
        $row.find('.input-price').val(price);

        recalculerLigne($row);
        $modalArticle.modal('hide');
        activeRowIndex = null;
    });


    // ── FONCTIONS CONCERNANT LES LIGNES DU FORMULAIRE ──

    function addLine(data = null) {
        const index = lineIndex++;
        
        const articleId = data ? data.article_id : '';
        const qty = data ? data.quantite : 1;
        const price = data ? data.prix_unitaire : 0;
        const subtotal = qty * price;

        // Si des données initiales existent, on récupère le nom/code de l'article pré-rempli
        let articleDesignation = '';
        let articleCode = '';
        if (data && data.article_id) {
            const matched = window.articlesCatalogue.find(a => a.id == data.article_id);
            if (matched) {
                articleDesignation = matched.designation;
                articleCode = matched.code_article;
            } else if (data.designation) {
                articleDesignation = data.designation;
                articleCode = data.code_article;
            }
        }

        const rowHtml = `
            <tr class="bc-line-row" id="bc-row-${index}" data-index="${index}">
                <td>
                    <input type="hidden" name="lignes[${index}][article_id]" class="line-article-id" value="${articleId}" required>
                    <div class="d-flex align-items-center gap-3">
                        <button type="button" class="btn btn-xs btn-outline-primary btn-choose-article-row rounded-1 text-nowrap">
                            ${articleId ? '<i class="fas fa-redo me-1"></i>Changer' : '<i class="fas fa-search me-1"></i>Choisir un article'}
                        </button>
                        <div class="article-details-container ${articleId ? '' : 'd-none'}">
                            <div class="fw-semibold article-designation-text text-dark" style="font-size: 0.95rem;">${articleDesignation}</div>
                            <div class="small text-muted article-code-text">Code : ${articleCode}</div>
                        </div>
                    </div>
                    <div class="invalid-feedback d-block mt-1 small line-article-error"></div>
                </td>
                <td>
                    <input type="number" name="lignes[${index}][quantite]" class="form-control form-control-sm text-center input-qty" min="1" value="${qty}" required style="max-width: 100px; margin: 0 auto;">
                    <div class="invalid-feedback d-block mt-1 small line-qty-error"></div>
                </td>
                <td>
                    <input type="number" name="lignes[${index}][prix_unitaire]" class="form-control form-control-sm text-end input-price" min="0" value="${price}" required style="max-width: 150px; margin-left: auto;">
                    <div class="invalid-feedback d-block mt-1 small line-price-error"></div>
                </td>
                <td class="text-end fw-semibold text-dark line-subtotal" id="subtotal-${index}" style="font-size: 0.95rem;">
                    ${formatXOF(subtotal)}
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-line rounded-1">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            </tr>
        `;

        $container.append(rowHtml);
        
        if (data) {
            const $row = $(`#bc-row-${index}`);
            recalculerLigne($row);
        } else {
            recalculerGrandTotal();
        }
    }

    // Bouton de suppression de ligne
    $container.on('click', '.btn-remove-line', function() {
        const rowCount = $container.find('.bc-line-row').length;
        if (rowCount <= 1) {
            Swal.fire('Attention', 'Un bon de commande doit contenir au moins une ligne.', 'warning');
            return;
        }
        $(this).closest('.bc-line-row').remove();
        recalculerGrandTotal();
    });

    // Recalculer sur saisie quantité ou prix
    $container.on('input change', '.input-qty, .input-price', function() {
        const $row = $(this).closest('.bc-line-row');
        recalculerLigne($row);
    });

    function recalculerLigne($row) {
        const qty = parseInt($row.find('.input-qty').val()) || 0;
        const price = parseFloat($row.find('.input-price').val()) || 0;
        const subtotal = qty * price;
        
        $row.find('.line-subtotal').text(formatXOF(subtotal));
        recalculerGrandTotal();
    }

    function recalculerGrandTotal() {
        let totalHt = 0;
        $container.find('.bc-line-row').each(function() {
            const qty = parseInt($(this).find('.input-qty').val()) || 0;
            const price = parseFloat($(this).find('.input-price').val()) || 0;
            totalHt += (qty * price);
        });

        const tva = totalHt * 0.18;
        const totalTtc = totalHt + tva;

        $totalHt.text(formatXOF(totalHt));
        $totalTva.text(formatXOF(tva));
        $totalGeneral.text(formatXOF(totalTtc));
    }

    $btnAdd.on('click', function() {
        addLine();
    });

    // Initialisation
    if (window.existingLines && window.existingLines.length > 0) {
        window.existingLines.forEach(line => addLine(line));
    } else {
        addLine();
    }


    // ── SUBMISSION AVEC VALIDATIONS VISUELLES AVANCÉES ──

    $form.on('submit', function(e) {
        e.preventDefault();
        
        // Clean previous error outputs
        $('.invalid-feedback').text('');
        $('.is-invalid').removeClass('is-invalid');

        let isValid = true;

        // 1. Validation du fournisseur
        if (!$inputFournisseurId.val()) {
            isValid = false;
            $displayFournisseurName.addClass('is-invalid');
            $('#err-fournisseur').text('Veuillez sélectionner un fournisseur.');
        }

        // 2. Validation de la date
        const $dateInput = $('input[name="date_commande"]');
        if (!$dateInput.val()) {
            isValid = false;
            $dateInput.addClass('is-invalid');
            $('#err-date_commande').text('La date de commande est requise.');
        }

        // 3. Validation de chaque ligne
        const lines = $container.find('.bc-line-row');
        if (lines.length === 0) {
            isValid = false;
            Swal.fire('Erreur', 'Veuillez ajouter au moins une ligne de commande.', 'warning');
            return;
        }

        lines.each(function() {
            const $row = $(this);
            const artId = $row.find('.line-article-id').val();
            const qty = parseInt($row.find('.input-qty').val());
            const price = parseFloat($row.find('.input-price').val());

            if (!artId) {
                isValid = false;
                $row.find('.btn-choose-article-row').addClass('is-invalid');
                $row.find('.line-article-error').text('Veuillez choisir un article.');
            }

            if (isNaN(qty) || qty < 1) {
                isValid = false;
                $row.find('.input-qty').addClass('is-invalid');
                $row.find('.line-qty-error').text('Qté >= 1.');
            }

            if (isNaN(price) || price < 0) {
                isValid = false;
                $row.find('.input-price').addClass('is-invalid');
                $row.find('.line-price-error').text('Prix >= 0.');
            }
        });

        if (!isValid) {
            Swal.fire('Formulaire incomplet', 'Veuillez corriger les erreurs indiquées en rouge.', 'warning');
            return;
        }

        // Loader de chargement
        $btnSave.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');

        const bcId = $('#bc-id').val();
        const url = bcId 
            ? route('achat.bons-commande.update', bcId) 
            : route('achat.bons-commande.store');
        
        const method = bcId ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            method: 'POST',
            data: $form.serialize() + (bcId ? '&_method=PUT' : ''),
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Enregistré avec succès !',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = res.redirect;
                    });
                }
            },
            error: function(xhr) {
                $btnSave.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer le Bon de Commande');
                
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};
                    
                    // Traitement des erreurs globales
                    if (errors.fournisseur_id) {
                        $displayFournisseurName.addClass('is-invalid');
                        $('#err-fournisseur').text(errors.fournisseur_id[0]);
                    }
                    if (errors.date_commande) {
                        $dateInput.addClass('is-invalid');
                        $('#err-date_commande').text(errors.date_commande[0]);
                    }

                    // Traitement des erreurs de lignes indexées
                    Object.keys(errors).forEach(key => {
                        if (key.startsWith('lignes.')) {
                            const parts = key.split('.');
                            const index = parts[1];
                            const field = parts[2];
                            const $row = $(`#bc-row-${index}`);

                            if (field === 'article_id') {
                                $row.find('.btn-choose-article-row').addClass('is-invalid');
                                $row.find('.line-article-error').text(errors[key][0]);
                            }
                            if (field === 'quantite') {
                                $row.find('.input-qty').addClass('is-invalid');
                                $row.find('.line-qty-error').text(errors[key][0]);
                            }
                            if (field === 'prix_unitaire') {
                                $row.find('.input-price').addClass('is-invalid');
                                $row.find('.line-price-error').text(errors[key][0]);
                            }
                        }
                    });

                    Swal.fire('Erreur de validation', 'Certains champs comportent des erreurs de saisie.', 'error');
                } else {
                    Swal.fire('Erreur', xhr.responseJSON?.message || 'Une erreur système s\'est produite.', 'error');
                }
            }
        });
    });
});
