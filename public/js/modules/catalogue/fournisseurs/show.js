/**
 * show.js — fiche fournisseur : onglets, actions d'en-tête, CRUD des contacts.
 */
import { FournisseurForm } from './FournisseurForm.js';
import { ContactForm } from './ContactForm.js';

// Les formatters doivent être en window.* et définis AVANT l'init des tables.

window.statutFormatter = function (value) {
    return value
        ? '<span class="badge bg-success">Actif</span>'
        : '<span class="badge bg-danger">Inactif</span>';
};

window.fcfaFormatter = function (value) {
    if (value === null || value === undefined || value === '') return '—';
    return `${Number(value).toLocaleString('fr-FR', { maximumFractionDigits: 0 })} FCFA`;
};

// Le téléphone et le courriel sont CLIQUABLES : la fiche sert à joindre
// quelqu'un, pas seulement à lire ses coordonnées.
window.telephoneFormatter = function (value) {
    if (!value) return '—';
    return `<a href="tel:${encodeURIComponent(value)}">${$('<div>').text(value).html()}</a>`;
};

window.emailFormatter = function (value) {
    if (!value) return '—';
    return `<a href="mailto:${encodeURIComponent(value)}">${$('<div>').text(value).html()}</a>`;
};

window.principalFormatter = function (value) {
    return value
        ? '<span class="badge bg-primary"><i class="fas fa-star me-1"></i>Principal</span>'
        : '<span class="text-muted">—</span>';
};

$(function () {
    const $fiche = $('#fiche-fournisseur');
    const id = $fiche.data('id');
    const raisonSociale = $fiche.data('raison-sociale');

    // ── Informations du fournisseur ───────────────────────────────────────

    const form = new FournisseurForm('#fournisseurModal', '#fournisseur-form', {
        // La fiche affiche les données en dur : on recharge pour tout rafraîchir
        onSaved: () => window.location.reload(),
    });

    const ouvrirEdition = () => {
        $.ajax({
            url: route('catalogue.fournisseurs.show', id),
            method: 'GET',
            dataType: 'json',
            success: (res) => {
                if (res.success) form.openForEdit(res.data);
            },
            error: () => Swal.fire({ icon: 'error', title: 'Erreur', text: 'Impossible de charger les données.' }),
        });
    };

    $('#btn-edit').on('click', ouvrirEdition);

    /*
     * Édition demandée depuis la LISTE : celle-ci amène désormais sur la
     * fiche avec ?edit=1, et c'est ici qu'on ouvre la modale.
     *
     * L'intérêt est qu'on modifie en voyant la fiche complète — contacts,
     * articles, journal — au lieu d'éditer à l'aveugle depuis un tableau.
     *
     * Le paramètre est retiré de l'URL aussitôt : sans cela, un rafraîchis-
     * sement ou un retour arrière rouvrirait la modale indéfiniment.
     */
    const params = new URLSearchParams(window.location.search);

    if (params.get('edit') === '1') {
        params.delete('edit');
        const reste = params.toString();
        window.history.replaceState({}, '', window.location.pathname + (reste ? `?${reste}` : ''));

        ouvrirEdition();
    }

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

    // ── Onglet Contacts ───────────────────────────────────────────────────

    const $contacts = $('#contacts-table');

    // L'onglet n'existe pas pour qui n'a pas la permission de le voir : on
    // s'arrête là plutôt que de câbler des boutons absents.
    if ($contacts.length === 0) return;

    const rafraichirContacts = () => $contacts.bootstrapTable('refresh');

    const contactForm = new ContactForm(id, {
        onSaved: () => rafraichirContacts(),
    });

    // Le compteur de l'onglet suit la table : un chiffre faux se remarque
    // tout de suite et ruine la confiance dans le reste de l'écran.
    $contacts.on('load-success.bs.table', function (_evenement, donnees) {
        $('#compteur-contacts').text(donnees.total ?? 0);
        majBoutonsContacts();
    });

    /**
     * Toolbar + sélection de ligne (convention du projet : jamais de colonne
     * d'actions). Les boutons ne s'activent que sur une sélection unique.
     */
    function selectionContact() {
        const selection = $contacts.bootstrapTable('getSelections');
        return selection.length === 1 ? selection[0] : null;
    }

    function majBoutonsContacts() {
        const contact = selectionContact();
        const unSeul = contact !== null;

        $('#btn-contact-edit, #btn-contact-delete').prop('disabled', !unSeul);

        // « Définir principal » n'a aucun sens sur le contact qui l'est déjà :
        // le bouton reste désactivé plutôt que de proposer une action sans effet.
        $('#btn-contact-principal').prop('disabled', !unSeul || contact.est_principal);
    }

    $contacts.on(
        'check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table',
        majBoutonsContacts
    );

    $('#btn-contact-add').on('click', () => contactForm.openForAdd());

    $('#btn-contact-edit').on('click', () => {
        const contact = selectionContact();
        if (contact) contactForm.openForEdit(contact.id);
    });

    $('#btn-contact-principal').on('click', () => {
        const contact = selectionContact();
        if (!contact) return;

        $.ajax({
            url: route('catalogue.contacts.principal', [id, contact.id]),
            method: 'PATCH',
            dataType: 'json',
            success: (res) => {
                if (res.success) {
                    rafraichirContacts();
                    Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 2000, showConfirmButton: false });
                }
            },
            error: (xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Action impossible.' }),
        });
    });

    $('#btn-contact-delete').on('click', () => {
        const contact = selectionContact();
        if (!contact) return;

        Swal.fire({
            title: `Supprimer « ${contact.nom_complet} » ?`,
            text: contact.est_principal
                ? 'Ce contact est le principal : un autre contact actif prendra sa place.'
                : 'Cette action est irréversible.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: route('catalogue.contacts.destroy', [id, contact.id]),
                method: 'DELETE',
                dataType: 'json',
                success: (res) => {
                    if (res.success) {
                        rafraichirContacts();
                        Swal.fire({ icon: 'success', title: 'Supprimé', text: res.message, timer: 2000, showConfirmButton: false });
                    }
                },
                error: (xhr) => Swal.fire({ icon: 'error', title: 'Erreur', text: xhr.responseJSON?.message ?? 'Suppression impossible.' }),
            });
        });
    });

    // Le lien « onglet Contacts » de la carte Informations.
    $('.lien-onglet-contacts').on('click', (evenement) => {
        evenement.preventDefault();
        $('[data-bs-target="#onglet-contacts"]').tab('show');
    });
});
