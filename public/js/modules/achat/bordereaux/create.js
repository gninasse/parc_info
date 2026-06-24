/**
 * Création d'un Bordereau de Livraison - Module Achat
 * Pattern: AJAX + Sélection par Modale + Edition des lignes
 */

document.addEventListener('DOMContentLoaded', function() {
    const $container = $('#lines-container');
    const $btnSave = $('#btn-save');
    const $form = $('#form-create-bl');

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
        $('#input-bc-id').val('');
        $('#input-bc-display').val('');
        chargerLignesBC('');
    });

    // ── CHARGER LES LIGNES DU BC ──
    function chargerLignesBC(bcId) {
        if (!bcId) {
            $container.html(`
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-arrow-up me-1"></i> Veuillez sélectionner un bon de commande ci-dessus pour charger ses lignes.
                    </td>
                </tr>
            `);
            $btnSave.prop('disabled', true);
            return;
        }

        $container.html(`
            <tr>
                <td colspan="6" class="text-center py-4">
                    <span class="spinner-border spinner-border-sm me-2 text-primary"></span>Chargement des lignes de commande...
                </td>
            </tr>
        `);

        $.ajax({
            url: route('achat.bons-commande.lignes-a-livrer', bcId),
            method: 'GET',
            success: function(res) {
                if (res.success && res.lignes.length > 0) {
                    let html = '';
                    let activeLines = 0;

                    res.lignes.forEach((l, index) => {
                        const isFullyDelivered = l.reste_a_livrer <= 0;
                        const defaultQty = isFullyDelivered ? 0 : l.reste_a_livrer;
                        
                        if (!isFullyDelivered) {
                            activeLines++;
                        }

                        html += `
                            <tr class="bl-line-row">
                                <td>
                                    <strong>${l.code_article}</strong> - ${l.designation}
                                    <span class="badge bg-secondary ms-1 small">${l.type_label}</span>
                                    <input type="hidden" name="lignes[${index}][article_id]" value="${l.article_id}">
                                </td>
                                <td class="text-center">${l.quantite_commandee}</td>
                                <td class="text-center text-muted">${l.quantite_livree}</td>
                                <td class="text-center fw-semibold text-primary">${l.reste_a_livrer}</td>
                                <td class="text-center">
                                    <input type="number" 
                                           name="lignes[${index}][quantite_livree]" 
                                           class="form-control form-control-sm text-center mx-auto input-qty-received" 
                                           style="width: 80px;" 
                                           min="0" 
                                           max="${l.reste_a_livrer}" 
                                           value="${defaultQty}" 
                                           ${isFullyDelivered ? 'disabled' : ''} 
                                           required>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-link text-danger btn-delete-bl-line p-0" title="Supprimer cette ligne">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });

                    $container.html(html);

                    if (activeLines === 0) {
                        Swal.fire('Info', 'Ce bon de commande est déjà entièrement livré.', 'info');
                        $btnSave.prop('disabled', true);
                    } else {
                        $btnSave.prop('disabled', false);
                    }
                } else {
                    $container.html(`
                        <tr>
                            <td colspan="6" class="text-center text-danger py-4">
                                <i class="fas fa-exclamation-circle me-1"></i> Aucune ligne de commande trouvée pour ce BC.
                            </td>
                        </tr>
                    `);
                    $btnSave.prop('disabled', true);
                }
            },
            error: function() {
                $container.html(`
                    <tr>
                        <td colspan="6" class="text-center text-danger py-4">
                            <i class="fas fa-exclamation-triangle me-1"></i> Erreur lors du chargement des lignes.
                        </td>
                    </tr>
                `);
                $btnSave.prop('disabled', true);
            }
        });
    }

    // ── SUPPRIMER UNE LIGNE DU BL ──
    $(document).on('click', '.btn-delete-bl-line', function() {
        const $row = $(this).closest('tr');
        
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
                reindexLignes();
                
                // Si plus aucune ligne dans le tableau
                if ($container.find('.bl-line-row').length === 0) {
                    $container.html(`
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-info-circle me-1"></i> Aucune ligne à livrer.
                            </td>
                        </tr>
                    `);
                    $btnSave.prop('disabled', true);
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

    // Déclencher au chargement si pré-sélectionné
    const initialBcId = $('#input-bc-id').val();
    if (initialBcId) {
        chargerLignesBC(initialBcId);
    }

    // ── SUBMIT FORM ──
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
            Swal.fire('Erreur', 'Veuillez sélectionner un bon de commande valide avec au moins une ligne.', 'warning');
            return;
        }

        if (totalReceived <= 0) {
            Swal.fire('Erreur', 'Veuillez saisir une quantité reçue supérieure à 0 pour au moins un article.', 'warning');
            return;
        }

        $btnSave.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...');

        $.ajax({
            url: route('achat.bordereaux.store'),
            method: 'POST',
            data: $form.serialize(),
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Enregistré !',
                        text: res.message,
                        timer: 1500
                    }).then(() => {
                        window.location.href = res.redirect;
                    });
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let msg = '';
                Object.values(errors).forEach(e => msg += e[0] + '<br>');
                Swal.fire('Erreur', msg || xhr.responseJSON?.message || 'Une erreur est survenue', 'error');
                $btnSave.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Enregistrer le Bordereau');
            }
        });
    });
});
