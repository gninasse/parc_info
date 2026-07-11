/**
 * Visualisation et édition in-place d'un Bordereau de Livraison - Module Achat
 * Pattern: AJAX in-place edit + Sélection par Modale & Suppression de lignes
 */

document.addEventListener('DOMContentLoaded', function() {
    const $container = $('#lines-container');
    const $form = $('#form-edit-bl');
    const $btnToggleEdit = $('#btn-toggle-edit');
    const $btnSaveEdit = $('#btn-save-edit');
    const $btnCancelEdit = $('#btn-cancel-edit');
    const $btnWizard = $('.btn-success, .btn-warning'); // Bouton lancer/continuer wizard
    const $btnPrint = $('#btn-print-bl');

    let isEditing = false;
    let originalBcId = '';
    let originalBcDisplay = '';
    let originalLines = [];

    // ── AFFICHAGE DES LIGNES ──
    function renderLines() {
        $container.empty();

        if (isEditing) {
            $('.th-action').removeClass('d-none');
        } else {
            $('.th-action').addClass('d-none');
        }

        if (window.existingLines && window.existingLines.length > 0) {
            window.existingLines.forEach((l, index) => {
                let qtyCell = '';
                let actionCell = '';
                if (isEditing) {
                    qtyCell = `
                        <input type="number" 
                               name="lignes[${index}][quantite_livree]" 
                               class="form-control form-control-sm text-center mx-auto input-qty-received" 
                               style="width: 80px;" 
                               min="1" 
                               max="${l.max_qty}" 
                               value="${l.quantite_livree}" 
                               required>
                        <input type="hidden" name="lignes[${index}][article_id]" value="${l.article_id}">
                    `;
                    actionCell = `
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-link text-danger btn-delete-bl-line p-0" title="Supprimer cette ligne">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    `;
                } else {
                    qtyCell = `<span class="fw-semibold text-dark">${l.quantite_livree}</span>`;
                    actionCell = '';
                }

                const rowHtml = `
                    <tr class="bl-line-row">
                        <td>
                            <strong>${l.code_article}</strong> - ${l.designation}
                            <span class="badge bg-secondary ms-1 small">${l.type_label}</span>
                        </td>
                        <td class="text-center">${l.quantite_commandee}</td>
                        <td class="text-center">
                            ${qtyCell}
                        </td>
                        ${actionCell}
                    </tr>
                `;
                $container.append(rowHtml);
            });
        } else {
            const colspan = isEditing ? 4 : 3;
            $container.html(`
                <tr>
                    <td colspan="${colspan}" class="text-center text-muted py-4">
                        <i class="fas fa-info-circle me-1"></i> Aucune ligne de livraison.
                    </td>
                </tr>
            `);
        }
    }

    // ── FILTRAGE EN TEMPS RÉEL DES BC DANS LA MODALE ──
    $('#search-bc').on('input', function() {
        const q = $(this).val().toLowerCase();
        $('#table-modal-bc tbody tr').each(function() {
            const text = $(this).data('search') || '';
            if (text.includes(q)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // ── SÉLECTION D'UN BC DEPUIS LA MODALE ──
    $(document).on('click', '.bc-row', function() {
        if (!isEditing) return;
        
        const bcId = $(this).data('id');
        const numero = $(this).data('numero');
        const fournisseur = $(this).data('fournisseur');

        $('#input-bc-id').val(bcId);
        $('#input-bc-display').val(`${numero} - ${fournisseur}`);
        
        $('#modal-select-bc').modal('hide');
        
        chargerLignesBC(bcId);
    });

    // ── VIDER LA SÉLECTION DU BC ──
    $('#btn-clear-bc').on('click', function() {
        if (!isEditing) return;
        $('#input-bc-id').val('');
        $('#input-bc-display').val('');
        window.existingLines = [];
        renderLines();
    });

    // ── CHARGER LES LIGNES DU BC SÉLECTIONNÉ ──
    function chargerLignesBC(bcId) {
        if (!bcId) {
            window.existingLines = [];
            renderLines();
            return;
        }

        $container.html(`
            <tr>
                <td colspan="4" class="text-center py-4">
                    <span class="spinner-border spinner-border-sm me-2 text-primary"></span>Chargement des nouvelles lignes...
                </td>
            </tr>
        `);

        $.ajax({
            url: route('achat.bons-commande.lignes-a-livrer', bcId),
            method: 'GET',
            success: function(res) {
                if (res.success && res.lignes.length > 0) {
                    window.existingLines = res.lignes.map(l => {
                        const isFullyDelivered = l.reste_a_livrer <= 0;
                        return {
                            id: null,
                            article_id: l.article_id,
                            code_article: l.code_article,
                            designation: l.designation,
                            type_label: l.type_label,
                            quantite_commandee: l.quantite_commandee,
                            quantite_livree: isFullyDelivered ? 0 : l.reste_a_livrer,
                            max_qty: l.reste_a_livrer
                        };
                    });
                    
                    renderLines();
                } else {
                    window.existingLines = [];
                    renderLines();
                    Swal.fire('Attention', 'Aucune ligne livrable trouvée pour ce bon de commande.', 'warning');
                }
            },
            error: function() {
                window.existingLines = [];
                renderLines();
                Swal.fire('Erreur', 'Impossible de charger les lignes de ce bon de commande.', 'error');
            }
        });
    }

    // ── SUPPRIMER UNE LIGNE DU BL EN MODE ÉDITION ──
    $(document).on('click', '.btn-delete-bl-line', function() {
        if (!isEditing) return;

        const $row = $(this).closest('tr');
        const index = $container.find('.bl-line-row').index($row);

        Swal.fire({
            title: 'Supprimer la ligne ?',
            text: 'Cette ligne ne sera pas enregistrée dans le bordereau de livraison.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $row.remove();
                
                if (index > -1 && window.existingLines) {
                    window.existingLines.splice(index, 1);
                }
                
                reindexLignes();
                
                if ($container.find('.bl-line-row').length === 0) {
                    $container.html(`
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="fas fa-info-circle me-1"></i> Aucune ligne à livrer.
                            </td>
                        </tr>
                    `);
                }
            }
        });
    });

    // Helper pour réindexer les inputs PHP après une suppression
    function reindexLignes() {
        $container.find('.bl-line-row').each(function(index) {
            $(this).find('input').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    const newName = name.replace(/lignes\[\d+\]/, `lignes[${index}]`);
                    $(this).attr('name', newName);
                }
            });
        });
    }

    // ── BASCULE EN MODE EDITION (IN-PLACE EDIT) ──
    if ($btnToggleEdit.length) {
        $btnToggleEdit.on('click', function() {
            isEditing = true;
            
            // Backup initial values
            originalBcId = $('#input-bc-id').val();
            originalBcDisplay = $('#input-bc-display').val();
            originalLines = JSON.parse(JSON.stringify(window.existingLines));

            // Toggle BC input visibility
            $('#bc-display-readonly').addClass('d-none');
            $('#bc-display-edit').removeClass('d-none');

            // Activer les champs de l'entête
            $form.find('.field-input').prop('disabled', false);

            // Gérer la visibilité des boutons
            $btnToggleEdit.addClass('d-none');
            $btnWizard.addClass('d-none');
            $btnPrint.addClass('d-none');
            $btnSaveEdit.removeClass('d-none');
            $btnCancelEdit.removeClass('d-none');

            // Re-render lines in edit mode
            renderLines();
        });
    }

    // Annuler l'édition
    $btnCancelEdit.on('click', function() {
        isEditing = false;

        // Restore values
        $('#input-bc-id').val(originalBcId);
        $('#input-bc-display').val(originalBcDisplay);
        window.existingLines = JSON.parse(JSON.stringify(originalLines));

        // Toggle BC input visibility
        $('#bc-display-readonly').removeClass('d-none');
        $('#bc-display-edit').addClass('d-none');

        $form.find('.field-input').prop('disabled', true);
        $btnToggleEdit.removeClass('d-none');
        $btnWizard.removeClass('d-none');
        $btnPrint.removeClass('d-none');
        $btnSaveEdit.addClass('d-none');
        $btnCancelEdit.addClass('d-none');

        renderLines();
    });

    // Enregistrer les modifications
    $form.on('submit', function(e) {
        e.preventDefault();

        // Vérifier qu'au moins un article a une quantité reçue > 0
        let totalReceived = 0;
        let hasRows = false;
        
        $container.find('.input-qty-received').each(function() {
            hasRows = true;
            totalReceived += (parseInt($(this).val()) || 0);
        });

        if (!hasRows) {
            Swal.fire('Erreur', 'Le bordereau de livraison doit contenir au moins une ligne livrée.', 'warning');
            return;
        }

        if (totalReceived <= 0) {
            Swal.fire('Erreur', 'Veuillez saisir une quantité reçue supérieure à 0 pour au moins un article.', 'warning');
            return;
        }

        $btnSaveEdit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');

        const id = $('#bl-id').val();

        $.ajax({
            url: route('achat.bordereaux.update', id),
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

    // ── ACTION PRINT ──
    $btnPrint.on('click', function() {
        const id = $('#bl-id').val();
        if (id) {
            const url = route('achat.bordereaux.imprimer', id);
            $('#print-bl-iframe').attr('src', url);
            const printModal = new bootstrap.Modal(document.getElementById('printBlModal'));
            printModal.show();
        }
    });

    // Initialisation
    renderLines();
});
