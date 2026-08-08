/**
 * show.js — fiche fournisseur (mini-table articles + actions d'en-tête)
 */
import { FournisseurForm } from './FournisseurForm.js';

// Formatters de la mini-table articles (globaux, avant init de la table)
window.statutFormatter = function (value) {
    return value
        ? '<span class="badge bg-success">Actif</span>'
        : '<span class="badge bg-danger">Inactif</span>';
};

window.fcfaFormatter = function (value) {
    if (value === null || value === undefined || value === '') return '—';
    return `${Number(value).toLocaleString('fr-FR', { maximumFractionDigits: 0 })} FCFA`;
};

$(function () {
    const $fiche = $('#fiche-fournisseur');
    const id = $fiche.data('id');
    const raisonSociale = $fiche.data('raison-sociale');

    const form = new FournisseurForm('#fournisseurModal', '#fournisseur-form', {
        // La fiche affiche les données en dur : on recharge pour tout rafraîchir
        onSaved: () => window.location.reload(),
    });

    // Modifier — pré-remplit la modale via le show JSON
    $('#btn-edit').on('click', () => {
        $.ajax({
            url: route('catalogue.fournisseurs.show', id),
            method: 'GET',
            dataType: 'json',
            success: (res) => {
                if (res.success) form.openForEdit(res.data);
            },
            error: () => Swal.fire({ icon: 'error', title: 'Erreur', text: 'Impossible de charger les données.' }),
        });
    });

    // Toggle statut
    $('#btn-toggle').on('click', () => {
        Swal.fire({
            title: `Changer le statut de « ${raisonSociale} » ?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Oui, changer',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: route('catalogue.fournisseurs.toggle-status', id),
                method: 'PATCH',
                dataType: 'json',
                success: (res) => {
                    if (res.success) window.location.reload();
                },
                error: (xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Changement de statut impossible.' }),
            });
        });
    });

    // Suppression (le bouton est désactivé par la vue si la garde bloque)
    $('#btn-delete').on('click', () => {
        Swal.fire({
            title: `Supprimer « ${raisonSociale} » ?`,
            text: 'Cette action est irréversible.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: route('catalogue.fournisseurs.destroy', id),
                method: 'DELETE',
                dataType: 'json',
                success: (res) => {
                    if (res.success) {
                        Swal.fire({ icon: 'success', title: 'Supprimé', text: res.message, timer: 2000, showConfirmButton: false })
                            .then(() => { window.location.href = route('catalogue.fournisseurs.index'); });
                    }
                },
                error: (xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Suppression impossible.' }),
            });
        });
    });
});
