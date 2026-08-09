/**
 * index.js — A-08, administration des paramètres (D-17).
 *
 * Chaque carte enregistre SA clé, indépendamment des autres : un écran de
 * paramètres à bouton unique oblige à tout renvoyer pour changer une valeur,
 * et fait passer des modifications involontaires. Ici, une carte = un PATCH.
 *
 * Les erreurs se posent SUR la carte concernée, avec le message du serveur —
 * qui explique pourquoi la valeur est refusée, pas seulement qu'elle l'est.
 */

const echapper = (texte) => $('<span>').text(texte ?? '').html();

$(function () {
    const $parametres = $('#parametres');
    if ($parametres.length === 0) return;

    const urlBase = $parametres.data('url-base');

    const toast = (message) => Swal.fire({
        icon: 'success',
        title: message,
        timer: 2200,
        showConfirmButton: false,
        toast: true,
        position: 'top-end',
    });

    /** La valeur courante d'une carte, selon sa nature. */
    const valeurDe = ($carte) => {
        const cle = $carte.data('cle');

        if (cle === 'motifs_observation') {
            return $carte.find('#liste-motifs .pilule-motif').map((_, el) => $(el).data('motif')).get();
        }

        const $champ = $carte.find('.champ-parametre');
        return $champ.attr('type') === 'number' ? Number($champ.val()) : $champ.val();
    };

    const enregistrer = ($carte) => {
        const cle = $carte.data('cle');
        const $message = $carte.find('.message-erreur').text('');
        const $bouton = $carte.find('.btn-enregistrer').prop('disabled', true);

        $.ajax({
            url: `${urlBase}/${cle}`,
            method: 'PATCH',
            data: JSON.stringify({ valeur: valeurDe($carte) }),
            contentType: 'application/json',
            dataType: 'json',
        })
            .done((reponse) => {
                $carte.find('.champ-parametre').removeClass('is-invalid');
                toast(reponse.message);

                // L'aperçu du numéro vient du SERVEUR : il montre ce que le
                // prochain bon portera vraiment, pas une reconstitution.
                if (reponse.data?.apercu) {
                    $('#apercu-numero').text(reponse.data.apercu);
                }
            })
            .fail((xhr) => {
                $carte.find('.champ-parametre').addClass('is-invalid');
                $message.text(xhr.responseJSON?.message ?? 'Valeur refusée.');
            })
            .always(() => $bouton.prop('disabled', false));
    };

    $parametres.on('click', '.btn-enregistrer', function () {
        enregistrer($(this).closest('.carte-parametre'));
    });

    // Entrée dans un champ = enregistrer cette carte.
    $parametres.on('keydown', '.champ-parametre', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            enregistrer($(this).closest('.carte-parametre'));
        }
    });

    // ── Aperçu vivant du préfixe ──────────────────────────────────────────

    $('[data-cle="prefixe_numerotation"] .champ-parametre').on('input', function () {
        const prefixe = this.value.toUpperCase();
        this.value = prefixe;
        $('#apercu-numero').text(`${prefixe || '?'}-${new Date().getFullYear()}-0042`);
    });

    // ── Motifs d'observation : pilules éditables ──────────────────────────

    const ajouterMotif = () => {
        const motif = $('#nouveau-motif').val().trim();
        if (motif === '') return;

        const existe = $('#liste-motifs .pilule-motif')
            .filter((_, el) => $(el).data('motif') === motif).length > 0;

        if (existe) {
            $('#nouveau-motif').addClass('is-invalid');
            return;
        }

        $('#liste-motifs').append(`
            <span class="badge bg-secondary-subtle text-secondary-emphasis pilule-motif" data-motif="${echapper(motif)}">
                ${echapper(motif)}
                <button type="button" class="btn-close btn-close-sm" aria-label="Retirer ${echapper(motif)}" style="font-size:.6rem;"></button>
            </span>`);

        $('#nouveau-motif').val('').removeClass('is-invalid').trigger('focus');
    };

    $('#btn-ajouter-motif').on('click', ajouterMotif);

    $('#nouveau-motif').on('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            ajouterMotif();
        }
    });

    $('#liste-motifs').on('click', '.btn-close', function () {
        $(this).closest('.pilule-motif').remove();
    });

    // ── SW-06 : réouverture de la régularisation ──────────────────────────

    $('#btn-rouvrir-regularisation').on('click', function () {
        const url = $('#carte-regularisation').data('url-reactiver');

        Swal.fire({
            title: 'Rouvrir la régularisation ?',
            input: 'textarea',
            inputLabel: 'Motif de la réouverture',
            inputPlaceholder: 'Quel oubli a été découvert ?…',
            html: `<p class="text-start small mb-2">
                La dette de l'intérim a été soldée : le mode s'est fermé de lui-même.
                Le rouvrir permet à nouveau de saisir des bons antidatés — ce geste
                est <strong>tracé au journal</strong>.
            </p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Rouvrir',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#fd7e14',
            inputValidator: (valeur) =>
                (!valeur || valeur.trim().length < 5)
                    ? 'Indiquez pourquoi la régularisation doit rouvrir.'
                    : undefined,
        }).then((r) => {
            if (!r.isConfirmed) return;

            $.ajax({
                url,
                method: 'POST',
                data: JSON.stringify({ motif: r.value }),
                contentType: 'application/json',
                dataType: 'json',
            })
                .done(() => window.location.reload())
                .fail((xhr) => Swal.fire({
                    icon: 'error',
                    title: 'Réouverture impossible',
                    text: xhr.responseJSON?.message ?? '',
                }));
        });
    });
});
