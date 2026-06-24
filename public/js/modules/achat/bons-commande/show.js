/**
 * Visualisation et édition in-place d'un Bon de Commande - Module Achat
 * Pattern: AJAX in-place edit
 */

document.addEventListener('DOMContentLoaded', function() {
    const $container = $('#lines-container');
    const $btnAdd = $('#btn-add-line');
    const $form = $('#form-edit-bc');
    const $totalGeneral = $('#total-general');
    const $btnToggleEdit = $('#btn-toggle-edit');
    const $btnSaveEdit = $('#btn-save-edit');
    const $btnCancelEdit = $('#btn-cancel-edit');
    const $btnValider = $('#btn-valider-bc');
    const $btnAnnuler = $('#btn-annuler-bc');

    let lineIndex = 0;
    let isEditing = false;

    // Formatter de prix local
    function formatXOF(value) {
        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(value);
    }

    // ── GESTION DE L'AFFICHAGE DU DÉTAIL DES LIGNES ──
    function renderLines() {
        $container.empty();
        lineIndex = 0;

        if (window.existingLines && window.existingLines.length > 0) {
            window.existingLines.forEach(line => addLineRow(line));
        } else {
            if (isEditing) addLineRow();
        }
        
        recalculerGrandTotal();
    }

    function addLineRow(data = null) {
        const index = lineIndex++;
        const qty = data ? data.quantite : 1;
        const qtyDelivered = data ? data.quantite_livree : 0;
        const price = data ? data.prix_unitaire : 0;
        const subtotal = qty * price;

        let rowHtml = '';

        if (isEditing) {
            // Mode édition : Selects et Inputs actifs
            let optionsHtml = '<option value="">Choisir un article...</option>';
            window.articlesCatalogue.forEach(a => {
                const selected = data && data.article_id == a.id ? 'selected' : '';
                optionsHtml += `<option value="${a.id}" data-price="${a.prix_indicatif}" ${selected}>${a.code_article} - ${a.designation}</option>`;
            });

            rowHtml = `
                <tr class="bc-line-row" id="bc-row-${index}">
                    <td>
                        <select name="lignes[${index}][article_id]" class="form-select form-select-sm select-article" required>
                            ${optionsHtml}
                        </select>
                    </td>
                    <td>
                        <input type="number" name="lignes[${index}][quantite]" class="form-control form-control-sm text-center input-qty" min="1" value="${qty}" required>
                    </td>
                    <td class="text-center fw-semibold text-muted">
                        ${qtyDelivered}
                    </td>
                    <td>
                        <input type="number" name="lignes[${index}][prix_unitaire]" class="form-control form-control-sm text-end input-price" min="0" value="${price}" required>
                    </td>
                    <td class="text-end fw-semibold text-muted line-subtotal">
                        ${formatXOF(subtotal)}
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-line rounded-1">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            `;
        } else {
            // Mode lecture seule
            const article = window.articlesCatalogue.find(a => data && a.id == data.article_id);
            const designation = article ? `${article.code_article} - ${article.designation}` : 'Article inconnu';
            
            rowHtml = `
                <tr class="bc-line-row">
                    <td>${designation}</td>
                    <td class="text-center fw-semibold">${qty}</td>
                    <td class="text-center">${qtyDelivered}</td>
                    <td class="text-end">${formatXOF(price)}</td>
                    <td class="text-end fw-semibold text-dark">${formatXOF(subtotal)}</td>
                </tr>
            `;
        }

        $container.append(rowHtml);
    }

    // ── CALCULATION DE TOTALS ──
    function recalculerGrandTotal() {
        let total = 0;
        $container.find('.bc-line-row').each(function() {
            let qty, price;
            if (isEditing) {
                qty = parseInt($(this).find('.input-qty').val()) || 0;
                price = parseFloat($(this).find('.input-price').val()) || 0;
            } else {
                // En mode lecture, on calcule par rapport aux données injectées
                const index = $(this).index();
                const data = window.existingLines[index];
                qty = data ? data.quantite : 0;
                price = data ? data.prix_unitaire : 0;
            }
            total += (qty * price);
        });

        $totalGeneral.text(formatXOF(total));
    }

    // ── GESTION DES BOUTONS DE LIGNES ──
    $container.on('click', '.btn-remove-line', function() {
        const rowCount = $container.find('.bc-line-row').length;
        if (rowCount <= 1) {
            Swal.fire('Attention', 'Un bon de commande doit contenir au moins une ligne.', 'warning');
            return;
        }
        $(this).closest('.bc-line-row').remove();
        recalculerGrandTotal();
    });

    $container.on('change', '.select-article', function() {
        const $option = $(this).find('option:selected');
        const price = parseFloat($option.data('price')) || 0;
        const $row = $(this).closest('.bc-line-row');
        
        $row.find('.input-price').val(price);
        recalculerLigne($row);
    });

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

    $btnAdd.on('click', function() {
        addLineRow();
    });

    // ── BASCULE EN MODE EDITION (IN-PLACE EDIT) ──
    if ($btnToggleEdit.length) {
        $btnToggleEdit.on('click', function() {
            isEditing = true;
            
            // Activer les champs de l'entête
            $form.find('.field-input').prop('disabled', false);

            // Gérer la visibilité des boutons de l'entête
            $btnToggleEdit.addClass('d-none');
            if ($btnValider.length) $btnValider.addClass('d-none');
            if ($btnAnnuler.length) $btnAnnuler.addClass('d-none');
            $btnSaveEdit.removeClass('d-none');
            $btnCancelEdit.removeClass('d-none');

            // Afficher le bouton ajouter de ligne et colonnes d'action
            $btnAdd.removeClass('d-none');
            $('.action-col').removeClass('d-none');

            // Re-render
            renderLines();
        });
    }

    // Annuler l'édition
    $btnCancelEdit.on('click', function() {
        isEditing = false;

        $form.find('.field-input').prop('disabled', true);
        $btnToggleEdit.removeClass('d-none');
        if ($btnValider.length) $btnValider.removeClass('d-none');
        if ($btnAnnuler.length) $btnAnnuler.removeClass('d-none');
        $btnSaveEdit.addClass('d-none');
        $btnCancelEdit.addClass('d-none');

        $btnAdd.addClass('d-none');
        $('.action-col').addClass('d-none');

        renderLines();
    });

    // Enregistrer les modifications
    $form.on('submit', function(e) {
        e.preventDefault();

        // Validation
        let valid = true;
        $container.find('.select-article').each(function() {
            if (!$(this).val()) {
                valid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        if (!valid) {
            Swal.fire('Erreur', 'Veuillez sélectionner un article pour chaque ligne de commande.', 'warning');
            return;
        }

        $btnSaveEdit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');

        const id = $('#bc-id').val();

        $.ajax({
            url: route('achat.bons-commande.update', id),
            method: 'POST',
            data: $form.serialize() + '&_method=PUT',
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Enregistré !',
                        text: res.message,
                        timer: 1500
                    }).then(() => {
                        window.location.reload();
                    });
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let msg = '';
                Object.values(errors).forEach(e => msg += e[0] + '<br>');
                Swal.fire('Erreur', msg || xhr.responseJSON?.message || 'Une erreur est survenue', 'error');
                $btnSaveEdit.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Enregistrer');
            }
        });
    });

    // ── VALIDER LA COMMANDE ──
    if ($btnValider.length) {
        $btnValider.on('click', function() {
            const id = $('#bc-id').val();

            Swal.fire({
                title: 'Valider ce bon de commande ?',
                text: "Une fois validé, il ne pourra plus être modifié, et vous pourrez y lier des bordereaux de livraison.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                confirmButtonText: 'Oui, valider'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: route('achat.bons-commande.valider', id),
                        method: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(res) {
                            if (res.success) {
                                Swal.fire('Validé !', res.message, 'success').then(() => {
                                    window.location.reload();
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur lors de la validation', 'error');
                        }
                    });
                }
            });
        });
    }

    // ── ANNULER LA COMMANDE ──
    if ($btnAnnuler.length) {
        $btnAnnuler.on('click', function() {
            const id = $('#bc-id').val();

            Swal.fire({
                title: 'Annuler ce bon de commande ?',
                text: "Cette action est irréversible !",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Oui, annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: route('achat.bons-commande.annuler', id),
                        method: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(res) {
                            if (res.success) {
                                Swal.fire('Annulé !', res.message, 'success').then(() => {
                                    window.location.reload();
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur lors de l\'annulation', 'error');
                        }
                    });
                }
            });
        });
    }

    // Initialisation
    renderLines();
});
