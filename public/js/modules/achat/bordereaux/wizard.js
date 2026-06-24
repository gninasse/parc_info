/**
 * Assistant d'intégration pas-à-pas (Wizard) - Module Achat
 * Pattern: AJAX + Navigation pas-à-pas
 */

document.addEventListener('DOMContentLoaded', function() {
    // ── NAVIGATION DES ÉTAPES ──
    const tabs = document.querySelectorAll('#wizard-tab button');
    const tabPanes = document.querySelectorAll('.tab-content .tab-pane');

    // Boutons Suivant / Précédent
    $('.btn-wizard-prev').on('click', function() {
        const activeTab = document.querySelector('#wizard-tab button.active');
        const prevTab = activeTab.previousElementSibling;
        if (prevTab) {
            bootstrap.Tab.getOrCreateInstance(prevTab).show();
        }
    });

    // ── SAUVEGARDE D'UNE ÉTAPE ──
    $('.form-wizard-step').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $btn = $form.find('.btn-save-step');
        const url = $form.data('url');
        const articleId = $form.data('article-id');

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Sauvegarde...');

        // Créer les données de soumission avec completed = true
        let formData = $form.serialize();
        formData += '&completed=1';

        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Étape enregistrée !',
                        text: res.message,
                        timer: 1000,
                        showConfirmButton: false
                    });

                    // Mettre à jour l'état de l'étape sur la navigation latérale
                    const $navBtn = $(`#tab-btn-${articleId}`);
                    $navBtn.addClass('completed');
                    $navBtn.find('.step-status-icon').html('<i class="fas fa-check-circle text-success"></i>');

                    // Mettre à jour la couleur dans l'étape finale de confirmation
                    const $summaryItem = $(`.step-summary-item[data-article-id="${articleId}"]`);
                    $summaryItem.find('.badge').removeClass('bg-danger').addClass('bg-success').text('Complété');

                    // Vérifier la validation générale
                    verifierValidationGenerale();

                    // Passer à l'étape suivante automatiquement
                    const activeTab = document.querySelector('#wizard-tab button.active');
                    const nextTab = activeTab.nextElementSibling;
                    if (nextTab) {
                        bootstrap.Tab.getOrCreateInstance(nextTab).show();
                    }
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let msg = '';
                Object.values(errors).forEach(e => msg += e[0] + '<br>');
                Swal.fire('Erreur de validation', msg || xhr.responseJSON?.message || 'Erreur lors de la sauvegarde', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Enregistrer cette étape & Continuer <i class="fas fa-chevron-right ms-1"></i>');
            }
        });
    });

    // ── VÉRIFICATION DE LA VALIDATION GÉNÉRALE ──
    function verifierValidationGenerale() {
        // Compter les étapes non complétées (hors validation finale)
        const totalWizardItems = tabs.length - 1; // exclure la confirmation
        const completedItems = document.querySelectorAll('#wizard-tab button.completed').length;

        const allCompleted = completedItems >= totalWizardItems;
        
        if (allCompleted) {
            $('#btn-finalize-wizard').prop('disabled', false);
            $('#finalize-warning').addClass('d-none');
        } else {
            $('#btn-finalize-wizard').prop('disabled', true);
            $('#finalize-warning').removeClass('d-none');
        }
    }

    // Déclencher une fois au démarrage
    verifierValidationGenerale();

    // ── FINALISATION ET VALIDATION DU BL ──
    $('#btn-finalize-wizard').on('click', function() {
        const url = $(this).data('url');

        Swal.fire({
            title: 'Finaliser l\'intégration ?',
            text: "Les équipements et licences saisis seront créés de manière définitive dans le parc informatique !",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Oui, finaliser et intégrer'
        }).then((result) => {
            if (result.isConfirmed) {
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Intégration en cours...');

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Intégration Réussie !',
                                text: res.message,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                window.location.href = res.redirect;
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Erreur d\'intégration', xhr.responseJSON?.message || 'Erreur lors de la validation finale', 'error');
                        $('#btn-finalize-wizard').prop('disabled', false).html('<i class="fas fa-check-double me-2"></i> Finaliser et valider l\'intégration');
                    }
                });
            }
        });
    });
});
